{{-- Shared by monitors.form (create) and monitors.show (edit-in-place) — plain Blade
     @include, not a nested Livewire component, so wire:model binds to whichever parent
     component included it. Keeps the ~5-monitor-type conditional field set in one place. --}}
<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-neutral-700">Ad</label>
        <input wire:model="name" type="text" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-neutral-700">Müşteri (opsiyonel)</label>
        <select wire:model="client_id" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
            <option value="">— Yok —</option>
            @foreach ($clients as $client)
                <option value="{{ $client->id }}">{{ $client->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-neutral-700">Tür</label>
        <select wire:model.live="type" {{ $editing ?? false ? 'disabled' : '' }} class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm disabled:bg-neutral-100">
            <option value="http">HTTP</option>
            <option value="keyword">Anahtar kelime (Keyword)</option>
            <option value="ssl">SSL sertifikası</option>
            <option value="tcp_port">TCP port</option>
            <option value="heartbeat">Heartbeat</option>
        </select>
        @if($editing ?? false)
            <p class="mt-1 text-xs text-neutral-500">Tür oluşturduktan sonra değiştirilemez.</p>
        @endif
    </div>

    @if (in_array($type, ['http', 'keyword', 'ssl']))
        <div>
            <label class="block text-sm font-medium text-neutral-700">URL</label>
            <input wire:model="url" type="text" placeholder="https://example.com" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
            @error('url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    @endif

    @if (in_array($type, ['http', 'keyword']))
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-neutral-700">Yöntem</label>
                <select wire:model="method" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                    @foreach (['GET','POST','PUT','PATCH','DELETE','HEAD'] as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700">Beklenen durum kodları</label>
                <input wire:model="expected_status_raw" type="text" placeholder="200, 201 (boş = 2xx/3xx)" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-neutral-700">Özel başlıklar (her satıra bir tane: Anahtar: Değer)</label>
            <textarea wire:model="headers_raw" rows="3" placeholder="Authorization: Bearer ..." class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 font-mono text-xs"></textarea>
        </div>

        <div class="flex items-center gap-6">
            <label class="flex items-center gap-2 text-sm text-neutral-700">
                <input wire:model="follow_redirects" type="checkbox" class="rounded border-neutral-300"> Yönlendirmeleri takip et
            </label>
            <label class="flex items-center gap-2 text-sm text-neutral-700">
                <input wire:model="verify_ssl" type="checkbox" class="rounded border-neutral-300"> SSL sertifikasını doğrula
            </label>
        </div>
    @endif

    @if ($type === 'keyword')
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-neutral-700">Anahtar kelime</label>
                <input wire:model="keyword" type="text" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                @error('keyword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700">Mod</label>
                <select wire:model="keyword_mode" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                    <option value="present">Bulunmalı</option>
                    <option value="absent">Bulunmamalı</option>
                </select>
            </div>
        </div>
    @endif

    @if ($type === 'ssl')
        <div>
            <label class="block text-sm font-medium text-neutral-700">Sertifika bitişine kaç gün kala uyar</label>
            <input wire:model="ssl_warn_days" type="number" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
        </div>
    @endif

    @if ($type === 'tcp_port')
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-neutral-700">Sunucu (host)</label>
                <input wire:model="host" type="text" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                @error('host') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700">Port</label>
                <input wire:model="port" type="number" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                @error('port') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    @endif

    @if ($type === 'heartbeat')
        <div>
            <label class="block text-sm font-medium text-neutral-700">Grace süresi (saniye)</label>
            <input wire:model="heartbeat_grace_s" type="number" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-neutral-500">Bu süre içinde ping gelmezse monitör DOWN'a düşer.</p>
        </div>
    @endif

    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-neutral-700">Kontrol aralığı (sn)</label>
            <input wire:model="interval_s" type="number" min="60" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
            @error('interval_s') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-neutral-700">Zaman aşımı (ms)</label>
            <input wire:model="timeout_ms" type="number" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
        </div>
        <div></div>
        <div>
            <label class="block text-sm font-medium text-neutral-700">Onay eşiği</label>
            <input wire:model="confirm_threshold" type="number" min="1" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-neutral-500">Kaç ardışık hatadan sonra DOWN sayılır.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-neutral-700">Kurtarma eşiği</label>
            <input wire:model="recover_threshold" type="number" min="1" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-neutral-500">Kaç ardışık başarıdan sonra UP sayılır.</p>
        </div>
    </div>
</div>
