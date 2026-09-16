@props([
    'label' => null,
    'error' => null,
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    @if ($label)
        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ $label }}</label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ $hint }}</p>
    @endif

    @if ($error)
        @error($error)
            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    @endif
</div>
