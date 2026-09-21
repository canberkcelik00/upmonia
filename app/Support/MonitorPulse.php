<?php

namespace App\Support;

use App\Models\CheckRollup1h;
use App\Models\CheckRollup1m;
use App\Models\Incident;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Batched reads behind the brand's signature graphic, the tick strip (see the brand guide's
 * "Kontrol şeridi" section): every method here takes a set of monitor ids and returns one
 * query's worth of answer for all of them, so a page with 40 monitors never fires 40 queries.
 */
class MonitorPulse
{
    /**
     * @param  iterable<int>  $monitorIds
     * @param  array<int, string>  $statusByMonitor  monitor_id => monitor_states.status, used only
     *                                                to tell an empty *paused* bucket (idle) apart
     *                                                from an empty bucket on an active monitor
     *                                                (nodata — a real gap, or too new to have data).
     * @return array<int, array{tones: list<string>, times: list<string>, upN: int, warnN: int, downN: int}>
     */
    public static function ticks(iterable $monitorIds, array $statusByMonitor, int $count = 30): array
    {
        $ids = collect($monitorIds)->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $now = Carbon::now()->startOfMinute();
        $windowStart = $now->copy()->subMinutes($count - 1);

        $rows = CheckRollup1m::query()
            ->whereIn('monitor_id', $ids)
            ->where('bucket', '>=', $windowStart)
            ->get(['monitor_id', 'bucket', 'ok_n', 'fail_n'])
            ->groupBy('monitor_id');

        // Only incidents that could possibly overlap the window are worth loading — a
        // confirmed outage is what turns a failed bucket red instead of amber.
        $incidents = Incident::query()
            ->whereIn('monitor_id', $ids)
            ->where(function ($q) use ($windowStart) {
                $q->whereNull('resolved_at')->orWhere('resolved_at', '>=', $windowStart);
            })
            ->get(['monitor_id', 'started_at', 'resolved_at'])
            ->groupBy('monitor_id');

        $buckets = collect(range($count - 1, 0))->map(fn ($i) => $now->copy()->subMinutes($i));

        return $ids->mapWithKeys(function ($id) use ($buckets, $rows, $incidents, $statusByMonitor) {
            $byBucket = ($rows->get($id) ?? collect())->keyBy(fn ($r) => $r->bucket->toDateTimeString());
            $ranges = $incidents->get($id) ?? collect();
            $paused = ($statusByMonitor[$id] ?? null) === 'paused';

            [$upN, $warnN, $downN] = [0, 0, 0];

            $tones = $buckets->map(function (Carbon $bucket) use ($byBucket, $ranges, $paused, &$upN, &$warnN, &$downN) {
                $row = $byBucket->get($bucket->toDateTimeString());

                if (! $row) {
                    return $paused ? 'idle' : 'nodata';
                }

                if ((int) $row->fail_n === 0) {
                    $upN++;

                    return 'up';
                }

                $confirmed = $ranges->contains(fn ($r) => $bucket->betweenIncluded($r->started_at, $r->resolved_at ?? Carbon::now()));

                $confirmed ? $downN++ : $warnN++;

                return $confirmed ? 'down' : 'warn';
            })->values()->all();

            $times = $buckets->map(fn (Carbon $b) => $b->copy()->utc()->toIso8601String())->values()->all();

            return [$id => ['tones' => $tones, 'times' => $times, 'upN' => $upN, 'warnN' => $warnN, 'downN' => $downN]];
        })->all();
    }

    /**
     * @param  iterable<int>  $monitorIds
     * @return array<int, string|null> monitor_id => Format::percent() result, or null when
     *                                  the monitor has no checks at all in the window.
     */
    public static function uptimePercents(iterable $monitorIds, Carbon $since): array
    {
        $ids = collect($monitorIds)->values();

        if ($ids->isEmpty()) {
            return [];
        }

        // 1m rollups are pruned after 2 days (maintenance:run); beyond that, fall back to the
        // 1h rollups, kept for 100 days. A day of margin keeps this correct right at the edge.
        $table = $since->diffInHours(Carbon::now()) <= 47 ? CheckRollup1m::class : CheckRollup1h::class;

        $rows = $table::query()
            ->whereIn('monitor_id', $ids)
            ->where('bucket', '>=', $since)
            ->selectRaw('monitor_id, SUM(ok_n) as ok_n, SUM(fail_n) as fail_n')
            ->groupBy('monitor_id')
            ->get()
            ->keyBy('monitor_id');

        return $ids->mapWithKeys(function ($id) use ($rows) {
            $row = $rows->get($id);

            return [$id => $row ? Format::percent((int) $row->ok_n, (int) $row->fail_n) : null];
        })->all();
    }

    /**
     * Hourly buckets for one monitor's trend chart, covering the last $hours whole hours
     * including the current one. "saatlik medyan", not "ortalama": the 1h rollup only
     * carries p50/p95/max, never a mean.
     */
    public static function hourlyLatency(int $monitorId, int $hours = 24): Collection
    {
        $currentHour = Carbon::now()->startOfHour();

        $buckets = CheckRollup1h::query()
            ->where('monitor_id', $monitorId)
            ->where('bucket', '>=', $currentHour->copy()->subHours($hours - 1))
            ->orderBy('bucket')
            ->get()
            ->map(fn ($r) => (object) [
                'ts' => $r->bucket,
                'p50' => $r->p50,
                'p95' => $r->p95,
                'ok_n' => (int) $r->ok_n,
                'fail_n' => (int) $r->fail_n,
            ]);

        // MaintenanceRun only rolls up completed hours, so the hour in progress is built from
        // its 1m buckets — otherwise the chart would always trail "now" by up to an hour.
        $minutes = CheckRollup1m::query()
            ->where('monitor_id', $monitorId)
            ->where('bucket', '>=', $currentHour)
            ->get(['p50', 'ok_n', 'fail_n']);

        if ($minutes->isNotEmpty() && ! $buckets->contains(fn ($b) => $b->ts->equalTo($currentHour))) {
            $latencies = $minutes->pluck('p50')->filter(fn ($v) => $v !== null)->sort()->values();

            $buckets->push((object) [
                'ts' => $currentHour,
                'p50' => self::percentile($latencies, 0.5),
                'p95' => self::percentile($latencies, 0.95),
                'ok_n' => (int) $minutes->sum('ok_n'),
                'fail_n' => (int) $minutes->sum('fail_n'),
            ]);
        }

        return $buckets;
    }

    /**
     * Nearest-rank percentile over already-sorted values. For monitors checked once a minute
     * each 1m bucket holds a single sample, so this is exact rather than a median of medians.
     */
    private static function percentile(Collection $sorted, float $p): ?int
    {
        if ($sorted->isEmpty()) {
            return null;
        }

        return (int) $sorted[max(0, (int) ceil($p * $sorted->count()) - 1)];
    }
}
