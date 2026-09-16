<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('partials.theme-init')
</head>
<body class="min-h-screen bg-neutral-50 text-neutral-900 antialiased dark:bg-neutral-950 dark:text-neutral-100">
<div x-data="{ open: false }" @keydown.escape.window="open = false">

    {{-- Mobile top bar: the sidebar collapses into a drawer below lg. --}}
    <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-neutral-200 bg-white/85 px-4 backdrop-blur-sm lg:hidden dark:border-neutral-800 dark:bg-neutral-900/85">
        <button
            type="button"
            @click="open = true"
            aria-label="{{ __('app.nav_open_menu') }}"
            class="-ml-1 flex size-9 items-center justify-center rounded-lg text-neutral-600 transition-colors duration-150 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800"
        >
            <x-phosphor-list class="size-5" />
        </button>

        <a href="{{ route('monitors.index') }}" wire:navigate class="flex items-center gap-2 font-semibold tracking-tight">
            <span class="flex size-7 items-center justify-center rounded-lg bg-brand-600 text-white">
                <x-phosphor-pulse class="size-4" />
            </span>
            Uptik
        </a>

        <div class="ml-auto flex items-center gap-1">
            <x-ui.locale-switcher />
            <x-ui.theme-toggle />
        </div>
    </header>

    {{-- Mobile drawer --}}
    <div x-show="open" x-cloak class="relative z-40 lg:hidden" role="dialog" aria-modal="true">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="open = false"
            class="fixed inset-0 bg-neutral-950/40 backdrop-blur-[2px]"
        ></div>

        <aside
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 flex w-[17rem] flex-col border-r border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900"
        >
            <div class="mb-6 flex items-center justify-between">
                <a href="{{ route('monitors.index') }}" wire:navigate class="flex items-center gap-2 font-semibold tracking-tight">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-brand-600 text-white">
                        <x-phosphor-pulse class="size-[1.125rem]" />
                    </span>
                    Uptik
                </a>
                <button
                    type="button"
                    @click="open = false"
                    aria-label="{{ __('app.nav_close_menu') }}"
                    class="flex size-8 items-center justify-center rounded-lg text-neutral-500 transition-colors duration-150 hover:bg-neutral-100 dark:hover:bg-neutral-800"
                >
                    <x-phosphor-x class="size-4" />
                </button>
            </div>

            <div @click="open = false">
                @include('partials.nav-items')
            </div>

            <div class="mt-auto border-t border-neutral-200 pt-4 dark:border-neutral-800">
                @include('partials.nav-account')
            </div>
        </aside>
    </div>

    {{-- Desktop sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-20 hidden w-64 flex-col border-r border-neutral-200 bg-white px-4 py-5 lg:flex dark:border-neutral-800 dark:bg-neutral-900">
        <a href="{{ route('monitors.index') }}" wire:navigate class="mb-7 flex items-center gap-2.5 px-1 text-[0.95rem] font-semibold tracking-tight">
            <span class="flex size-8 items-center justify-center rounded-lg bg-brand-600 text-white">
                <x-phosphor-pulse class="size-[1.125rem]" />
            </span>
            Uptik
        </a>

        @include('partials.nav-items')

        <div class="mt-auto flex flex-col gap-2 border-t border-neutral-200 pt-4 dark:border-neutral-800">
            <div class="flex items-center gap-1 px-1">
                {{-- Sitting at the bottom of the sidebar, so both menus open upward;
                     a downward menu would cover the account row underneath. --}}
                <x-ui.locale-switcher placement="top" />
                <x-ui.theme-toggle placement="top" />
            </div>
            @include('partials.nav-account')
        </div>
    </aside>

    <main class="lg:pl-64">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            {{ $slot }}
        </div>
    </main>
</div>

@livewireScripts
</body>
</html>
