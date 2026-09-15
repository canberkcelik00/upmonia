<?php

use App\Models\Client;
use App\Services\ClientContactChannelSync;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $brand_color = '#0f766e';

    public string $logo_url = '';

    public string $contact_emails_raw = '';

    public function startCreate(): void
    {
        $this->reset(['editingId', 'name', 'logo_url', 'contact_emails_raw']);
        $this->brand_color = '#0f766e';
        $this->editingId = 0; // sentinel: form open, no existing record
    }

    public function edit(int $clientId): void
    {
        $client = Client::findOrFail($clientId);
        $this->editingId = $client->id;
        $this->name = $client->name;
        $this->brand_color = $client->brand_color ?? '#0f766e';
        $this->logo_url = $client->logo_url ?? '';
        $this->contact_emails_raw = implode(', ', $client->contact_emails ?? []);
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'name', 'logo_url', 'contact_emails_raw']);
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo_url' => ['nullable', 'url'],
            'brand_color' => ['nullable', 'string', 'max:7'],
        ]);

        $emails = collect(explode(',', $this->contact_emails_raw))
            ->map(fn ($e) => trim($e))->filter()->values()->all();

        $data = [
            'name' => $this->name,
            'brand_color' => $this->brand_color ?: null,
            'logo_url' => $this->logo_url ?: null,
            'contact_emails' => $emails,
        ];

        $client = $this->editingId
            ? tap(Client::findOrFail($this->editingId))->update($data)
            : Client::create($data);

        ClientContactChannelSync::sync($client);

        $this->cancel();
    }

    public function delete(int $clientId): void
    {
        Client::findOrFail($clientId)->delete();
    }

    public function with(): array
    {
        return ['clients' => Client::withCount('monitors')->orderBy('name')->get()];
    }
};
?>

<div class="max-w-2xl">
    <h1 class="mb-6 text-lg font-semibold">Ayarlar</h1>
    <x-settings.tabs />

    <div class="mb-4 flex justify-end">
        @if ($editingId === null)
            <button wire:click="startCreate" class="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800">+ Yeni müşteri</button>
        @endif
    </div>

    @if ($editingId !== null)
        <form wire:submit="save" class="mb-6 rounded-lg border border-neutral-200 bg-white p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Ad</label>
                    <input wire:model="name" type="text" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700">Marka rengi</label>
                        <input wire:model="brand_color" type="color" class="mt-1 h-10 w-full rounded-md border border-neutral-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700">Logo URL</label>
                        <input wire:model="logo_url" type="text" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">İletişim e-postaları (virgülle ayır)</label>
                    <input wire:model="contact_emails_raw" type="text" placeholder="ops@acme.com, alerts@acme.com" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-neutral-500">Bu adresler için otomatik olarak doğrulanmış bildirim kanalları oluşturulur.</p>
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
                    <th class="px-4 py-2 font-medium">Monitör sayısı</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($clients as $client)
                    <tr wire:key="client-{{ $client->id }}">
                        <td class="px-4 py-3 font-medium">{{ $client->name }}</td>
                        <td class="px-4 py-3 text-neutral-600">{{ $client->monitors_count }}</td>
                        <td class="px-4 py-3 text-right text-xs">
                            <button wire:click="edit({{ $client->id }})" class="font-medium text-neutral-600 hover:text-neutral-900">Düzenle</button>
                            <button wire:click="delete({{ $client->id }})" wire:confirm="Bu müşteriyi silmek istediğine emin misin?" class="ml-3 font-medium text-red-600 hover:text-red-800">Sil</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-neutral-500">Henüz müşteri yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
