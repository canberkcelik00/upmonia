<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->title }} — Durum</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-50 text-neutral-900 antialiased">
    <main class="mx-auto max-w-2xl px-4 py-12">
        <div class="mb-8 flex items-center gap-3">
            @if ($page->logo_url)
                <img src="{{ $page->logo_url }}" alt="" class="h-8 w-8 rounded">
            @endif
            <h1 class="text-xl font-semibold">{{ $page->title }}</h1>
        </div>

        <div class="mb-8 rounded-lg border px-4 py-3 text-sm font-medium
            {{ $overallStatus === 'operational' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
            {{ $overallStatus === 'operational' ? 'Tüm sistemler çalışıyor' : 'Bazı sistemlerde sorun var' }}
        </div>

        <div class="mb-8 overflow-hidden rounded-lg border border-neutral-200 bg-white">
            <ul class="divide-y divide-neutral-100">
                @forelse ($monitors as $monitor)
                    <li class="flex items-center justify-between px-4 py-3 text-sm">
                        <span class="font-medium">{{ $monitor['name'] }}</span>
                        <x-ui.status-pill :status="$monitor['status']" />
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-sm text-neutral-500">Bu sayfada henüz izlenen bir sistem yok.</li>
                @endforelse
            </ul>
        </div>

        @if ($page->show_history && $incidents->isNotEmpty())
            <h2 class="mb-3 text-sm font-semibold text-neutral-700">Geçmiş olaylar</h2>
            <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white">
                <ul class="divide-y divide-neutral-100 text-sm">
                    @foreach ($incidents as $incident)
                        <li class="px-4 py-3">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">{{ $incident->monitor->name }}</span>
                                <span class="text-xs text-neutral-500">{{ $incident->started_at->format('Y-m-d H:i') }}</span>
                            </div>
                            <div class="mt-1 text-xs text-neutral-500">
                                {{ $incident->state === 'resolved' ? 'Çözüldü' : 'Devam ediyor' }}
                                @if ($incident->resolved_at) — {{ $incident->started_at->diffForHumans($incident->resolved_at, true) }} sürdü @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="mt-8 text-center text-xs text-neutral-400">Uptik ile desteklenmektedir</p>
    </main>
</body>
</html>
