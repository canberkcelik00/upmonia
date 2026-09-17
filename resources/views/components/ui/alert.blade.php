@props([
    'variant' => 'info',
])

@php
$variants = [
    'success' => ['box' => 'border-up/25 bg-up-soft text-up-text', 'icon' => 'check-circle', 'iconColor' => 'text-up'],
    'error' => ['box' => 'border-down/25 bg-down-soft text-down-text', 'icon' => 'warning-circle', 'iconColor' => 'text-down'],
    'warning' => ['box' => 'border-warn/25 bg-warn-soft text-warn-text', 'icon' => 'warning', 'iconColor' => 'text-warn'],
    'info' => ['box' => 'border-line bg-surface-2 text-ink-2', 'icon' => 'info', 'iconColor' => 'text-muted'],
];

$v = $variants[$variant] ?? $variants['info'];
// Errors interrupt (assertive); everything else waits for a pause (polite).
$role = $variant === 'error' ? 'alert' : 'status';
@endphp

<div role="{{ $role }}" {{ $attributes->merge(['class' => 'flex items-start gap-2.5 rounded-control border px-3 py-2.5 text-sm '.$v['box']]) }}>
    <x-dynamic-component :component="'phosphor-'.$v['icon']" class="mt-0.5 size-4 shrink-0 {{ $v['iconColor'] }}" />
    <div class="min-w-0">{{ $slot }}</div>
</div>
