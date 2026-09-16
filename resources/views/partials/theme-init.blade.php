{{-- Single source of truth for the light/dark class on <html>.

     Runs immediately (before first paint) to avoid a flash, and again after every
     wire:navigate page swap: the theme lives only in localStorage, so server-rendered
     HTML never carries the `dark` class and Livewire would otherwise drop it when it
     applies the new document's <html> attributes. --}}
<script>
    (function () {
        function resolve() {
            var stored = localStorage.getItem('theme');
            return stored === 'light' || stored === 'dark' ? stored : 'system';
        }

        function apply() {
            var theme = resolve();
            var isDark = theme === 'dark'
                || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', isDark);
            return theme;
        }

        window.applyTheme = apply;
        apply();

        document.addEventListener('livewire:navigated', apply);

        // Follow the OS while the page is open, but only when the user chose "system".
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
            if (resolve() === 'system') {
                apply();
            }
        });
    })();
</script>
