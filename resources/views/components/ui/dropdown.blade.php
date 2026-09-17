@props([
    'align' => 'right', // 'right' | 'left'
])

<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative">
    <div @click="open = !open">{{ $trigger }}</div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-cloak
        @click="open = false"
        {{ $attributes->merge(['class' => 'absolute top-full z-50 mt-2 min-w-[10rem] rounded-control border border-line bg-surface p-1 shadow-[var(--shadow-popover)] '.($align === 'left' ? 'left-0 origin-top-left' : 'right-0 origin-top-right')]) }}
    >
        {{ $slot }}
    </div>
</div>
