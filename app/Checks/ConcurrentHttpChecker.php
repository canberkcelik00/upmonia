<?php

namespace App\Checks;

use App\Checks\Checkers\HttpChecker;
use App\Models\Monitor;

/**
 * Runs many `http`/`keyword` monitors' checks in parallel within one PHP process, using raw
 * curl_multi rather than Guzzle's async Pool — this is the piece that makes cron-driven
 * probing viable on shared hosting: cPanel cron fires at most once a minute, so a batch of
 * (by default) 60 monitors run sequentially at up to 10s timeout each could take 600s in the
 * worst case, blowing straight through the next tick. Run concurrently, the whole batch
 * finishes in roughly one timeout period regardless of batch size.
 *
 * Redirects are handled in synchronous "rounds": every job's current hop is fired at once,
 * then jobs that came back with a redirect are re-armed for another round with the next URL
 * (each re-validated by SsrfGuard before it's added), while finished jobs drop out. A job
 * that's still redirecting after Monitor::max_redirects rounds is reported as redirect_loop.
 *
 * runBatch() itself only chunks the incoming monitors into config('upmonia.probe.concurrency')
 * -sized waves and hands each wave to runChunk() in turn, so no more than `concurrency`
 * outbound sockets/DNS lookups are ever in flight at once — worst-case wall time becomes
 * ceil(batch_size / concurrency) * timeout instead of one flat timeout period, a deliberate
 * latency/safety tradeoff to bound simultaneous connections on shared hosting.
 */
class ConcurrentHttpChecker
{
    private const MAX_BODY_BYTES = 512 * 1024;

    private const MAX_ROUNDS = 20; // absolute safety ceiling, independent of any one monitor's max_redirects

    /**
     * @param  iterable<Monitor>  $monitors
     * @return array<int,CheckOutcome> keyed by monitor id
     */
    public function runBatch(iterable $monitors): array
    {
        $concurrency = max(1, (int) config('upmonia.probe.concurrency'));
        $outcomes = [];

        // Chunked so curl_multi never runs more than `concurrency` handles at once —
        // config('upmonia.probe.concurrency') used to be read nowhere; batch_size (up to 60)
        // was fired as a single curl_multi round regardless. Each chunk still runs its own
        // full redirect-following round loop to completion before the next chunk starts.
        foreach (collect($monitors)->chunk($concurrency) as $chunk) {
            $outcomes += $this->runChunk($chunk);
        }

        return $outcomes;
    }

    /**
     * @param  iterable<Monitor>  $monitors
     * @return array<int,CheckOutcome> keyed by monitor id
     */
    private function runChunk(iterable $monitors): array
    {
        $jobs = [];
        $outcomes = [];

        foreach ($monitors as $monitor) {
            try {
                SsrfGuard::assertNoCredentials($monitor->url);
                $jobs[$monitor->id] = $this->prepareJob($monitor, $monitor->url, 0, [], null);
            } catch (\Throwable $e) {
                [$class, $msg] = $this->classify($e);
                $outcomes[$monitor->id] = new CheckOutcome(ok: false, errorClass: $class, errorMsg: $msg);
            }
        }

        for ($round = 0; $round < self::MAX_ROUNDS && ! empty($jobs); $round++) {
            $results = $this->executeRound($jobs);
            $nextJobs = [];

            foreach ($results as $monitorId => $result) {
                $job = $jobs[$monitorId];
                $monitor = $job['monitor'];

                if ($result['error']) {
                    $outcomes[$monitorId] = new CheckOutcome(
                        ok: false, errorClass: $result['errorClass'], errorMsg: $result['errorMsg'],
                        resolvedIp: $job['ip'], redirectChain: $job['chain'],
                    );

                    continue;
                }

                $statusCode = $result['statusCode'];
                $chain = [...$job['chain'], ['status' => $statusCode, 'url' => $job['url']]];

                if ($monitor->follow_redirects && $statusCode >= 300 && $statusCode < 400 && $result['location']) {
                    if ($job['redirects'] >= $monitor->max_redirects) {
                        $outcomes[$monitorId] = new CheckOutcome(ok: false, statusCode: $statusCode, errorClass: 'redirect_loop', errorMsg: 'Too many redirects', resolvedIp: $job['ip'], redirectChain: $chain);

                        continue;
                    }

                    try {
                        $nextUrl = HttpChecker::resolveUrl($job['url'], $result['location']);
                        $nextJobs[$monitorId] = $this->prepareJob($monitor, $nextUrl, $job['redirects'] + 1, $chain, $job['dnsMs']);
                    } catch (\Throwable $e) {
                        [$class, $msg] = $this->classify($e);
                        $outcomes[$monitorId] = new CheckOutcome(ok: false, errorClass: $class, errorMsg: $msg, redirectChain: $chain);
                    }

                    continue;
                }

                $outcomes[$monitorId] = $this->finalize($job, $statusCode, $result['body'], $result['info'], $chain);
            }

            $jobs = $nextJobs;
        }

        foreach ($jobs as $monitorId => $job) {
            $outcomes[$monitorId] = new CheckOutcome(ok: false, errorClass: 'redirect_loop', errorMsg: 'Redirect chain exceeded the internal round limit', redirectChain: $job['chain']);
        }

        return $outcomes;
    }

    private function finalize(array $job, int $statusCode, string $body, array $info, array $chain): CheckOutcome
    {
        $monitor = $job['monitor'];
        $timing = CurlTiming::extract($info, $job['dnsMs']);

        $certExpiresAt = null;
        $certIssuer = null;
        if ($job['scheme'] === 'https') {
            $cert = (new CertInspector($job['ip'], $job['host'], $job['port']))->inspect($monitor->timeout_ms, (bool) $monitor->verify_ssl);
            $certExpiresAt = $cert['expires_at'];
            $certIssuer = $cert['issuer'];
        }

        $verdict = HttpResponseEvaluator::evaluate($monitor, $statusCode, $body);

        return new CheckOutcome(
            ok: $verdict['ok'], statusCode: $statusCode, errorClass: $verdict['errorClass'], errorMsg: $verdict['errorMsg'],
            resolvedIp: $job['ip'], redirectChain: $chain,
            dnsMs: $timing['dns'], tcpMs: $timing['tcp'], tlsMs: $timing['tls'], ttfbMs: $timing['ttfb'], latencyMs: $timing['total'],
            certExpiresAt: $certExpiresAt, certIssuer: $certIssuer,
        );
    }

    private function prepareJob(Monitor $monitor, string $url, int $redirects, array $chain, ?int $priorDnsMs): array
    {
        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? '';
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        SsrfGuard::assertHttpPortAllowed($port);

        if ($priorDnsMs !== null) {
            $ip = SsrfGuard::resolvePublicIp($host);
            $dnsMs = $priorDnsMs;
        } else {
            $start = microtime(true);
            $ip = SsrfGuard::resolvePublicIp($host);
            $dnsMs = (int) round((microtime(true) - $start) * 1000);
        }

        return [
            'monitor' => $monitor, 'url' => $url, 'redirects' => $redirects, 'chain' => $chain,
            'dnsMs' => $dnsMs, 'ip' => $ip, 'host' => $host, 'port' => $port, 'scheme' => $scheme,
        ];
    }

    /**
     * @return array<int,array{error:bool,errorClass?:string,errorMsg?:string,statusCode?:int,body?:string,location?:?string,info?:array}>
     */
    private function executeRound(array $jobs): array
    {
        $mh = curl_multi_init();
        $handles = [];
        $handlesByObjectId = [];
        $locations = [];

        foreach ($jobs as $monitorId => $job) {
            $monitor = $job['monitor'];
            $locations[$monitorId] = null;

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $job['url'],
                CURLOPT_CUSTOMREQUEST => $monitor->method ?: 'GET',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT_MS => $monitor->timeout_ms,
                CURLOPT_TIMEOUT_MS => $monitor->timeout_ms,
                CURLOPT_SSL_VERIFYPEER => (bool) $monitor->verify_ssl,
                CURLOPT_SSL_VERIFYHOST => $monitor->verify_ssl ? 2 : 0,
                CURLOPT_RESOLVE => ["{$job['host']}:{$job['port']}:{$job['ip']}"],
                CURLOPT_HTTPHEADER => $this->buildHeaders($monitor->headers ?? []),
                CURLOPT_POSTFIELDS => $monitor->body,
                CURLOPT_HEADERFUNCTION => function ($ch, $headerLine) use (&$locations, $monitorId) {
                    if (stripos($headerLine, 'Location:') === 0) {
                        $locations[$monitorId] = trim(substr($headerLine, 9));
                    }

                    return strlen($headerLine);
                },
            ]);

            curl_multi_add_handle($mh, $ch);
            $handles[$monitorId] = $ch;
            $handlesByObjectId[spl_object_id($ch)] = $monitorId;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            if ($running > 0) {
                curl_multi_select($mh, 1.0);
            }
        } while ($running > 0);

        // A per-handle error (TLS failure, refused connection, timeout, ...) is only reliably
        // available via curl_multi_info_read()'s 'result' field with the multi interface —
        // curl_errno($ch) was empirically unreliable here (returned 0 on a handle that had in
        // fact failed TLS verification, confirmed against https://expired.badssl.com).
        $errnos = [];
        while ($info = curl_multi_info_read($mh)) {
            $monitorId = $handlesByObjectId[spl_object_id($info['handle'])] ?? null;
            if ($monitorId !== null) {
                $errnos[$monitorId] = $info['result'];
            }
        }

        $results = [];
        foreach ($handles as $monitorId => $ch) {
            $errno = $errnos[$monitorId] ?? 0;

            if ($errno !== 0) {
                [$class, $msg] = ErrorClassifier::fromCurlErrno($errno, curl_error($ch) ?: "curl error {$errno}");
                $results[$monitorId] = ['error' => true, 'errorClass' => $class, 'errorMsg' => $msg];
            } else {
                $results[$monitorId] = [
                    'error' => false,
                    'statusCode' => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
                    'body' => substr((string) curl_multi_getcontent($ch), 0, self::MAX_BODY_BYTES),
                    'location' => $locations[$monitorId],
                    'info' => curl_getinfo($ch),
                ];
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);

        return $results;
    }

    private function buildHeaders(array $headers): array
    {
        $lines = [];
        foreach ($headers as $key => $value) {
            $lines[] = "{$key}: {$value}";
        }

        return $lines;
    }

    private function classify(\Throwable $e): array
    {
        return $e instanceof SsrfBlockedException
            ? [$e->errorClass, $e->getMessage()]
            : ErrorClassifier::fromException($e);
    }
}
