// Marketing pages: a one-shot fade-and-rise as a section first enters the viewport (the
// signature tick-strip's own "bars growing in" is a CSS @keyframes rule in resources/css/
// app.css instead — see its comment for why). The only motion beyond the system's 150ms
// colour/opacity transitions (see docs/brand/upmonia-brand-guidelines.html, "Arayüz" →
// "Hareket", whose one *continuous* animation stays the open-incident pulse; this is a
// one-shot entrance, not a loop). The hidden starting state is only ever set here, in JS,
// never in CSS — so [data-reveal] content stays visible by default with no script, a failed
// script, or prefers-reduced-motion, instead of depending on the animation to reveal it.
(function () {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var fadeTargets = document.querySelectorAll('[data-reveal]');
    var barTargets = document.querySelectorAll('[data-reveal-stagger]');

    if (reduceMotion || ! ('IntersectionObserver' in window) || (! fadeTargets.length && ! barTargets.length)) {
        return;
    }

    fadeTargets.forEach(function (el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity .8s ease-out, transform .8s cubic-bezier(.16,.8,.24,1)';
        if (el.dataset.revealDelay) {
            el.style.transitionDelay = el.dataset.revealDelay;
        }
    });

    // The strip's own pop-in animation is a real CSS @keyframes rule (resources/css/
    // app.css, `tick-pop`), but it only runs once the .is-revealing class is present — the
    // delay has to be set on each bar *before* that class is added, or the browser restarts
    // an already-playing (or already-finished) animation the moment animation-delay changes,
    // which is exactly what made the whole strip visibly play its entrance twice. Gating this
    // on the same IntersectionObserver as the fades (rather than firing on load) also matters
    // for a strip nested inside a card that's still fading in itself: it only pops once its
    // own row is actually visible, instead of finishing off-screen before anyone sees it.
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (! entry.isIntersecting) {
                return;
            }

            var el = entry.target;

            if (el.hasAttribute('data-reveal-stagger')) {
                Array.from(el.children).forEach(function (bar, i) {
                    bar.style.animationDelay = Math.min(i * 9, 350) + 'ms';
                });
                el.classList.add('is-revealing');
            } else {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }

            observer.unobserve(el);
        });
    }, {threshold: 0.15, rootMargin: '0px 0px -40px 0px'});

    fadeTargets.forEach(function (el) {
        observer.observe(el);
    });
    barTargets.forEach(function (el) {
        observer.observe(el);
    });
})();

// Keeps the browser tab live while the user stays on a page: the favicon and the "(n) "
// title prefix reflect the organization's current health without a full page reload. The
// server-rendered <head> already has the right values on first paint (layouts/app.blade.php's
// view composer) — this only updates them afterwards, driven by monitors.index's
// wire:poll.30s re-dispatching 'org-health' on every tick (see its with() method).
document.addEventListener('livewire:init', () => {
    Livewire.on('org-health', ({ status, openIncidents }) => {
        const icon = document.querySelector('link[rel="icon"]');
        if (icon) {
            icon.href = `/favicon-${status}.svg`;
        }

        const bareTitle = document.title.replace(/^\(\d+\)\s*/, '');
        document.title = openIncidents > 0 ? `(${openIncidents}) ${bareTitle}` : bareTitle;

        document.querySelectorAll('[data-open-incidents]').forEach((badge) => {
            badge.textContent = String(openIncidents);
            badge.hidden = openIncidents === 0;
        });

        document.querySelectorAll('[data-org-mark]').forEach((mark) => {
            mark.classList.remove('text-up', 'text-warn', 'text-down');
            mark.classList.add(`text-${status}`);
        });
    });
});
