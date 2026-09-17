<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="description" content="{{ $description ?? __('marketing.meta_description') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    <meta property="og:description" content="{{ $description ?? __('marketing.meta_description') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('partials.theme-init')
</head>
<body class="min-h-screen bg-canvas text-ink antialiased">
<div x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">

    <header class="sticky top-0 z-30 h-[52px] border-b border-line bg-surface px-4 lg:px-7">
        <div class="mx-auto flex h-full max-w-[1200px] items-center gap-2">
            <a href="{{ route('home') }}" class="flex items-center">
                <x-ui.logo class="text-[19px]" />
            </a>

            <nav class="ml-7 hidden items-center gap-6 text-[13.5px] font-medium text-ink-2 lg:flex">
                <a href="{{ route('features') }}" class="transition-colors duration-150 hover:text-ink {{ request()->routeIs('features') ? 'text-ink' : '' }}">
                    {{ __('marketing.nav_features') }}
                </a>
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <x-ui.locale-switcher class="hidden sm:block" />
                <x-ui.theme-toggle />
                <a href="{{ route('login') }}" class="hidden text-[13.5px] font-medium text-ink-2 transition-colors duration-150 hover:text-ink sm:inline-flex sm:items-center sm:px-2">
                    {{ __('marketing.nav_login') }}
                </a>
                <x-ui.button variant="primary" size="sm" :href="route('signup')">
                    {{ __('marketing.nav_signup') }}
                </x-ui.button>

                <button
                    type="button"
                    @click="menuOpen = ! menuOpen"
                    aria-label="{{ __('app.nav_open_menu') }}"
                    :aria-expanded="menuOpen"
                    class="-mr-1 flex size-8 items-center justify-center rounded-control text-ink-2 hover:bg-surface-2 lg:hidden"
                >
                    <x-phosphor-list class="size-5" />
                </button>
            </div>
        </div>
    </header>

    <div
        x-show="menuOpen"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        @click="menuOpen = false"
        class="border-b border-line bg-surface px-4 py-3 lg:hidden"
    >
        <a href="{{ route('features') }}" class="block rounded-control px-3 py-2 text-sm font-medium text-ink-2 hover:bg-surface-2 hover:text-ink">{{ __('marketing.nav_features') }}</a>
        <a href="{{ route('login') }}" class="block rounded-control px-3 py-2 text-sm font-medium text-ink-2 hover:bg-surface-2 hover:text-ink sm:hidden">{{ __('marketing.nav_login') }}</a>
    </div>

    <main>
        {{ $slot }}
    </main>

    <footer class="border-t border-line">
        <div class="mx-auto flex max-w-[1200px] flex-wrap items-center justify-between gap-3 px-4 py-6 text-[12.5px] text-muted lg:px-7">
            <x-ui.logo class="text-sm" />
            <div class="flex flex-wrap items-center gap-5">
                <a href="{{ route('features') }}" class="hover:text-ink">{{ __('marketing.nav_features') }}</a>
                <a href="{{ route('login') }}" class="hover:text-ink">{{ __('marketing.nav_login') }}</a>
                <span class="font-mono">{{ __('marketing.footer_domain') }}</span>
            </div>
            <span>{{ __('marketing.footer_rights', ['year' => now()->year]) }}</span>
        </div>
    </footer>
</div>

@livewireScripts
</body>
</html>
