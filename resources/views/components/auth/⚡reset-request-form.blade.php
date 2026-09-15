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

<div class="rounded-lg border border-neutral-200 bg-white p-6">
    <h1 class="mb-6 text-lg font-semibold">Parolamı unuttum</h1>

    @if ($sent)
        <p class="text-sm text-neutral-700">{{ __('auth.password_reset_sent') }}</p>
    @else
        @error('form')
            <div class="mb-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</div>
        @enderror

        <form wire:submit="submit" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-neutral-700">E-posta</label>
                <input wire:model="email" id="email" type="email" autocomplete="email"
                       class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled"
                    class="w-full rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 disabled:opacity-50">
                Sıfırlama bağlantısı gönder
            </button>
        </form>
    @endif

    <p class="mt-4 text-center text-sm text-neutral-600">
        <a href="{{ route('login') }}" class="font-medium text-neutral-900 underline">Girişe dön</a>
    </p>
</div>
