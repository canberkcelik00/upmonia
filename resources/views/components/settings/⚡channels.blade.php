<?php

use App\Mail\TestEmail;
use App\Models\AlertChannel;
use App\Services\ChannelVerificationService;
use App\Support\Throttle;
use App\Support\TooManyAttemptsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public function create(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $channel = AlertChannel::create([
            'type' => 'email', 'name' => $this->name,
            'config' => ['email' => $this->email], 'enabled' => true,
        ]);

        ChannelVerificationService::send($channel);

        $this->reset(['name', 'email']);
        session()->flash('channels-status', 'Kanal oluşturuldu. Doğrulama bağlantısı gönderildi.');
    }

    public function resendVerification(int $channelId): void
    {
        try {
            Throttle::hit('resend-channel-verify:'.request()->ip(), 3);
        } catch (TooManyAttemptsException) {
            session()->flash('channels-status', 'Çok fazla deneme. Lütfen biraz sonra tekrar deneyin.');

            return;
        }

        ChannelVerificationService::send(AlertChannel::findOrFail($channelId));
        session()->flash('channels-status', 'Doğrulama bağlantısı tekrar gönderildi.');
    }

    public function toggleEnabled(int $channelId): void
    {
        $channel = AlertChannel::findOrFail($channelId);
        $channel->update(['enabled' => ! $channel->enabled]);
    }

    public function sendTest(int $channelId): void
    {
        try {
            Throttle::hit('test-email:'.request()->ip(), 5);
        } catch (TooManyAttemptsException) {
            session()->flash('channels-status', 'Çok fazla deneme. Lütfen biraz sonra tekrar deneyin.');

            return;
        }

        $channel = AlertChannel::findOrFail($channelId);
        Mail::to($channel->config['email'])->send(new TestEmail($channel, Auth::user()->locale));
        session()->flash('channels-status', 'Test e-postası gönderildi.');
    }

    public function delete(int $channelId): void
    {
        AlertChannel::findOrFail($channelId)->delete();
    }

    public function with(): array
    {
        return ['channels' => AlertChannel::whereNull('client_id')->orderBy('name')->get()];
    }
};
?>

<div class="max-w-2xl">
    <h1 class="mb-6 text-lg font-semibold">Ayarlar</h1>
    <x-settings.tabs />

    @if (session('channels-status'))
        <p class="mb-4 text-sm text-emerald-700">{{ session('channels-status') }}</p>
    @endif

    <div class="mb-6 rounded-lg border border-neutral-200 bg-white p-6">
        <h2 class="mb-4 text-sm font-semibold text-neutral-700">Yeni e-posta kanalı</h2>
        <form wire:submit="create" class="flex items-end gap-3">
            <div>
                <label class="block text-sm font-medium text-neutral-700">Ad</label>
                <input wire:model="name" type="text" placeholder="Ops ekibi" class="mt-1 w-40 rounded-md border border-neutral-300 px-3 py-2 text-sm">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-neutral-700">E-posta</label>
                <input wire:model="email" type="email" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800">Ekle</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-left text-xs uppercase text-neutral-500">
                <tr>
                    <th class="px-4 py-2 font-medium">Ad</th>
                    <th class="px-4 py-2 font-medium">Durum</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($channels as $channel)
                    <tr wire:key="channel-{{ $channel->id }}">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $channel->name }}</div>
                            <div class="text-xs text-neutral-500">{{ $channel->config['email'] ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if ($channel->isVerified())
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Doğrulandı</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Doğrulanmadı</span>
                            @endif
                            @unless ($channel->enabled)
                                <span class="ml-1 inline-flex items-center rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium text-neutral-500">Pasif</span>
                            @endunless
                        </td>
                        <td class="px-4 py-3 text-right text-xs">
                            <div class="flex justify-end gap-3">
                                @unless ($channel->isVerified())
                                    <button wire:click="resendVerification({{ $channel->id }})" class="font-medium text-neutral-600 hover:text-neutral-900">Tekrar gönder</button>
                                @else
                                    <button wire:click="sendTest({{ $channel->id }})" class="font-medium text-neutral-600 hover:text-neutral-900">Test gönder</button>
                                @endunless
                                <button wire:click="toggleEnabled({{ $channel->id }})" class="font-medium text-neutral-600 hover:text-neutral-900">
                                    {{ $channel->enabled ? 'Pasifleştir' : 'Aktifleştir' }}
                                </button>
                                <button wire:click="delete({{ $channel->id }})" wire:confirm="Bu kanalı silmek istediğine emin misin?" class="font-medium text-red-600 hover:text-red-800">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-neutral-500">Henüz bildirim kanalı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
