{{-- Shared by monitors.form (create) and monitors.show (edit-in-place) — plain Blade
     @include, not a nested Livewire component, so wire:model binds to whichever parent
     component included it. Keeps the ~5-monitor-type conditional field set in one place. --}}
<div class="space-y-4">
    <x-ui.field :label="__('app.field_name')" error="name">
        <x-ui.input wire:model="name" type="text" :invalid="$errors->has('name')" />
    </x-ui.field>

    <x-ui.field :label="__('app.field_client')">
        <x-ui.select wire:model="client_id">
            <option value="">{{ __('app.field_client_none') }}</option>
            @foreach ($clients as $client)
                <option value="{{ $client->id }}">{{ $client->name }}</option>
            @endforeach
        </x-ui.select>
    </x-ui.field>

    <x-ui.field :label="__('app.field_type')" :hint="($editing ?? false) ? __('app.field_type_locked_hint') : null">
        <x-ui.select wire:model.live="type" :disabled="$editing ?? false">
            <option value="http">{{ __('app.monitor_type_http') }}</option>
            <option value="keyword">{{ __('app.monitor_type_keyword') }}</option>
            <option value="ssl">{{ __('app.monitor_type_ssl') }}</option>
            <option value="tcp_port">{{ __('app.monitor_type_tcp_port') }}</option>
            <option value="heartbeat">{{ __('app.monitor_type_heartbeat') }}</option>
        </x-ui.select>
    </x-ui.field>

    @if (in_array($type, ['http', 'keyword', 'ssl']))
        <x-ui.field :label="__('app.field_url')" error="url">
            <x-ui.input wire:model="url" type="text" placeholder="https://example.com" :invalid="$errors->has('url')" />
        </x-ui.field>
    @endif

    @if ($type === 'keyword')
        <div class="grid grid-cols-2 gap-4">
            <x-ui.field :label="__('app.field_keyword')" error="keyword">
                <x-ui.input wire:model="keyword" type="text" :invalid="$errors->has('keyword')" />
            </x-ui.field>
            <x-ui.field :label="__('app.field_keyword_mode')">
                <x-ui.select wire:model="keyword_mode">
                    <option value="present">{{ __('app.field_keyword_mode_present') }}</option>
                    <option value="absent">{{ __('app.field_keyword_mode_absent') }}</option>
                </x-ui.select>
            </x-ui.field>
        </div>
    @endif

    @if ($type === 'ssl')
        <x-ui.field :label="__('app.field_ssl_warn_days')">
            <x-ui.input wire:model="ssl_warn_days" type="number" />
        </x-ui.field>
    @endif

    @if ($type === 'tcp_port')
        <div class="grid grid-cols-2 gap-4">
            <x-ui.field :label="__('app.field_host')" error="host">
                <x-ui.input wire:model="host" type="text" :invalid="$errors->has('host')" />
            </x-ui.field>
            <x-ui.field :label="__('app.field_port')" error="port">
                <x-ui.input wire:model="port" type="number" :invalid="$errors->has('port')" />
            </x-ui.field>
        </div>
    @endif

    @if ($type === 'heartbeat')
        <x-ui.field :label="__('app.field_heartbeat_grace')" :hint="__('app.field_heartbeat_grace_hint')">
            <x-ui.input wire:model="heartbeat_grace_s" type="number" />
        </x-ui.field>
    @endif

    <x-ui.field :label="__('app.field_interval')" error="interval_s">
        <x-ui.input wire:model="interval_s" type="number" min="60" :invalid="$errors->has('interval_s')" />
    </x-ui.field>

    {{-- Advanced settings: type-tuning fields with sensible defaults, rarely touched after
         initial setup. Collapsed by default per the progressive-disclosure brief — keeps the
         always-relevant fields above uncluttered while power users can still reach everything. --}}
    <details class="group rounded-xl border border-neutral-200 bg-white shadow-[var(--shadow-card)] dark:border-neutral-800 dark:bg-neutral-900">
        <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-3.5 text-sm font-medium text-neutral-700 select-none dark:text-neutral-300">
            {{ __('app.field_advanced') }}
            <x-phosphor-caret-down class="size-4 text-neutral-400 transition-transform duration-150 group-open:rotate-180" />
        </summary>

        <div class="space-y-4 border-t border-neutral-200 p-5 dark:border-neutral-800">
            @if (in_array($type, ['http', 'keyword']))
                <div class="grid grid-cols-2 gap-4">
                    <x-ui.field :label="__('app.field_method')">
                        <x-ui.select wire:model="method">
                            @foreach (['GET','POST','PUT','PATCH','DELETE','HEAD'] as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field :label="__('app.field_expected_status')">
                        <x-ui.input wire:model="expected_status_raw" type="text" placeholder="{{ __('app.field_expected_status_placeholder') }}" />
                    </x-ui.field>
                </div>

                <x-ui.field :label="__('app.field_headers')">
                    <x-ui.textarea wire:model="headers_raw" rows="3" placeholder="Authorization: Bearer ..." mono />
                </x-ui.field>

                <div class="flex items-center gap-6">
                    <x-ui.checkbox wire:model="follow_redirects" :label="__('app.field_follow_redirects')" />
                    <x-ui.checkbox wire:model="verify_ssl" :label="__('app.field_verify_ssl')" />
                </div>
            @endif

            <div class="grid grid-cols-3 gap-4">
                <x-ui.field :label="__('app.field_timeout')">
                    <x-ui.input wire:model="timeout_ms" type="number" />
                </x-ui.field>
                <x-ui.field :label="__('app.field_confirm_threshold')" :hint="__('app.field_confirm_threshold_hint')">
                    <x-ui.input wire:model="confirm_threshold" type="number" min="1" />
                </x-ui.field>
                <x-ui.field :label="__('app.field_recover_threshold')" :hint="__('app.field_recover_threshold_hint')">
                    <x-ui.input wire:model="recover_threshold" type="number" min="1" />
                </x-ui.field>
            </div>
        </div>
    </details>
</div>
