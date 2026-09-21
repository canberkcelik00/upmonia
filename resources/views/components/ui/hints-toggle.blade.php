<button
    type="button"
    {{-- Flips the stored choice, then defers to window.applyHints() from partials/theme-init,
         same split as x-ui.theme-toggle. --}}
    x-data="{
        on: window.hintsOn(),
        toggle() {
            this.on = ! this.on;
            localStorage.setItem('hints', this.on ? 'on' : 'off');
            window.applyHints();
        },
    }"
    @click="toggle()"
    :aria-pressed="on.toString()"
    :aria-label="on ? @js(__('app.hints_hide')) : @js(__('app.hints_show'))"
    :title="on ? @js(__('app.hints_hide')) : @js(__('app.hints_show'))"
    {{-- Styled off the .hints-off class (set before first paint), not Alpine, so there's no flash. --}}
    class="flex size-8 items-center justify-center rounded-control bg-surface-2 text-ink transition-colors duration-150 hover:bg-surface-2 hover:text-ink in-[.hints-off]:bg-transparent in-[.hints-off]:text-faint in-[.hints-off]:hover:bg-surface-2 in-[.hints-off]:hover:text-ink"
>
    <x-phosphor-question class="size-4" />
</button>
