<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('partials.theme-init')
</head>
<body class="min-h-screen bg-canvas text-ink antialiased">
    <div class="flex min-h-[100dvh] flex-col">
        <div class="flex items-center justify-end gap-1 px-4 pt-4">
            <x-ui.locale-switcher />
            <x-ui.theme-toggle />
        </div>

        <main class="flex flex-1 items-center justify-center px-4 py-10">
            <div class="w-full max-w-sm">
                <div class="mb-7 flex justify-center">
                    <a href="{{ url('/') }}">
                        <x-ui.logo class="text-xl" />
                    </a>
                </div>

                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>
