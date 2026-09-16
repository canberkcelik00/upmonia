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
// sufficient (see ProbeRun/MaintenanceRun docblocks for the full reasoning). Deliberately NOT
// ->runInBackground(): that needs proc_open(), which restrictive shared-hosting PHP configs
// often disable. Both commands run in-process, one after the other, inside one schedule:run —
// fine, since ConcurrentHttpChecker keeps probe:run itself fast regardless of batch size.
//
// withoutOverlapping(5): an explicit 5-minute mutex expiry. Without an argument Laravel
// defaults to 1440 minutes (24h) — if a run is ever killed abnormally (OOM, host kill,
// max_execution_time) without a graceful signal, the mutex is never released and both
// commands would silently stop firing for up to a day. 5 minutes comfortably covers normal
// runtime (ProbeRun's own lock_seconds default is 50s) while keeping any stuck-lock outage short.
Schedule::command('probe:run')->everyMinute()->withoutOverlapping(5);
Schedule::command('maintenance:run')->everyMinute()->withoutOverlapping(5);
