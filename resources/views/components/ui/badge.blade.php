@props([
    'color' => 'neutral',
])

@php
$colors = [
    'neutral' => 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300',
    'brand' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
    'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400',
    'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
    'red' => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.($colors[$color] ?? $colors['neutral'])]) }}>
    {{ $slot }}
</span>
