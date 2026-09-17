<?php

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\Organization;
use App\Models\StatusPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StatusPageTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org-'.uniqid()]);
    }

    private function monitor(string $status): Monitor
    {
        $m = Monitor::create([
            'organization_id' => $this->org->id,
            'name' => 'Public Monitor '.uniqid(),
            'type' => 'http',
            'url' => 'https://example.com',
        ]);
        $m->state()->update(['status' => $status]);

        return $m;
    }

    private function publish(Monitor ...$monitors): StatusPage
    {
        $page = StatusPage::create([
            'organization_id' => $this->org->id,
            'slug' => 'public-'.uniqid(),
            'title' => 'Test Status Page',
            'enabled' => true,
            'show_history' => true,
        ]);

        foreach ($monitors as $i => $m) {
            $page->monitors()->attach($m->id, ['sort_order' => $i]);
        }

        return $page;
    }

    public function test_all_up_shows_operational_and_the_up_favicon(): void
    {
        $m = $this->monitor('up');
        $page = $this->publish($m);

        $response = $this->get(route('status-page.show', $page->slug));

        $response->assertOk();
        $response->assertSee('Tüm sistemler çalışıyor');
        $response->assertSee('favicon-up.svg', false);
    }

    public function test_a_down_monitor_shows_degraded_and_the_down_favicon(): void
    {
        $up = $this->monitor('up');
        $down = $this->monitor('down');
        $page = $this->publish($up, $down);

        $response = $this->get(route('status-page.show', $page->slug));

        $response->assertSee('Bazı sistemlerde sorun var');
        $response->assertSee('favicon-down.svg', false);
    }

    public function test_daily_uptime_percent_is_computed_from_hourly_rollups(): void
    {
        $m = $this->monitor('up');
        $page = $this->publish($m);

        // 3 ok, 1 fail today = 75% for the one day with data.
        DB::table('check_rollups_1h')->insert([
            'monitor_id' => $m->id, 'region' => 'local', 'bucket' => now()->startOfDay()->addHours(9),
            'ok_n' => 3, 'fail_n' => 1, 'p50' => 100, 'p95' => 120, 'max_ms' => 150,
        ]);

        $response = $this->get(route('status-page.show', $page->slug));

        $response->assertSee('%75,00');
    }
}
