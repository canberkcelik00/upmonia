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

<div class="rounded-lg border border-neutral-200 bg-white p-6">
    <h1 class="mb-6 text-lg font-semibold">Giriş yap</h1>

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
            <label for="password" class="block text-sm font-medium text-neutral-700">Parola</label>
            <input wire:model="password" id="password" type="password" autocomplete="current-password"
                   class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-neutral-700">
                <input wire:model="remember" type="checkbox" class="rounded border-neutral-300">
                Beni hatırla
            </label>
            <a href="{{ route('password.request') }}" class="text-neutral-600 underline">Parolamı unuttum</a>
        </div>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 disabled:opacity-50">
            Giriş yap
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-neutral-600">
        Hesabın yok mu? <a href="{{ route('signup') }}" class="font-medium text-neutral-900 underline">Kayıt ol</a>
    </p>
</div>
