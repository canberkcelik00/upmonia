@php $current = request()->route()->getName(); @endphp
<div class="mb-6 flex items-center gap-1 overflow-x-auto border-b border-neutral-200 text-sm dark:border-neutral-800">
    @foreach ([
        'settings.index' => __('app.settings_tab_account'),
        'settings.channels' => __('app.settings_tab_channels'),
        'settings.clients' => __('app.settings_tab_clients'),
        'settings.maintenance-windows' => __('app.settings_tab_maintenance'),
        'settings.status-pages' => __('app.settings_tab_status_pages'),
    ] as $route => $label)
        <a href="{{ route($route) }}" wire:navigate
           class="shrink-0 border-b-2 px-3 py-2 whitespace-nowrap transition-colors duration-150 {{ $current === $route ? 'border-brand-600 font-medium text-neutral-900 dark:border-brand-400 dark:text-neutral-100' : 'border-transparent text-neutral-500 hover:text-neutral-800 dark:text-neutral-400 dark:hover:text-neutral-200' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
