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
        session()->flash('channels-status', __('app.flash_channel_created'));
    }

    public function resendVerification(int $channelId): void
    {
        try {
            Throttle::hit('resend-channel-verify:'.request()->ip(), 3);
        } catch (TooManyAttemptsException $e) {
            session()->flash('channels-status', __('auth.too_many_attempts', ['seconds' => $e->availableInSeconds]));

            return;
        }

        ChannelVerificationService::send(AlertChannel::findOrFail($channelId));
        session()->flash('channels-status', __('app.flash_channel_verification_resent'));
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
        } catch (TooManyAttemptsException $e) {
            session()->flash('channels-status', __('auth.too_many_attempts', ['seconds' => $e->availableInSeconds]));

            return;
        }

        $channel = AlertChannel::findOrFail($channelId);
        Mail::to($channel->config['email'])->send(new TestEmail($channel, Auth::user()->locale));
        session()->flash('channels-status', __('app.flash_test_email_sent'));
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

<div>
    <x-ui.page-header :title="__('app.settings_title')" :description="__('app.settings_description')" class="mb-6" />
    <x-settings.tabs />

    @if (session('channels-status'))
        <x-ui.alert variant="success" class="mb-4">{{ session('channels-status') }}</x-ui.alert>
    @endif

    <x-ui.form-section :title="__('app.channels_new')" :description="__('app.channels_new_description')">
        <x-slot:actions>
            <x-ui.button type="submit" form="channel-form" variant="primary">{{ __('app.add') }}</x-ui.button>
        </x-slot:actions>
        <form id="channel-form" wire:submit="create" class="grid max-w-[420px] gap-3.5">
            <x-ui.field :label="__('app.field_name')" error="name">
                <x-ui.input wire:model="name" type="text" placeholder="{{ __('app.channels_name_placeholder') }}" :invalid="$errors->has('name')" />
            </x-ui.field>
            <x-ui.field :label="__('auth.field_email')" error="email">
                <x-ui.input wire:model="email" type="email" :invalid="$errors->has('email')" />
            </x-ui.field>
        </form>
    </x-ui.form-section>

    <x-ui.table class="mt-8">
        <x-slot:head>
            <th>{{ __('app.field_name') }}</th>
            <th>{{ __('app.monitors_col_status') }}</th>
            <th></th>
        </x-slot:head>

        @forelse ($channels as $channel)
            <tr wire:key="channel-{{ $channel->id }}">
                <td>
                    <div class="font-medium">{{ $channel->name }}</div>
                    <div class="text-xs text-muted">{{ $channel->config['email'] ?? '' }}</div>
                </td>
                <td>
                    <x-ui.badge :color="$channel->isVerified() ? 'emerald' : 'amber'">{{ $channel->isVerified() ? __('app.channel_verified') : __('app.channel_unverified') }}</x-ui.badge>
                    @unless ($channel->enabled)
                        <x-ui.badge color="neutral" class="ml-1">{{ __('app.channel_inactive') }}</x-ui.badge>
                    @endunless
                </td>
                <td class="text-right text-xs">
                    <div class="flex justify-end gap-3">
                        @unless ($channel->isVerified())
                            <button wire:click="resendVerification({{ $channel->id }})" class="font-medium text-ink-2 hover:text-ink">{{ __('app.channel_resend') }}</button>
                        @else
                            <button wire:click="sendTest({{ $channel->id }})" class="font-medium text-ink-2 hover:text-ink">{{ __('app.channel_send_test') }}</button>
                        @endunless
                        <button wire:click="toggleEnabled({{ $channel->id }})" class="font-medium text-ink-2 hover:text-ink">
                            {{ $channel->enabled ? __('app.channel_deactivate') : __('app.channel_activate') }}
                        </button>
                        <button wire:click="delete({{ $channel->id }})" wire:confirm="{{ __('app.channel_delete_confirm') }}" class="font-medium text-down-text hover:underline">{{ __('app.delete') }}</button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3">
                    <x-ui.empty-state icon="bell" :title="__('app.channels_empty')" />
                </td>
            </tr>
        @endforelse
    </x-ui.table>
</div>
