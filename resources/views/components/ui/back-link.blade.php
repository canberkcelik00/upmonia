@props([
    'href',
    'label' => null,
])

<a
    href="{{ $href }}"
    wire:navigate
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-md text-sm font-medium text-neutral-500 transition-colors duration-150 hover:text-neutral-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:text-neutral-400 dark:hover:text-neutral-100']) }}
>
    <x-phosphor-arrow-left class="size-4" />
    {{ $label ?? $slot }}
</a>
