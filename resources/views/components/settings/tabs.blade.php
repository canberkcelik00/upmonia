@php $current = request()->route()->getName(); @endphp
<div class="mb-6 flex items-center gap-1 overflow-x-auto border-b border-line text-sm">
    @foreach ([
        'settings.index' => __('app.settings_tab_account'),
        'settings.channels' => __('app.settings_tab_channels'),
        'settings.clients' => __('app.settings_tab_clients'),
        'settings.maintenance-windows' => __('app.settings_tab_maintenance'),
        'settings.status-pages' => __('app.settings_tab_status_pages'),
    ] as $route => $label)
        <a href="{{ route($route) }}" wire:navigate
           class="shrink-0 border-b-2 px-3 py-2 whitespace-nowrap transition-colors duration-150 {{ $current === $route ? 'border-ink font-medium text-ink' : 'border-transparent text-muted hover:text-ink' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
