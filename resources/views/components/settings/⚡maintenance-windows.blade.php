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
    <x-ui.page-header :title="__('app.settings_title')" :description="__('app.settings_description')" class="mb-6" />
    <x-settings.tabs />

    <div class="mb-4 flex justify-end">
        @unless ($creating)
            <x-ui.button variant="primary" wire:click="startCreate">
                <x-phosphor-plus class="size-4" /> {{ __('app.maintenance_new') }}
            </x-ui.button>
        @endunless
    </div>

    @if ($creating)
        <form wire:submit="save" class="mb-6">
            <x-ui.card>
                <div class="space-y-4">
                    <x-ui.field :label="__('app.field_name')" error="name">
                        <x-ui.input wire:model="name" type="text" :invalid="$errors->has('name')" />
                    </x-ui.field>
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.field :label="__('app.maintenance_starts_at')" error="starts_at">
                            <x-ui.input
                                type="datetime-local"
                                :invalid="$errors->has('starts_at')"
                                x-data
                                x-on:change="$wire.set('starts_at', $event.target.value ? new Date($event.target.value).toISOString().slice(0, 16) : '')"
                            />
                        </x-ui.field>
                        <x-ui.field :label="__('app.maintenance_ends_at')" error="ends_at">
                            <x-ui.input
                                type="datetime-local"
                                :invalid="$errors->has('ends_at')"
                                x-data
                                x-on:change="$wire.set('ends_at', $event.target.value ? new Date($event.target.value).toISOString().slice(0, 16) : '')"
                            />
                        </x-ui.field>
                    </div>
                    <x-ui.field :label="__('app.maintenance_monitors')">
                        <div class="max-h-40 space-y-1 overflow-y-auto rounded-md border border-neutral-300 p-2 dark:border-neutral-700">
                            @foreach ($monitors as $monitor)
                                <x-ui.checkbox wire:model="monitor_ids" value="{{ $monitor->id }}" :label="$monitor->name" />
                            @endforeach
                        </div>
                    </x-ui.field>
                </div>
                <div class="mt-6 flex items-center gap-3">
                    <x-ui.button type="submit" variant="primary">{{ __('app.save') }}</x-ui.button>
                    <x-ui.button type="button" variant="ghost" wire:click="cancel">{{ __('app.cancel') }}</x-ui.button>
                </div>
            </x-ui.card>
        </form>
    @endif

    <x-ui.table>
        <x-slot:head>
            <th>{{ __('app.field_name') }}</th>
            <th>{{ __('app.maintenance_scope') }}</th>
            <th>{{ __('app.maintenance_range') }}</th>
            <th></th>
        </x-slot:head>

        @forelse ($windows as $window)
            <tr wire:key="window-{{ $window->id }}">
                <td class="font-medium">
                    {{ $window->name }}
                    @if ($window->coversNow())
                        <x-ui.badge color="amber" class="ml-1">{{ __('app.maintenance_active') }}</x-ui.badge>
                    @endif
                </td>
                <td class="text-neutral-600 dark:text-neutral-400">{{ $window->monitors->isEmpty() ? __('app.maintenance_scope_all') : __('app.maintenance_scope_count', ['count' => $window->monitors->count()]) }}</td>
                <td class="font-mono text-xs text-neutral-600 dark:text-neutral-400">{{ $window->starts_at->toDisplay() }} - {{ $window->ends_at->toDisplay() }}</td>
                <td class="text-right text-xs">
                    <button wire:click="delete({{ $window->id }})" wire:confirm="{{ __('app.maintenance_delete_confirm') }}" class="font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">{{ __('app.delete') }}</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4">
                    <x-ui.empty-state icon="calendar-blank" :title="__('app.maintenance_empty')" />
                </td>
            </tr>
        @endforelse
    </x-ui.table>
</div>
