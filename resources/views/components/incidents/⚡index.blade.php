<?php

use App\Models\Incident;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Url]
    public string $filter = 'open';

    public function with(): array
    {
        $incidents = Incident::query()
            ->with('monitor')
            ->when($this->filter === 'open', fn ($q) => $q->where('state', 'open'))
            ->when($this->filter === 'resolved', fn ($q) => $q->where('state', 'resolved'))
            ->orderByDesc('started_at')
            ->paginate(20);

        return ['incidents' => $incidents];
    }
};
?>

<div>
    <x-ui.page-header :title="__('app.incidents_title')" :description="__('app.incidents_description')" />

    <div class="mb-4 inline-flex items-center gap-0.5 rounded-md border border-neutral-200 bg-neutral-50 p-0.5 text-sm dark:border-neutral-800 dark:bg-neutral-900">
        @php
            $filterBtn = fn (bool $active) => 'rounded px-3 py-1.5 font-medium transition-colors duration-150 active:scale-[0.98] '
                .($active
                    ? 'bg-white text-neutral-900 shadow-[var(--shadow-card)] dark:bg-neutral-800 dark:text-neutral-100'
                    : 'text-neutral-500 hover:text-neutral-800 dark:text-neutral-400 dark:hover:text-neutral-200');
        @endphp
        <button wire:click="$set('filter', 'open')" class="{{ $filterBtn($filter === 'open') }}">{{ __('app.incidents_filter_open') }}</button>
        <button wire:click="$set('filter', 'resolved')" class="{{ $filterBtn($filter === 'resolved') }}">{{ __('app.incidents_filter_resolved') }}</button>
        <button wire:click="$set('filter', 'all')" class="{{ $filterBtn($filter === 'all') }}">{{ __('app.incidents_filter_all') }}</button>
    </div>

    <x-ui.table wire:loading.delay.class="opacity-50" wire:target="$set,gotoPage,previousPage,nextPage" class="transition-opacity duration-150">
        <x-slot:head>
            <th>{{ __('app.incidents_col_monitor') }}</th>
            <th>{{ __('app.monitors_col_status') }}</th>
            <th>{{ __('app.incidents_col_cause') }}</th>
            <th>{{ __('app.incidents_col_started') }}</th>
            <th>{{ __('app.incidents_col_duration') }}</th>
        </x-slot:head>

        @forelse ($incidents as $incident)
            <tr wire:key="incident-{{ $incident->id }}" class="transition-colors duration-150 hover:bg-neutral-50 dark:hover:bg-neutral-800/60">
                <td>
                    <a href="{{ route('incidents.show', $incident) }}" wire:navigate class="font-medium text-neutral-900 hover:text-brand-700 dark:text-neutral-100 dark:hover:text-brand-400">{{ $incident->monitor->name }}</a>
                    @if ($incident->flapping)
                        <x-ui.badge color="amber" class="ml-1">{{ __('app.monitor_flapping') }}</x-ui.badge>
                    @endif
                </td>
                <td>
                    <x-ui.badge :color="$incident->state === 'open' ? 'red' : 'emerald'">{{ $incident->state === 'open' ? __('app.incident_state_open') : __('app.incident_state_resolved') }}</x-ui.badge>
                    @if ($incident->acknowledged_at)
                        <span class="ml-1 text-xs text-neutral-500 dark:text-neutral-400">{{ __('app.incident_acknowledged_badge') }}</span>
                    @endif
                </td>
                <td class="text-neutral-600 dark:text-neutral-400">{{ \App\Checks\ErrorClassifier::label($incident->cause_class) }}</td>
                <td class="font-mono text-xs text-neutral-600 dark:text-neutral-400">{{ $incident->started_at->toDisplay() }}</td>
                <td class="text-neutral-600 dark:text-neutral-400">
                    {{ $incident->duration_s ? \Carbon\CarbonInterval::seconds($incident->duration_s)->cascade()->locale(app()->getLocale())->forHumans(['short' => true]) : '—' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">
                    <x-ui.empty-state icon="warning" :title="__('app.incidents_empty')" />
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-4">{{ $incidents->links() }}</div>
</div>
