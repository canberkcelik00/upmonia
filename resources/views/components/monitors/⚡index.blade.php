<?php

use App\Models\Monitor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function pause(int $monitorId): void
    {
        $monitor = Monitor::findOrFail($monitorId);
        $monitor->state->update(['status' => 'paused']);
    }

    public function resume(int $monitorId): void
    {
        $monitor = Monitor::findOrFail($monitorId);
        $monitor->state->update(['status' => 'pending', 'next_check_at' => now()]);
    }

    public function with(): array
    {
        $monitors = Monitor::query()
            ->with('state', 'client')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->status, fn ($q) => $q->whereHas('state', fn ($s) => $s->where('status', $this->status)))
            ->orderBy('name')
            ->paginate(20);

        // Unfiltered counts for the summary stat tiles — reflects the whole org regardless
        // of the active search/status filter, matching the usual dashboard-summary convention.
        $statusCounts = Monitor::query()
            ->join('monitor_states', 'monitor_states.monitor_id', '=', 'monitors.id')
            ->selectRaw('monitor_states.status, count(*) as count')
            ->groupBy('monitor_states.status')
            ->pluck('count', 'status');

        return ['monitors' => $monitors, 'statusCounts' => $statusCounts];
    }
};
?>

<div>
    <x-ui.page-header :title="__('app.monitors_title')" :description="__('app.monitors_description')">
        <x-slot:actions>
            <x-ui.button variant="primary" href="{{ route('monitors.create') }}" wire:navigate>
                <x-phosphor-plus class="size-4" />
                {{ __('app.monitors_new') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @php
        $statUp = $statusCounts['up'] ?? 0;
        $statDown = ($statusCounts['down'] ?? 0) + ($statusCounts['suspect'] ?? 0) + ($statusCounts['recovering'] ?? 0);
        $statPaused = $statusCounts['paused'] ?? 0;
        $statTotal = $statusCounts->sum();
    @endphp
    @if ($statTotal > 0)
        @php
            $tiles = [
                ['label' => __('app.stat_total'), 'value' => $statTotal, 'icon' => 'pulse',
                 'tone' => 'text-neutral-900 dark:text-neutral-50', 'chip' => 'bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400'],
                ['label' => __('app.stat_up'), 'value' => $statUp, 'icon' => 'check-circle',
                 'tone' => 'text-emerald-600 dark:text-emerald-400', 'chip' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400'],
                ['label' => __('app.stat_issues'), 'value' => $statDown, 'icon' => 'warning',
                 'tone' => $statDown > 0 ? 'text-red-600 dark:text-red-400' : 'text-neutral-400 dark:text-neutral-600',
                 'chip' => $statDown > 0 ? 'bg-red-50 text-red-600 dark:bg-red-950/60 dark:text-red-400' : 'bg-neutral-100 text-neutral-400 dark:bg-neutral-800 dark:text-neutral-500'],
                ['label' => __('app.stat_paused'), 'value' => $statPaused, 'icon' => 'pause',
                 'tone' => 'text-neutral-900 dark:text-neutral-50', 'chip' => 'bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400'],
            ];
        @endphp
        <div class="mb-8 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($tiles as $tile)
                <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-[var(--shadow-card)] dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="flex items-center gap-2">
                        <span class="flex size-7 items-center justify-center rounded-lg {{ $tile['chip'] }}">
                            <x-dynamic-component :component="'phosphor-'.$tile['icon']" class="size-4" />
                        </span>
                        <span class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ $tile['label'] }}</span>
                    </div>
                    <div class="mt-3 font-mono text-3xl font-semibold tracking-tight {{ $tile['tone'] }}">{{ $tile['value'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative sm:w-64">
            {{-- Magnifier swaps to a spinner while the debounced search round-trips. --}}
            <x-phosphor-magnifying-glass wire:loading.remove wire:target="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
            <x-phosphor-spinner-gap wire:loading wire:target="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 animate-spin text-brand-600 dark:text-brand-400" />
            <x-ui.input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('app.monitors_search_placeholder') }}" class="pl-9" />
        </div>
        <x-ui.select wire:model.live="status" class="sm:w-48">
            <option value="">{{ __('app.monitors_filter_all') }}</option>
            <option value="up">{{ __('app.status_up') }}</option>
            <option value="down">{{ __('app.status_down') }}</option>
            <option value="suspect">{{ __('app.status_suspect') }}</option>
            <option value="paused">{{ __('app.status_paused') }}</option>
        </x-ui.select>
    </div>

    {{-- Refresh feedback: the table dims rather than being replaced by a skeleton, because
         rows are swapped in place on an already-painted list (no layout jump). --}}
    <x-ui.table wire:loading.delay.class="opacity-50" wire:target="search,status,gotoPage,previousPage,nextPage" class="transition-opacity duration-150">
        <x-slot:head>
            <th>{{ __('app.monitors_col_name') }}</th>
            <th>{{ __('app.monitors_col_type') }}</th>
            <th>{{ __('app.monitors_col_status') }}</th>
            <th>{{ __('app.monitors_col_latency') }}</th>
            <th></th>
        </x-slot:head>

        @forelse ($monitors as $monitor)
            <tr wire:key="monitor-{{ $monitor->id }}" class="transition-colors duration-150 hover:bg-neutral-50 dark:hover:bg-neutral-800/60">
                <td>
                    <a href="{{ route('monitors.show', $monitor) }}" wire:navigate class="font-medium text-neutral-900 hover:text-brand-700 dark:text-neutral-100 dark:hover:text-brand-400">{{ $monitor->name }}</a>
                    @if ($monitor->client)
                        <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ $monitor->client->name }}</div>
                    @endif
                </td>
                <td class="text-neutral-600 dark:text-neutral-400">{{ __('app.monitor_type_'.$monitor->type) }}</td>
                <td><x-ui.status-pill :status="$monitor->state->status" /></td>
                <td class="font-mono text-xs text-neutral-600 dark:text-neutral-400">{{ $monitor->state->last_latency_ms ? $monitor->state->last_latency_ms.' ms' : '—' }}</td>
                <td class="text-right">
                    @php
                        $rowActionClass = 'inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-neutral-600 transition-colors duration-150 hover:bg-neutral-100 hover:text-neutral-900 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100';
                    @endphp
                    @if ($monitor->state->status === 'paused')
                        <button wire:click="resume({{ $monitor->id }})" wire:loading.attr="disabled" wire:target="resume({{ $monitor->id }})" class="{{ $rowActionClass }}">
                            <x-phosphor-play wire:loading.remove wire:target="resume({{ $monitor->id }})" class="size-3.5" />
                            <x-phosphor-spinner-gap wire:loading wire:target="resume({{ $monitor->id }})" class="size-3.5 animate-spin" />
                            {{ __('app.monitors_resume') }}
                        </button>
                    @else
                        <button wire:click="pause({{ $monitor->id }})" wire:loading.attr="disabled" wire:target="pause({{ $monitor->id }})" class="{{ $rowActionClass }}">
                            <x-phosphor-pause wire:loading.remove wire:target="pause({{ $monitor->id }})" class="size-3.5" />
                            <x-phosphor-spinner-gap wire:loading wire:target="pause({{ $monitor->id }})" class="size-3.5 animate-spin" />
                            {{ __('app.monitors_pause') }}
                        </button>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">
                    <x-ui.empty-state icon="pulse" :title="__('app.monitors_empty_title')" :description="__('app.monitors_empty_description')">
                        <x-slot:action>
                            <x-ui.button variant="secondary" size="sm" href="{{ route('monitors.create') }}" wire:navigate>
                                <x-phosphor-plus class="size-4" /> {{ __('app.monitors_empty_cta') }}
                            </x-ui.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-4">{{ $monitors->links() }}</div>
</div>
