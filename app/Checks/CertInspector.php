<?php

namespace App\Checks;

/**
 * Lightweight TLS handshake used both by SslChecker (the dedicated `ssl` monitor type) and,
 * as a side effect, by HttpChecker on every https:// http/keyword check — so plain uptime
 * monitors surface certificate expiry too, without the customer having to add a second
 * monitor just to watch a cert.
 */
class CertInspector
{
    public function __construct(private readonly string $ip, private readonly string $sniHost, private readonly int $port) {}

    /**
     * @return array{authorized: bool, self_signed: bool, expires_at: ?\DateTimeImmutable, issuer: ?string, error: ?string}
     */
    public function inspect(int $timeoutMs, bool $verify = true): array
    {
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => $verify,
                'verify_peer_name' => $verify,
                'peer_name' => $this->sniHost,
                'SNI_enabled' => true,
                'allow_self_signed' => ! $verify,
            ],
        ]);

        $errno = 0;
        $errstr = '';

        // Connect to the pre-validated IP directly (SsrfGuard already vetted it), but present
        // the original hostname via SNI/peer_name above so the right certificate is served.
        $stream = @stream_socket_client(
            "ssl://{$this->ip}:{$this->port}",
            $errno,
            $errstr,
            $timeoutMs / 1000,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($stream === false) {
            $selfSigned = str_contains($errstr, 'self signed') || str_contains($errstr, 'self-signed');

            return [
                'authorized' => false,
                'self_signed' => $selfSigned,
                'expires_at' => null,
                'issuer' => null,
                'error' => $errstr ?: 'TLS handshake failed',
            ];
        }

        $params = stream_context_get_params($stream);
        fclose($stream);

        $cert = $params['options']['ssl']['peer_certificate'] ?? null;

        if (! $cert) {
            return ['authorized' => false, 'self_signed' => false, 'expires_at' => null, 'issuer' => null, 'error' => 'No peer certificate presented'];
        }

        $parsed = openssl_x509_parse($cert);

        $issuer = $parsed['issuer']['O'] ?? $parsed['issuer']['CN'] ?? null;
        $expiresAt = isset($parsed['validTo_time_t'])
            ? (new \DateTimeImmutable('@'.$parsed['validTo_time_t']))
            : null;

        $selfSigned = ($parsed['issuer']['CN'] ?? null) !== null
            && ($parsed['issuer']['CN'] ?? null) === ($parsed['subject']['CN'] ?? '__no_match__');

        return [
            'authorized' => true,
            'self_signed' => $selfSigned,
            'expires_at' => $expiresAt,
            'issuer' => $issuer,
            'error' => null,
        ];
    }
}
