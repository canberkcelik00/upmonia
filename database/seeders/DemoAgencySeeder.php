<?php

namespace Database\Seeders;

use App\Models\AlertChannel;
use App\Models\Client;
use App\Models\Membership;
use App\Models\Monitor;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds one demo tenant exercising every failure path the check engine understands, so
 * `php artisan db:seed --class=DemoAgencySeeder` followed by `php artisan probe:run` and
 * `php artisan maintenance:run` proves the whole probe → state machine → incident pipeline
 * end to end. Mirrors the source Node app's packages/db/src/cli/seed.ts.
 */
class DemoAgencySeeder extends Seeder
{
    public function run(): void
    {
        Organization::where('slug', 'demo-agency')->delete();
        User::where('email', 'owner@demo-agency.example')->delete();

        $org = Organization::create(['name' => 'Demo Agency', 'slug' => 'demo-agency', 'plan' => 'agency']);

        $owner = User::create([
            'name' => 'Demo Owner', 'email' => 'owner@demo-agency.example',
            'password' => Str::random(32), 'locale' => 'tr', 'email_verified_at' => now(),
        ]);
        Membership::create(['user_id' => $owner->id, 'organization_id' => $org->id, 'role' => 'owner']);

        $client = Client::create([
            'organization_id' => $org->id,
            'name' => 'Acme Ltd',
            'brand_color' => '#0f766e',
            'contact_emails' => ['ops@acme.example'],
        ]);

        $defaults = ['organization_id' => $org->id, 'interval_s' => 60, 'timeout_ms' => 10000];

        // 1. Plain HTTP — expected UP.
        Monitor::create($defaults + [
            'name' => 'Example (HTTP)', 'type' => 'http', 'url' => 'https://example.com',
        ]);

        // 2. Keyword present — expected UP.
        Monitor::create($defaults + [
            'name' => 'Example (keyword present)', 'type' => 'keyword', 'url' => 'https://example.com',
            'keyword' => 'Example Domain', 'keyword_mode' => 'present',
        ]);

        // 3. Encrypted custom header — expected UP, exercises the `encrypted` Eloquent cast.
        Monitor::create($defaults + [
            'name' => 'Example (auth header)', 'type' => 'http', 'url' => 'https://example.com',
            'headers' => ['Authorization' => 'Bearer demo-secret-token'],
        ]);

        // 4. Non-existent domain — expected DOWN, dns_nxdomain. Also the vehicle for exercising
        // the notification pipeline end-to-end: a verified email channel is attached below.
        $downMonitor = Monitor::create($defaults + [
            'name' => 'Nonexistent domain', 'type' => 'http', 'url' => 'https://this-domain-does-not-exist-upvane-demo.invalid',
        ]);

        // 5. Wrong expected_status against a 200 — expected DOWN, http_unexpected_status.
        Monitor::create($defaults + [
            'name' => 'Wrong expected status', 'type' => 'http', 'url' => 'https://example.com',
            'expected_status' => [418],
        ]);

        // 6. Keyword that isn't there — expected DOWN, keyword_missing.
        Monitor::create($defaults + [
            'name' => 'Missing keyword', 'type' => 'keyword', 'url' => 'https://example.com',
            'keyword' => 'this text will never appear on example.com', 'keyword_mode' => 'present',
        ]);

        // 7. Expired cert — expected DOWN, tls_expired.
        Monitor::create($defaults + [
            'name' => 'Expired TLS cert', 'type' => 'http', 'url' => 'https://expired.badssl.com',
        ]);

        // 8. Self-signed cert — expected DOWN, tls_self_signed.
        Monitor::create($defaults + [
            'name' => 'Self-signed TLS cert', 'type' => 'http', 'url' => 'https://self-signed.badssl.com',
        ]);

        // 9. Dedicated SSL check — expected UP unless the cert is genuinely expiring soon.
        Monitor::create($defaults + [
            'name' => 'Example (SSL cert check)', 'type' => 'ssl', 'url' => 'https://example.com:443',
            'ssl_warn_days' => 14,
        ]);

        // 10. Raw TCP connect — expected UP.
        Monitor::create($defaults + [
            'name' => 'Google DNS (TCP 53)', 'type' => 'tcp_port', 'host' => '8.8.8.8', 'port' => 53,
        ]);

        // 11. Heartbeat, never pinged — expected DOWN, heartbeat_missed.
        Monitor::create($defaults + [
            'name' => 'Never-pinged heartbeat', 'type' => 'heartbeat',
            'heartbeat_token' => Str::random(32), 'heartbeat_grace_s' => 60,
        ]);

        $channel = AlertChannel::create([
            'organization_id' => $org->id, 'type' => 'email', 'name' => 'ops@acme.example',
            'config' => ['email' => 'ops@acme.example'], 'verified_at' => now(), 'enabled' => true,
        ]);
        $downMonitor->alertChannels()->attach($channel->id, ['delay_s' => 0]);

        $this->command?->info('Seeded demo-agency with '.Monitor::count().' monitors (client: '.$client->name.').');
    }
}
