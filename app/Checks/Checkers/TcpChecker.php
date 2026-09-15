<?php

namespace App\Checks\Checkers;

use App\Checks\CheckOutcome;
use App\Checks\SsrfBlockedException;
use App\Checks\SsrfGuard;
use App\Models\Monitor;

/**
 * `tcp_port` monitor type: a raw TCP connect, no protocol handshake — for things like
 * SMTP/Postgres/Redis where an HTTP check doesn't apply.
 */
class TcpChecker
{
    public function check(Monitor $monitor): CheckOutcome
    {
        try {
            SsrfGuard::assertTcpPortAllowed($monitor->port);

            $dnsStart = microtime(true);
            $ip = SsrfGuard::resolvePublicIp($monitor->host);
            $dnsMs = (int) round((microtime(true) - $dnsStart) * 1000);

            $connectStart = microtime(true);
            $errno = 0;
            $errstr = '';
            $stream = @stream_socket_client(
                "tcp://{$ip}:{$monitor->port}",
                $errno,
                $errstr,
                $monitor->timeout_ms / 1000,
                STREAM_CLIENT_CONNECT
            );
            $tcpMs = (int) round((microtime(true) - $connectStart) * 1000);

            if ($stream === false) {
                $class = str_contains(strtolower($errstr), 'timed out') ? 'tcp_timeout' : 'tcp_refused';

                return new CheckOutcome(ok: false, errorClass: $class, errorMsg: $errstr ?: 'Connection failed', resolvedIp: $ip, dnsMs: $dnsMs, tcpMs: $tcpMs, latencyMs: $dnsMs + $tcpMs);
            }

            fclose($stream);

            return new CheckOutcome(ok: true, resolvedIp: $ip, dnsMs: $dnsMs, tcpMs: $tcpMs, latencyMs: $dnsMs + $tcpMs);
        } catch (SsrfBlockedException $e) {
            return new CheckOutcome(ok: false, errorClass: $e->errorClass, errorMsg: $e->getMessage());
        } catch (\Throwable $e) {
            return new CheckOutcome(ok: false, errorClass: 'unknown_error', errorMsg: $e->getMessage());
        }
    }
}
