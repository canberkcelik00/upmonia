@props([
    'incident',
    // Livewire wire:click expression for the Onayla button, e.g. "acknowledge({$incident->id})".
    // Omitted (null) hides the button — used where the caller has no acknowledge method (emails have none anyway).
    'acknowledgeAction' => null,
    'showLink' => true,
])

@php
    $causeLabel = \App\Checks\ErrorClassifier::label($incident->cause_class);
    $state = $incident->monitor->state;
@endphp

<div
    x-data="{ elapsed: Math.max(0, Math.floor((Date.now() - new Date(@js($incident->started_at->toIso8601String())).getTime()) / 1000)) }"
    x-init="setInterval(() => elapsed++, 1000)"
    {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-3 rounded-panel border border-down/25 bg-down-soft px-3 py-2.5']) }}
>
    <span class="relative flex size-2 shrink-0" aria-hidden="true">
        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-down opacity-60 motion-reduce:animate-none"></span>
        <span class="relative inline-flex size-2 rounded-full bg-down"></span>
    </span>

    <div class="flex min-w-0 flex-1 flex-wrap items-baseline gap-x-2 gap-y-0.5">
        <strong class="font-semibold text-down-text">{{ __('app.status_down') }}</strong>
        <a href="{{ route('monitors.show', $incident->monitor) }}" wire:navigate class="truncate font-medium text-ink hover:underline">{{ $incident->monitor->name }}</a>
        <span class="truncate font-mono text-xs text-muted">
            {{ $causeLabel }}
            @if ($state && $incident->monitor->confirm_threshold)
                · {{ $state->consecutive_fails }}/{{ $incident->monitor->confirm_threshold }}
            @endif
        </span>
    </div>

    <span
        class="font-mono text-sm font-medium tabular-nums text-down-text"
        x-text="elapsed >= 3600 ? Math.floor(elapsed / 3600) + ':' + String(Math.floor((elapsed % 3600) / 60)).padStart(2, '0') + ':' + String(elapsed % 60).padStart(2, '0') : String(Math.floor(elapsed / 60)).padStart(2, '0') + ':' + String(elapsed % 60).padStart(2, '0')"
    ></span>

    @if ($incident->acknowledged_at)
        <span class="text-xs text-muted">{{ __('app.incident_acknowledged_badge') }}</span>
    @elseif ($acknowledgeAction)
        <x-ui.button variant="secondary" size="sm" wire:click="{{ $acknowledgeAction }}" wire:loading.attr="disabled" wire:target="{{ $acknowledgeAction }}">
            <x-phosphor-check class="size-3.5" />
            {{ __('app.incident_acknowledge') }}
        </x-ui.button>
    @endif

    @if ($showLink)
        <x-ui.button variant="ghost" size="sm" href="{{ route('incidents.show', $incident) }}" wire:navigate>
            {{ __('app.incident_detail') }}
            <x-phosphor-arrow-right class="size-3.5" />
        </x-ui.button>
    @endif
</div>
