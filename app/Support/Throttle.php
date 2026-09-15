<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Livewire component actions (signup, login, password reset, resend verification, ...)
 * are all submitted to Livewire's own shared update endpoint, not a per-action route, so
 * the `throttle:name` route middleware can't protect them the way it protects a real route.
 * This wraps the same underlying Illuminate\Cache\RateLimiter (backed by the `database`
 * cache store, per .env CACHE_STORE) for direct use inside a component's action method —
 * the fixed-window semantics the source Node app implemented by hand against a
 * `rate_limit_hits` table, minus the hand-rolled table.
 */
class Throttle
{
    /**
     * @throws TooManyAttemptsException
     */
    public static function hit(string $key, int $maxAttempts, int $decaySeconds = 60): void
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw new TooManyAttemptsException(RateLimiter::availableIn($key));
        }

        RateLimiter::hit($key, $decaySeconds);
    }
}
