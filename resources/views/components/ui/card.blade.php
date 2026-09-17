@props([
    'padding' => 'p-5',
    'title' => null,
])

{{-- Panel: the one container shape in the system (rounded-panel, border, no shadow — shadow
     is reserved for things that float above the page). When $title is set, a header row with
     a bottom rule separates it from the body; pair it with $titleMeta for a small mono aside
     (e.g. "son 24 saat · saatlik medyan"). --}}
<div {{ $attributes->merge(['class' => "rounded-panel border border-line bg-surface $padding"]) }}>
    @if ($title)
        <div class="mb-4 flex items-center justify-between border-b border-line pb-3">
            <h3 class="text-[13.5px] font-semibold text-ink">{{ $title }}</h3>
            @isset($titleMeta)
                <span class="font-mono text-[11.5px] text-muted">{{ $titleMeta }}</span>
            @endisset
        </div>
    @endif
    {{ $slot }}
</div>
