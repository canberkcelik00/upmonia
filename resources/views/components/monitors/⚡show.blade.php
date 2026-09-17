<?php

use App\Models\Client;
use App\Models\Monitor;
use App\Support\Format;
use App\Support\MonitorPulse;
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

        if ($state->status === 'paused') {
            // Resuming with an open incident must re-enter the state machine as 'down' (not
            // 'pending'), otherwise IncidentStateMachine::evaluate() falls into the pending/
            // suspect/up branch, which never produces a 'recovered' transition — the incident
            // would stay open forever even after the monitor starts reporting ok again.
            $state->update([
                'status' => $state->current_incident_id ? 'down' : 'pending',
                'consecutive_ok' => 0,
                'next_check_at' => now(),
            ]);
        } else {
            $state->update(['status' => 'paused']);
        }

        $this->monitor->refresh();
    }

    public function delete(): void
    {
        $this->monitor->delete();
        $this->redirect(route('monitors.index'), navigate: true);
    }

    public function with(): array
    {
        $this->monitor->loadMissing('state.currentIncident');

        return [
            'clients' => Client::orderBy('name')->get(),
            'recentChecks' => $this->monitor->checkResults()->orderByDesc('ts')->limit(20)->get(),
            'chartChecks' => MonitorPulse::hourlyLatency($this->monitor->id),
            'uptime24h' => MonitorPulse::uptimePercents([$this->monitor->id], now()->subDay())[$this->monitor->id] ?? null,
            'uptime30d' => MonitorPulse::uptimePercents([$this->monitor->id], now()->subDays(30))[$this->monitor->id] ?? null,
            'channels' => \App\Models\AlertChannel::whereNull('client_id')->orderBy('name')->get(),
            'attachedChannelIds' => $this->monitor->alertChannels()->pluck('alert_channels.id')->all(),
        ];
    }
};
?>

<div wire:poll.30s>
    <x-ui.page-header :back="route('monitors.index')" :back-label="__('app.monitors_title')" title="{{ $monitor->name }}">
        <x-slot:titleMeta>
            <x-ui.status-pill :status="$monitor->state->status" :flapping="$monitor->state->flapping" />
        </x-slot:titleMeta>
        <x-slot:actions>
            <x-ui.button variant="secondary" size="sm" wire:click="checkNow" wire:loading.attr="disabled" wire:target="checkNow">
                <x-phosphor-arrow-clockwise wire:loading.remove wire:target="checkNow" class="size-4" />
                <x-phosphor-spinner-gap wire:loading wire:target="checkNow" class="size-4 animate-spin" />
                {{ __('app.monitor_check_now') }}
            </x-ui.button>
            <x-ui.button variant="ghost" size="sm" wire:click="togglePause" wire:loading.attr="disabled" wire:target="togglePause">
                @if ($monitor->state->status === 'paused')
                    <x-phosphor-play class="size-4" /> {{ __('app.monitors_resume') }}
                @else
                    <x-phosphor-pause class="size-4" /> {{ __('app.monitors_pause') }}
                @endif
            </x-ui.button>
            @unless ($editing)
                <x-ui.button variant="ghost" size="sm" wire:click="startEditing">
                    <x-phosphor-pencil class="size-4" />
                    {{ __('app.edit') }}
                </x-ui.button>
            @endunless
            <x-ui.dropdown>
                <x-slot:trigger>
                    <button type="button" class="flex size-8 items-center justify-center rounded-control text-ink-2 hover:bg-surface-2" aria-label="{{ __('app.nav_more') }}">
                        <x-phosphor-dots-three-bold class="size-4" />
                    </button>
                </x-slot:trigger>
                <button wire:click="delete" wire:confirm="{{ __('app.monitor_delete_confirm') }}" class="flex w-full items-center gap-2 rounded-[4px] px-2.5 py-1.5 text-left text-sm text-down-text hover:bg-down-soft">
                    <x-phosphor-trash class="size-4" /> {{ __('app.delete') }}
                </button>
            </x-ui.dropdown>
        </x-slot:actions>
    </x-ui.page-header>

    <p class="-mt-4 mb-6 font-mono text-xs text-muted">
        @if (in_array($monitor->type, ['http', 'keyword', 'ssl']))
            {{ $monitor->url }}
        @elseif ($monitor->type === 'tcp_port')
            {{ $monitor->host }}:{{ $monitor->port }}
        @else
            {{ __('app.monitor_type_heartbeat') }}
        @endif
        @if ($monitor->type === 'keyword')
            · {{ __('app.monitor_keyword_rule', ['keyword' => $monitor->keyword, 'mode' => \Illuminate\Support\Str::lower($monitor->keyword_mode === 'present' ? __('app.field_keyword_mode_present') : __('app.field_keyword_mode_absent'))]) }}
        @endif
        · {{ __('app.monitor_every_seconds', ['seconds' => $monitor->interval_s]) }}
    </p>

    @if ($monitor->state->last_error_msg && $monitor->state->status !== 'up' && $monitor->state->status !== 'down')
        <x-ui.alert variant="error" class="mb-6">
            <strong class="font-medium">{{ \App\Checks\ErrorClassifier::label($monitor->state->last_error_class) }}</strong>
            <div class="mt-1 break-words text-xs opacity-80">{{ $monitor->state->last_error_msg }}</div>
        </x-ui.alert>
    @endif

    @if ($monitor->state->status === 'down' && $monitor->state->currentIncident)
        <x-ui.incident-strip :incident="$monitor->state->currentIncident->setRelation('monitor', $monitor)" :show-link="true" class="mb-6" />
    @elseif (in_array($monitor->state->status, ['suspect', 'recovering']))
        @php
            $isRecovering = $monitor->state->status === 'recovering';
            $current = $isRecovering ? $monitor->state->consecutive_ok : $monitor->state->consecutive_fails;
            $total = $isRecovering ? $monitor->recover_threshold : $monitor->confirm_threshold;
            $remaining = max(0, $total - $current);
        @endphp
        <x-ui.threshold
            :current="$current"
            :total="$total"
            :message="__($isRecovering ? 'app.monitor_recover_threshold_message' : 'app.monitor_threshold_message', ['current' => $current, 'total' => $total, 'remaining' => $remaining])"
            class="mb-6"
        />
    @endif

    @if ($editing)
        <form wire:submit="save">
            @include('components.monitors.form-fields', ['editing' => true])
        </form>
    @else
        @php
            $figureTone = match ($monitor->state->status) {
                'down' => 'down',
                'suspect', 'recovering' => 'warn',
                default => null,
            };
        @endphp
        <div class="mb-6 grid grid-cols-2 divide-x divide-line rounded-panel border border-line bg-surface sm:grid-cols-4">
            <x-ui.figure class="px-4 py-3.5" :label="__('app.monitor_last_latency')" :value="$monitor->state->last_latency_ms ?? '—'" unit="ms" :tone="$figureTone" />
            <x-ui.figure class="px-4 py-3.5" :label="__('app.monitor_uptime_24h')" :value="$uptime24h ?? '—'" />
            <x-ui.figure class="px-4 py-3.5" :label="__('app.monitor_uptime_30d')" :value="$uptime30d ?? '—'" />
            <x-ui.figure class="px-4 py-3.5" :label="__('app.monitor_timeout')" :value="Format::ms($monitor->timeout_ms)" />
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-[1.45fr_1fr]">
            <x-ui.card padding="p-0" :title="__('app.monitor_latency_trend')" class="[&>div:first-child]:mx-5 [&>div:first-child]:mt-5">
                <x-ui.latency-chart :checks="$chartChecks" :timeout-ms="$monitor->timeout_ms" :tone="$figureTone ?? 'up'" class="px-1 pb-4" />
            </x-ui.card>

            <x-ui.card padding="p-0" :title="__('app.monitor_recent_checks')" :title-meta="__('app.monitor_checks_merged_hint')" class="[&>div:first-child]:mx-5 [&>div:first-child]:mt-5">
                <div class="divide-y divide-line">
                    @forelse ($recentChecks as $check)
                        <div class="flex items-center gap-3 px-5 py-2.5 text-[12.5px]">
                            <span class="font-mono text-ink-2">{!! $check->updated_at->toDisplayHtml('time') !!}</span>
                            <span class="font-mono text-[11.5px] font-semibold {{ $check->ok ? 'text-up-text' : 'text-down-text' }}">{{ $check->ok ? __('app.monitor_result_ok') : __('app.monitor_result_fail') }}</span>
                            <span class="min-w-0 flex-1 truncate text-ink-2">{{ $check->error_class ? \App\Checks\ErrorClassifier::label($check->error_class) : ($check->status_code ?? '—') }}</span>
                            <span class="shrink-0 font-mono text-muted">{{ Format::count($check->sample_count) }}</span>
                        </div>
                    @empty
                        <x-ui.empty-state icon="clock-counter-clockwise" :title="__('app.monitor_no_checks')" />
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @if (in_array($monitor->type, ['http', 'keyword', 'ssl', 'heartbeat']))
                <x-ui.card :title="__('app.monitor_details')">
                    <dl class="divide-y divide-line text-[13px]">
                        @if (in_array($monitor->type, ['http', 'keyword']))
                            <div class="flex justify-between py-2"><dt class="text-muted">{{ __('app.field_method') }}</dt><dd class="text-ink">{{ $monitor->method }}</dd></div>
                            <div class="flex justify-between py-2"><dt class="text-muted">{{ __('app.field_follow_redirects') }}</dt><dd class="text-ink">{{ $monitor->follow_redirects ? __('app.monitor_on') : __('app.monitor_off') }}</dd></div>
                            <div class="flex justify-between py-2"><dt class="text-muted">{{ __('app.field_verify_ssl') }}</dt><dd class="text-ink">{{ $monitor->verify_ssl ? __('app.monitor_on') : __('app.monitor_off') }}</dd></div>
                        @endif
                        @if ($monitor->type === 'ssl' && $monitor->state->cert_expires_at)
                            <div class="flex justify-between py-2"><dt class="text-muted">{{ __('app.monitor_cert_expiry') }}</dt><dd class="text-ink">{!! $monitor->state->cert_expires_at->toDisplayHtml('date') !!}</dd></div>
                            @if ($monitor->state->cert_issuer)
                                <div class="flex justify-between py-2"><dt class="text-muted">{{ __('app.monitor_cert_issuer') }}</dt><dd class="truncate text-ink">{{ $monitor->state->cert_issuer }}</dd></div>
                            @endif
                        @endif
                        @if ($monitor->type === 'heartbeat')
                            <div class="flex flex-col gap-1 py-2"><dt class="text-muted">{{ __('app.field_heartbeat_url') }}</dt><dd class="break-all font-mono text-xs text-ink">{{ route('heartbeat', $monitor->heartbeat_token) }}</dd></div>
                            <div class="flex justify-between py-2"><dt class="text-muted">{{ __('app.field_heartbeat_grace') }}</dt><dd class="text-ink">{{ $monitor->heartbeat_grace_s }}{{ __('app.unit_seconds_short') }}</dd></div>
                        @endif
                    </dl>
                </x-ui.card>
            @endif

            <x-ui.card :title="__('app.monitor_channels')">
                @if ($channels->isEmpty())
                    <p class="text-sm text-muted">
                        {{ __('app.monitor_channels_empty') }} <a href="{{ route('settings.channels') }}" wire:navigate class="font-medium text-ink hover:underline">{{ __('app.monitor_channels_empty_cta') }}</a>.
                    </p>
                @else
                    <div class="space-y-2">
                        @foreach ($channels as $channel)
                            <x-ui.checkbox wire:click="toggleChannel({{ $channel->id }})" :checked="in_array($channel->id, $attachedChannelIds)" :label="$channel->name">
                                @unless ($channel->isVerified())
                                    <x-ui.badge color="amber" class="ml-1.5">{{ __('app.channel_unverified') }}</x-ui.badge>
                                @endunless
                            </x-ui.checkbox>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>
    @endif
</div>
