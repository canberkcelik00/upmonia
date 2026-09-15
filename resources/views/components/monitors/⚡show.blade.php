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
            'channels' => \App\Models\AlertChannel::whereNull('client_id')->orderBy('name')->get(),
            'attachedChannelIds' => $this->monitor->alertChannels()->pluck('alert_channels.id')->all(),
        ];
    }
};
?>

<div class="max-w-3xl">
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h1 class="text-lg font-semibold">{{ $monitor->name }}</h1>
            <x-ui.status-pill :status="$monitor->state->status" />
            @if ($monitor->state->flapping)
                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Flapping</span>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="checkNow" wire:loading.attr="disabled" class="rounded-md border border-neutral-300 px-3 py-1.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50">
                Şimdi kontrol et
            </button>
            <button wire:click="togglePause" class="rounded-md border border-neutral-300 px-3 py-1.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50">
                {{ $monitor->state->status === 'paused' ? 'Devam ettir' : 'Duraklat' }}
            </button>
            @unless ($editing)
                <button wire:click="startEditing" class="rounded-md border border-neutral-300 px-3 py-1.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50">
                    Düzenle
                </button>
            @endunless
            <button wire:click="delete" wire:confirm="Bu monitörü silmek istediğine emin misin? Bu işlem geri alınamaz." class="rounded-md border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50">
                Sil
            </button>
        </div>
    </div>

    @if ($monitor->state->last_error_msg && $monitor->state->status !== 'up')
        <div class="mb-6 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
            <strong>{{ \App\Checks\ErrorClassifier::label($monitor->state->last_error_class) }}</strong>
            <div class="mt-1 text-xs text-red-600">{{ $monitor->state->last_error_msg }}</div>
        </div>
    @endif

    @if ($editing)
        <form wire:submit="save" class="mb-6 rounded-lg border border-neutral-200 bg-white p-6">
            @include('components.monitors.form-fields')

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800">Kaydet</button>
                <button type="button" wire:click="cancelEditing" class="text-sm text-neutral-600">Vazgeç</button>
            </div>
        </form>
    @else
        <div class="mb-6 grid grid-cols-2 gap-4 rounded-lg border border-neutral-200 bg-white p-6 text-sm sm:grid-cols-3">
            <div><div class="text-neutral-500">Tür</div><div class="font-medium">{{ $monitor->type }}</div></div>
            @if ($monitor->url)
                <div class="col-span-2"><div class="text-neutral-500">URL</div><div class="font-medium break-all">{{ $monitor->url }}</div></div>
            @endif
            @if ($monitor->host)
                <div><div class="text-neutral-500">Sunucu</div><div class="font-medium">{{ $monitor->host }}:{{ $monitor->port }}</div></div>
            @endif
            <div><div class="text-neutral-500">Kontrol aralığı</div><div class="font-medium">{{ $monitor->interval_s }}sn</div></div>
            <div><div class="text-neutral-500">Son gecikme</div><div class="font-medium">{{ $monitor->state->last_latency_ms ? $monitor->state->last_latency_ms.' ms' : '—' }}</div></div>
            @if ($monitor->type === 'heartbeat')
                <div class="col-span-2"><div class="text-neutral-500">Heartbeat URL</div><div class="font-mono text-xs break-all">{{ route('heartbeat', $monitor->heartbeat_token) }}</div></div>
            @endif
            @if ($monitor->state->cert_expires_at)
                <div><div class="text-neutral-500">Sertifika bitişi</div><div class="font-medium">{{ $monitor->state->cert_expires_at->format('Y-m-d') }}</div></div>
            @endif
        </div>
    @endif

    <h2 class="mb-3 text-sm font-semibold text-neutral-700">Bildirim kanalları</h2>
    <div class="mb-6 rounded-lg border border-neutral-200 bg-white p-4">
        @if ($channels->isEmpty())
            <p class="text-sm text-neutral-500">
                Henüz bildirim kanalı yok. <a href="{{ route('settings.channels') }}" class="text-neutral-900 underline">Ayarlar'dan ekle</a>.
            </p>
        @else
            <div class="space-y-2">
                @foreach ($channels as $channel)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:click="toggleChannel({{ $channel->id }})" @checked(in_array($channel->id, $attachedChannelIds)) class="rounded border-neutral-300">
                        {{ $channel->name }}
                        @unless ($channel->isVerified())
                            <span class="text-xs text-amber-600">(doğrulanmadı — bildirim gönderilmez)</span>
                        @endunless
                    </label>
                @endforeach
            </div>
        @endif
    </div>

    <h2 class="mb-3 text-sm font-semibold text-neutral-700">Son kontroller</h2>
    <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-left text-xs uppercase text-neutral-500">
                <tr>
                    <th class="px-4 py-2 font-medium">Zaman</th>
                    <th class="px-4 py-2 font-medium">Sonuç</th>
                    <th class="px-4 py-2 font-medium">Gecikme</th>
                    <th class="px-4 py-2 font-medium">Detay</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($recentChecks as $check)
                    <tr wire:key="check-{{ $check->id }}">
                        <td class="px-4 py-2 text-neutral-600">{{ $check->ts->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $check->ok ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                {{ $check->ok ? 'OK' : 'FAIL' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-neutral-600">{{ $check->latency_ms ? $check->latency_ms.' ms' : '—' }}</td>
                        <td class="px-4 py-2 text-neutral-600">{{ $check->error_class ? \App\Checks\ErrorClassifier::label($check->error_class) : ($check->status_code ?? '—') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-neutral-500">Henüz kontrol yapılmadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
