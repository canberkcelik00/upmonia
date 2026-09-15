<?php

use App\Models\Incident;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Incident $incident;

    public function mount(Incident $incident): void
    {
        $this->incident = $incident;
    }

    public function acknowledge(): void
    {
        $this->incident->update([
            'acknowledged_by' => Auth::id(),
            'acknowledged_at' => now(),
        ]);
    }

    public function with(): array
    {
        return [
            'events' => $this->incident->events()->orderBy('ts')->get(),
            'notifications' => $this->incident->notifications()->with('channel')->orderBy('created_at')->get(),
        ];
    }
};
?>

<div class="max-w-3xl">
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h1 class="text-lg font-semibold">{{ $incident->monitor->name }}</h1>
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $incident->state === 'open' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                {{ $incident->state === 'open' ? 'Açık' : 'Çözüldü' }}
            </span>
            @if ($incident->flapping)
                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Flapping</span>
            @endif
        </div>

        @if (! $incident->acknowledged_at)
            <button wire:click="acknowledge" class="rounded-md border border-neutral-300 px-3 py-1.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50">
                Onayla
            </button>
        @else
            <span class="text-xs text-neutral-500">{{ $incident->acknowledgedBy?->name }} tarafından {{ $incident->acknowledged_at->format('Y-m-d H:i') }} itibarıyla onaylandı</span>
        @endif
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 rounded-lg border border-neutral-200 bg-white p-6 text-sm">
        <div><div class="text-neutral-500">Neden</div><div class="font-medium">{{ \App\Checks\ErrorClassifier::label($incident->cause_class) }}</div></div>
        <div><div class="text-neutral-500">Detay</div><div class="font-medium">{{ $incident->cause_detail ?? '—' }}</div></div>
        <div><div class="text-neutral-500">Başlangıç</div><div class="font-medium">{{ $incident->started_at->format('Y-m-d H:i:s') }}</div></div>
        <div>
            <div class="text-neutral-500">{{ $incident->state === 'open' ? 'Süren süre' : 'Bitiş' }}</div>
            <div class="font-medium">
                {{ $incident->resolved_at ? $incident->resolved_at->format('Y-m-d H:i:s') : $incident->started_at->diffForHumans(null, true, false, 2) }}
            </div>
        </div>
    </div>

    <h2 class="mb-3 text-sm font-semibold text-neutral-700">Zaman çizelgesi</h2>
    <div class="mb-6 overflow-hidden rounded-lg border border-neutral-200 bg-white">
        <ul class="divide-y divide-neutral-100 text-sm">
            @forelse ($events as $event)
                <li class="flex items-center justify-between px-4 py-2">
                    <span>{{ $event->type === 'triggered' ? 'Tetiklendi' : 'Çözüldü' }}</span>
                    <span class="text-neutral-500">{{ $event->ts->format('Y-m-d H:i:s') }}</span>
                </li>
            @empty
                <li class="px-4 py-4 text-center text-neutral-500">Olay kaydı yok.</li>
            @endforelse
        </ul>
    </div>

    <h2 class="mb-3 text-sm font-semibold text-neutral-700">Bildirimler</h2>
    <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-left text-xs uppercase text-neutral-500">
                <tr>
                    <th class="px-4 py-2 font-medium">Kanal</th>
                    <th class="px-4 py-2 font-medium">Olay</th>
                    <th class="px-4 py-2 font-medium">Durum</th>
                    <th class="px-4 py-2 font-medium">Deneme</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($notifications as $n)
                    <tr>
                        <td class="px-4 py-2">{{ $n->channel->name }}</td>
                        <td class="px-4 py-2 text-neutral-600">{{ $n->event_type === 'triggered' ? 'Tetiklendi' : 'Çözüldü' }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $n->status === 'sent' ? 'bg-emerald-100 text-emerald-700' : ($n->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-neutral-100 text-neutral-600') }}">
                                {{ $n->status }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-neutral-600">{{ $n->attempts }}/{{ $n->max_attempts }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-neutral-500">Bildirim yok (bağlı kanal olmayabilir).</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
