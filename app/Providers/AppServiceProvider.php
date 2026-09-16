<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Single source of truth for user-facing date/time display (dd-mm-yyyy H:i).
        Carbon::macro('toDisplay', fn () => $this->format('d-m-Y H:i'));
        Carbon::macro('toDisplayDate', fn () => $this->format('d-m-Y'));

        // Dev fallback: without a Resend key, log outbound mail instead of letting the
        // Resend SDK throw on every send. In production a missing key is left alone on
        // purpose — sends then fail loudly (fail closed) instead of silently no-op'ing.
        if (! $this->app->environment('production') && blank(config('services.resend.key'))) {
            config(['mail.default' => 'log']);
        }

        $this->registerRateLimiters();
    }

    protected function registerRateLimiters(): void
    {
        // Only routes that go through real Laravel route middleware are registered here
        // (the `throttle:name` middleware needs a route to attach to). Livewire component
        // actions (signup, login, password reset, resend verification, test email) are all
        // submitted through Livewire's own update endpoint, not a per-action route, so those
        // are throttled directly inside each component via App\Support\Throttle — see its
        // docblock for why.
        RateLimiter::for('heartbeat', fn ($request) => Limit::perMinute(60)->by('heartbeat:'.$request->route('token')));
    }
}
