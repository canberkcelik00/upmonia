<?php

namespace App\Checks\Checkers;

use App\Checks\CertInspector;
use App\Checks\CheckOutcome;
use App\Checks\SsrfBlockedException;
use App\Checks\SsrfGuard;
use App\Models\Monitor;

/**
 * `ssl` monitor type: a TLS handshake only, no HTTP request. Fails early as tls_expired or
 * tls_expired-adjacent states; warns (still counts as ok, but flagged) as the cert approaches
 * $monitor->ssl_warn_days.
 */
class SslChecker
{
    public function check(Monitor $monitor): CheckOutcome
    {
        try {
            $parts = parse_url($monitor->url);
            $host = $parts['host'] ?? $monitor->url;
            $port = $parts['port'] ?? 443;

            SsrfGuard::assertHttpPortAllowed($port);

            $start = microtime(true);
            $ip = SsrfGuard::resolvePublicIp($host);
            $dnsMs = (int) round((microtime(true) - $start) * 1000);

            $handshakeStart = microtime(true);
            $result = (new CertInspector($ip, $host, $port))->inspect($monitor->timeout_ms, true);
            $tlsMs = (int) round((microtime(true) - $handshakeStart) * 1000);

            if (! $result['authorized']) {
                $class = $result['self_signed'] ? 'tls_self_signed' : 'tls_error';

                return new CheckOutcome(
                    ok: false, errorClass: $class, errorMsg: $result['error'] ?? 'TLS handshake failed',
                    resolvedIp: $ip, dnsMs: $dnsMs, tlsMs: $tlsMs, latencyMs: $dnsMs + $tlsMs,
                );
            }

            $expiresAt = $result['expires_at'];

            if ($expiresAt && $expiresAt->getTimestamp() < time()) {
                return new CheckOutcome(
                    ok: false, errorClass: 'tls_expired', errorMsg: 'Certificate expired on '.$expiresAt->format('Y-m-d'),
                    resolvedIp: $ip, dnsMs: $dnsMs, tlsMs: $tlsMs, latencyMs: $dnsMs + $tlsMs,
                    certExpiresAt: $expiresAt, certIssuer: $result['issuer'],
                );
            }

            $daysUntilExpiry = $expiresAt ? (int) floor(($expiresAt->getTimestamp() - now()->getTimestamp()) / 86400) : null;

            if ($daysUntilExpiry !== null && $daysUntilExpiry <= $monitor->ssl_warn_days) {
                return new CheckOutcome(
                    ok: false, errorClass: 'tls_expiring', errorMsg: 'Certificate expires on '.$expiresAt->format('Y-m-d'),
                    resolvedIp: $ip, dnsMs: $dnsMs, tlsMs: $tlsMs, latencyMs: $dnsMs + $tlsMs,
                    certExpiresAt: $expiresAt, certIssuer: $result['issuer'],
                );
            }

            return new CheckOutcome(
                ok: true, resolvedIp: $ip, dnsMs: $dnsMs, tlsMs: $tlsMs, latencyMs: $dnsMs + $tlsMs,
                certExpiresAt: $expiresAt, certIssuer: $result['issuer'],
            );
        } catch (SsrfBlockedException $e) {
            return new CheckOutcome(ok: false, errorClass: $e->errorClass, errorMsg: $e->getMessage());
        } catch (\Throwable $e) {
            return new CheckOutcome(ok: false, errorClass: 'unknown_error', errorMsg: $e->getMessage());
        }
    }
}
