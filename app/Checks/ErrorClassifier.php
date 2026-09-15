<?php

namespace App\Checks;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ConnectTimeoutException;
use GuzzleHttp\Exception\NetworkException;
use GuzzleHttp\Exception\NetworkTimeoutException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ResponseTimeoutException;

/**
 * Maps a low-level failure (curl errno, HTTP status, keyword mismatch, ...) to one of a
 * small set of error_class strings, each with a bilingual label in lang/{tr,en}/errors.php —
 * this is what lets an alert email say "TLS certificate expired" instead of "site down".
 *
 * Guzzle 8 dropped the getHandlerContext()/errno-array API older Guzzle versions exposed on
 * exceptions (verified against the installed 8.2.0 — no such method exists). It now throws a
 * typed exception hierarchy instead (ConnectTimeoutException, NetworkException, ...), which
 * this classifies on first; the curl errno — still baked into the message as
 * "cURL error {errno}: ..." — is parsed out only to distinguish sub-cases (DNS vs TCP-refused
 * vs TLS) within that hierarchy.
 */
class ErrorClassifier
{
    public static function label(?string $errorClass, ?string $locale = null): ?string
    {
        if ($errorClass === null) {
            return null;
        }

        $key = 'errors.'.$errorClass;

        return __($key, [], $locale) !== $key
            ? __($key, [], $locale)
            : __('errors.unknown_error', [], $locale);
    }

    public static function fromException(\Throwable $e): array
    {
        if ($e instanceof SsrfBlockedException) {
            return [$e->errorClass, $e->getMessage()];
        }

        if ($e instanceof ConnectTimeoutException || $e instanceof NetworkTimeoutException || $e instanceof ResponseTimeoutException) {
            return ['timeout', $e->getMessage()];
        }

        if ($e instanceof ConnectException || $e instanceof NetworkException || $e instanceof RequestException) {
            $errno = self::extractCurlErrno($e->getMessage());

            return self::fromCurlErrno($errno, $e->getMessage());
        }

        return ['unknown_error', $e->getMessage()];
    }

    /**
     * Shared with App\Checks\ConcurrentHttpChecker, which drives curl_multi directly (for
     * real per-batch concurrency) rather than going through Guzzle/exceptions — same errno
     * table, so the two HTTP transports never disagree on what a given failure means.
     */
    public static function fromCurlErrno(?int $errno, string $message): array
    {
        return match ($errno) {
            6 => ['dns_nxdomain', $message],       // CURLE_COULDNT_RESOLVE_HOST
            7 => ['tcp_refused', $message],        // CURLE_COULDNT_CONNECT
            28 => ['timeout', $message],           // CURLE_OPERATION_TIMEOUTED
            52 => ['connection_reset', $message],  // CURLE_GOT_NOTHING
            35, 51, 60, 83 => [self::classifyTlsMessage($message), $message],
            default => ['unknown_error', $message],
        };
    }

    public static function classifyTlsMessage(string $message): string
    {
        $lower = strtolower($message);

        if (str_contains($lower, 'expired')) {
            return 'tls_expired';
        }

        if (str_contains($lower, 'self signed') || str_contains($lower, 'self-signed')) {
            return 'tls_self_signed';
        }

        return 'tls_error';
    }

    private static function extractCurlErrno(string $message): ?int
    {
        return preg_match('/cURL error (\d+):/', $message, $m) ? (int) $m[1] : null;
    }
}
