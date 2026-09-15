<?php

use App\Models\Monitor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function pause(int $monitorId): void
    {
        $monitor = Monitor::findOrFail($monitorId);
        $monitor->state->update(['status' => 'paused']);
    }

    public function resume(int $monitorId): void
    {
        $monitor = Monitor::findOrFail($monitorId);
        $monitor->state->update(['status' => 'pending', 'next_check_at' => now()]);
    }

    public function with(): array
    {
        $monitors = Monitor::query()
            ->with('state', 'client')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->status, fn ($q) => $q->whereHas('state', fn ($s) => $s->where('status', $this->status)))
            ->orderBy('name')
            ->paginate(20);

        return ['monitors' => $monitors];
    }
};
?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-lg font-semibold">Monitörler</h1>
        <a href="{{ route('monitors.create') }}" class="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800">
            + Yeni monitör
        </a>
    </div>

    <div class="mb-4 flex items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Monitör ara..."
               class="w-64 rounded-md border border-neutral-300 px-3 py-2 text-sm">
        <select wire:model.live="status" class="rounded-md border border-neutral-300 px-3 py-2 text-sm">
            <option value="">Tüm durumlar</option>
            <option value="up">Çalışıyor</option>
            <option value="down">Kesinti</option>
            <option value="suspect">Şüpheli</option>
            <option value="paused">Duraklatıldı</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-left text-xs uppercase text-neutral-500">
                <tr>
                    <th class="px-4 py-2 font-medium">Ad</th>
                    <th class="px-4 py-2 font-medium">Tür</th>
                    <th class="px-4 py-2 font-medium">Durum</th>
                    <th class="px-4 py-2 font-medium">Gecikme</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($monitors as $monitor)
                    <tr wire:key="monitor-{{ $monitor->id }}" class="hover:bg-neutral-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('monitors.show', $monitor) }}" class="font-medium text-neutral-900 hover:underline">{{ $monitor->name }}</a>
                            @if ($monitor->client)
                                <div class="text-xs text-neutral-500">{{ $monitor->client->name }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-600">{{ $monitor->type }}</td>
                        <td class="px-4 py-3"><x-ui.status-pill :status="$monitor->state->status" /></td>
                        <td class="px-4 py-3 text-neutral-600">{{ $monitor->state->last_latency_ms ? $monitor->state->last_latency_ms.' ms' : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($monitor->state->status === 'paused')
                                <button wire:click="resume({{ $monitor->id }})" class="text-xs font-medium text-neutral-600 hover:text-neutral-900">Devam ettir</button>
                            @else
                                <button wire:click="pause({{ $monitor->id }})" class="text-xs font-medium text-neutral-600 hover:text-neutral-900">Duraklat</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-neutral-500">
                            Henüz monitör yok. <a href="{{ route('monitors.create') }}" class="text-neutral-900 underline">İlk monitörünü ekle</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $monitors->links() }}</div>
</div>
