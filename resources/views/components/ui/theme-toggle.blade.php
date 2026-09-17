<button
    type="button"
    {{-- Flips the stored choice, then defers to window.applyTheme() from partials/theme-init
         so the resolve-and-apply logic lives in exactly one place. --}}
    x-data="{
        theme: window.resolveTheme(),
        toggle() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('theme', this.theme);
            window.applyTheme();
        },
    }"
    @click="toggle()"
    :aria-label="theme === 'dark' ? @js(__('app.theme_to_light')) : @js(__('app.theme_to_dark'))"
    :title="theme === 'dark' ? @js(__('app.theme_to_light')) : @js(__('app.theme_to_dark'))"
    class="flex size-8 items-center justify-center rounded-control text-ink-2 transition-colors duration-150 hover:bg-surface-2 hover:text-ink"
>
    {{-- Shows where a click takes you: the moon in light mode, the sun in dark mode. --}}
    {{-- Driven by the .dark class (set before first paint), not Alpine, so there's no icon flash. --}}
    <x-phosphor-moon class="size-4 dark:hidden" />
    <x-phosphor-sun class="hidden size-4 dark:block" />
</button>
