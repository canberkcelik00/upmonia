@props([
    // Raw mode (monitor detail page): individual check results, newest first,
    // each exposing ->ts (Carbon) and ->ok (bool). One bar per check.
    'checks' => null,
    // Daily mode (public status page): pre-aggregated day buckets, oldest first,
    // each ['date' => Carbon, 'total' => int, 'failed' => int]. One bar per day.
    'days' => null,
    'slots' => 40,
])

@php
    $dayMode = $days !== null;
    $ordered = $dayMode
        ? collect($days)->values()
        : collect($checks)->reverse()->values(); // oldest -> newest

    $padCount = max($slots - $ordered->count(), 0);
    $bars = collect(array_fill(0, $padCount, null))->concat($ordered);

    $describe = function ($bar) use ($dayMode) {
        if ($bar === null) {
            return ['color' => 'bg-neutral-100 dark:bg-neutral-800', 'title' => __('app.chart_no_data')];
        }

        if ($dayMode) {
            $total = $bar['total'] ?? 0;
            $failed = $bar['failed'] ?? 0;
            $date = $bar['date']->toDisplayDate();

            if ($total === 0) {
                return ['color' => 'bg-neutral-100 dark:bg-neutral-800', 'title' => "$date - ".__('app.chart_no_data')];
            }

            $ratio = $failed / $total;

            return match (true) {
                $ratio === 0.0 => ['color' => 'bg-emerald-500', 'title' => "$date - ".__('app.chart_uptime_full')],
                $ratio < 0.5 => ['color' => 'bg-amber-500', 'title' => "$date - ".__('app.chart_uptime_partial')],
                default => ['color' => 'bg-red-500', 'title' => "$date - ".__('app.chart_uptime_down')],
            };
        }

        return [
            'color' => $bar->ok ? 'bg-emerald-500' : 'bg-red-500',
            'title' => $bar->ts->toDisplay().' - '.($bar->ok ? __('app.chart_ok') : __('app.chart_fail')),
        ];
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex items-end gap-0.5']) }} role="img" aria-label="{{ __('app.chart_uptime_aria') }}">
    @foreach ($bars as $bar)
        @php $d = $describe($bar); @endphp
        <div title="{{ $d['title'] }}" class="h-6 min-w-[3px] flex-1 rounded-sm {{ $d['color'] }}"></div>
    @endforeach
</div>
