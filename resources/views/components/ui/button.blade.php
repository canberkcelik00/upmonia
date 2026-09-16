@props([
    'variant' => 'secondary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
$variants = [
    'primary' => 'bg-neutral-900 text-white hover:bg-neutral-800 focus-visible:outline-neutral-900 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white dark:focus-visible:outline-neutral-100',
    'secondary' => 'border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 focus-visible:outline-neutral-400 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800',
    'danger' => 'border border-red-200 text-red-600 hover:bg-red-50 focus-visible:outline-red-500 dark:border-red-900/60 dark:text-red-400 dark:hover:bg-red-950/40',
    'ghost' => 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-neutral-400 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100',
];

$sizes = [
    'sm' => 'px-3 py-1.5 text-sm gap-1.5',
    'md' => 'px-4 py-2 text-sm gap-2',
];

$base = 'inline-flex items-center justify-center rounded-md font-medium transition-colors duration-150 active:scale-[0.98] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50 disabled:active:scale-100';

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
