@props([
    'current',
    'total',
    'message',
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2.5 text-[13px] text-ink-2']) }}>
    <span class="flex gap-[3px]" aria-hidden="true">
        @for ($i = 1; $i <= $total; $i++)
            <span class="h-1.5 w-3.5 rounded-[2px] {{ $i <= $current ? 'bg-warn' : 'bg-nodata' }}"></span>
        @endfor
    </span>
    {{ $message }}
</div>
