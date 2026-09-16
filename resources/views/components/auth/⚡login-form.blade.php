<?php

use App\Support\Throttle;
use App\Support\TooManyAttemptsException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function submit(): void
    {
        $ip = request()->ip();

        try {
            Throttle::hit('login-ip:'.$ip, 20);
            Throttle::hit('login-email:'.strtolower($this->email).'|'.$ip, 10);
        } catch (TooManyAttemptsException $e) {
            $this->addError('form', __('auth.too_many_attempts', ['seconds' => $e->availableInSeconds]));

            return;
        }

        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Deliberately generic message on failure — never reveals whether the email exists.
        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('form', __('auth.invalid_credentials'));

            return;
        }

        request()->session()->regenerate();

        $this->redirect(session()->pull('url.intended', route('monitors.index')), navigate: true);
    }
}
?>

<x-ui.card padding="p-6">
    <h1 class="mb-6 text-xl font-semibold tracking-tight text-neutral-900 dark:text-neutral-50">{{ __('auth.login_title') }}</h1>

    @error('form')
        <x-ui.alert variant="error" class="mb-4">{{ $message }}</x-ui.alert>
    @enderror

    <form wire:submit="submit" class="space-y-4">
        <x-ui.field :label="__('auth.field_email')" error="email">
            <x-ui.input wire:model="email" id="email" type="email" autocomplete="email" :invalid="$errors->has('email')" />
        </x-ui.field>

        <x-ui.field :label="__('auth.field_password')" error="password">
            <x-ui.input wire:model="password" id="password" type="password" autocomplete="current-password" :invalid="$errors->has('password')" />
        </x-ui.field>

        <div class="flex items-center justify-between text-sm">
            <x-ui.checkbox wire:model="remember" :label="__('auth.remember_me')" />
            <a href="{{ route('password.request') }}" wire:navigate class="text-brand-700 hover:underline dark:text-brand-400">{{ __('auth.forgot_password') }}</a>
        </div>

        <x-ui.button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
            {{ __('auth.login_title') }}
        </x-ui.button>
    </form>

    <p class="mt-4 text-center text-sm text-neutral-600 dark:text-neutral-400">
        {{ __('auth.no_account') }} <a href="{{ route('signup') }}" wire:navigate class="font-medium text-brand-700 hover:underline dark:text-brand-400">{{ __('auth.signup_title') }}</a>
    </p>
</x-ui.card>
