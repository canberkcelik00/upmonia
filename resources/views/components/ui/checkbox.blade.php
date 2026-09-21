@props([
    'label' => null,
])

<label class="flex items-center gap-2 text-[13px] text-ink-2">
    <input
        type="checkbox"
        {{ $attributes->merge(['class' => 'size-4 shrink-0 accent-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-ink/15']) }}
    >
    @if ($label)
        <span>{{ $label }}</span>
    @endif
    {{ $slot }}
</label>
