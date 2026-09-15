<?php

use App\Mail\WelcomeEmail;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\EmailVerificationService;
use App\Services\SlugGenerator;
use App\Support\Throttle;
use App\Support\TooManyAttemptsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';

    public string $organization_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function submit(): void
    {
        try {
            Throttle::hit('signup:'.request()->ip(), 5);
        } catch (TooManyAttemptsException $e) {
            $this->addError('form', __('auth.too_many_attempts', ['seconds' => $e->availableInSeconds]));

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'organization_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $locale = app()->getLocale();

        $user = DB::transaction(function () use ($validated, $locale) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'locale' => $locale,
            ]);

            $organization = Organization::create([
                'name' => $validated['organization_name'],
                'slug' => SlugGenerator::uniqueOrganizationSlug($validated['organization_name']),
            ]);

            Membership::create([
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'role' => 'owner',
            ]);

            return $user;
        });

        Mail::to($user->email)->send(new WelcomeEmail($user));
        EmailVerificationService::send($user);

        Auth::login($user);
        request()->session()->regenerate();

        $this->redirect(route('monitors.index'), navigate: true);
    }
}
?>

<div class="rounded-lg border border-neutral-200 bg-white p-6">
    <h1 class="mb-6 text-lg font-semibold">Hesap oluştur</h1>

    @error('form')
        <div class="mb-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <form wire:submit="submit" class="space-y-4">
        <div>
            <label for="name" class="block text-sm font-medium text-neutral-700">Ad Soyad</label>
            <input wire:model="name" id="name" type="text" autocomplete="name"
                   class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="organization_name" class="block text-sm font-medium text-neutral-700">Şirket / ajans adı</label>
            <input wire:model="organization_name" id="organization_name" type="text"
                   class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
            @error('organization_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-neutral-700">E-posta</label>
            <input wire:model="email" id="email" type="email" autocomplete="email"
                   class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-neutral-700">Parola</label>
            <input wire:model="password" id="password" type="password" autocomplete="new-password"
                   class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-neutral-700">Parola (tekrar)</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password"
                   class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
        </div>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 disabled:opacity-50">
            Hesap oluştur
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-neutral-600">
        Zaten hesabın var mı? <a href="{{ route('login') }}" class="font-medium text-neutral-900 underline">Giriş yap</a>
    </p>
</div>
