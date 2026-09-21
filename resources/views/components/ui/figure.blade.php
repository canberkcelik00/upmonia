@props([
    'label',
    'value',
    'unit' => null,
    'tone' => null, // null (ink) | 'up' | 'warn' | 'down'
    'raw' => false, // true when $value is trusted, pre-escaped HTML (e.g. Carbon::toDisplayHtml())
    'hint' => null, // plain-language explanation of the label, shown on hover
])

@php
$toneClass = ['up' => 'text-up-text', 'warn' => 'text-warn-text', 'down' => 'text-down-text'][$tone] ?? 'text-ink';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1']) }}>
    <span class="text-xs text-muted">
        @if ($hint)
            <x-ui.hint :text="$hint" align="start">{{ $label }}</x-ui.hint>
        @else
            {{ $label }}
        @endif
    </span>
    <span class="font-mono text-[22px] font-medium tracking-[-0.02em] {{ $toneClass }}">
        @if ($raw)
            {!! $value !!}
        @else
            {{ $value }}
        @endif
        @if ($unit)
            <small class="ml-0.5 text-[13px] font-normal text-muted">{{ $unit }}</small>
        @endif
    </span>
</div>
