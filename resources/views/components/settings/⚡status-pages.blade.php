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
    <x-ui.page-header :title="__('app.settings_title')" :description="__('app.settings_description')" class="mb-6" />
    <x-settings.tabs />

    <div class="mb-4 flex justify-end">
        @unless ($creating)
            <x-ui.button variant="primary" wire:click="startCreate">
                <x-phosphor-plus class="size-4" /> {{ __('app.status_pages_new') }}
            </x-ui.button>
        @endunless
    </div>

    @if ($creating)
        <form wire:submit="save" class="mb-6">
            <x-ui.card>
                <div class="space-y-4">
                    <x-ui.field :label="__('app.status_pages_title_field')" error="title">
                        <x-ui.input wire:model.live="title" type="text" :invalid="$errors->has('title')" />
                    </x-ui.field>
                    <x-ui.field :label="__('app.status_pages_slug')" error="slug">
                        <div class="flex items-center rounded-md border border-neutral-300 text-sm dark:border-neutral-700">
                            <span class="px-3 text-neutral-500 dark:text-neutral-400">{{ url('/durum') }}/</span>
                            <input wire:model="slug" type="text" class="w-full rounded-r-md bg-transparent px-1 py-2 text-neutral-900 focus:outline-none dark:text-neutral-100">
                        </div>
                    </x-ui.field>
                    <x-ui.checkbox wire:model="show_history" :label="__('app.status_pages_show_history')" />
                    <x-ui.field :label="__('app.status_pages_monitors')" :hint="__('app.status_pages_monitors_hint')">
                        <div class="max-h-40 space-y-1 overflow-y-auto rounded-md border border-neutral-300 p-2 dark:border-neutral-700">
                            @foreach ($monitors as $monitor)
                                <x-ui.checkbox wire:model="monitor_ids" value="{{ $monitor->id }}" :label="$monitor->name" />
                            @endforeach
                        </div>
                    </x-ui.field>
                </div>
                <div class="mt-6 flex items-center gap-3">
                    <x-ui.button type="submit" variant="primary">{{ __('app.status_pages_publish') }}</x-ui.button>
                    <x-ui.button type="button" variant="ghost" wire:click="cancel">{{ __('app.cancel') }}</x-ui.button>
                </div>
            </x-ui.card>
        </form>
    @endif

    <x-ui.table>
        <x-slot:head>
            <th>{{ __('app.status_pages_title_field') }}</th>
            <th>{{ __('app.incidents_col_monitor') }}</th>
            <th>{{ __('app.monitors_col_status') }}</th>
            <th></th>
        </x-slot:head>

        @forelse ($pages as $page)
            <tr wire:key="page-{{ $page->id }}">
                <td>
                    <div class="font-medium">{{ $page->title }}</div>
                    <a href="{{ route('status-page.show', $page->slug) }}" target="_blank" class="text-xs text-neutral-500 hover:text-brand-700 hover:underline dark:text-neutral-400 dark:hover:text-brand-400">/durum/{{ $page->slug }}</a>
                </td>
                <td class="text-neutral-600 dark:text-neutral-400">{{ $page->monitors_count }}</td>
                <td>
                    <x-ui.badge :color="$page->enabled ? 'emerald' : 'neutral'">{{ $page->enabled ? __('app.status_pages_live') : __('app.channel_inactive') }}</x-ui.badge>
                </td>
                <td class="text-right text-xs">
                    <button wire:click="toggleEnabled({{ $page->id }})" class="font-medium text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-100">
                        {{ $page->enabled ? __('app.status_pages_unpublish') : __('app.status_pages_publish') }}
                    </button>
                    <button wire:click="delete({{ $page->id }})" wire:confirm="{{ __('app.maintenance_delete_confirm') }}" class="ml-3 font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">{{ __('app.delete') }}</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4">
                    <x-ui.empty-state icon="globe" :title="__('app.status_pages_empty')" />
                </td>
            </tr>
        @endforelse
    </x-ui.table>
</div>
