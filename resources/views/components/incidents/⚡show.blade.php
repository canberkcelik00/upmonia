<?php

use App\Models\Incident;
use App\Support\Format;
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
        $this->incident->loadMissing('monitor.state');

        return [
            'events' => $this->incident->events()->orderBy('ts')->get(),
            'notifications' => $this->incident->notifications()->with('channel')->orderBy('created_at')->get(),
        ];
    }
};
?>

<div>
    <x-ui.page-header :back="route('incidents.index')" :back-label="__('app.incidents_title')" title="{{ $incident->monitor->name }}">
        <x-slot:actions>
            @if (! $incident->acknowledged_at && $incident->state === 'open')
                <x-ui.button variant="secondary" size="sm" wire:click="acknowledge">
                    <x-phosphor-check class="size-4" />
                    {{ __('app.incident_acknowledge') }}
                </x-ui.button>
            @elseif ($incident->acknowledged_at)
                <span class="text-xs text-muted">{{ __('app.incident_acknowledged_by', ['name' => $incident->acknowledgedBy?->name, 'time' => $incident->acknowledged_at->toDisplay()]) }}</span>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($incident->state === 'open')
        <x-ui.incident-strip :incident="$incident" :show-link="false" class="mb-6" />
    @endif

    <div class="mb-6 grid grid-cols-2 divide-x divide-line rounded-panel border border-line bg-surface sm:grid-cols-4">
        <x-ui.figure class="px-4 py-3.5" :label="__('app.incidents_col_cause')" :value="\App\Checks\ErrorClassifier::label($incident->cause_class)" />
        <x-ui.figure class="px-4 py-3.5" :label="__('app.incident_detail')" :value="$incident->cause_detail ?? '—'" />
        <x-ui.figure class="px-4 py-3.5" :label="__('app.incidents_col_started')" :value="$incident->started_at->toDisplayTime()" />
        <x-ui.figure
            class="px-4 py-3.5"
            :label="$incident->state === 'open' ? __('app.incident_ongoing_duration') : __('app.incident_ended_at')"
            :value="$incident->resolved_at ? $incident->resolved_at->toDisplayTime() : Format::liveDuration($incident->started_at->diffInSeconds(now()))"
            :tone="$incident->state === 'open' ? 'down' : null"
        />
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-ui.card :title="__('app.incident_timeline')">
            <div class="divide-y divide-line">
                @forelse ($events as $event)
                    <div class="flex items-center justify-between py-2 text-[13px]">
                        <span class="text-ink-2">{{ $event->type === 'triggered' ? __('app.incident_event_triggered') : __('app.incident_event_resolved') }}</span>
                        <span class="font-mono text-xs text-muted">{{ $event->ts->toDisplayTime() }}</span>
                    </div>
                @empty
                    <x-ui.empty-state icon="clock-counter-clockwise" :title="__('app.incident_no_events')" />
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card :title="__('app.incident_notifications')">
            @if ($notifications->isEmpty())
                <x-ui.empty-state icon="bell" :title="__('app.incident_notifications_empty')" :description="__('app.incident_notifications_empty_description')" />
            @else
                <div class="divide-y divide-line">
                    @foreach ($notifications as $n)
                        <div class="flex items-center gap-3 py-2 text-[13px]">
                            <span class="min-w-0 flex-1 truncate text-ink">{{ $n->channel->name }}</span>
                            <span class="text-ink-2">{{ $n->event_type === 'triggered' ? __('app.incident_event_triggered') : __('app.incident_event_resolved') }}</span>
                            <span class="inline-flex h-[22px] items-center gap-1.5 rounded-tag px-2 text-[12.5px] font-medium {{ ['sent' => 'bg-up-soft text-up-text', 'failed' => 'bg-down-soft text-down-text'][$n->status] ?? 'bg-idle-soft text-ink-2' }}">
                                {{ __('app.notification_status_'.$n->status) }}
                            </span>
                            <span class="shrink-0 font-mono text-xs text-muted">{{ $n->attempts }}/{{ $n->max_attempts }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>
</div>
