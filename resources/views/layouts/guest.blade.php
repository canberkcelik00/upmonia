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
    <div class="relative flex min-h-[100dvh] flex-col">
        {{-- A single soft brand wash behind the card: enough to stop the page reading as a
             blank sheet, without becoming a decorative gradient. --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(60rem_24rem_at_50%_-8rem,var(--color-brand-100),transparent)] dark:bg-[radial-gradient(60rem_24rem_at_50%_-8rem,var(--color-brand-900),transparent)]"></div>

        <div class="relative flex items-center justify-end gap-1 px-4 pt-4">
            <x-ui.locale-switcher />
            <x-ui.theme-toggle />
        </div>

        <main class="relative flex flex-1 items-center justify-center px-4 py-10">
            <div class="w-full max-w-sm">
                <div class="mb-7 flex flex-col items-center gap-3 text-center">
                    <a href="{{ url('/') }}" class="flex items-center gap-2.5 text-lg font-semibold tracking-tight">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-white shadow-[var(--shadow-card)]">
                            <x-phosphor-pulse class="size-5" />
                        </span>
                        Uptik
                    </a>
                </div>

                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>
