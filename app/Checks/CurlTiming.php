<?php

namespace App\Checks;

/**
 * Turns curl_getinfo()-shaped timing (Guzzle's on_stats handler stats use the identical
 * keys, since Guzzle's curl handler is just a thin wrapper over curl_getinfo) into the
 * dns/tcp/tls/ttfb/total breakdown check_results stores. Shared by HttpChecker (Guzzle) and
 * ConcurrentHttpChecker (raw curl_multi) so the two transports report timing the same way.
 */
class CurlTiming
{
    /**
     * curl's own namelookup_time is discarded — CURLOPT_RESOLVE pins the address, so curl's
     * own lookup is a near-instant hosts-file-style hit, not real DNS time. $dnsMs (measured
     * separately, around the actual SsrfGuard::resolvePublicIp() call that hit the network)
     * is used instead; the rest of the breakdown still comes from curl's real connect/TLS timing.
     *
     * @return array{dns: int, tcp: int, tls: ?int, ttfb: int, total: int}
     */
    public static function extract(array $info, int $dnsMs): array
    {
        $connect = ($info['connect_time'] ?? 0) * 1000;
        $appconnect = ($info['appconnect_time'] ?? 0) * 1000;
        $starttransfer = ($info['starttransfer_time'] ?? 0) * 1000;
        $total = ($info['total_time'] ?? 0) * 1000;

        $tlsMs = $appconnect > 0 ? (int) round($appconnect - $connect) : null;
        $ttfbMs = (int) round($starttransfer - ($appconnect > 0 ? $appconnect : $connect));

        return [
            'dns' => $dnsMs,
            'tcp' => max(0, (int) round($connect)),
            'tls' => $tlsMs !== null ? max(0, $tlsMs) : null,
            'ttfb' => max(0, $ttfbMs),
            'total' => (int) round($total) + $dnsMs,
        ];
    }
}
