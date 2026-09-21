@props([
    'buckets', // hourly buckets, any order: ->ts (hour start), ->p50, ->ok_n, ->fail_n
    'hours' => 24, // window length; one slot per hour, the last slot is the hour in progress
    'timeoutMs' => null, // draws a dashed reference line + label, skipped if it would flatten the real data
    'tone' => 'up', // endpoint dot colour — matches the monitor's current status, not the last bucket's own result
    'height' => 170,
])

@php
    $width = 600;
    $padLeft = 46;
    $padRight = 10;
    $padTop = 14;
    $padBottom = 24;
    $plotH = $height - $padTop - $padBottom;
    $plotW = $width - $padLeft - $padRight;
    $slotW = $plotW / $hours;

    // Fixed time axis: a gap in the data (monitor paused, cron down) stays visible as a gap
    // instead of the remaining points being spread evenly across the width.
    $start = now()->startOfHour()->subHours($hours - 1);
    $byHour = collect($buckets)->keyBy(fn ($b) => $b->ts->copy()->utc()->format('Y-m-d H'));
    $slots = collect(range(0, $hours - 1))->map(function ($i) use ($start, $byHour, $padLeft, $slotW) {
        $t = $start->copy()->addHours($i);
        $b = $byHour->get($t->copy()->utc()->format('Y-m-d H'));

        return [
            't' => $t,
            'x' => round($padLeft + ($i + 0.5) * $slotW, 1),
            'p50' => $b?->p50,
            'ok' => $b->ok_n ?? 0,
            'fail' => $b->fail_n ?? 0,
        ];
    });

    $rawMax = $slots->max('p50') ?? 0;

    // Axis always starts at 0 — never a zoomed-in range that exaggerates small jitter.
    // The timeout line only draws if it's within 2x the real data; further out, it would
    // flatten the actual latency trend into a barely-visible sliver at the bottom.
    $showTimeout = $timeoutMs && $rawMax > 0 && $timeoutMs <= $rawMax * 2;
    $ceiling = max($showTimeout ? max($timeoutMs, $rawMax) : $rawMax, 1);

    // Round the axis top to a "nice" step (1/2/5 × 10^n) so grid labels read as real numbers.
    $rawStep = $ceiling / 3;
    $magnitude = 10 ** floor(log10(max($rawStep, 1)));
    $niceStep = collect([1, 2, 5, 10])->first(fn ($m) => $m * $magnitude >= $rawStep) * $magnitude;
    $axisMax = max(ceil($ceiling / $niceStep) * $niceStep, $niceStep);

    $y = fn ($ms) => round($padTop + $plotH - ($ms / $axisMax) * $plotH, 1);
    $baseline = $y(0);

    // Consecutive hours with data form one line segment; an empty hour breaks the line.
    $segments = [];
    $current = [];
    foreach ($slots as $s) {
        if ($s['p50'] === null) {
            if ($current) {
                $segments[] = $current;
            }
            $current = [];

            continue;
        }
        $current[] = ['x' => $s['x'], 'y' => $y($s['p50'])];
    }
    if ($current) {
        $segments[] = $current;
    }

    $last = $slots->filter(fn ($s) => $s['p50'] !== null)->last();
    $hasData = $last !== null;

    // 24h: a label every 6 hours, counted back from the current hour so the ticks stay on
    // whole hours. 7d: one per day at midnight UTC.
    $ticks = $slots->keys()->filter(fn ($i) => $hours <= 24
        ? ($hours - 1 - $i) % 6 === 0
        : (int) $slots[$i]['t']->copy()->utc()->format('G') === 0
    );

    $toneColor = ['up' => 'fill-up', 'warn' => 'fill-warn', 'down' => 'fill-down'][$tone] ?? 'fill-up';

    $js = $slots->map(fn ($s) => [
        'x' => round($s['x'] / $width * 100, 2),
        't' => $s['t']->copy()->utc()->toIso8601String(),
        'p50' => $s['p50'],
        'ok' => $s['ok'],
        'fail' => $s['fail'],
    ])->values();
@endphp

@if (! $hasData)
    <x-ui.empty-state icon="chart-line" :title="__('app.chart_no_data')" />
@else
    {{-- wire:key changes with the data so a poll re-render rebuilds the Alpine state
         instead of morphing new markup under stale x-data. --}}
    <div
        wire:key="latency-chart-{{ md5($js->toJson()) }}"
        x-data="{
            slots: @js($js),
            hours: {{ $hours }},
            active: null,
            locale: document.documentElement.lang || 'en',
            pick(e) {
                const r = this.$refs.svg.getBoundingClientRect();
                const vx = (e.clientX - r.left) / r.width * {{ $width }};
                const i = Math.floor((vx - {{ $padLeft }}) / {{ $slotW }});
                this.active = i >= 0 && i < this.slots.length ? i : null;
            },
            get slot() { return this.active === null ? null : this.slots[this.active] },
            ms(v) { return new Intl.NumberFormat(this.locale).format(v) + ' ms' },
            range(s) {
                const from = new Date(s.t), to = new Date(from.getTime() + 3600000);
                const hm = new Intl.DateTimeFormat(this.locale, { hour: '2-digit', minute: '2-digit', hour12: false });
                const day = this.hours > 24 ? new Intl.DateTimeFormat(this.locale, { day: 'numeric', month: 'short' }).format(from) + ' ' : '';
                return day + hm.format(from) + '–' + hm.format(to);
            },
        }"
        {{ $attributes->merge(['class' => 'relative']) }}
    >
        {{-- Uniform scaling (no preserveAspectRatio="none"): a non-uniform stretch would
             squash the 2px stroke and turn the round point markers into ellipses. --}}
        <svg
            x-ref="svg"
            x-on:mousemove="pick($event)"
            x-on:mouseleave="active = null"
            viewBox="0 0 {{ $width }} {{ $height }}"
            class="h-auto w-full"
            role="img"
            aria-label="{{ __('app.chart_latency_aria') }}"
        >
            @foreach (collect(range(0, (int) round($axisMax / $niceStep)))->map(fn ($i) => $i * $niceStep) as $step)
                <line x1="{{ $padLeft }}" y1="{{ $y($step) }}" x2="{{ $width - $padRight }}" y2="{{ $y($step) }}" class="stroke-line" stroke-width="1" />
                <text x="{{ $padLeft - 6 }}" y="{{ $y($step) + 3 }}" text-anchor="end" class="fill-muted font-mono" style="font-size:10px">{{ \App\Support\Format::intGroup((int) $step) }}</text>
            @endforeach

            @foreach ($ticks as $i)
                @php($tickX = round($padLeft + $i * $slotW, 1))
                <line x1="{{ $tickX }}" y1="{{ $baseline }}" x2="{{ $tickX }}" y2="{{ $baseline + 4 }}" class="stroke-line-strong" stroke-width="1" />
                {{-- A <tspan>, not toDisplayHtml()'s <span> (not valid inside SVG text); same
                     data attributes, so time-init still relabels it in the viewer's timezone. --}}
                <text x="{{ $tickX }}" y="{{ $height - 6 }}" text-anchor="middle" class="fill-muted font-mono" style="font-size:10px"><tspan data-x-time="{{ $hours > 24 ? 'day' : 'hm' }}" data-x-time-utc="{{ $slots[$i]['t']->copy()->utc()->toIso8601String() }}">{{ $hours > 24 ? $slots[$i]['t']->locale(app()->getLocale())->translatedFormat('j M') : $slots[$i]['t']->format('H:i') }}</tspan></text>
            @endforeach

            @if ($showTimeout)
                <line x1="{{ $padLeft }}" y1="{{ $y($timeoutMs) }}" x2="{{ $width - $padRight }}" y2="{{ $y($timeoutMs) }}" class="stroke-line-strong" stroke-width="1" stroke-dasharray="4 4" />
                <text x="{{ $padLeft + 4 }}" y="{{ $y($timeoutMs) - 4 }}" class="fill-muted font-mono" style="font-size:10px">{{ __('app.chart_timeout_limit') }}</text>
            @endif

            @foreach ($segments as $seg)
                @php($d = collect($seg)->map(fn ($p, $i) => ($i === 0 ? 'M' : 'L').$p['x'].','.$p['y'])->implode(' '))
                @if (count($seg) > 1)
                    <path d="{{ $d }} L{{ end($seg)['x'] }},{{ $baseline }} L{{ $seg[0]['x'] }},{{ $baseline }} Z" class="fill-ink" opacity="0.06" />
                    <path d="{{ $d }}" fill="none" class="stroke-ink" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                @else
                    <circle cx="{{ $seg[0]['x'] }}" cy="{{ $seg[0]['y'] }}" r="2.25" class="fill-ink" />
                @endif
            @endforeach

            {{-- Hours with at least one failed check: a red mark on the baseline, so a latency
                 spike and an outage can be read against each other. --}}
            @foreach ($slots as $s)
                @if ($s['fail'] > 0)
                    <rect x="{{ round($s['x'] - max($slotW - 2, 2) / 2, 1) }}" y="{{ $baseline - 3 }}" width="{{ round(max($slotW - 2, 2), 1) }}" height="3" rx="1" class="fill-down" />
                @endif
            @endforeach

            <g x-show="active !== null" x-cloak>
                <line x-bind:x1="slot ? slot.x / 100 * {{ $width }} : 0" x-bind:x2="slot ? slot.x / 100 * {{ $width }} : 0" y1="{{ $padTop }}" y2="{{ $baseline }}" class="stroke-line-strong" stroke-width="1" />
                <circle x-show="slot && slot.p50 !== null" x-bind:cx="slot ? slot.x / 100 * {{ $width }} : 0" x-bind:cy="slot && slot.p50 !== null ? {{ $padTop + $plotH }} - slot.p50 / {{ $axisMax }} * {{ $plotH }} : 0" r="3.5" class="fill-ink stroke-surface" stroke-width="2" />
            </g>

            <circle cx="{{ $last['x'] }}" cy="{{ $y($last['p50']) }}" r="4" class="{{ $toneColor }} stroke-surface" stroke-width="2" x-show="active === null" />
        </svg>

        <div
            x-show="slot"
            x-cloak
            class="pointer-events-none absolute top-1 z-10 min-w-36 rounded-control border border-line bg-surface px-3 py-2 text-[12px] shadow-popover"
            x-bind:style="slot && { left: slot.x + '%', transform: 'translateX(' + (slot.x < 18 ? '0' : slot.x > 82 ? '-100%' : '-50%') + ')' }"
        >
            <template x-if="slot">
                <div>
                    <div class="mb-1 font-mono text-[11px] text-muted" x-text="range(slot)"></div>
                    <template x-if="slot.p50 === null">
                        <div class="text-muted">{{ __('app.chart_no_data') }}</div>
                    </template>
                    <template x-if="slot.p50 !== null">
                        <div>
                            <div class="flex items-baseline justify-between gap-4">
                                <span class="text-muted">{{ __('app.chart_tooltip_response') }}</span>
                                <span class="font-mono text-ink" x-text="ms(slot.p50)"></span>
                            </div>
                            <div
                                class="mt-0.5 whitespace-nowrap"
                                x-bind:class="slot.fail > 0 ? 'text-down-text' : 'text-muted'"
                                x-text="slot.fail > 0
                                    ? @js(__('app.chart_tooltip_some_failed')).replace(':failed', slot.fail).replace(':total', slot.ok + slot.fail)
                                    : @js(__('app.chart_tooltip_all_ok'))"
                            ></div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 px-4 text-[11.5px] text-muted">
            <span class="flex items-center gap-1.5"><span class="h-0.5 w-3 rounded-full bg-ink"></span><x-ui.hint :text="__('app.chart_legend_median_hint')" align="start">{{ __('app.chart_legend_median') }}</x-ui.hint></span>
            <span class="flex items-center gap-1.5"><span class="h-[3px] w-3 rounded-full bg-down"></span>{{ __('app.chart_legend_failed') }}</span>
            @if ($showTimeout)
                <span class="flex items-center gap-1.5"><span class="w-3 border-t border-dashed border-line-strong"></span>{{ __('app.chart_timeout_limit') }}</span>
            @endif
        </div>
    </div>
@endif
