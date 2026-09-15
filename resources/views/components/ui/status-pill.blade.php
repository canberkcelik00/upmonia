@php
$labels = [
    'pending' => 'Bekliyor', 'up' => 'Çalışıyor', 'suspect' => 'Şüpheli',
    'down' => 'Kesinti', 'recovering' => 'Düzeliyor', 'paused' => 'Duraklatıldı',
];
$colors = [
    'pending' => 'bg-neutral-100 text-neutral-600',
    'up' => 'bg-emerald-100 text-emerald-700',
    'suspect' => 'bg-amber-100 text-amber-700',
    'down' => 'bg-red-100 text-red-700',
    'recovering' => 'bg-amber-100 text-amber-700',
    'paused' => 'bg-neutral-100 text-neutral-500',
];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.($colors[$status] ?? $colors['pending'])]) }}>
    {{ $labels[$status] ?? $status }}
</span>
