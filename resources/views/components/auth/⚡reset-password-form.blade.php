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

<x-ui.card padding="p-6">
    <h1 class="mb-6 text-xl font-semibold tracking-tight text-ink">{{ __('auth.set_new_password') }}</h1>

    @error('form')
        <x-ui.alert variant="error" class="mb-4">{{ $message }}</x-ui.alert>
    @enderror

    <form wire:submit="submit" class="space-y-4">
        <x-ui.field :label="__('auth.field_email')" error="email">
            <x-ui.input wire:model="email" id="email" type="email" autocomplete="email" :invalid="$errors->has('email')" />
        </x-ui.field>

        <x-ui.field :label="__('auth.field_new_password')" error="password">
            <x-ui.input wire:model="password" id="password" type="password" autocomplete="new-password" :invalid="$errors->has('password')" />
        </x-ui.field>

        <x-ui.field :label="__('auth.field_new_password_confirm')">
            <x-ui.input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" />
        </x-ui.field>

        <x-ui.button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
            {{ __('auth.update_password') }}
        </x-ui.button>
    </form>
</x-ui.card>
