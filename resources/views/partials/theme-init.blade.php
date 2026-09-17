{{-- Single source of truth for the light/dark class on <html>.

     Runs immediately (before first paint) to avoid a flash, and again after every
     wire:navigate page swap: the theme lives only in localStorage, so server-rendered
     HTML never carries the `dark` class and Livewire would otherwise drop it when it
     applies the new document's <html> attributes. --}}
<script>
    (function () {
        // Only light and dark are choosable. Dark is the default until the user picks light
        // (a stale 'system' value from older builds also falls back to dark).
        function resolve() {
            return localStorage.getItem('theme') === 'light' ? 'light' : 'dark';
        }

        function apply() {
            var theme = resolve();
            document.documentElement.classList.toggle('dark', theme === 'dark');
            return theme;
        }

        window.resolveTheme = resolve;
        window.applyTheme = apply;
        apply();

        document.addEventListener('livewire:navigated', apply);
    })();
</script>
