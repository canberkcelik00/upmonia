@props([
    // 'ink' (default, no health context — guest pages) | 'up' | 'warn' | 'down' (organization's
    // current worst status — nav bar, favicon). The wordmark itself never changes colour, only
    // the mark ("şerit" — five bars, see App\Support\BrandMark for the favicon that colours
    // each bar individually). See docs/brand/upmonia-brand-guidelines.html, "Logo".
    'status' => 'ink',
    // Marks the icon for resources/js/app.js's live org-health update (harmless where there's
    // no health context, e.g. the guest layout — it just never receives that event there).
    'live' => false,
])

@php
$iconClass = [
    'up' => 'text-up',
    'warn' => 'text-warn',
    'down' => 'text-down',
    'ink' => 'text-ink',
][$status] ?? 'text-ink';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-[0.28em] font-bold leading-none tracking-[-0.035em] text-ink']) }}>
    <svg viewBox="0 0 24 24" fill="currentColor" class="size-[0.95em] shrink-0 {{ $iconClass }}" @if ($live) data-org-mark @endif aria-hidden="true">
        <rect x="1.5" y="8.5" width="3" height="11.5" rx="1.2" />
        <rect x="6" y="8.5" width="3" height="11.5" rx="1.2" />
        <rect x="10.5" y="8.5" width="3" height="11.5" rx="1.2" />
        <rect x="15" y="8.5" width="3" height="11.5" rx="1.2" />
        <rect x="19.5" y="4" width="3" height="16" rx="1.2" />
    </svg>
    <span>upmonia</span>
</span>
