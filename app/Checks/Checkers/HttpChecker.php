<?php

namespace App\Checks\Checkers;

use App\Checks\CertInspector;
use App\Checks\CheckOutcome;
use App\Checks\CurlTiming;
use App\Checks\ErrorClassifier;
use App\Checks\HttpResponseEvaluator;
use App\Checks\SsrfBlockedException;
use App\Checks\SsrfGuard;
use App\Models\Monitor;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;

/**
 * Handles both the `http` and `keyword` monitor types for a *single* monitor — used by the
 * "check now" dashboard button and anywhere else one-off, synchronous checks make sense.
 * ProbeRun's batch tick uses App\Checks\ConcurrentHttpChecker instead (curl_multi, real
 * concurrency across many monitors in one PHP process); the two share SSRF/redirect-URL
 * resolution and pass/fail rules (HttpResponseEvaluator) so they can't drift apart.
 *
 * Resolves DNS itself and pins the connection to the validated IP (CURLOPT_RESOLVE) rather
 * than letting curl re-resolve — closes the DNS-rebinding gap between SsrfGuard's check and
 * the actual connection. Redirects are followed by hand (not Guzzle's allow_redirects) so
 * every hop gets re-validated by SsrfGuard before it's connected to.
 */
class HttpChecker
{
    private const MAX_BODY_BYTES = 512 * 1024;

    public function check(Monitor $monitor): CheckOutcome
    {
        try {
            SsrfGuard::assertNoCredentials($monitor->url);

            return $this->followAndCheck($monitor, $monitor->url, 0, []);
        } catch (SsrfBlockedException $e) {
            return new CheckOutcome(ok: false, errorClass: $e->errorClass, errorMsg: $e->getMessage());
        } catch (\Throwable $e) {
            [$class, $msg] = ErrorClassifier::fromException($e);

            return new CheckOutcome(ok: false, errorClass: $class, errorMsg: $msg);
        }
    }

    private function followAndCheck(Monitor $monitor, string $url, int $redirectCount, array $chain): CheckOutcome
    {
        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? '';
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        SsrfGuard::assertHttpPortAllowed($port);

        $dnsStart = microtime(true);
        $ip = SsrfGuard::resolvePublicIp($host);
        $dnsMs = (int) round((microtime(true) - $dnsStart) * 1000);

        $client = new Client;
        $stats = null;

        $response = $client->request($monitor->method ?: 'GET', $url, [
            'connect_timeout' => $monitor->timeout_ms / 1000,
            'timeout' => $monitor->timeout_ms / 1000,
            'verify' => (bool) $monitor->verify_ssl,
            'allow_redirects' => false,
            'http_errors' => false,
            // No 'stream' => true here: Guzzle's stream handler silently drops all 'curl'
            // options, including the CURLOPT_RESOLVE pin SSRF protection depends on (verified
            // empirically — Guzzle throws "stream handler ignores cURL options" otherwise).
            // The body is read in full and truncated to MAX_BODY_BYTES below instead of being
            // capped at the transport level; acceptable since monitor targets are URLs the
            // account owner configured, not arbitrary third-party input.
            'headers' => $monitor->headers ?? [],
            'body' => $monitor->body,
            'curl' => [
                CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"],
            ],
            'on_stats' => function (TransferStats $s) use (&$stats) {
                $stats = $s->getHandlerStats();
            },
        ]);

        $statusCode = $response->getStatusCode();
        $chain[] = ['status' => $statusCode, 'url' => $url];

        if ($monitor->follow_redirects && $statusCode >= 300 && $statusCode < 400 && $response->hasHeader('Location')) {
            if ($redirectCount >= $monitor->max_redirects) {
                return new CheckOutcome(ok: false, statusCode: $statusCode, errorClass: 'redirect_loop', errorMsg: 'Too many redirects', resolvedIp: $ip, redirectChain: $chain);
            }

            $nextUrl = self::resolveUrl($url, $response->getHeaderLine('Location'));

            return $this->followAndCheck($monitor, $nextUrl, $redirectCount + 1, $chain);
        }

        $body = substr((string) $response->getBody(), 0, self::MAX_BODY_BYTES);

        $timing = CurlTiming::extract($stats ?? [], $dnsMs);

        $certExpiresAt = null;
        $certIssuer = null;
        if ($scheme === 'https') {
            $cert = (new CertInspector($ip, $host, $port))->inspect($monitor->timeout_ms, (bool) $monitor->verify_ssl);
            $certExpiresAt = $cert['expires_at'];
            $certIssuer = $cert['issuer'];
        }

        $verdict = HttpResponseEvaluator::evaluate($monitor, $statusCode, $body);

        return new CheckOutcome(
            ok: $verdict['ok'], statusCode: $statusCode, errorClass: $verdict['errorClass'], errorMsg: $verdict['errorMsg'],
            resolvedIp: $ip, redirectChain: $chain,
            dnsMs: $timing['dns'], tcpMs: $timing['tcp'], tlsMs: $timing['tls'], ttfbMs: $timing['ttfb'], latencyMs: $timing['total'],
            certExpiresAt: $certExpiresAt, certIssuer: $certIssuer,
        );
    }

    public static function resolveUrl(string $base, string $location): string
    {
        if (parse_url($location, PHP_URL_SCHEME)) {
            return $location;
        }

        $baseParts = parse_url($base);
        $scheme = $baseParts['scheme'];
        $host = $baseParts['host'];
        $port = isset($baseParts['port']) ? ':'.$baseParts['port'] : '';

        if (str_starts_with($location, '/')) {
            return "{$scheme}://{$host}{$port}{$location}";
        }

        $basePath = rtrim(dirname($baseParts['path'] ?? '/'), '/');

        return "{$scheme}://{$host}{$port}{$basePath}/{$location}";
    }
}
