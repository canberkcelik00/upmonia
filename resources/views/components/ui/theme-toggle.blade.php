@props([
    // 'bottom' opens downward (top bars), 'top' opens upward (sidebar footer,
    // where a downward menu would cover the account row below it).
    'placement' => 'bottom',
])

@php
    $options = [
        'light' => ['icon' => 'sun', 'label' => __('app.theme_light')],
        'dark' => ['icon' => 'moon', 'label' => __('app.theme_dark')],
        'system' => ['icon' => 'monitor', 'label' => __('app.theme_system')],
    ];

    $panelPosition = $placement === 'top'
        ? 'bottom-full left-0 mb-2 origin-bottom-left'
        : 'top-full right-0 mt-2 origin-top-right';
@endphp

<div
    {{-- Writes the choice, then defers to window.applyTheme() from partials/theme-init
         so the resolve-and-apply logic lives in exactly one place. --}}
    x-data="{
        open: false,
        theme: localStorage.getItem('theme') || 'system',
        apply(theme) {
            this.theme = theme;
            localStorage.setItem('theme', theme);
            window.applyTheme();
            this.open = false;
        },
    }"
    @click.outside="open = false"
    @keydown.escape="open = false"
    class="relative"
>
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        aria-label="{{ __('app.theme_switch') }}"
        class="flex size-9 items-center justify-center rounded-md text-neutral-600 transition-colors duration-150 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100"
    >
        <template x-if="theme === 'light'"><x-phosphor-sun class="size-4" /></template>
        <template x-if="theme === 'dark'"><x-phosphor-moon class="size-4" /></template>
        <template x-if="theme === 'system'"><x-phosphor-monitor class="size-4" /></template>
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
        class="absolute z-50 w-40 rounded-md border border-neutral-200 bg-white p-1 shadow-[var(--shadow-popover)] dark:border-neutral-800 dark:bg-neutral-900 {{ $panelPosition }}"
    >
        @foreach ($options as $value => $option)
            <button
                type="button"
                @click="apply('{{ $value }}')"
                class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-1.5 text-left text-sm text-neutral-700 transition-colors duration-150 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800"
                :class="theme === '{{ $value }}' && 'text-brand-700 dark:text-brand-400'"
            >
                <x-dynamic-component :component="'phosphor-'.$option['icon']" class="size-4 shrink-0" />
                <span class="truncate">{{ $option['label'] }}</span>
                <x-phosphor-check class="ml-auto size-3.5 shrink-0" x-show="theme === '{{ $value }}'" x-cloak />
            </button>
        @endforeach
    </div>
</div>
