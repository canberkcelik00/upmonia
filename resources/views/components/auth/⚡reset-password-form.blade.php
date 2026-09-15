<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    public function submit(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user) {
                $user->forceFill(['password' => $this->password])->save();

                // A password reset invalidates every other session, not just this browser.
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('form', __($status));

            return;
        }

        session()->flash('status', __('auth.password_reset_success'));
        $this->redirect(route('login'), navigate: true);
    }
}
?>

<div class="rounded-lg border border-neutral-200 bg-white p-6">
    <h1 class="mb-6 text-lg font-semibold">Yeni parola belirle</h1>

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

        <div>
            <label for="password" class="block text-sm font-medium text-neutral-700">Yeni parola</label>
            <input wire:model="password" id="password" type="password" autocomplete="new-password"
                   class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-neutral-700">Yeni parola (tekrar)</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password"
                   class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
        </div>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 disabled:opacity-50">
            Parolamı güncelle
        </button>
    </form>
</div>
