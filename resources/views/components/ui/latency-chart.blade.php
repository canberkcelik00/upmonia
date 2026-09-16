@props([
    'checks', // any iterable of check results, newest first, each exposing ->ts, ->ok, ->latency_ms
    'height' => 96,
])

@php
    $width = 600;
    $padTop = 10;
    $padBottom = 22;
    // Horizontal inset so the first/last point markers (r=3 + 2px ring) are fully
    // inside the viewBox instead of being half-clipped at the edges.
    $padX = 6;
    $plotH = $height - $padTop - $padBottom;
    $plotW = $width - ($padX * 2);

    $points = collect($checks)->reverse()->values(); // chronological: oldest -> newest
    $withLatency = $points->filter(fn ($c) => $c->latency_ms !== null);

    $rawMax = $withLatency->max('latency_ms') ?? 0;
    $rawMin = $withLatency->min('latency_ms') ?? 0;
    // Every value identical (or a single reading): there is no real range to scale
    // against, so draw the line through the middle rather than pinning it to the floor.
    $isFlat = ($rawMax - $rawMin) <= 0;
    $span = $rawMax - $rawMin;
    $min = $rawMin - $span * 0.15;
    $max = $rawMax + $span * 0.15;
    $range = max($max - $min, 0.0001);

    $count = $points->count();
    $coords = $points->map(function ($c, $i) use ($count, $padX, $plotW, $min, $range, $plotH, $padTop, $isFlat) {
        // A lone point is centred; otherwise spread evenly across the inset plot width.
        $x = $count <= 1
            ? round($padX + $plotW / 2, 1)
            : round($padX + ($i / ($count - 1)) * $plotW, 1);

        $y = $c->latency_ms === null
            ? null
            : ($isFlat
                ? round($padTop + $plotH / 2, 1)
                : round($padTop + $plotH - (($c->latency_ms - $min) / $range) * $plotH, 1));

        return ['x' => $x, 'y' => $y, 'check' => $c];
    });

    $linePoints = $coords->filter(fn ($p) => $p['y'] !== null)->values();
    $pathD = $linePoints->map(fn ($p, $i) => ($i === 0 ? 'M' : 'L').$p['x'].','.$p['y'])->implode(' ');
    $baseline = $height - $padBottom;
    $areaD = $linePoints->isNotEmpty()
        ? $pathD." L{$linePoints->last()['x']},{$baseline} L{$linePoints->first()['x']},{$baseline} Z"
        : '';
    $last = $linePoints->last();
@endphp

@if ($linePoints->isEmpty())
    <x-ui.empty-state icon="chart-line" :title="__('app.chart_no_data')" />
@else
    <div {{ $attributes->merge(['class' => 'relative']) }}>
        {{-- Uniform scaling (no preserveAspectRatio="none"): a non-uniform stretch would
             squash the 2px stroke and turn the round point markers into ellipses. --}}
        <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-auto w-full" role="img" aria-label="{{ __('app.chart_latency_aria') }}">
            <line x1="0" y1="{{ $baseline }}" x2="{{ $width }}" y2="{{ $baseline }}" class="stroke-neutral-200 dark:stroke-neutral-800" stroke-width="1" />
            <path d="{{ $areaD }}" class="fill-brand-500/10" />
            <path d="{{ $pathD }}" fill="none" class="stroke-brand-600 dark:stroke-brand-400" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            @foreach ($coords as $p)
                @if ($p['y'] !== null)
                    <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="8" fill="transparent">
                        <title>{{ $p['check']->ts->toDisplay() }} - {{ $p['check']->latency_ms }} ms</title>
                    </circle>
                    <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="3" stroke-width="2" class="{{ $p['check']->ok ? 'fill-brand-600 dark:fill-brand-400' : 'fill-red-500' }} stroke-white dark:stroke-neutral-900" />
                @endif
            @endforeach
        </svg>
        <div class="pointer-events-none absolute right-0 top-0 rounded-md bg-white/90 px-1.5 py-0.5 font-mono text-[11px] font-medium text-neutral-600 dark:bg-neutral-900/80 dark:text-neutral-400">
            {{ $last['check']->latency_ms }} ms
        </div>
    </div>
@endif
