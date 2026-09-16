@props([
    // 'bottom' opens downward (top bars), 'top' opens upward (sidebar footer,
    // where a downward menu would cover the account row below it).
    'placement' => 'bottom',
])

@php
    $locales = ['tr' => 'Türkçe', 'en' => 'English'];
    $current = app()->getLocale();

    $panelPosition = $placement === 'top'
        ? 'bottom-full left-0 mb-2 origin-bottom-left'
        : 'top-full right-0 mt-2 origin-top-right';
@endphp

<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative">
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        aria-label="{{ __('app.language_switch') }}"
        class="flex h-9 items-center gap-1.5 rounded-md px-2 text-sm font-medium text-neutral-600 transition-colors duration-150 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100"
    >
        <x-phosphor-translate class="size-4 shrink-0" />
        {{ strtoupper($current) }}
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-cloak
        class="absolute z-50 w-36 rounded-md border border-neutral-200 bg-white p-1 shadow-[var(--shadow-popover)] dark:border-neutral-800 dark:bg-neutral-900 {{ $panelPosition }}"
    >
        @foreach ($locales as $code => $label)
            <a
                href="{{ route('locale.switch', $code) }}"
                class="flex items-center gap-2.5 rounded-md px-2.5 py-1.5 text-sm transition-colors duration-150 hover:bg-neutral-100 dark:hover:bg-neutral-800 {{ $current === $code ? 'text-brand-700 dark:text-brand-400' : 'text-neutral-700 dark:text-neutral-300' }}"
            >
                <span class="truncate">{{ $label }}</span>
                @if ($current === $code)
                    <x-phosphor-check class="ml-auto size-3.5 shrink-0" />
                @endif
            </a>
        @endforeach
    </div>
</div>
