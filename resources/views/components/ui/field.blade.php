@props([
    'label' => null,
    'error' => null,
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    @if ($label)
        <label class="block text-[13px] text-muted">{{ $label }}</label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="text-xs text-muted">{{ $hint }}</p>
    @endif

    @if ($error)
        @error($error)
            <p class="text-xs text-down-text">{{ $message }}</p>
        @enderror
    @endif
</div>
