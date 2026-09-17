@props([
    'label',
    'value',
    'unit' => null,
    'tone' => null, // null (ink) | 'up' | 'warn' | 'down'
])

@php
$toneClass = ['up' => 'text-up-text', 'warn' => 'text-warn-text', 'down' => 'text-down-text'][$tone] ?? 'text-ink';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1']) }}>
    <span class="text-xs text-muted">{{ $label }}</span>
    <span class="font-mono text-[22px] font-medium tracking-[-0.02em] {{ $toneClass }}">
        {{ $value }}
        @if ($unit)
            <small class="ml-0.5 text-[13px] font-normal text-muted">{{ $unit }}</small>
        @endif
    </span>
</div>
