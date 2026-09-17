@props([
    // Ordered oldest -> newest, one entry per slot: 'up' | 'warn' | 'down' | 'idle' | 'nodata'.
    // Classification lives in App\Support\MonitorPulse — this component only draws it.
    'tones',
    'label', // required: a full sentence for screen readers, e.g. "Son 30 kontrol: 27 başarılı, son 3 başarısız"
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

<div {{ $attributes->merge(['class' => 'flex h-5 items-stretch gap-0.5']) }} role="img" aria-label="{{ $label }}">
    @foreach ($tones as $tone)
        <span class="min-w-[2px] flex-1 rounded-[1px] {{ $toneClasses[$tone] ?? $toneClasses['nodata'] }}"></span>
    @endforeach
</div>
