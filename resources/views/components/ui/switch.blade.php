@props([
    'checked' => false,
])

<button
    type="button"
    role="switch"
    aria-checked="{{ $checked ? 'true' : 'false' }}"
    {{ $attributes->merge(['class' => 'inline-flex h-[18px] w-8 shrink-0 cursor-pointer items-center rounded-full border transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-ink/15 '.($checked ? 'border-ink bg-ink' : 'border-line-strong bg-surface-2')]) }}
>
    <span class="pointer-events-none size-3 rounded-full transition-transform duration-150 {{ $checked ? 'translate-x-4 bg-on-ink' : 'translate-x-0.5 bg-faint' }}"></span>
</button>
