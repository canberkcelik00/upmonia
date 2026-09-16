@props(['status' => 'pending'])

@php
$labels = [
    'pending' => __('app.status_pending'), 'up' => __('app.status_up'), 'suspect' => __('app.status_suspect'),
    'down' => __('app.status_down'), 'recovering' => __('app.status_recovering'), 'paused' => __('app.status_paused'),
];
$colors = [
    'pending' => 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300',
    'up' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400',
    'suspect' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
    'down' => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400',
    'recovering' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
    'paused' => 'bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400',
];
$dots = [
    'pending' => 'bg-neutral-400 dark:bg-neutral-500',
    'up' => 'bg-emerald-500',
    'suspect' => 'bg-amber-500',
    'down' => 'bg-red-500',
    'recovering' => 'bg-amber-500',
    'paused' => 'bg-neutral-400 dark:bg-neutral-500',
];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium '.($colors[$status] ?? $colors['pending'])]) }}>
    <span class="size-1.5 rounded-full {{ $dots[$status] ?? $dots['pending'] }}" aria-hidden="true"></span>
    {{ $labels[$status] ?? $status }}
</span>
