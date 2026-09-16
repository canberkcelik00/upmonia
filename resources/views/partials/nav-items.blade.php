@php
    $items = [
        ['route' => 'monitors.index', 'pattern' => 'monitors.*', 'icon' => 'pulse', 'label' => __('app.nav_monitors')],
        ['route' => 'incidents.index', 'pattern' => 'incidents.*', 'icon' => 'warning', 'label' => __('app.nav_incidents')],
        ['route' => 'settings.index', 'pattern' => 'settings.*', 'icon' => 'gear', 'label' => __('app.nav_settings')],
    ];
@endphp

<nav class="flex flex-col gap-1">
    @foreach ($items as $item)
        @php $active = request()->routeIs($item['pattern']); @endphp
        <a
            href="{{ route($item['route']) }}"
            wire:navigate
            @if ($active) aria-current="page" @endif
            class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150 {{ $active
                ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-300'
                : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100' }}"
        >
            <x-dynamic-component
                :component="'phosphor-'.$item['icon']"
                class="size-[1.125rem] shrink-0 {{ $active ? 'text-brand-600 dark:text-brand-400' : 'text-neutral-400 group-hover:text-neutral-500 dark:text-neutral-500 dark:group-hover:text-neutral-400' }}"
            />
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
