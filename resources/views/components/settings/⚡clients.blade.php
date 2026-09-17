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

<div>
    <x-ui.page-header :title="__('app.settings_title')" :description="__('app.settings_description')" class="mb-6">
        @if ($editingId === null)
            <x-slot:actions>
                <x-ui.button variant="primary" wire:click="startCreate">
                    <x-phosphor-plus class="size-4" /> {{ __('app.clients_new') }}
                </x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>
    <x-settings.tabs />

    @if ($editingId !== null)
        <x-ui.form-section :title="$editingId ? __('app.edit') : __('app.clients_new')" :description="__('app.clients_new_description')">
            <x-slot:actions>
                <x-ui.button type="submit" form="client-form" variant="primary">{{ __('app.save') }}</x-ui.button>
                <x-ui.button type="button" variant="ghost" wire:click="cancel">{{ __('app.cancel') }}</x-ui.button>
            </x-slot:actions>
            <form id="client-form" wire:submit="save" class="grid max-w-[420px] gap-3.5">
                <x-ui.field :label="__('app.field_name')" error="name">
                    <x-ui.input wire:model="name" type="text" :invalid="$errors->has('name')" />
                </x-ui.field>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui.field :label="__('app.clients_brand_color')">
                        <input wire:model="brand_color" type="color" class="mt-1 h-8 w-full rounded-control border border-line-strong">
                    </x-ui.field>
                    <x-ui.field label="Logo URL">
                        <x-ui.input wire:model="logo_url" type="text" />
                    </x-ui.field>
                </div>
                <x-ui.field :label="__('app.clients_contact_emails')" :hint="__('app.clients_contact_emails_hint')">
                    <x-ui.input wire:model="contact_emails_raw" type="text" placeholder="ops@acme.com, alerts@acme.com" />
                </x-ui.field>
            </form>
        </x-ui.form-section>
    @endif

    <x-ui.table class="{{ $editingId !== null ? 'mt-8' : '' }}">
        <x-slot:head>
            <th>{{ __('app.field_name') }}</th>
            <th>{{ __('app.clients_monitor_count') }}</th>
            <th></th>
        </x-slot:head>

        @forelse ($clients as $client)
            <tr wire:key="client-{{ $client->id }}">
                <td class="font-medium">{{ $client->name }}</td>
                <td class="text-ink-2">{{ $client->monitors_count }}</td>
                <td class="text-right text-xs">
                    <button wire:click="edit({{ $client->id }})" class="font-medium text-ink-2 hover:text-ink">{{ __('app.edit') }}</button>
                    <button wire:click="delete({{ $client->id }})" wire:confirm="{{ __('app.clients_delete_confirm') }}" class="ml-3 font-medium text-down-text hover:underline">{{ __('app.delete') }}</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3">
                    <x-ui.empty-state icon="buildings" :title="__('app.clients_empty')" />
                </td>
            </tr>
        @endforelse
    </x-ui.table>
</div>
