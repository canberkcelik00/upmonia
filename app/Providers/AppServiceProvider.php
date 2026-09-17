<?php

namespace App\Providers;

use App\Support\OrgHealth;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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
        // Single source of truth for user-facing date/time display, in the current
        // request's locale (see lang/{tr,en}/app.php and the brand guide's "Sayı, saat ve
        // süre" table): "16 Eyl 2026 14:28", "16 Eylül 2026", "14:28:04".
        Carbon::macro('toDisplay', fn () => $this->locale(app()->getLocale())->translatedFormat('d M Y H:i'));
        Carbon::macro('toDisplayDate', fn () => $this->locale(app()->getLocale())->translatedFormat('d F Y'));
        Carbon::macro('toDisplayTime', fn () => $this->format('H:i:s'));

        // Dev fallback: without a Resend key, log outbound mail instead of letting the
        // Resend SDK throw on every send. In production a missing key is left alone on
        // purpose — sends then fail loudly (fail closed) instead of silently no-op'ing.
        if (! $this->app->environment('production') && blank(config('services.resend.key'))) {
            config(['mail.default' => 'log']);
        }

        $this->registerRateLimiters();

        // Shared with layouts.app: drives the nav logo's mark colour and the browser tab's
        // favicon/title. Only resolves for authenticated requests that actually render the
        // app shell (guest pages don't hit this view), so it never fires on login/signup.
        View::composer('layouts.app', function ($view) {
            $view->with('orgHealth', OrgHealth::current());
        });
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
