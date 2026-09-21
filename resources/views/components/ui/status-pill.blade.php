@props(['status' => 'pending', 'flapping' => false, 'hint' => true])

@php
$labels = [
    'pending' => __('app.status_pending'), 'up' => __('app.status_up'), 'suspect' => __('app.status_suspect'),
    'down' => __('app.status_down'), 'recovering' => __('app.status_recovering'), 'paused' => __('app.status_paused'),
];

// suspect/recovering read as "warn" — a hint something moved, not yet a confirmed incident.
// paused/pending read as "idle" — nothing being measured right now, not a status judgement.
$tones = [
    'up' => 'up', 'suspect' => 'warn', 'recovering' => 'warn', 'down' => 'down',
    'paused' => 'idle', 'pending' => 'idle',
];
$tone = $tones[$status] ?? 'idle';

$toneClasses = [
    'up' => 'bg-up-soft text-up-text',
    'warn' => 'bg-warn-soft text-warn-text',
    'down' => 'bg-down-soft text-down-text',
    'idle' => 'bg-idle-soft text-ink-2',
];
$dotClasses = [
    'up' => 'bg-up', 'warn' => 'bg-warn', 'down' => 'bg-down', 'idle' => 'bg-idle',
];
@endphp

<span class="inline-flex items-center gap-1.5">
    <span {{ $attributes->merge(['class' => 'inline-flex h-[22px] items-center gap-1.5 rounded-tag px-2 text-[12.5px] font-medium '.$toneClasses[$tone]]) }}>
        <span class="size-1.5 rounded-full {{ $dotClasses[$tone] }}" aria-hidden="true"></span>
        @if ($hint && array_key_exists($status, $labels))
            <x-ui.hint :text="__('app.status_'.$status.'_hint')" :underline="false">{{ $labels[$status] }}</x-ui.hint>
        @else
            {{ $labels[$status] ?? $status }}
        @endif
    </span>
    @if ($flapping)
        <span class="inline-flex h-[22px] items-center rounded-tag bg-warn-soft px-2 text-[12.5px] font-medium text-warn-text">
            @if ($hint)
                <x-ui.hint :text="__('app.monitor_flapping_hint')" :underline="false">{{ __('app.monitor_flapping') }}</x-ui.hint>
            @else
                {{ __('app.monitor_flapping') }}
            @endif
        </span>
    @endif
</span>
