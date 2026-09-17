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

    // The strip's own growing-in animation is a real CSS @keyframes rule (resources/css/
    // app.css, `tick-grow`) that plays on its own — this only staggers each bar ~24ms apart.
    // Skipped under reduced motion, though the global reduced-motion rule already collapses
    // the animation to ~instant even without this.
    if (! reduceMotion) {
        document.querySelectorAll('[data-reveal-stagger]').forEach(function (el) {
            Array.from(el.children).forEach(function (bar, i) {
                bar.style.animationDelay = Math.min(i * 24, 650) + 'ms';
            });
        });
    }

    var targets = document.querySelectorAll('[data-reveal]');

    if (! targets.length || reduceMotion || ! ('IntersectionObserver' in window)) {
        return;
    }

    targets.forEach(function (el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity .8s ease-out, transform .8s cubic-bezier(.16,.8,.24,1)';
        if (el.dataset.revealDelay) {
            el.style.transitionDelay = el.dataset.revealDelay;
        }
    });

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (! entry.isIntersecting) {
                return;
            }

            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
            observer.unobserve(entry.target);
        });
    }, {threshold: 0.15, rootMargin: '0px 0px -40px 0px'});

    targets.forEach(function (el) {
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
