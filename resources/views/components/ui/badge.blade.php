@props([
    'color' => 'neutral',
])

@php
$colors = [
    'neutral' => 'bg-idle-soft text-ink-2',
    'emerald' => 'bg-up-soft text-up-text',
    'amber' => 'bg-warn-soft text-warn-text',
    'red' => 'bg-down-soft text-down-text',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-tag px-2 py-0.5 text-xs font-medium '.($colors[$color] ?? $colors['neutral'])]) }}>
    {{ $slot }}
</span>
