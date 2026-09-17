<?php

namespace App\Console\Commands;

use App\Checks\CheckDispatcher;
use App\Checks\CheckResultApplier;
use App\Models\Monitor;
use App\Models\MonitorState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the Node worker's `probe` role (apps/worker/src/probe.ts) — instead of an
 * infinite polling loop, this runs once per invocation and is fired by the Laravel
 * scheduler (routes/console.php), itself driven by a single cPanel cron entry
 * (`* * * * * php artisan schedule:run`). withoutOverlapping() on the schedule is this
 * app's stand-in for the source app's pg_try_advisory_lock/FOR UPDATE SKIP LOCKED —
 * on shared hosting there's only ever one process, so full lease/lock machinery for
 * concurrent workers isn't needed; monitor_states.locked_until is kept only as cheap
 * insurance against one slow-running invocation overlapping the next.
 */
class ProbeRun extends Command
{
    protected $signature = 'probe:run';

    protected $description = 'Lease and run all monitors currently due for a check';

    public function handle(CheckDispatcher $dispatcher, CheckResultApplier $applier): int
    {
        $monitors = $this->leaseDue();

        if ($monitors->isEmpty()) {
            $this->info('No due monitors.');

            return self::SUCCESS;
        }

        $started = microtime(true);
        $outcomes = $dispatcher->runBatch($monitors);

        foreach ($monitors as $monitor) {
            $outcome = $outcomes[$monitor->id] ?? null;

            if ($outcome === null) {
                continue; // shouldn't happen; defensive skip rather than a fatal error mid-batch
            }

            $applier->apply($monitor, $outcome);
        }

        $elapsed = round((microtime(true) - $started) * 1000);
        $this->info("Checked {$monitors->count()} monitor(s) in {$elapsed}ms.");

        return self::SUCCESS;
    }

    private function leaseDue(): \Illuminate\Support\Collection
    {
        $batchSize = config('upvane.probe.batch_size');
        $lockSeconds = config('upvane.probe.lock_seconds');

        $ids = DB::table('monitor_states')
            ->join('monitors', 'monitors.id', '=', 'monitor_states.monitor_id')
            ->where('monitors.enabled', true)
            ->where('monitor_states.status', '!=', 'paused')
            ->whereIn('monitors.type', ['http', 'keyword', 'ssl', 'tcp_port'])
            ->where(function ($q) {
                $q->where('monitor_states.next_check_at', '<=', now())
                    ->orWhereNotNull('monitor_states.check_requested_at');
            })
            ->where(function ($q) {
                $q->whereNull('monitor_states.locked_until')
                    ->orWhere('monitor_states.locked_until', '<', now());
            })
            ->orderByRaw('monitor_states.check_requested_at is null') // manual "check now" requests first
            ->orderBy('monitor_states.next_check_at')
            ->limit($batchSize)
            ->pluck('monitor_states.monitor_id');

        if ($ids->isEmpty()) {
            return collect();
        }

        MonitorState::whereIn('monitor_id', $ids)->update(['locked_until' => now()->addSeconds($lockSeconds)]);

        return Monitor::whereIn('id', $ids)->get();
    }
}
