@php
    $items = [
        ['route' => 'monitors.index', 'pattern' => 'monitors.*', 'label' => __('app.nav_monitors')],
        ['route' => 'incidents.index', 'pattern' => 'incidents.*', 'label' => __('app.nav_incidents'), 'count' => $orgHealth['openIncidents'] ?? 0],
        ['route' => 'settings.index', 'pattern' => 'settings.*', 'label' => __('app.nav_settings')],
    ];
    $vertical ??= false;
@endphp

<nav class="{{ $vertical ? 'flex flex-col gap-1' : 'flex h-full items-stretch gap-1' }}">
    @foreach ($items as $item)
        @php $active = request()->routeIs($item['pattern']); @endphp
        <a
            href="{{ route($item['route']) }}"
            wire:navigate
            @if ($active) aria-current="page" @endif
            @class([
                'flex items-center gap-1.5 font-medium transition-colors duration-150',
                'rounded-control px-3 py-2 text-sm' => $vertical,
                'bg-surface-2 text-ink' => $vertical && $active,
                'text-ink-2 hover:bg-surface-2' => $vertical && ! $active,
                '-mb-px border-b-2 px-1 text-[13.5px]' => ! $vertical,
                'border-ink text-ink' => ! $vertical && $active,
                'border-transparent text-muted hover:text-ink' => ! $vertical && ! $active,
            ])
        >
            {{ $item['label'] }}
            @if (isset($item['count']))
                <span
                    data-open-incidents
                    @if (empty($item['count'])) hidden @endif
                    class="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-badge px-1 font-mono text-[11px] font-semibold text-on-badge"
                >{{ $item['count'] }}</span>
            @endif
        </a>
    @endforeach
</nav>
