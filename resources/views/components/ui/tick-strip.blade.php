@props([
    // Ordered oldest -> newest, one entry per slot: 'up' | 'warn' | 'down' | 'idle' | 'nodata'.
    // Classification lives in App\Support\MonitorPulse — this component only draws it.
    'tones',
    'label', // required: a full sentence for screen readers, e.g. "Son 30 kontrol: 27 başarılı, son 3 başarısız"
    'times' => null, // optional ISO start time per slot (one-minute slots); enables the hover bubble
])

@php
$toneClasses = [
    'up' => 'bg-up',
    'warn' => 'bg-warn',
    'down' => 'bg-down',
    'idle' => 'bg-idle-soft',
    'nodata' => 'bg-nodata',
];
@endphp

@if ($times)
    {{-- One bubble for the whole strip, positioned fixed like x-ui.hint so an overflow-x-auto
         table can't clip it; the hovered bar stays full strength and the rest dim. wire:key
         follows the data so a wire:poll refresh rebuilds the Alpine state instead of keeping it. --}}
    <div
        wire:key="ticks-{{ md5(json_encode([$times, $tones])) }}"
        x-data="{
            i: null,
            style: '',
            times: @js(array_values($times)),
            tones: @js(array_values($tones)),
            labels: @js([
                'up' => __('app.tick_up'), 'warn' => __('app.tick_warn'), 'down' => __('app.tick_down'),
                'idle' => __('app.tick_idle'), 'nodata' => __('app.chart_no_data'),
            ]),
            pick(e) {
                const r = this.$el.getBoundingClientRect();
                const n = this.tones.length;
                this.i = Math.min(n - 1, Math.max(0, Math.floor((e.clientX - r.left) / r.width * n)));
                this.style = `top:${r.bottom + 6}px;left:${r.left + (this.i + 0.5) * r.width / n}px;transform:translateX(-50%)`;
            },
            get text() {
                if (this.i === null) return '';
                const hm = new Intl.DateTimeFormat(document.documentElement.lang || 'en', { hour: '2-digit', minute: '2-digit', hour12: false });
                return hm.format(new Date(this.times[this.i])) + ' · ' + this.labels[this.tones[this.i]];
            },
        }"
        x-on:mousemove="pick($event)"
        x-on:mouseleave="i = null"
        x-on:scroll.window="i = null"
        {{ $attributes->merge(['class' => 'relative flex h-5 items-stretch gap-0.5']) }}
        role="img"
        aria-label="{{ $label }}"
    >
        @foreach ($tones as $tone)
            <span class="min-w-[2px] flex-1 rounded-[1px] transition-opacity duration-100 {{ $toneClasses[$tone] ?? $toneClasses['nodata'] }}" x-bind:class="i !== null && i !== {{ $loop->index }} ? 'opacity-40' : ''"></span>
        @endforeach
        <span
            x-show="i !== null"
            x-cloak
            x-bind:style="style"
            x-text="text"
            aria-hidden="true"
            class="pointer-events-none fixed z-50 w-max rounded-control border border-line bg-surface px-2.5 py-1 font-mono text-[11.5px] text-ink-2 shadow-popover"
        ></span>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'flex h-5 items-stretch gap-0.5']) }} role="img" aria-label="{{ $label }}">
        @foreach ($tones as $tone)
            <span class="min-w-[2px] flex-1 rounded-[1px] {{ $toneClasses[$tone] ?? $toneClasses['nodata'] }}"></span>
        @endforeach
    </div>
@endif
