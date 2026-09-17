<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreatesOrgUser;
use Tests\TestCase;

class IncidentsPagesTest extends TestCase
{
    use RefreshDatabase, CreatesOrgUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpOrgUser();
    }

    private function monitorWithIncident(string $state): Incident
    {
        $monitor = Monitor::create([
            'organization_id' => $this->org->id,
            'name' => 'Monitor '.uniqid(),
            'type' => 'http',
            'url' => 'https://example.com',
        ]);

        return Incident::create([
            'organization_id' => $this->org->id,
            'monitor_id' => $monitor->id,
            'state' => $state,
            'started_at' => now()->subMinutes(10),
            'resolved_at' => $state === 'resolved' ? now() : null,
            'duration_s' => $state === 'resolved' ? 600 : null,
            'cause_class' => 'timeout',
        ]);
    }

    public function test_open_and_resolved_counts_are_correct(): void
    {
        $this->monitorWithIncident('open');
        $this->monitorWithIncident('resolved');
        $this->monitorWithIncident('resolved');

        $html = $this->get(route('incidents.index'))->getContent();

        $this->assertStringContainsString('1 açık olay', $html);
        // Livewire's @if conditional-comment markers sit between the label and the count
        // span, so match loosely across them rather than requiring plain adjacency.
        $this->assertMatchesRegularExpression('/Açık[\s\S]{0,150}>1</u', $html);
        $this->assertMatchesRegularExpression('/Çözüldü[\s\S]{0,150}>2</u', $html);
        $this->assertMatchesRegularExpression('/Tümü[\s\S]{0,150}>3</u', $html);
    }

    public function test_no_open_incidents_shows_the_empty_statement(): void
    {
        $this->monitorWithIncident('resolved');

        $html = $this->get(route('incidents.index'))->getContent();

        $this->assertStringContainsString('Açık olay yok', $html);
    }

    public function test_acknowledge_button_on_detail_page_sets_the_fields(): void
    {
        $incident = $this->monitorWithIncident('open');

        Livewire::test('incidents.show', ['incident' => $incident])
            ->call('acknowledge');

        $incident->refresh();
        $this->assertNotNull($incident->acknowledged_at);
        $this->assertSame($this->user->id, $incident->acknowledged_by);
    }
}
