<?php

namespace App\Http\Controllers;

use App\Models\CheckRollup1h;
use App\Models\StatusPage;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class StatusPageController extends Controller
{
    private const HISTORY_DAYS = 90;

    /**
     * Deliberately narrow: only ever selects fields safe to expose publicly (name, status,
     * uptime history). Never url/host/port/error_msg — those stay internal even though the
     * monitor row technically has them, matching the source app's getPublicStatusPage().
     *
     * The daily uptime query below keeps the same boundary: it reads only check_rollups_1h
     * (monitor_id, region, bucket, ok_n, fail_n) — never raw check_results, which may hold
     * per-check error_msg/resolved_ip/redirect_chain/status_code and (post segment-log
     * compaction) no longer has one row per minute anyway. check_rollups_1h is used instead
     * of check_rollups_1m because 1m rollups are pruned after 2 days while this page shows
     * HISTORY_DAYS (45) of daily history — 1h rollups are retained for 100 days.
     */
    public function show(string $slug): View
    {
        $page = StatusPage::where('slug', $slug)->where('enabled', true)->firstOrFail();

        $monitorRows = $page->monitors()->get(['monitors.id', 'monitors.name']);
        $monitorIds = $monitorRows->pluck('id');

        $monitorRows->load('state:monitor_id,status,last_checked_at');

        $dailyByMonitor = $this->dailyUptimeByMonitor($monitorIds);

        $monitors = $monitorRows->map(function ($m) use ($dailyByMonitor) {
            $days = $dailyByMonitor->get($m->id, collect());
            $okN = $days->sum(fn ($d) => $d['total'] - $d['failed']);
            $failN = $days->sum('failed');

            return [
                'name' => $m->pivot->display_name ?: $m->name,
                'status' => $m->state->status,
                'last_checked_at' => $m->state->last_checked_at,
                'days' => $days,
                'percent' => \App\Support\Format::percent($okN, $failN),
                'tones' => $days->map(fn ($d) => match (true) {
                    $d['total'] === 0 => 'nodata',
                    $d['failed'] === 0 => 'up',
                    $d['failed'] / $d['total'] < 0.5 => 'warn',
                    default => 'down',
                })->values()->all(),
            ];
        });

        $hasDegraded = $monitors->contains(fn ($m) => in_array($m['status'], ['down', 'suspect'], true));
        $hasPending = $monitors->contains(fn ($m) => $m['status'] === 'pending');

        $overallStatus = match (true) {
            $hasDegraded => 'degraded',
            $hasPending => 'pending',
            default => 'operational',
        };

        $incidents = $page->show_history
            ? \App\Models\Incident::withoutGlobalScopes()
                ->whereIn('monitor_id', $monitorIds)
                ->orderByDesc('started_at')
                ->limit(20)
                ->get(['id', 'monitor_id', 'state', 'started_at', 'resolved_at', 'cause_class'])
                ->load('monitor:id,name')
            : collect();

        return view('status-page.show', compact('page', 'monitors', 'overallStatus', 'incidents'));
    }

    /**
     * One batched query for every monitor on the page (no N+1), grouped by day, so the
     * public page can show a per-monitor uptime history bar. Returns every monitor id
     * mapped to a collection of the last HISTORY_DAYS days (oldest first), each day
     * always present even with zero checks, as ['date' => Carbon, 'total' => int, 'failed' => int].
     *
     * @param  \Illuminate\Support\Collection<int, int>  $monitorIds
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection>
     */
    private function dailyUptimeByMonitor($monitorIds): \Illuminate\Support\Collection
    {
        $since = now()->subDays(self::HISTORY_DAYS - 1)->startOfDay();

        $rows = CheckRollup1h::query()
            ->whereIn('monitor_id', $monitorIds)
            ->where('bucket', '>=', $since)
            ->selectRaw('monitor_id, DATE(bucket) as day, SUM(ok_n) as ok_n, SUM(fail_n) as fail_n')
            ->groupBy('monitor_id', 'day')
            ->get()
            ->groupBy('monitor_id');

        $dateRange = collect(range(0, self::HISTORY_DAYS - 1))
            ->map(fn ($i) => $since->copy()->addDays($i));

        return $monitorIds->mapWithKeys(function ($monitorId) use ($rows, $dateRange) {
            $byDay = ($rows->get($monitorId) ?? collect())->keyBy(fn ($r) => Carbon::parse($r->day)->toDateString());

            $days = $dateRange->map(function (Carbon $date) use ($byDay) {
                $row = $byDay->get($date->toDateString());
                $okN = $row ? (int) $row->ok_n : 0;
                $failN = $row ? (int) $row->fail_n : 0;

                return [
                    'date' => $date,
                    'total' => $okN + $failN,
                    'failed' => $failN,
                ];
            });

            return [$monitorId => $days];
        });
    }
}
