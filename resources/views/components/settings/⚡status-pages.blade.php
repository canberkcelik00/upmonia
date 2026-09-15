<?php

use App\Models\Monitor;
use App\Models\StatusPage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    public bool $creating = false;

    public string $slug = '';

    public string $title = '';

    public bool $show_history = true;

    public array $monitor_ids = [];

    public function startCreate(): void
    {
        $this->reset(['slug', 'title', 'monitor_ids']);
        $this->show_history = true;
        $this->creating = true;
    }

    public function cancel(): void
    {
        $this->creating = false;
    }

    public function updatedTitle(string $value): void
    {
        if (! $this->creating) {
            return;
        }
        $this->slug = Str::slug($value);
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', 'unique:status_pages,slug'],
        ]);

        $page = StatusPage::create([
            'slug' => $this->slug,
            'title' => $this->title,
            'show_history' => $this->show_history,
            'enabled' => true,
        ]);

        foreach ($this->monitor_ids as $i => $monitorId) {
            $page->monitors()->attach($monitorId, ['sort_order' => $i]);
        }

        $this->creating = false;
    }

    public function toggleEnabled(int $pageId): void
    {
        $page = StatusPage::findOrFail($pageId);
        $page->update(['enabled' => ! $page->enabled]);
    }

    public function delete(int $pageId): void
    {
        StatusPage::findOrFail($pageId)->delete();
    }

    public function with(): array
    {
        return [
            'pages' => StatusPage::withCount('monitors')->orderByDesc('id')->get(),
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
            <button wire:click="startCreate" class="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800">+ Yeni durum sayfası</button>
        @endunless
    </div>

    @if ($creating)
        <form wire:submit="save" class="mb-6 rounded-lg border border-neutral-200 bg-white p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Başlık</label>
                    <input wire:model.live="title" type="text" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Adres</label>
                    <div class="mt-1 flex items-center rounded-md border border-neutral-300 text-sm">
                        <span class="px-3 text-neutral-500">{{ url('/durum') }}/</span>
                        <input wire:model="slug" type="text" class="w-full rounded-r-md px-1 py-2">
                    </div>
                    @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="flex items-center gap-2 text-sm text-neutral-700">
                        <input wire:model="show_history" type="checkbox" class="rounded border-neutral-300"> Geçmiş kesintileri göster
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Gösterilecek monitörler (opt-in — hiçbiri işaretlenmezse sayfa boş kalır)</label>
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
                <button type="submit" class="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800">Yayınla</button>
                <button type="button" wire:click="cancel" class="text-sm text-neutral-600">Vazgeç</button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-left text-xs uppercase text-neutral-500">
                <tr>
                    <th class="px-4 py-2 font-medium">Başlık</th>
                    <th class="px-4 py-2 font-medium">Monitör</th>
                    <th class="px-4 py-2 font-medium">Durum</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($pages as $page)
                    <tr wire:key="page-{{ $page->id }}">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $page->title }}</div>
                            <a href="{{ route('status-page.show', $page->slug) }}" target="_blank" class="text-xs text-neutral-500 hover:underline">/durum/{{ $page->slug }}</a>
                        </td>
                        <td class="px-4 py-3 text-neutral-600">{{ $page->monitors_count }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $page->enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-500' }}">
                                {{ $page->enabled ? 'Yayında' : 'Pasif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-xs">
                            <button wire:click="toggleEnabled({{ $page->id }})" class="font-medium text-neutral-600 hover:text-neutral-900">
                                {{ $page->enabled ? 'Yayından kaldır' : 'Yayınla' }}
                            </button>
                            <button wire:click="delete({{ $page->id }})" wire:confirm="Sil?" class="ml-3 font-medium text-red-600 hover:text-red-800">Sil</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-neutral-500">Durum sayfası yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
