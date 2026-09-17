<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Monitor;
use App\Support\OrgHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesOrgUser;
use Tests\TestCase;

class OrgHealthTest extends TestCase
{
    use RefreshDatabase, CreatesOrgUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpOrgUser();
    }

    private function monitor(string $status): Monitor
    {
        $m = Monitor::create([
            'organization_id' => $this->org->id,
            'name' => 'Monitor '.uniqid(),
            'type' => 'http',
            'url' => 'https://example.com',
        ]);
        $m->state()->update(['status' => $status]);

        return $m;
    }

    public function test_down_monitor_makes_the_org_status_down(): void
    {
        $this->monitor('up');
        $this->monitor('down');

        $this->assertSame('down', OrgHealth::current()['status']);
    }

    public function test_suspect_or_recovering_without_a_down_makes_it_warn(): void
    {
        $this->monitor('up');
        $this->monitor('suspect');

        $this->assertSame('warn', OrgHealth::current()['status']);
    }

    public function test_pending_and_paused_alone_read_as_up(): void
    {
        $this->monitor('pending');
        $this->monitor('paused');

        $this->assertSame('up', OrgHealth::current()['status']);
    }

    public function test_open_incident_count_is_correct(): void
    {
        $monitor = $this->monitor('down');
        Incident::create([
            'organization_id' => $this->org->id,
            'monitor_id' => $monitor->id,
            'state' => 'open',
            'started_at' => now(),
        ]);
        Incident::create([
            'organization_id' => $this->org->id,
            'monitor_id' => $monitor->id,
            'state' => 'resolved',
            'started_at' => now()->subDay(),
            'resolved_at' => now(),
        ]);

        $this->assertSame(1, OrgHealth::current()['openIncidents']);
    }
}
