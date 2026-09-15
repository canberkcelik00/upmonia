<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-neutral-50 text-neutral-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-8 text-center">
                <a href="{{ url('/') }}" class="text-xl font-semibold tracking-tight">Uptik</a>
            </div>

            {{ $slot }}
        </div>
    </main>

    @livewireScripts
</body>
</html>
