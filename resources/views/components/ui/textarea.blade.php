@props([
    'invalid' => false,
    'mono' => false,
])

@php
$base = 'w-full rounded-md border px-3 py-2 text-sm text-neutral-900 placeholder:text-neutral-500 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-0 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-400 dark:bg-neutral-900 dark:text-neutral-100 dark:placeholder:text-neutral-400 dark:disabled:bg-neutral-800';

$font = $mono ? 'font-mono text-xs' : '';

$state = $invalid
    ? 'border-red-300 focus:border-red-500 focus:ring-red-500/30 dark:border-red-800'
    : 'border-neutral-300 focus:border-brand-500 focus:ring-brand-500/30 dark:border-neutral-700';
@endphp

<textarea {{ $attributes->merge(['class' => "$base $state $font"]) }}>{{ $slot }}</textarea>
