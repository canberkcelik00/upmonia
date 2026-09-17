<?php

use App\Support\Throttle;
use App\Support\TooManyAttemptsException;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    public bool $sent = false;

    public function submit(): void
    {
        try {
            Throttle::hit('pwreset:'.request()->ip(), 5);
        } catch (TooManyAttemptsException $e) {
            $this->addError('form', __('auth.too_many_attempts', ['seconds' => $e->availableInSeconds]));

            return;
        }

        $this->validate(['email' => ['required', 'email']]);

        // Always report success, regardless of whether the email exists (anti-enumeration) —
        // Password::sendResetLink() itself silently no-ops for an unknown address.
        Password::sendResetLink(['email' => $this->email]);

        $this->sent = true;
    }
}
?>

<x-ui.card padding="p-6">
    <h1 class="mb-6 text-xl font-semibold tracking-tight text-ink">{{ __('auth.forgot_password') }}</h1>

    @if ($sent)
        <x-ui.alert variant="success">{{ __('auth.password_reset_sent') }}</x-ui.alert>
    @else
        @error('form')
            <x-ui.alert variant="error" class="mb-4">{{ $message }}</x-ui.alert>
        @enderror

        <form wire:submit="submit" class="space-y-4">
            <x-ui.field :label="__('auth.field_email')" error="email">
                <x-ui.input wire:model="email" id="email" type="email" autocomplete="email" :invalid="$errors->has('email')" />
            </x-ui.field>

            <x-ui.button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                {{ __('auth.send_reset_link') }}
            </x-ui.button>
        </form>
    @endif

    <p class="mt-4 text-center text-sm text-ink-2">
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-ink hover:underline">{{ __('auth.back_to_login') }}</a>
    </p>
</x-ui.card>
