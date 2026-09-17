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
