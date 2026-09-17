@props([
    'title',
    'description' => null,
    'danger' => false,
])

{{-- One simple, reasonably-sized panel per form — not a two-column label/panel split.
     These forms don't carry enough fields to justify that much structure. --}}
<div {{ $attributes->merge(['class' => 'mb-8 max-w-xl']) }}>
    <h3 class="text-[15px] font-semibold {{ $danger ? 'text-down-text' : 'text-ink' }}">{{ $title }}</h3>
    @if ($description)
        <p class="mt-0.5 text-[13px] text-muted">{{ $description }}</p>
    @endif

    <div class="mt-3 rounded-panel border bg-surface p-5 {{ $danger ? 'border-down/30' : 'border-line' }}">
        <div class="grid gap-3.5">
            {{ $slot }}
        </div>
        @isset($actions)
            <div class="mt-5 flex items-center gap-2.5">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>
