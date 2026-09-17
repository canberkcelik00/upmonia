<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @unless ($page->indexable)
        <meta name="robots" content="noindex, nofollow">
    @endunless
    <title>{{ $page->title }} - {{ __('app.status_page_title_suffix') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon-'.match ($overallStatus) { 'operational' => 'up', 'degraded' => 'down', default => 'warn' }.'.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('partials.theme-init')
</head>
<body class="min-h-screen bg-canvas text-ink antialiased">
    <main class="mx-auto max-w-2xl px-4 py-10 sm:py-12">
        <div class="mb-7 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 font-bold tracking-[-0.02em]">
                @if ($page->logo_url)
                    <img src="{{ $page->logo_url }}" alt="" class="size-[30px] rounded-[7px]" loading="lazy">
                @elseif ($page->brand_color)
                    <span class="flex size-[30px] items-center justify-center rounded-[7px] text-[15px] font-bold text-white" style="background-color: {{ $page->brand_color }}">{{ mb_strtoupper(mb_substr($page->title, 0, 1)) }}</span>
                @endif
                <span class="text-lg">{{ $page->title }}</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="hidden text-[13px] text-muted sm:inline">{{ __('app.status_page_service_status') }}</span>
                <x-ui.theme-toggle />
            </div>
        </div>

        <div class="mb-7 flex items-center gap-3.5 rounded-panel border border-line bg-surface px-5 py-4">
            @php
                $stateIcon = match ($overallStatus) {
                    'operational' => ['check', 'up', 'bg-up'],
                    'degraded' => ['warning', 'down', 'bg-down'],
                    default => ['clock', 'idle', 'bg-idle'],
                };
            @endphp
            <span class="flex size-[34px] shrink-0 items-center justify-center rounded-full {{ $stateIcon[2] }}">
                <x-dynamic-component :component="'phosphor-'.$stateIcon[0]" class="size-[18px] text-white" />
            </span>
            <h1 class="flex-1 text-xl font-bold tracking-[-0.02em] text-ink">
                {{ match ($overallStatus) {
                    'operational' => __('app.status_page_all_operational'),
                    'pending' => __('app.status_page_pending'),
                    default => __('app.status_page_degraded'),
                } }}
            </h1>
            <span class="hidden font-mono text-xs text-muted sm:inline">{{ __('app.status_page_last_checked', ['time' => now()->toDisplayTime()]) }}</span>
        </div>

        <div class="mb-7 rounded-panel border border-line bg-surface">
            @forelse ($monitors as $monitor)
                <div class="border-t border-line px-5 py-4 first:border-t-0">
                    <div class="mb-2.5 flex items-center justify-between gap-3">
                        <span class="font-medium">{{ $monitor['name'] }}</span>
                        <span class="font-mono text-xs text-muted">{{ $monitor['percent'] ?? '—' }}</span>
                    </div>
                    <x-ui.tick-strip :tones="$monitor['tones']" :label="__('app.chart_uptime_aria')" class="h-7 gap-px" />
                    @if ($monitor['last_checked_at'])
                        <p class="mt-2 text-xs text-faint">
                            {{ __('app.status_page_last_checked', ['time' => $monitor['last_checked_at']->diffForHumans()]) }}
                        </p>
                    @endif
                </div>
            @empty
                <x-ui.empty-state icon="pulse" :title="__('app.status_page_empty')" />
            @endforelse
        </div>

        @if ($page->show_history && $incidents->isNotEmpty())
            <h2 class="mb-3 text-sm font-semibold text-ink-2">{{ __('app.status_page_history') }}</h2>
            <div class="mb-7 rounded-panel border border-line bg-surface">
                @foreach ($incidents as $incident)
                    <div class="border-t border-line px-5 py-3 text-sm first:border-t-0">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">{{ $incident->monitor->name }}</span>
                            <span class="font-mono text-xs text-muted">{{ $incident->started_at->toDisplayDate() }}</span>
                        </div>
                        <div class="mt-0.5 text-xs text-muted">
                            {{ $incident->state === 'resolved' ? __('app.incident_state_resolved') : __('app.incident_state_open') }}
                            @if ($incident->resolved_at)
                                — {{ __('app.status_page_incident_duration', ['duration' => \App\Support\Format::shortDuration($incident->started_at->diffInSeconds($incident->resolved_at))]) }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <p class="mt-8 flex items-center justify-center gap-1.5 text-xs font-medium text-muted">
            <svg viewBox="0 0 24 24" fill="none" class="size-[13px] text-ink-2" aria-hidden="true">
                <path d="M3.5 12.5 9 18 20.5 6.5" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M13.5 6.5h7v7" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            {{ __('app.status_page_footer') }}
        </p>
    </main>

    @livewireScripts
</body>
</html>
