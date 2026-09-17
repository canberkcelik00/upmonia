<?php

use App\Models\Client;
use App\Models\Monitor;
use App\Support\MonitorValidationRules;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $name = '';

    public ?int $client_id = null;

    public string $type = 'http';

    public string $url = '';

    public string $method = 'GET';

    public string $expected_status_raw = '';

    public string $headers_raw = '';

    public string $body = '';

    public bool $follow_redirects = true;

    public int $max_redirects = 5;

    public bool $verify_ssl = true;

    public string $keyword = '';

    public string $keyword_mode = 'present';

    public int $ssl_warn_days = 14;

    public string $host = '';

    public ?int $port = null;

    public int $heartbeat_grace_s = 300;

    public int $interval_s = 60;

    public int $timeout_ms = 10000;

    public int $confirm_threshold = 2;

    public int $recover_threshold = 2;

    public bool $enabled = true;

    public function save(): void
    {
        $this->validate(MonitorValidationRules::rules($this->type));

        $monitor = Monitor::create([
            'name' => $this->name,
            'client_id' => $this->client_id ?: null,
            'type' => $this->type,
            'url' => in_array($this->type, ['http', 'keyword', 'ssl']) ? $this->url : null,
            'method' => $this->method,
            'expected_status' => MonitorValidationRules::parseExpectedStatus($this->expected_status_raw),
            'headers' => MonitorValidationRules::parseHeaders($this->headers_raw),
            'body' => $this->body ?: null,
            'follow_redirects' => $this->follow_redirects,
            'max_redirects' => $this->max_redirects,
            'verify_ssl' => $this->verify_ssl,
            'keyword' => $this->type === 'keyword' ? $this->keyword : null,
            'keyword_mode' => $this->keyword_mode,
            'ssl_warn_days' => $this->ssl_warn_days,
            'host' => $this->type === 'tcp_port' ? $this->host : null,
            'port' => $this->type === 'tcp_port' ? $this->port : null,
            'heartbeat_grace_s' => $this->type === 'heartbeat' ? $this->heartbeat_grace_s : null,
            'heartbeat_token' => $this->type === 'heartbeat' ? Str::random(32) : null,
            'interval_s' => $this->interval_s,
            'timeout_ms' => $this->timeout_ms,
            'confirm_threshold' => $this->confirm_threshold,
            'recover_threshold' => $this->recover_threshold,
            'enabled' => $this->enabled,
            'region' => config('upvane.default_region'),
        ]);

        $this->redirect(route('monitors.show', $monitor), navigate: true);
    }

    public function with(): array
    {
        return ['clients' => Client::orderBy('name')->get()];
    }
};
?>

<div>
    <x-ui.page-header :back="route('monitors.index')" :back-label="__('app.monitors_title')" :title="__('app.monitors_new')" />

    <form wire:submit="save">
        @include('components.monitors.form-fields')
    </form>
</div>
