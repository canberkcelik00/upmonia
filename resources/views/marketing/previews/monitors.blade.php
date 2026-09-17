@php
    $monitors = \App\Support\Marketing\PreviewData::monitors();
    $class ??= '';
@endphp

<div class="overflow-hidden rounded-panel border border-line-strong bg-surface {{ $class }}">
    <div class="border-b border-line px-5 pt-5 pb-4">
        <p class="text-[19px] font-bold tracking-[-0.025em] text-ink">{{ __('marketing.preview_statement') }}</p>
    </div>

    <div class="flex flex-wrap items-center gap-3 border-b border-line bg-down-soft px-5 py-3">
        <span class="relative flex size-2 shrink-0" aria-hidden="true">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-down opacity-60 motion-reduce:animate-none"></span>
            <span class="relative inline-flex size-2 rounded-full bg-down"></span>
        </span>
        <div class="flex min-w-0 flex-1 flex-wrap items-baseline gap-x-2">
            <strong class="font-semibold text-down-text">{{ __('app.status_down') }}</strong>
            <span class="truncate text-[13.5px] text-ink">{{ __('marketing.preview_incident_name') }}</span>
        </div>
        <span class="font-mono text-sm font-medium tabular-nums text-down-text">04:12</span>
    </div>

    <ul class="divide-y divide-line">
        @foreach ($monitors as $monitor)
            <li
                class="flex items-center gap-4 px-5 py-3.5"
                data-reveal
                data-reveal-delay="{{ 0.25 + $loop->index * 0.08 }}s"
            >
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-medium text-ink">{{ $monitor['name'] }}</p>
                    <p class="truncate text-[11.5px] text-muted">{{ $monitor['sub'] }}</p>
                </div>
                <x-ui.tick-strip :tones="$monitor['tones']" :label="$monitor['aria']" class="h-5 w-24 shrink-0" data-reveal-stagger />
                <x-ui.status-pill :status="$monitor['status']" class="shrink-0" />
            </li>
        @endforeach
    </ul>
</div>
