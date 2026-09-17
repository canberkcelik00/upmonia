@props([
    'invalid' => false,
])

@php
$base = 'w-full rounded-control border bg-surface px-2.5 py-1.5 text-[13px] text-ink placeholder:text-muted transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-0 disabled:cursor-not-allowed disabled:bg-surface-2 disabled:text-faint';

$state = $invalid
    ? 'border-down focus:border-down focus:ring-down/20'
    : 'border-line-strong focus:border-ink focus:ring-ink/15';
@endphp

<input {{ $attributes->merge(['class' => "$base $state"]) }}>
