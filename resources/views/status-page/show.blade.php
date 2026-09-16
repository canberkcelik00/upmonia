<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @unless ($page->indexable)
        <meta name="robots" content="noindex, nofollow">
    @endunless
    <title>{{ $page->title }} - {{ __('app.status_page_title_suffix') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('partials.theme-init')
</head>
<body class="min-h-screen bg-neutral-50 text-neutral-900 antialiased dark:bg-neutral-950 dark:text-neutral-100">
    {{-- A customer's brand colour gets a single, restrained touch point (a top accent
         bar) rather than re-theming the page — status semantics (up/down colours) and
         the product's own accent stay untouched. --}}
    @if ($page->brand_color)
        <div class="h-1" style="background-color: {{ $page->brand_color }}"></div>
    @endif

    <main class="mx-auto max-w-2xl px-4 py-12">
        <div class="mb-8 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                @if ($page->logo_url)
                    <img src="{{ $page->logo_url }}" alt="" class="h-8 w-8 rounded-md" loading="lazy">
                @endif
                <h1 class="text-2xl font-semibold tracking-tight text-neutral-900 dark:text-neutral-50">{{ $page->title }}</h1>
            </div>
            <x-ui.theme-toggle />
        </div>

        <x-ui.alert :variant="match ($overallStatus) { 'operational' => 'success', 'pending' => 'info', default => 'warning' }" class="mb-8">
            {{ match ($overallStatus) {
                'operational' => __('app.status_page_all_operational'),
                'pending' => __('app.status_page_pending'),
                default => __('app.status_page_degraded'),
            } }}
        </x-ui.alert>

        <x-ui.card padding="p-0" class="mb-8 overflow-hidden">
            <ul class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse ($monitors as $monitor)
                    <li class="px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">{{ $monitor['name'] }}</span>
                            <x-ui.status-pill :status="$monitor['status']" />
                        </div>
                        <div class="mt-3 flex items-end justify-between gap-3">
                            <x-ui.uptime-bar :days="$monitor['days']" :slots="45" />
                        </div>
                        @if ($monitor['last_checked_at'])
                            <p class="mt-2 text-xs text-neutral-400 dark:text-neutral-600">
                                {{ __('app.status_page_last_checked', ['time' => $monitor['last_checked_at']->diffForHumans()]) }}
                            </p>
                        @endif
                    </li>
                @empty
                    <li>
                        <x-ui.empty-state icon="pulse" :title="__('app.status_page_empty')" />
                    </li>
                @endforelse
            </ul>
        </x-ui.card>

        @if ($page->show_history && $incidents->isNotEmpty())
            <h2 class="mb-3 text-sm font-semibold text-neutral-700 dark:text-neutral-300">{{ __('app.status_page_history') }}</h2>
            <x-ui.card padding="p-0" class="mb-8 overflow-hidden">
                <ul class="divide-y divide-neutral-100 text-sm dark:divide-neutral-800">
                    @foreach ($incidents as $incident)
                        <li class="px-5 py-3.5">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">{{ $incident->monitor->name }}</span>
                                <span class="font-mono text-xs text-neutral-500 dark:text-neutral-400">{{ $incident->started_at->toDisplay() }}</span>
                            </div>
                            <div class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $incident->state === 'resolved' ? __('app.incident_state_resolved') : __('app.incident_state_open') }}
                                @if ($incident->resolved_at)
                                    {{ __('app.status_page_incident_duration', ['duration' => $incident->started_at->diffForHumans($incident->resolved_at, true)]) }}
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        @endif

        <p class="mt-8 text-center text-xs text-neutral-400 dark:text-neutral-600">{{ __('app.status_page_footer') }}</p>
    </main>

    @livewireScripts
</body>
</html>
