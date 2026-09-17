<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $orgHealth['openIncidents'] > 0 ? '('.$orgHealth['openIncidents'].') ' : '' }}{{ $title ?? config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon-'.$orgHealth['status'].'.svg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('partials.theme-init')
    @include('partials.time-init')
</head>
<body class="min-h-screen bg-canvas text-ink antialiased">
<div x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">

    <header class="sticky top-0 z-30 h-[52px] border-b border-line bg-surface px-4 lg:px-6">
        <div class="mx-auto flex h-full max-w-[1200px] items-stretch gap-2">
            <button
                type="button"
                @click="menuOpen = ! menuOpen"
                aria-label="{{ __('app.nav_open_menu') }}"
                :aria-expanded="menuOpen"
                class="-ml-1 flex items-center justify-center rounded-control px-1 text-ink-2 hover:bg-surface-2 lg:hidden"
            >
                <x-phosphor-list class="size-5" />
            </button>

            <a href="{{ route('monitors.index') }}" wire:navigate class="flex items-center">
                <x-ui.logo :status="$orgHealth['status']" live class="text-[19px]" />
            </a>

            <div class="hidden lg:flex lg:h-full lg:items-stretch lg:pl-2">
                @include('partials.nav-items')
            </div>

            <div class="ml-auto flex items-center gap-2">
                <span class="hidden items-center gap-2 px-1 text-[13px] font-medium text-ink sm:flex">
                    <span class="flex size-[18px] items-center justify-center rounded-[4px] bg-ink font-mono text-[10.5px] font-bold text-on-ink">{{ mb_strtoupper(mb_substr(auth()->user()->currentOrganization()?->name ?? '?', 0, 1)) }}</span>
                    {{ auth()->user()->currentOrganization()?->name }}
                </span>
                <x-ui.locale-switcher class="hidden sm:block" />
                <x-ui.theme-toggle />

                <x-ui.dropdown>
                    <x-slot:trigger>
                        <button type="button" class="flex size-8 items-center justify-center rounded-full border border-line bg-surface-2 text-[11.5px] font-semibold text-ink-2" aria-label="{{ __('app.nav_account') }}">
                            {{ collect(preg_split('/\s+/', trim((string) auth()->user()->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: '?' }}
                        </button>
                    </x-slot:trigger>
                    @include('partials.nav-account')
                </x-ui.dropdown>
            </div>
        </div>
    </header>

    {{-- Mobile tab panel: below 1024px the tabs collapse behind the menu button above. --}}
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
        @include('partials.nav-items', ['vertical' => true])
    </div>

    <main>
        <div class="mx-auto max-w-[1200px] px-4 py-6 sm:px-6 lg:px-7 lg:py-[22px]">
            {{ $slot }}
        </div>
    </main>
</div>

@livewireScripts
</body>
</html>
