<?php

namespace App\Console\Commands;

use App\Checks\Checkers\HeartbeatChecker;
use App\Checks\CheckResultApplier;
use App\Models\CheckResult;
use App\Models\JobState;
use App\Models\Monitor;
use App\Services\Alerts\NotificationDispatcher;
use App\Services\Alerts\NotificationEnqueuer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the Node worker's `maintenance` role (apps/worker/src/maintenance.ts). Source app
 * ran this every ~60s as a single always-on instance guarded by a Postgres advisory lock;
 * here it's one more scheduled command (routes/console.php), and the scheduler's
 * withoutOverlapping() is the single-instance guarantee instead — see ProbeRun's docblock for
 * why that's sufficient on shared hosting (one server, one cron, no concurrent workers).
 *
 * check_results is never partitioned here (no native MySQL partitioning management without
 * SSH access to the server) — retention is a plain DELETE instead, and it's kept aggressive
 * (config('uptik.check_retention_hours')) because the dashboard reads only the rollup tables.
 */
class MaintenanceRun extends Command
{
    protected $signature = 'maintenance:run';

    protected $description = 'Rollups, retention cleanup, and heartbeat monitor evaluation';

    public function handle(
        HeartbeatChecker $heartbeatChecker,
        CheckResultApplier $applier,
        NotificationEnqueuer $enqueuer,
        NotificationDispatcher $dispatcher,
    ): int {
        $this->rollup('1m', 'check_rollups_1m', now()->startOfMinute());
        $this->rollup('1h', 'check_rollups_1h', now()->startOfHour(), fromRollup: '1m');

        $this->evaluateHeartbeats($heartbeatChecker, $applier);

        $enqueued = $enqueuer->enqueue();
        if ($enqueued > 0) {
            $this->info("Enqueued {$enqueued} notification(s).");
        }

        $delivery = $dispatcher->deliverDue();
        if ($delivery['total'] > 0) {
            $this->info("Notifications: {$delivery['sent']} sent, {$delivery['failed']} failed (of {$delivery['total']} due).");
        }

        $this->pruneOldData();

        $this->info('maintenance:run complete.');

        return self::SUCCESS;
    }

    /**
     * Builds ok_n/fail_n/p50/p95/max_ms per (monitor_id, region, bucket) since the last
     * watermark, up to (but not including) $upTo — so an in-progress bucket is never rolled
     * up half-finished. 1h buckets aggregate the 1m rollup table rather than raw check_results
     * (cheaper, and check_results may already be pruned for older hours); 1m aggregates raw
     * check_results. All percentile math happens in PHP — MySQL has no PERCENTILE_CONT — which
     * is fine at the row volumes a single shared-hosting account handles.
     */
    private function rollup(string $granularity, string $table, \Carbon\Carbon $upTo, ?string $fromRollup = null): void
    {
        $watermarkKey = "rollup_{$granularity}_watermark";
        $since = JobState::get($watermarkKey);
        $since = $since ? \Carbon\Carbon::parse($since) : now()->subDays(2);

        if ($since->gte($upTo)) {
            return;
        }

        $bucketFormat = $granularity === '1m' ? '%Y-%m-%d %H:%i:00' : '%Y-%m-%d %H:00:00';

        $rows = $fromRollup
            ? DB::table("check_rollups_{$fromRollup}")
                ->selectRaw("monitor_id, region, DATE_FORMAT(bucket, '{$bucketFormat}') as bucket_key, ok_n, fail_n, max_ms")
                ->where('bucket', '>=', $since)->where('bucket', '<', $upTo)
                ->get()
            : DB::table('check_results')
                ->selectRaw("monitor_id, region, DATE_FORMAT(ts, '{$bucketFormat}') as bucket_key, ok, latency_ms")
                ->where('ts', '>=', $since)->where('ts', '<', $upTo)
                ->get();

        $groups = [];
        foreach ($rows as $row) {
            $key = "{$row->monitor_id}|{$row->region}|{$row->bucket_key}";
            $groups[$key] ??= ['monitor_id' => $row->monitor_id, 'region' => $row->region, 'bucket' => $row->bucket_key, 'ok_n' => 0, 'fail_n' => 0, 'latencies' => []];

            if ($fromRollup) {
                $groups[$key]['ok_n'] += $row->ok_n;
                $groups[$key]['fail_n'] += $row->fail_n;
                if ($row->max_ms !== null) {
                    $groups[$key]['latencies'][] = $row->max_ms;
                }
            } else {
                $row->ok ? $groups[$key]['ok_n']++ : $groups[$key]['fail_n']++;
                if ($row->latency_ms !== null) {
                    $groups[$key]['latencies'][] = (int) $row->latency_ms;
                }
            }
        }

        foreach (array_chunk($groups, 200, true) as $chunk) {
            $upsertRows = [];
            foreach ($chunk as $g) {
                sort($g['latencies']);
                $upsertRows[] = [
                    'monitor_id' => $g['monitor_id'],
                    'region' => $g['region'],
                    'bucket' => $g['bucket'],
                    'ok_n' => $g['ok_n'],
                    'fail_n' => $g['fail_n'],
                    'p50' => $this->percentile($g['latencies'], 0.50),
                    'p95' => $this->percentile($g['latencies'], 0.95),
                    'max_ms' => empty($g['latencies']) ? null : max($g['latencies']),
                ];
            }

            if (! empty($upsertRows)) {
                DB::table($table)->upsert($upsertRows, ['monitor_id', 'region', 'bucket'], ['ok_n', 'fail_n', 'p50', 'p95', 'max_ms']);
            }
        }

        JobState::put($watermarkKey, $upTo->toDateTimeString());
        $this->info(ucfirst($granularity)." rollup: {$rows->count()} row(s) -> ".count($groups).' bucket(s).');
    }

    private function percentile(array $sorted, float $p): ?int
    {
        if (empty($sorted)) {
            return null;
        }

        $index = (int) ceil($p * count($sorted)) - 1;

        return $sorted[max(0, min($index, count($sorted) - 1))];
    }

    /**
     * heartbeat monitors have no network check — only maintenance:run evaluates them (never
     * ProbeRun/CheckDispatcher), comparing monitor_states.last_heartbeat_at against
     * heartbeat_grace_s. The customer's own cron pings HeartbeatController, which just stamps
     * last_heartbeat_at; going down is entirely a matter of that stamp going stale.
     */
    private function evaluateHeartbeats(HeartbeatChecker $checker, CheckResultApplier $applier): void
    {
        $monitors = Monitor::where('type', 'heartbeat')
            ->where('enabled', true)
            ->whereHas('state', fn ($q) => $q->where('status', '!=', 'paused'))
            ->with('state')
            ->get();

        foreach ($monitors as $monitor) {
            $outcome = $checker->check($monitor, $monitor->state->last_heartbeat_at);
            $applier->apply($monitor, $outcome);
        }

        if ($monitors->isNotEmpty()) {
            $this->info("Evaluated {$monitors->count()} heartbeat monitor(s).");
        }
    }

    private function pruneOldData(): void
    {
        $retentionHours = config('uptik.check_retention_hours');
        $deleted = CheckResult::where('ts', '<', now()->subHours($retentionHours))->delete();

        DB::table('check_rollups_1m')->where('bucket', '<', now()->subDays(2))->delete();
        DB::table('check_rollups_1h')->where('bucket', '<', now()->subDays(100))->delete();
        DB::table('sessions')->where('last_activity', '<', now()->subDays(30)->timestamp)->delete();

        // Resolved incidents beyond a year are pruned; cascades to incident_events/notifications.
        DB::table('incidents')->where('state', 'resolved')->where('resolved_at', '<', now()->subDays(400))->delete();

        if ($deleted > 0) {
            $this->info("Pruned {$deleted} old check_results row(s).");
        }
    }
}
