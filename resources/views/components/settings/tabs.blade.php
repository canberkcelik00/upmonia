@php $current = request()->route()->getName(); @endphp
<div class="mb-6 flex items-center gap-1 border-b border-neutral-200 text-sm">
    @foreach ([
        'settings.index' => 'Hesap',
        'settings.channels' => 'Bildirim kanalları',
        'settings.clients' => 'Müşteriler',
        'settings.maintenance-windows' => 'Bakım pencereleri',
        'settings.status-pages' => 'Durum sayfaları',
    ] as $route => $label)
        <a href="{{ route($route) }}"
           class="border-b-2 px-3 py-2 {{ $current === $route ? 'border-neutral-900 font-medium text-neutral-900' : 'border-transparent text-neutral-500 hover:text-neutral-800' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
