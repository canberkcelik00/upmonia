@props([
    'variant' => 'info',
])

@php
$variants = [
    'success' => [
        'box' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300',
        'icon' => 'check-circle',
        'iconColor' => 'text-emerald-600 dark:text-emerald-400',
    ],
    'error' => [
        'box' => 'border-red-200 bg-red-50 text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300',
        'icon' => 'warning-circle',
        'iconColor' => 'text-red-600 dark:text-red-400',
    ],
    'warning' => [
        'box' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-300',
        'icon' => 'warning',
        'iconColor' => 'text-amber-600 dark:text-amber-400',
    ],
    'info' => [
        'box' => 'border-neutral-200 bg-neutral-50 text-neutral-700 dark:border-neutral-800 dark:bg-neutral-800/50 dark:text-neutral-300',
        'icon' => 'info',
        'iconColor' => 'text-neutral-500 dark:text-neutral-400',
    ],
];

$v = $variants[$variant] ?? $variants['info'];
// Errors interrupt (assertive); everything else waits for a pause (polite).
$role = $variant === 'error' ? 'alert' : 'status';
@endphp

<div role="{{ $role }}" {{ $attributes->merge(['class' => 'flex items-start gap-2.5 rounded-md border px-3 py-2.5 text-sm '.$v['box']]) }}>
    <x-dynamic-component :component="'phosphor-'.$v['icon']" class="mt-0.5 size-4 shrink-0 {{ $v['iconColor'] }}" />
    <div class="min-w-0">{{ $slot }}</div>
</div>
