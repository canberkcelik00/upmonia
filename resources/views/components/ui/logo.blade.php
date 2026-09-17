@props([
    // 'ink' (default, no health context — guest pages) | 'up' | 'warn' | 'down' (organization's
    // current worst status — nav bar, favicon). The wordmark itself never changes colour, only
    // the mark. See docs/brand/upvane-brand-guidelines.html, "Uygulama ikonu ve canlı favicon".
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
    <svg viewBox="0 0 24 24" fill="none" class="size-[0.95em] shrink-0 {{ $iconClass }}" @if ($live) data-org-mark @endif aria-hidden="true">
        <path d="M3.5 12.5 9 18 20.5 6.5" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M13.5 6.5h7v7" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
    <span>upvane</span>
</span>
