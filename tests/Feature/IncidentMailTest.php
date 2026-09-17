<?php

namespace Tests\Feature;

use App\Mail\IncidentResolvedEmail;
use App\Mail\IncidentTriggeredEmail;
use App\Models\Client;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentMailTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org-'.uniqid()]);
    }

    public function test_triggered_subject_and_body_carry_the_status_first(): void
    {
        $client = Client::create(['organization_id' => $this->org->id, 'name' => 'Acme Tekstil']);
        $monitor = Monitor::create([
            'organization_id' => $this->org->id,
            'client_id' => $client->id,
            'name' => 'Ödeme sayfası',
            'type' => 'http',
            'url' => 'https://acme.example/odeme',
        ]);
        $incident = Incident::create([
            'organization_id' => $this->org->id,
            'monitor_id' => $monitor->id,
            'state' => 'open',
            'started_at' => now(),
            'cause_class' => 'timeout',
        ]);

        $mailable = new IncidentTriggeredEmail($incident->fresh(), 'tr');

        $this->assertSame('Kesinti — Ödeme sayfası', $mailable->envelope()->subject);

        $rendered = $mailable->render();
        $this->assertStringContainsString('Acme Tekstil', $rendered);
        $this->assertStringContainsString('acme.example/odeme', $rendered);
        $this->assertStringContainsString('upvane-mark-down@2x.png', $rendered);
    }

    public function test_resolved_subject_and_body(): void
    {
        $monitor = Monitor::create([
            'organization_id' => $this->org->id,
            'name' => 'Ödeme sayfası',
            'type' => 'http',
            'url' => 'https://acme.example/odeme',
        ]);
        $incident = Incident::create([
            'organization_id' => $this->org->id,
            'monitor_id' => $monitor->id,
            'state' => 'resolved',
            'started_at' => now()->subMinutes(10),
            'resolved_at' => now(),
            'duration_s' => 600,
            'cause_class' => 'timeout',
        ]);

        $mailable = new IncidentResolvedEmail($incident->fresh(), 'tr');

        $this->assertSame('Düzeldi — Ödeme sayfası', $mailable->envelope()->subject);

        $rendered = $mailable->render();
        $this->assertStringContainsString('10 dk', $rendered);
        $this->assertStringContainsString('upvane-mark-up@2x.png', $rendered);
    }
}
