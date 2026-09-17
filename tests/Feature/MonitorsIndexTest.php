<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreatesOrgUser;
use Tests\TestCase;

class MonitorsIndexTest extends TestCase
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
            'name' => 'Monitor '.$status.' '.uniqid(),
            'type' => 'http',
            'url' => 'https://example.com',
        ]);
        $m->state()->update(['status' => $status]);

        return $m;
    }

    /** Regression test for the bug where $statusCounts was computed but never
     *  returned from with(), so every count in the summary line silently read as 0. */
    public function test_status_sentence_and_counts_reflect_real_data(): void
    {
        $this->monitor('down');
        $this->monitor('suspect');
        $this->monitor('up');
        $this->monitor('up');
        $this->monitor('pending');

        $html = $this->get(route('monitors.index'))->getContent();

        $this->assertStringContainsString('2 monitörde sorun var', $html);
        $this->assertMatchesRegularExpression('/5\s*Toplam/u', $html);
        $this->assertMatchesRegularExpression('/2\s*Çalışıyor/u', $html);
        $this->assertMatchesRegularExpression('/2\s*Sorunlu/u', $html);
    }

    public function test_all_up_statement_when_nothing_is_wrong(): void
    {
        $this->monitor('up');

        $html = $this->get(route('monitors.index'))->getContent();

        $this->assertStringContainsString('Her şey çalışıyor', $html);
    }

    /** Regression test: the collapsed view's "hepsi çalışıyor" footer must only ever
     *  hide monitors that are actually up — pending/paused monitors always stay visible. */
    public function test_collapsed_view_only_hides_up_monitors_not_pending_or_paused(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->monitor('up');
        }
        $pending = $this->monitor('pending');
        $paused = $this->monitor('paused');

        $html = $this->get(route('monitors.index'))->getContent();

        $this->assertStringContainsString($pending->name, $html);
        $this->assertStringContainsString($paused->name, $html);
        $this->assertStringContainsString('monitör daha, hepsi çalışıyor', $html);
    }

    public function test_issues_filter_only_shows_problem_statuses(): void
    {
        $down = $this->monitor('down');
        $up = $this->monitor('up');

        Livewire::test('monitors.index')
            ->set('status', 'issues')
            ->assertSee($down->name)
            ->assertDontSee($up->name);
    }

    public function test_acknowledge_sets_acknowledged_fields_on_the_incident(): void
    {
        $monitor = $this->monitor('down');
        $incident = Incident::create([
            'organization_id' => $this->org->id,
            'monitor_id' => $monitor->id,
            'state' => 'open',
            'started_at' => now(),
            'cause_class' => 'timeout',
        ]);

        Livewire::test('monitors.index')->call('acknowledge', $incident->id);

        $incident->refresh();
        $this->assertNotNull($incident->acknowledged_at);
        $this->assertSame($this->user->id, $incident->acknowledged_by);
    }
}
