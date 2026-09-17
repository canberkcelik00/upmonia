@props([
    'title',
    'description' => null,
    'back' => null,
    'backLabel' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-start justify-between gap-4']) }}>
    <div class="min-w-0">
        @if ($back)
            <nav class="mb-1 text-xs text-muted">
                <a href="{{ $back }}" wire:navigate class="hover:text-ink">{{ $backLabel }}</a>
                <span class="mx-1">/</span>
                <span class="text-ink-2">{{ $title }}</span>
            </nav>
        @endif
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold tracking-[-0.025em] text-ink">{{ $title }}</h1>
            {{-- Room for a status pill / badge next to the title, without a wrapper
                 that would force a second row or a margin hack against mb-6 above. --}}
            @isset($titleMeta)
                <div class="flex items-center gap-2">{{ $titleMeta }}</div>
            @endisset
        </div>
        @if ($description)
            <p class="mt-1.5 max-w-prose text-sm text-muted">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
