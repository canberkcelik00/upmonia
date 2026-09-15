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
    <nav class="border-b border-neutral-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <a href="{{ route('monitors.index') }}" class="text-lg font-semibold tracking-tight">Uptik</a>
            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('monitors.index') }}" class="text-neutral-600 hover:text-neutral-900">Monitörler</a>
                <a href="{{ route('incidents.index') }}" class="text-neutral-600 hover:text-neutral-900">Olaylar</a>
                <a href="{{ route('settings.index') }}" class="text-neutral-600 hover:text-neutral-900">Ayarlar</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-neutral-600 hover:text-neutral-900">Çıkış yap</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-5xl px-4 py-8">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
