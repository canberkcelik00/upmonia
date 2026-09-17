@props([
    'label' => null,
])

<label class="flex items-center gap-2 text-[13px] text-ink-2">
    <input
        type="checkbox"
        {{ $attributes->merge(['class' => 'size-4 rounded-[4px] border-line-strong text-ink transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-ink/15 focus:ring-offset-0']) }}
    >
    @if ($label)
        <span>{{ $label }}</span>
    @endif
    {{ $slot }}
</label>
