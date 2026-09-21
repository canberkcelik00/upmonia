{{-- Renders every [data-x-time-utc] element in the viewer's own timezone, not the
     server's. The server emits an app-timezone fallback (see toDisplay*/toDisplayHtml
     Carbon macros) for no-JS clients; this script swaps in the local equivalent using
     the page's own locale, keyed off the UTC instant in data-x-time-utc.

     The element starts hidden (see the inline style below) so a hard load never shows
     the wrong (server-timezone) number before flipping to the right one — same flash
     this avoids for theme via partials/theme-init.blade.php, just for time instead of
     color. <noscript> restores visibility for the rare no-JS visitor, who then only
     ever sees the server-timezone fallback text.

     A MutationObserver (not just livewire:navigated) covers wire:poll-driven re-renders
     too — monitors.index polls every 30s, so "last checked" needs to relocalize on every
     tick, not just on full-page navigation. --}}
<style>[data-x-time-utc] { visibility: hidden; }</style>
<noscript><style>[data-x-time-utc] { visibility: visible !important; }</style></noscript>
<script>
    (function () {
        var locale = document.documentElement.lang || 'en';

        var formatters = {
            datetime: new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false }),
            date: new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'long', year: 'numeric' }),
            time: new Intl.DateTimeFormat(locale, { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }),
        };

        // Writes only when something differs, so re-formatting from the observer below
        // can't feed back into itself.
        function format(el) {
            var formatter = formatters[el.dataset.xTime] || formatters.datetime;
            var date = new Date(el.dataset.xTimeUtc);

            if (!isNaN(date.getTime())) {
                var text = formatter.format(date);

                if (el.textContent !== text) {
                    el.textContent = text;
                }
            }

            if (el.style.visibility !== 'visible') {
                el.style.visibility = 'visible';
            }
        }

        function closestTime(node) {
            var el = node.nodeType === 1 ? node : node.parentElement;

            return el && el.closest ? el.closest('[data-x-time-utc]') : null;
        }

        function localize(root) {
            (root || document).querySelectorAll('[data-x-time-utc]').forEach(format);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () { localize(); });
        } else {
            localize();
        }

        document.addEventListener('livewire:navigated', function () { localize(); });

        // Livewire morphs existing elements in place rather than re-adding them: it resets the
        // text to the server fallback and strips the inline visibility, which a childList-only
        // watch on added elements misses — so also re-format whatever time element was touched.
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                var touched = closestTime(mutation.target);

                if (touched) {
                    format(touched);
                }

                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) {
                        return;
                    }

                    if (node.matches && node.matches('[data-x-time-utc]')) {
                        format(node);
                    }

                    if (node.querySelectorAll) {
                        localize(node);
                    }
                });
            });
        }).observe(document.documentElement, {
            childList: true,
            subtree: true,
            characterData: true,
            attributes: true,
            attributeFilter: ['style', 'data-x-time-utc'],
        });
    })();
</script>
