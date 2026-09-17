<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Monitor;
use App\Models\Organization;
use App\Support\MonitorPulse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonitorPulseTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $now;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = Carbon::create(2026, 1, 1, 12, 0, 0);
        Carbon::setTestNow($this->now);
        app()->setLocale('tr');

        $this->org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeMonitor(string $status): Monitor
    {
        $monitor = Monitor::create([
            'organization_id' => $this->org->id,
            'name' => 'Example',
            'type' => 'http',
            'url' => 'https://example.com',
        ]);

        // Monitor::booted() already creates a 'pending' state row on creation — update it
        // rather than inserting a second row for the same (primary-key) monitor_id.
        $monitor->state()->update(['status' => $status]);

        return $monitor;
    }

    private function rollup(Monitor $monitor, Carbon $bucket, int $ok, int $fail): void
    {
        DB::table('check_rollups_1m')->insert([
            'monitor_id' => $monitor->id,
            'region' => 'local',
            'bucket' => $bucket,
            'ok_n' => $ok,
            'fail_n' => $fail,
        ]);
    }

    public function test_ticks_classifies_up_warn_down_and_pads_missing_buckets_as_nodata(): void
    {
        $monitor = $this->makeMonitor('up');

        $this->rollup($monitor, $this->now->copy()->subMinutes(2), ok: 1, fail: 0);
        $this->rollup($monitor, $this->now->copy()->subMinutes(1), ok: 0, fail: 1); // unconfirmed — no incident covers it
        $this->rollup($monitor, $this->now->copy(), ok: 0, fail: 1); // confirmed — covered by the incident below

        Incident::create([
            'organization_id' => $this->org->id,
            'monitor_id' => $monitor->id,
            'state' => 'open',
            'started_at' => $this->now->copy(),
            'cause_class' => 'timeout',
        ]);

        $result = MonitorPulse::ticks([$monitor->id], [$monitor->id => 'up'], count: 5);
        $tones = $result[$monitor->id]['tones'];

        // 5 buckets ending now: [now-4, now-3, now-2, now-1, now]
        $this->assertSame(['nodata', 'nodata', 'up', 'warn', 'down'], $tones);
        $this->assertSame(1, $result[$monitor->id]['upN']);
        $this->assertSame(1, $result[$monitor->id]['warnN']);
        $this->assertSame(1, $result[$monitor->id]['downN']);
    }

    public function test_ticks_reads_empty_buckets_as_idle_for_a_paused_monitor(): void
    {
        $monitor = $this->makeMonitor('paused');

        $tones = MonitorPulse::ticks([$monitor->id], [$monitor->id => 'paused'], count: 4)[$monitor->id]['tones'];

        $this->assertSame(['idle', 'idle', 'idle', 'idle'], $tones);
    }

    public function test_uptime_percents_floors_and_returns_null_with_no_data(): void
    {
        $withData = $this->makeMonitor('up');
        $noData = $this->makeMonitor('pending');

        $this->rollup($withData, $this->now->copy()->subMinutes(2), ok: 1, fail: 0);
        $this->rollup($withData, $this->now->copy()->subMinutes(1), ok: 0, fail: 2);

        $percents = MonitorPulse::uptimePercents([$withData->id, $noData->id], $this->now->copy()->subMinutes(5));

        // 1 ok / 3 total = 33.333...% — must floor to 33,33, not round to 33,34.
        $this->assertSame('%33,33', $percents[$withData->id]);
        $this->assertNull($percents[$noData->id]);
    }
}
