<?php

use App\Models\Incident;
use App\Support\Format;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Url]
    public string $filter = 'open';

    public function with(): array
    {
        $counts = Incident::query()->selectRaw('state, count(*) as count')->groupBy('state')->pluck('count', 'state');
        $openCount = $counts['open'] ?? 0;
        $resolvedCount = $counts['resolved'] ?? 0;

        $incidents = Incident::query()
            ->with('monitor')
            ->when($this->filter === 'open', fn ($q) => $q->where('state', 'open'))
            ->when($this->filter === 'resolved', fn ($q) => $q->where('state', 'resolved'))
            ->orderByDesc('started_at')
            ->paginate(20);

        return [
            'incidents' => $incidents,
            'openCount' => $openCount,
            'resolvedCount' => $resolvedCount,
            'totalCount' => $openCount + $resolvedCount,
        ];
    }
};
?>

<div>
    <x-ui.page-header :title="__('app.incidents_title')" :description="__('app.incidents_description')" />

    <div class="mb-6 text-[27px] font-bold leading-[1.1] tracking-[-0.025em] text-ink">
        {{ $openCount > 0 ? __('app.incidents_statement_open', ['count' => $openCount]) : __('app.incidents_statement_none') }}
    </div>

    <x-ui.segmented
        :options="['open' => __('app.incidents_filter_open'), 'resolved' => __('app.incidents_filter_resolved'), 'all' => __('app.incidents_filter_all')]"
        :active="$filter"
        action="$set('filter', '%s')"
        :counts="['open' => $openCount, 'resolved' => $resolvedCount, 'all' => $totalCount]"
        class="mb-4"
    />

    <x-ui.table wire:loading.delay.class="opacity-50" wire:target="$set,gotoPage,previousPage,nextPage" class="transition-opacity duration-150">
        <x-slot:head>
            <th>{{ __('app.incidents_col_monitor') }}</th>
            <th>{{ __('app.monitors_col_status') }}</th>
            <th>{{ __('app.incidents_col_cause') }}</th>
            <th>{{ __('app.incidents_col_started') }}</th>
            <th>{{ __('app.incidents_col_duration') }}</th>
        </x-slot:head>

        @forelse ($incidents as $incident)
            <tr wire:key="incident-{{ $incident->id }}" @class(['hover:bg-surface-2 transition-colors duration-150', 'bg-down-soft/45' => $incident->state === 'open'])>
                <td>
                    <a href="{{ route('incidents.show', $incident) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $incident->monitor->name }}</a>
                    @if ($incident->flapping)
                        <x-ui.badge color="amber" class="ml-1">{{ __('app.monitor_flapping') }}</x-ui.badge>
                    @endif
                </td>
                <td>
                    <span class="inline-flex h-[22px] items-center gap-1.5 rounded-tag px-2 text-[12.5px] font-medium {{ $incident->state === 'open' ? 'bg-down-soft text-down-text' : 'bg-idle-soft text-ink-2' }}">
                        <span class="size-1.5 rounded-full {{ $incident->state === 'open' ? 'bg-down' : 'bg-idle' }}"></span>
                        {{ $incident->state === 'open' ? __('app.incident_state_open') : __('app.incident_state_resolved') }}
                    </span>
                    @if ($incident->acknowledged_at)
                        <span class="ml-1 text-xs text-muted">{{ __('app.incident_acknowledged_badge') }}</span>
                    @endif
                </td>
                <td class="text-ink-2">{{ \App\Checks\ErrorClassifier::label($incident->cause_class) }}</td>
                <td class="font-mono text-xs text-muted">{!! $incident->started_at->toDisplayHtml() !!}</td>
                <td class="text-ink-2">
                    {{ $incident->duration_s ? Format::shortDuration($incident->duration_s) : '—' }}
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
