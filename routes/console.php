<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The only cron entry this app needs on the host: `* * * * * php artisan schedule:run`.
// withoutOverlapping() stands in for the source Node worker's pg_try_advisory_lock /
// FOR UPDATE SKIP LOCKED — on shared hosting there's exactly one process, ever, so that's
// sufficient (see ProbeRun/MaintenanceRun docblocks for the full reasoning).
//
// Schedule::call + Artisan::call, not Schedule::command: the latter spawns each command as a
// child process via proc_open(), which shared hosts commonly list in disable_functions — the
// event then "finishes" instantly without ever running. Calling in-process avoids it.
//
// withoutOverlapping(5, false): 5-minute mutex expiry instead of the 24h default, so a run
// killed abnormally can't silence both commands for a day. `false` skips Laravel's
// pcntl_signal() handler — with pcntl loaded but pcntl_signal disabled, that call throws right
// after the mutex is acquired and before it can be released, wedging the lock every run.
Schedule::call(fn () => Artisan::call('probe:run'))
    ->name('probe:run')
    ->everyMinute()
    ->withoutOverlapping(5, false);

Schedule::call(fn () => Artisan::call('maintenance:run'))
    ->name('maintenance:run')
    ->everyMinute()
    ->withoutOverlapping(5, false);
