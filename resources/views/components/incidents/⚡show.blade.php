<?php

use App\Models\Incident;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Incident $incident;

    public function mount(Incident $incident): void
    {
        $this->incident = $incident;
    }

    public function acknowledge(): void
    {
        $this->incident->update([
            'acknowledged_by' => Auth::id(),
            'acknowledged_at' => now(),
        ]);
    }

    public function with(): array
    {
        return [
            'events' => $this->incident->events()->orderBy('ts')->get(),
            'notifications' => $this->incident->notifications()->with('channel')->orderBy('created_at')->get(),
        ];
    }
};
?>

<div class="max-w-4xl">
    <x-ui.page-header :back="route('incidents.index')" :back-label="__('app.incidents_title')" title="{{ $incident->monitor->name }}">
        <x-slot:titleMeta>
            <x-ui.badge :color="$incident->state === 'open' ? 'red' : 'emerald'">{{ $incident->state === 'open' ? __('app.incident_state_open') : __('app.incident_state_resolved') }}</x-ui.badge>
            @if ($incident->flapping)
                <x-ui.badge color="amber">{{ __('app.monitor_flapping') }}</x-ui.badge>
            @endif
        </x-slot:titleMeta>
        <x-slot:actions>
            @if (! $incident->acknowledged_at)
                <x-ui.button variant="secondary" size="sm" wire:click="acknowledge">
                    <x-phosphor-check class="size-4" />
                    {{ __('app.incident_acknowledge') }}
                </x-ui.button>
            @else
                <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('app.incident_acknowledged_by', ['name' => $incident->acknowledgedBy?->name, 'time' => $incident->acknowledged_at->toDisplay()]) }}</span>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card class="mb-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><div class="text-neutral-500 dark:text-neutral-400">{{ __('app.incidents_col_cause') }}</div><div class="font-medium">{{ \App\Checks\ErrorClassifier::label($incident->cause_class) }}</div></div>
            <div><div class="text-neutral-500 dark:text-neutral-400">{{ __('app.incident_detail') }}</div><div class="font-medium">{{ $incident->cause_detail ?? '—' }}</div></div>
            <div><div class="text-neutral-500 dark:text-neutral-400">{{ __('app.incidents_col_started') }}</div><div class="font-mono text-xs font-medium">{{ $incident->started_at->toDisplay() }}</div></div>
            <div>
                <div class="text-neutral-500 dark:text-neutral-400">{{ $incident->state === 'open' ? __('app.incident_ongoing_duration') : __('app.incident_ended_at') }}</div>
                <div class="font-mono text-xs font-medium">
                    {{ $incident->resolved_at ? $incident->resolved_at->toDisplay() : $incident->started_at->diffForHumans(null, true, false, 2) }}
                </div>
            </div>
        </div>
    </x-ui.card>

    <x-ui.section-heading>{{ __('app.incident_timeline') }}</x-ui.section-heading>
    <x-ui.table class="mb-6">
        @forelse ($events as $event)
            <tr>
                <td class="text-neutral-700 dark:text-neutral-300">{{ $event->type === 'triggered' ? __('app.incident_event_triggered') : __('app.incident_event_resolved') }}</td>
                <td class="text-right font-mono text-xs text-neutral-500 dark:text-neutral-400">{{ $event->ts->toDisplay() }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2">
                    <x-ui.empty-state icon="clock-counter-clockwise" :title="__('app.incident_no_events')" />
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <x-ui.section-heading>{{ __('app.incident_notifications') }}</x-ui.section-heading>
    <x-ui.table>
        <x-slot:head>
            <th>{{ __('app.incident_col_channel') }}</th>
            <th>{{ __('app.incident_col_event') }}</th>
            <th>{{ __('app.monitors_col_status') }}</th>
            <th>{{ __('app.incident_col_attempt') }}</th>
        </x-slot:head>

        @forelse ($notifications as $n)
            <tr>
                <td>{{ $n->channel->name }}</td>
                <td class="text-neutral-600 dark:text-neutral-400">{{ $n->event_type === 'triggered' ? __('app.incident_event_triggered') : __('app.incident_event_resolved') }}</td>
                <td>
                    <x-ui.badge :color="$n->status === 'sent' ? 'emerald' : ($n->status === 'failed' ? 'red' : 'neutral')">{{ __('app.notification_status_'.$n->status) }}</x-ui.badge>
                </td>
                <td class="font-mono text-xs text-neutral-600 dark:text-neutral-400">{{ $n->attempts }}/{{ $n->max_attempts }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4">
                    <x-ui.empty-state icon="bell" :title="__('app.incident_notifications_empty')" :description="__('app.incident_notifications_empty_description')" />
                </td>
            </tr>
        @endforelse
    </x-ui.table>
</div>
