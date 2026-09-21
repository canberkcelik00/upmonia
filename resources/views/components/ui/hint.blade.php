@props([
    'text', // plain-language explanation shown on hover/focus
    'align' => 'center', // 'start' | 'center' | 'end' — which edge of the term the bubble lines up with
    'underline' => true, // false when the term is already visually distinct (e.g. a status pill)
])

{{-- position: fixed, computed from the term's own box, so the bubble isn't clipped by a
     scrolling ancestor (x-ui.table is overflow-x-auto). Closes on scroll rather than tracking. --}}
<span
    x-data="{
        open: false,
        style: '',
        show() {
            if (window.hintsOn && ! window.hintsOn()) return;
            const r = this.$el.getBoundingClientRect();
            const left = @js($align) === 'start' ? r.left : @js($align) === 'end' ? r.right : r.left + r.width / 2;
            const shift = @js($align) === 'start' ? '0' : @js($align) === 'end' ? '-100%' : '-50%';
            this.style = `top:${r.bottom + 6}px;left:${left}px;transform:translateX(${shift})`;
            this.open = true;
        },
    }"
    x-on:mouseenter="show()"
    x-on:mouseleave="open = false"
    x-on:focus="show()"
    x-on:blur="open = false"
    x-on:scroll.window="open = false"
    tabindex="0"
    {{-- .hints-off on <html> (header toggle, partials/theme-init) turns the term back into plain
         text; the sr-only explanation stays for screen readers either way. --}}
    {{ $attributes->merge(['class' => 'cursor-help focus:outline-none focus-visible:rounded-[2px] focus-visible:ring-2 focus-visible:ring-ink/15 in-[.hints-off]:cursor-auto in-[.hints-off]:no-underline'.($underline ? ' underline decoration-faint decoration-dotted underline-offset-[3px]' : '')]) }}
>{{ $slot }}<span class="sr-only"> — {{ $text }}</span><span
        x-show="open"
        x-cloak
        x-bind:style="style"
        aria-hidden="true"
        class="pointer-events-none fixed z-50 w-max max-w-[240px] whitespace-normal rounded-control border border-line bg-surface px-2.5 py-1.5 text-left font-sans text-[12px] font-normal normal-case leading-snug tracking-normal text-ink-2 shadow-popover"
    >{{ $text }}</span></span>
