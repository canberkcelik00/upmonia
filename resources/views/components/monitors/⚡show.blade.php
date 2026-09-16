<?php

use App\Models\Client;
use App\Models\Monitor;
use App\Support\MonitorValidationRules;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Monitor $monitor;

    public bool $editing = false;

    // Form fields — mirrors monitors.form, populated from $monitor in mount().
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

    public function mount(Monitor $monitor): void
    {
        $this->monitor = $monitor;
        $this->fillFromMonitor();
    }

    private function fillFromMonitor(): void
    {
        $m = $this->monitor;
        $this->name = $m->name;
        $this->client_id = $m->client_id;
        $this->type = $m->type;
        $this->url = $m->url ?? '';
        $this->method = $m->method;
        $this->expected_status_raw = MonitorValidationRules::expectedStatusToRaw($m->expected_status);
        $this->headers_raw = MonitorValidationRules::headersToRaw($m->headers);
        $this->body = $m->body ?? '';
        $this->follow_redirects = $m->follow_redirects;
        $this->max_redirects = $m->max_redirects;
        $this->verify_ssl = $m->verify_ssl;
        $this->keyword = $m->keyword ?? '';
        $this->keyword_mode = $m->keyword_mode;
        $this->ssl_warn_days = $m->ssl_warn_days;
        $this->host = $m->host ?? '';
        $this->port = $m->port;
        $this->heartbeat_grace_s = $m->heartbeat_grace_s ?? 300;
        $this->interval_s = $m->interval_s;
        $this->timeout_ms = $m->timeout_ms;
        $this->confirm_threshold = $m->confirm_threshold;
        $this->recover_threshold = $m->recover_threshold;
        $this->enabled = $m->enabled;
    }

    public function startEditing(): void
    {
        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->fillFromMonitor();
        $this->editing = false;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->validate(MonitorValidationRules::rules($this->type));

        $this->monitor->update([
            'name' => $this->name,
            'client_id' => $this->client_id ?: null,
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
            'interval_s' => $this->interval_s,
            'timeout_ms' => $this->timeout_ms,
            'confirm_threshold' => $this->confirm_threshold,
            'recover_threshold' => $this->recover_threshold,
            'enabled' => $this->enabled,
        ]);

        $this->editing = false;
        $this->monitor->refresh();
    }

    public function checkNow(): void
    {
        $this->monitor->state->update(['check_requested_at' => now()]);
    }

    public function toggleChannel(int $channelId): void
    {
        if ($this->monitor->alertChannels()->where('alert_channels.id', $channelId)->exists()) {
            $this->monitor->alertChannels()->detach($channelId);
        } else {
            $this->monitor->alertChannels()->attach($channelId, ['delay_s' => 0]);
        }
    }

    public function togglePause(): void
    {
        $state = $this->monitor->state;
        $state->update($state->status === 'paused'
            ? ['status' => 'pending', 'next_check_at' => now()]
            : ['status' => 'paused']);
        $this->monitor->refresh();
    }

    public function delete(): void
    {
        $this->monitor->delete();
        $this->redirect(route('monitors.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'clients' => Client::orderBy('name')->get(),
            'recentChecks' => $this->monitor->checkResults()->orderByDesc('ts')->limit(20)->get(),
            // Trend charts read the 1m rollup table, not raw check_results: post segment-log
            // compaction (CheckResultApplier), a stable monitor's raw checks collapse into one
            // long-lived row, so the rollup table is what still has one data point per minute.
            'chartChecks' => $this->monitor->rollups1m()
                ->orderByDesc('bucket')
                ->limit(90)
                ->get()
                ->map(fn ($r) => (object) [
                    'ts' => $r->bucket,
                    'ok' => $r->fail_n === 0,
                    'latency_ms' => $r->max_ms,
                ]),
            'channels' => \App\Models\AlertChannel::whereNull('client_id')->orderBy('name')->get(),
            'attachedChannelIds' => $this->monitor->alertChannels()->pluck('alert_channels.id')->all(),
        ];
    }
};
?>

<div class="max-w-4xl">
    <x-ui.page-header :back="route('monitors.index')" :back-label="__('app.monitors_title')" title="{{ $monitor->name }}">
        <x-slot:titleMeta>
            <x-ui.status-pill :status="$monitor->state->status" />
            @if ($monitor->state->flapping)
                <x-ui.badge color="amber">{{ __('app.monitor_flapping') }}</x-ui.badge>
            @endif
        </x-slot:titleMeta>
        <x-slot:actions>
            <x-ui.button variant="secondary" size="sm" wire:click="checkNow" wire:loading.attr="disabled" wire:target="checkNow">
                <x-phosphor-lightning wire:loading.remove wire:target="checkNow" class="size-4" />
                <x-phosphor-spinner-gap wire:loading wire:target="checkNow" class="size-4 animate-spin" />
                {{ __('app.monitor_check_now') }}
            </x-ui.button>
            <x-ui.button variant="secondary" size="sm" wire:click="togglePause" wire:loading.attr="disabled" wire:target="togglePause">
                @if ($monitor->state->status === 'paused')
                    <x-phosphor-play class="size-4" /> {{ __('app.monitors_resume') }}
                @else
                    <x-phosphor-pause class="size-4" /> {{ __('app.monitors_pause') }}
                @endif
            </x-ui.button>
            @unless ($editing)
                <x-ui.button variant="secondary" size="sm" wire:click="startEditing">
                    <x-phosphor-pencil class="size-4" />
                    {{ __('app.edit') }}
                </x-ui.button>
            @endunless
            <x-ui.button variant="danger" size="sm" wire:click="delete" wire:confirm="{{ __('app.monitor_delete_confirm') }}">
                <x-phosphor-trash class="size-4" />
                {{ __('app.delete') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($monitor->state->last_error_msg && $monitor->state->status !== 'up')
        <x-ui.alert variant="error" class="mb-6">
            <strong class="font-medium">{{ \App\Checks\ErrorClassifier::label($monitor->state->last_error_class) }}</strong>
            <div class="mt-1 break-words text-xs opacity-80">{{ $monitor->state->last_error_msg }}</div>
        </x-ui.alert>
    @endif

    @if ($editing)
        <form wire:submit="save" class="mb-6">
            <x-ui.card>
                @include('components.monitors.form-fields')

                <div class="mt-6 flex items-center gap-3">
                    <x-ui.button type="submit" variant="primary">{{ __('app.save') }}</x-ui.button>
                    <x-ui.button type="button" variant="ghost" wire:click="cancelEditing">{{ __('app.cancel') }}</x-ui.button>
                </div>
            </x-ui.card>
        </form>
    @else
        <x-ui.card class="mb-6">
            <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                <div><div class="text-neutral-500 dark:text-neutral-400">{{ __('app.monitors_col_type') }}</div><div class="font-medium">{{ __('app.monitor_type_'.$monitor->type) }}</div></div>
                @if ($monitor->url)
                    <div class="col-span-2"><div class="text-neutral-500 dark:text-neutral-400">{{ __('app.field_url') }}</div><div class="font-medium break-all">{{ $monitor->url }}</div></div>
                @endif
                @if ($monitor->host)
                    <div><div class="text-neutral-500 dark:text-neutral-400">{{ __('app.field_host') }}</div><div class="font-medium">{{ $monitor->host }}:{{ $monitor->port }}</div></div>
                @endif
                <div><div class="text-neutral-500 dark:text-neutral-400">{{ __('app.field_interval') }}</div><div class="font-medium">{{ $monitor->interval_s }}{{ __('app.unit_seconds_short') }}</div></div>
                <div><div class="text-neutral-500 dark:text-neutral-400">{{ __('app.monitor_last_latency') }}</div><div class="font-mono font-medium">{{ $monitor->state->last_latency_ms ? $monitor->state->last_latency_ms.' ms' : '—' }}</div></div>
                @if ($monitor->type === 'heartbeat')
                    <div class="col-span-2"><div class="text-neutral-500 dark:text-neutral-400">{{ __('app.field_heartbeat_url') }}</div><div class="break-all font-mono text-xs">{{ route('heartbeat', $monitor->heartbeat_token) }}</div></div>
                @endif
                @if ($monitor->state->cert_expires_at)
                    <div><div class="text-neutral-500 dark:text-neutral-400">{{ __('app.monitor_cert_expiry') }}</div><div class="font-medium">{{ $monitor->state->cert_expires_at->toDisplayDate() }}</div></div>
                @endif
            </div>
        </x-ui.card>
    @endif

    @if ($chartChecks->isNotEmpty())
        <x-ui.section-heading>{{ __('app.monitor_performance') }}</x-ui.section-heading>
        <x-ui.card class="mb-6">
            <div class="mb-4 flex items-center justify-between">
                <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">{{ __('app.monitor_latency_trend') }}</span>
            </div>
            <x-ui.latency-chart :checks="$chartChecks" />
            <div class="mt-5 flex items-center justify-between">
                <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">{{ __('app.monitor_uptime_history') }}</span>
            </div>
            <x-ui.uptime-bar :checks="$chartChecks" class="mt-2" />
        </x-ui.card>
    @endif

    <x-ui.section-heading>{{ __('app.monitor_channels') }}</x-ui.section-heading>
    <x-ui.card class="mb-6">
        @if ($channels->isEmpty())
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                {{ __('app.monitor_channels_empty') }} <a href="{{ route('settings.channels') }}" wire:navigate class="font-medium text-brand-700 hover:underline dark:text-brand-400">{{ __('app.monitor_channels_empty_cta') }}</a>.
            </p>
        @else
            <div class="space-y-2">
                @foreach ($channels as $channel)
                    <x-ui.checkbox wire:click="toggleChannel({{ $channel->id }})" :checked="in_array($channel->id, $attachedChannelIds)" :label="$channel->name">
                        @unless ($channel->isVerified())
                            <x-ui.badge color="amber">{{ __('app.channel_unverified') }}</x-ui.badge>
                        @endunless
                    </x-ui.checkbox>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <x-ui.section-heading>{{ __('app.monitor_recent_checks') }}</x-ui.section-heading>
    <x-ui.table>
        <x-slot:head>
            <th>{{ __('app.monitor_col_time') }}</th>
            <th>{{ __('app.monitor_col_result') }}</th>
            <th>{{ __('app.monitors_col_latency') }}</th>
            <th>{{ __('app.monitor_col_detail') }}</th>
            <th>{{ __('app.monitor_col_seen') }}</th>
            <th>{{ __('app.monitor_col_last_confirmed') }}</th>
        </x-slot:head>

        @forelse ($recentChecks as $check)
            <tr wire:key="check-{{ $check->id }}">
                <td class="font-mono text-xs text-neutral-600 dark:text-neutral-400">{{ $check->ts->toDisplay() }}</td>
                <td>
                    <x-ui.badge :color="$check->ok ? 'emerald' : 'red'">{{ $check->ok ? __('app.monitor_result_ok') : __('app.monitor_result_fail') }}</x-ui.badge>
                </td>
                <td class="font-mono text-xs text-neutral-600 dark:text-neutral-400">{{ $check->latency_ms ? $check->latency_ms.' ms' : '—' }}</td>
                <td class="text-neutral-600 dark:text-neutral-400">{{ $check->error_class ? \App\Checks\ErrorClassifier::label($check->error_class) : ($check->status_code ?? '—') }}</td>
                <td class="font-mono text-xs text-neutral-600 dark:text-neutral-400">{{ __('app.monitor_seen_nx', ['count' => $check->sample_count]) }}</td>
                <td class="font-mono text-xs text-neutral-600 dark:text-neutral-400">{{ $check->updated_at->toDisplay() }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6">
                    <x-ui.empty-state icon="clock-counter-clockwise" :title="__('app.monitor_no_checks')" />
                </td>
            </tr>
        @endforelse
    </x-ui.table>
</div>
