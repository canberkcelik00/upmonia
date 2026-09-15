<?php

use App\Models\Incident;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Url]
    public string $filter = 'open';

    public function with(): array
    {
        $incidents = Incident::query()
            ->with('monitor')
            ->when($this->filter === 'open', fn ($q) => $q->where('state', 'open'))
            ->when($this->filter === 'resolved', fn ($q) => $q->where('state', 'resolved'))
            ->orderByDesc('started_at')
            ->paginate(20);

        return ['incidents' => $incidents];
    }
};
?>

<div>
    <h1 class="mb-6 text-lg font-semibold">Olaylar</h1>

    <div class="mb-4 flex items-center gap-2 text-sm">
        <button wire:click="$set('filter', 'open')" class="rounded-md px-3 py-1.5 {{ $filter === 'open' ? 'bg-neutral-900 text-white' : 'border border-neutral-300 text-neutral-700' }}">Açık</button>
        <button wire:click="$set('filter', 'resolved')" class="rounded-md px-3 py-1.5 {{ $filter === 'resolved' ? 'bg-neutral-900 text-white' : 'border border-neutral-300 text-neutral-700' }}">Çözüldü</button>
        <button wire:click="$set('filter', 'all')" class="rounded-md px-3 py-1.5 {{ $filter === 'all' ? 'bg-neutral-900 text-white' : 'border border-neutral-300 text-neutral-700' }}">Tümü</button>
    </div>

    <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-left text-xs uppercase text-neutral-500">
                <tr>
                    <th class="px-4 py-2 font-medium">Monitör</th>
                    <th class="px-4 py-2 font-medium">Durum</th>
                    <th class="px-4 py-2 font-medium">Neden</th>
                    <th class="px-4 py-2 font-medium">Başlangıç</th>
                    <th class="px-4 py-2 font-medium">Süre</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($incidents as $incident)
                    <tr wire:key="incident-{{ $incident->id }}" class="hover:bg-neutral-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('incidents.show', $incident) }}" class="font-medium text-neutral-900 hover:underline">{{ $incident->monitor->name }}</a>
                            @if ($incident->flapping)
                                <span class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Flapping</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $incident->state === 'open' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $incident->state === 'open' ? 'Açık' : 'Çözüldü' }}
                            </span>
                            @if ($incident->acknowledged_at)
                                <span class="ml-1 text-xs text-neutral-500">onaylandı</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-600">{{ \App\Checks\ErrorClassifier::label($incident->cause_class) }}</td>
                        <td class="px-4 py-3 text-neutral-600">{{ $incident->started_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-neutral-600">
                            {{ $incident->duration_s ? \Carbon\CarbonInterval::seconds($incident->duration_s)->cascade()->locale('tr')->forHumans(['short' => true]) : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-neutral-500">Kayıtlı olay yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $incidents->links() }}</div>
</div>
