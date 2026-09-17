<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HeartbeatController;
use App\Http\Controllers\StatusPageController;
use Illuminate\Support\Facades\Route;

// Signed-in visitors skip straight to where the product actually starts; everyone else
// gets the marketing home page (see docs/brand/upmonia-brand-guidelines.html, "Uygulamalar").
Route::get('/', function () {
    return auth()->check()
        ? redirect()->to(route('monitors.index'))
        : view('marketing.home');
})->name('home');

Route::get('/ozellikler', function () {
    return view('marketing.features');
})->name('features');

Route::middleware('guest')->group(function () {
    Route::livewire('/signup', 'auth.signup-form')->name('signup');
    Route::livewire('/login', 'auth.login-form')->name('login');
    Route::livewire('/parola-sifirla', 'auth.reset-request-form')->name('password.request');
    Route::livewire('/parola-sifirla/{token}', 'auth.reset-password-form')->name('password.reset');
});

// Public: reachable whether or not the visitor is logged in (e.g. verifying a second
// browser/session, or an email-change verification while already authenticated elsewhere).
Route::livewire('/dogrula/{token}', 'auth.verify-email')->name('verify-email');
Route::livewire('/kanal-dogrula/{token}', 'auth.verify-channel')->name('verify-channel');

Route::match(['get', 'post'], '/heartbeat/{token}', HeartbeatController::class)
    ->middleware('throttle:heartbeat')
    ->name('heartbeat');

Route::get('/durum/{slug}', [StatusPageController::class, 'show'])->name('status-page.show');

Route::get('/api/health', [HealthController::class, 'liveness'])->name('health');
Route::get('/api/health/deep', [HealthController::class, 'deep'])->name('health.deep');

Route::get('/deploy', \App\Http\Controllers\DeployController::class)->name('deploy');

Route::get('/dil/{locale}', function (string $locale) {
    if (in_array($locale, \App\Http\Middleware\SetLocale::SUPPORTED, true)) {
        session(['locale' => $locale]);

        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }
    }

    return redirect()->back();
})->name('locale.switch');

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::livewire('/monitors', 'monitors.index')->name('monitors.index');
    Route::livewire('/monitors/new', 'monitors.form')->name('monitors.create');
    Route::livewire('/monitors/{monitor}', 'monitors.show')->name('monitors.show');
    Route::livewire('/incidents', 'incidents.index')->name('incidents.index');
    Route::livewire('/incidents/{incident}', 'incidents.show')->name('incidents.show');
    Route::get('/settings/export', \App\Http\Controllers\AccountExportController::class)->name('settings.export');
    Route::livewire('/settings', 'settings.index')->name('settings.index');
    Route::livewire('/settings/channels', 'settings.channels')->name('settings.channels');
    Route::livewire('/settings/clients', 'settings.clients')->name('settings.clients');
    Route::livewire('/settings/maintenance-windows', 'settings.maintenance-windows')->name('settings.maintenance-windows');
    Route::livewire('/settings/status-pages', 'settings.status-pages')->name('settings.status-pages');
});
