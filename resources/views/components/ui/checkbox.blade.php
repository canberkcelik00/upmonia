@props([
    'label' => null,
])

<label class="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
    <input
        type="checkbox"
        {{ $attributes->merge(['class' => 'size-4 rounded-sm border-neutral-300 text-brand-600 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:ring-offset-0 dark:border-neutral-700 dark:bg-neutral-900']) }}
    >
    @if ($label)
        <span>{{ $label }}</span>
    @endif
    {{ $slot }}
</label>
