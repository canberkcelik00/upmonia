<?php

use App\Models\MaintenanceWindow;
use App\Models\Monitor;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    public bool $creating = false;

    public string $name = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public array $monitor_ids = [];

    public function startCreate(): void
    {
        $this->reset(['name', 'starts_at', 'ends_at', 'monitor_ids']);
        $this->creating = true;
    }

    public function cancel(): void
    {
        $this->creating = false;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $window = MaintenanceWindow::create([
            'name' => $this->name,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
        ]);

        if (! empty($this->monitor_ids)) {
            $window->monitors()->attach($this->monitor_ids);
        }

        $this->creating = false;
    }

    public function delete(int $windowId): void
    {
        MaintenanceWindow::findOrFail($windowId)->delete();
    }

    public function with(): array
    {
        return [
            'windows' => MaintenanceWindow::with('monitors')->orderByDesc('starts_at')->get(),
            'monitors' => Monitor::orderBy('name')->get(),
        ];
    }
};
?>

<div class="max-w-2xl">
    <h1 class="mb-6 text-lg font-semibold">Ayarlar</h1>
    <x-settings.tabs />

    <div class="mb-4 flex justify-end">
        @unless ($creating)
            <button wire:click="startCreate" class="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800">+ Yeni bakım penceresi</button>
        @endunless
    </div>

    @if ($creating)
        <form wire:submit="save" class="mb-6 rounded-lg border border-neutral-200 bg-white p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Ad</label>
                    <input wire:model="name" type="text" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700">Başlangıç</label>
                        <input wire:model="starts_at" type="datetime-local" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                        @error('starts_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700">Bitiş</label>
                        <input wire:model="ends_at" type="datetime-local" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                        @error('ends_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Monitörler (boş = tüm organizasyon)</label>
                    <div class="mt-1 max-h-40 space-y-1 overflow-y-auto rounded-md border border-neutral-300 p-2">
                        @foreach ($monitors as $monitor)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="monitor_ids" value="{{ $monitor->id }}" class="rounded border-neutral-300">
                                {{ $monitor->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800">Kaydet</button>
                <button type="button" wire:click="cancel" class="text-sm text-neutral-600">Vazgeç</button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-left text-xs uppercase text-neutral-500">
                <tr>
                    <th class="px-4 py-2 font-medium">Ad</th>
                    <th class="px-4 py-2 font-medium">Kapsam</th>
                    <th class="px-4 py-2 font-medium">Başlangıç — Bitiş</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($windows as $window)
                    <tr wire:key="window-{{ $window->id }}">
                        <td class="px-4 py-3 font-medium">
                            {{ $window->name }}
                            @if ($window->coversNow())
                                <span class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Aktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-600">{{ $window->monitors->isEmpty() ? 'Tüm organizasyon' : $window->monitors->count().' monitör' }}</td>
                        <td class="px-4 py-3 text-neutral-600">{{ $window->starts_at->format('Y-m-d H:i') }} — {{ $window->ends_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-right text-xs">
                            <button wire:click="delete({{ $window->id }})" wire:confirm="Sil?" class="font-medium text-red-600 hover:text-red-800">Sil</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-neutral-500">Bakım penceresi yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
