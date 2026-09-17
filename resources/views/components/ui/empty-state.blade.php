@props([
    'icon' => 'tray',
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-1.5 px-4 py-12 text-center']) }}>
    <div class="mb-2 flex size-11 items-center justify-center rounded-full bg-surface-2">
        <x-dynamic-component :component="'phosphor-'.$icon" class="size-5 text-faint" />
    </div>
    <p class="text-sm font-medium text-ink-2">{{ $title }}</p>
    @if ($description)
        <p class="max-w-sm text-sm text-muted">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-3">{{ $action }}</div>
    @endisset
</div>
