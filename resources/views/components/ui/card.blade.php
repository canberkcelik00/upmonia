@props([
    'padding' => 'p-5 sm:p-6',
])

{{-- Shape rule: containers (cards, tables, tiles) use rounded-xl, controls rounded-md,
     pills rounded-full. Nothing else. --}}
<div {{ $attributes->merge(['class' => "rounded-xl border border-neutral-200 bg-white $padding shadow-[var(--shadow-card)] dark:border-neutral-800 dark:bg-neutral-900"]) }}>
    {{ $slot }}
</div>
