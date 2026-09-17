@props([
    'variant' => 'secondary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
$variants = [
    'primary' => 'bg-ink text-on-ink hover:opacity-90 focus-visible:outline-ink',
    'secondary' => 'border border-line-strong bg-surface text-ink hover:bg-surface-2 focus-visible:outline-ink',
    'ghost' => 'text-ink-2 hover:bg-surface-2 focus-visible:outline-ink',
    'danger' => 'border border-line-strong bg-surface text-down-text hover:bg-down-soft focus-visible:outline-down',
];

$sizes = [
    'sm' => 'h-7 px-2.5 text-[12.5px] gap-1.5',
    'md' => 'h-8 px-3 text-sm gap-2',
];

$base = 'inline-flex items-center justify-center rounded-control font-medium transition-colors duration-150 active:scale-[0.98] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50 disabled:active:scale-100';

$classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['secondary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
