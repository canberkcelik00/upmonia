<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Opaque-token/hashed-id pattern shared by email verification and alert-channel
 * verification: the raw token only ever exists in the email link; the DB stores its
 * SHA-256 hash as the row's primary key, so a DB leak alone can't be replayed.
 */
class TokenService
{
    public static function generate(): array
    {
        $raw = Str::random(40);

        return [$raw, hash('sha256', $raw)];
    }

    public static function hash(string $raw): string
    {
        return hash('sha256', $raw);
    }
}
