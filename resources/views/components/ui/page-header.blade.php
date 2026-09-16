@props([
    'title',
    'description' => null,
    'back' => null,
    'backLabel' => null,
])

<div {{ $attributes->merge(['class' => 'mb-8 flex flex-wrap items-start justify-between gap-4']) }}>
    <div class="min-w-0">
        @if ($back)
            <div class="mb-2">
                <x-ui.back-link :href="$back" :label="$backLabel" />
            </div>
        @endif
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-semibold tracking-tight text-neutral-900 dark:text-neutral-50">{{ $title }}</h1>
            {{-- Room for a status pill / badge next to the title, without a wrapper
                 that would force a second row or a margin hack against mb-8 above. --}}
            @isset($titleMeta)
                <div class="flex items-center gap-2">{{ $titleMeta }}</div>
            @endisset
        </div>
        @if ($description)
            <p class="mt-1.5 max-w-prose text-sm text-neutral-500 dark:text-neutral-400">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
