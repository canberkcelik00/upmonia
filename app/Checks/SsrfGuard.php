<?php

namespace App\Checks;

/**
 * Stops a monitor URL/host from being used to probe the server's own private network.
 * Resolves DNS itself (rather than letting curl do it) so the caller can pin the outgoing
 * connection to the exact IP that was validated — closing the classic DNS-rebinding gap
 * where a hostname resolves to a public IP at validation time and a private one at
 * connect time. Every redirect hop must be re-validated by calling resolvePublicIp() again;
 * this class only vets one hop at a time.
 */
class SsrfGuard
{
    private const DNS_TIMEOUT_S = 8;

    public static function assertNoCredentials(string $url): void
    {
        $user = parse_url($url, PHP_URL_USER);

        if ($user !== null && $user !== false) {
            throw new SsrfBlockedException('ssrf_blocked', 'URL must not contain embedded credentials.');
        }
    }

    public static function assertHttpPortAllowed(int $port): void
    {
        if (! in_array($port, config('upvane.ssrf.http_ports'), true)) {
            throw new SsrfBlockedException('ssrf_blocked', "Port {$port} is not in the allowed HTTP port list.");
        }
    }

    public static function assertTcpPortAllowed(int $port): void
    {
        if (! in_array($port, config('upvane.ssrf.tcp_ports'), true)) {
            throw new SsrfBlockedException('ssrf_blocked', "Port {$port} is not in the allowed TCP port list.");
        }
    }

    /**
     * Resolves $host to one public IP, validating every candidate address. Throws unless at
     * least one resolved (or literal) address is safe to connect to.
     */
    public static function resolvePublicIp(string $host): string
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            self::assertPublicIp($host);

            return $host;
        }

        $ips = self::lookup($host);

        if (empty($ips)) {
            throw new SsrfBlockedException('dns_nxdomain', "Could not resolve host: {$host}");
        }

        foreach ($ips as $ip) {
            if (self::isPublicIp($ip)) {
                return $ip;
            }
        }

        throw new SsrfBlockedException('ssrf_blocked', "{$host} resolves only to private/reserved addresses.");
    }

    public static function assertPublicIp(string $ip): void
    {
        if (! self::isPublicIp($ip)) {
            throw new SsrfBlockedException('ssrf_blocked', "{$ip} is a private/reserved address.");
        }
    }

    public static function isPublicIp(string $ip): bool
    {
        if (self::allowPrivateTargets()) {
            return true;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    public static function allowPrivateTargets(): bool
    {
        return (bool) config('upvane.allow_private_targets');
    }

    /**
     * @return string[] IPv4 and IPv6 addresses for $host, empty array if none resolve.
     */
    private static function lookup(string $host): array
    {
        $ips = [];

        $deadline = microtime(true) + self::DNS_TIMEOUT_S;
        ini_set('default_socket_timeout', (string) self::DNS_TIMEOUT_S);

        $v4 = @dns_get_record($host, DNS_A);
        if (is_array($v4)) {
            foreach ($v4 as $record) {
                if (isset($record['ip'])) {
                    $ips[] = $record['ip'];
                }
            }
        }

        if (microtime(true) < $deadline) {
            $v6 = @dns_get_record($host, DNS_AAAA);
            if (is_array($v6)) {
                foreach ($v6 as $record) {
                    if (isset($record['ipv6'])) {
                        $ips[] = $record['ipv6'];
                    }
                }
            }
        }

        return $ips;
    }
}
