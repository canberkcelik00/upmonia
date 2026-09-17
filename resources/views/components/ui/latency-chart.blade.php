@props([
    'checks', // any iterable of check results, newest first, each exposing ->ts, ->ok, ->latency_ms
    'timeoutMs' => null, // draws a dashed reference line + label, skipped if it would flatten the real data
    'tone' => 'up', // endpoint dot colour — matches the monitor's current status, not the last check's own result
    'height' => 160,
])

@php
    $width = 600;
    $padLeft = 46;
    $padRight = 10;
    $padTop = 16;
    $padBottom = 22;
    $plotH = $height - $padTop - $padBottom;
    $plotW = $width - $padLeft - $padRight;

    $points = collect($checks)->reverse()->values(); // chronological: oldest -> newest
    $withLatency = $points->filter(fn ($c) => $c->latency_ms !== null);
    $rawMax = $withLatency->max('latency_ms') ?? 0;

    // Axis always starts at 0 — never a zoomed-in range that exaggerates small jitter.
    // The timeout line only draws if it's within 2x the real data; further out, it would
    // flatten the actual latency trend into a barely-visible sliver at the bottom.
    $showTimeout = $timeoutMs && $rawMax > 0 && $timeoutMs <= $rawMax * 2;
    $ceiling = $showTimeout ? max($timeoutMs, $rawMax) : $rawMax;
    $ceiling = max($ceiling, 1);

    // Round the axis top to a "nice" step (1/2/5 × 10^n) so grid labels read as real numbers.
    $rawStep = $ceiling / 3;
    $magnitude = 10 ** floor(log10(max($rawStep, 1)));
    $niceStep = collect([1, 2, 5, 10])->first(fn ($m) => $m * $magnitude >= $rawStep) * $magnitude;
    $axisMax = ceil($ceiling / $niceStep) * $niceStep;
    $axisMax = max($axisMax, $niceStep);

    $y = fn ($ms) => round($padTop + $plotH - ($ms / $axisMax) * $plotH, 1);
    $x = fn ($i, $count) => $count <= 1 ? round($padLeft + $plotW / 2, 1) : round($padLeft + ($i / ($count - 1)) * $plotW, 1);

    $count = $points->count();
    $coords = $points->map(fn ($c, $i) => ['x' => $x($i, $count), 'y' => $c->latency_ms === null ? null : $y($c->latency_ms), 'check' => $c]);
    $linePoints = $coords->filter(fn ($p) => $p['y'] !== null)->values();
    $pathD = $linePoints->map(fn ($p, $i) => ($i === 0 ? 'M' : 'L').$p['x'].','.$p['y'])->implode(' ');
    $baseline = $y(0);
    $areaD = $linePoints->isNotEmpty()
        ? $pathD." L{$linePoints->last()['x']},{$baseline} L{$linePoints->first()['x']},{$baseline} Z"
        : '';
    $last = $linePoints->last();

    $gridSteps = collect(range(0, (int) round($axisMax / $niceStep)))->map(fn ($i) => $i * $niceStep);

    $toneColor = ['up' => 'fill-up', 'warn' => 'fill-warn', 'down' => 'fill-down'][$tone] ?? 'fill-up';
@endphp

@if ($linePoints->isEmpty())
    <x-ui.empty-state icon="chart-line" :title="__('app.chart_no_data')" />
@else
    <div {{ $attributes->merge(['class' => 'relative']) }}>
        {{-- Uniform scaling (no preserveAspectRatio="none"): a non-uniform stretch would
             squash the 2px stroke and turn the round point markers into ellipses. --}}
        <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-auto w-full" role="img" aria-label="{{ __('app.chart_latency_aria') }}">
            @foreach ($gridSteps as $step)
                <line x1="{{ $padLeft }}" y1="{{ $y($step) }}" x2="{{ $width - $padRight }}" y2="{{ $y($step) }}" class="stroke-line" stroke-width="1" />
                <text x="{{ $padLeft - 6 }}" y="{{ $y($step) + 3 }}" text-anchor="end" class="fill-muted font-mono" style="font-size:10px">{{ \App\Support\Format::intGroup((int) $step) }}</text>
            @endforeach

            @if ($showTimeout)
                <line x1="{{ $padLeft }}" y1="{{ $y($timeoutMs) }}" x2="{{ $width - $padRight }}" y2="{{ $y($timeoutMs) }}" class="stroke-line-strong" stroke-width="1" stroke-dasharray="4 4" />
                <text x="{{ $padLeft + 4 }}" y="{{ $y($timeoutMs) - 4 }}" class="fill-muted font-mono" style="font-size:10px">{{ __('app.chart_timeout_limit') }}</text>
            @endif

            <path d="{{ $areaD }}" class="fill-ink" opacity="0.06" />
            <path d="{{ $pathD }}" fill="none" class="stroke-ink" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />

            @foreach ($coords as $p)
                @if ($p['y'] !== null)
                    <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="8" fill="transparent">
                        <title>{{ $p['check']->ts->toDisplay() }} - {{ $p['check']->latency_ms }} ms</title>
                    </circle>
                @endif
            @endforeach

            @if ($last)
                <circle cx="{{ $last['x'] }}" cy="{{ $last['y'] }}" r="4" class="{{ $toneColor }} stroke-surface" stroke-width="2" />
                <text x="{{ min($last['x'], $width - $padRight - 6) }}" y="{{ max($last['y'] - 8, $padTop + 8) }}" text-anchor="end" class="fill-ink font-mono font-medium" style="font-size:11px">{{ \App\Support\Format::ms($last['check']->latency_ms) }}</text>
            @endif
        </svg>
    </div>
@endif
