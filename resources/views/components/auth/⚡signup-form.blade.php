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

<x-ui.card padding="p-6">
    <h1 class="mb-6 text-xl font-semibold tracking-tight text-neutral-900 dark:text-neutral-50">{{ __('auth.signup_heading') }}</h1>

    @error('form')
        <x-ui.alert variant="error" class="mb-4">{{ $message }}</x-ui.alert>
    @enderror

    <form wire:submit="submit" class="space-y-4">
        <x-ui.field :label="__('auth.field_full_name')" error="name">
            <x-ui.input wire:model="name" id="name" type="text" autocomplete="name" :invalid="$errors->has('name')" />
        </x-ui.field>

        <x-ui.field :label="__('auth.field_organization')" error="organization_name">
            <x-ui.input wire:model="organization_name" id="organization_name" type="text" :invalid="$errors->has('organization_name')" />
        </x-ui.field>

        <x-ui.field :label="__('auth.field_email')" error="email">
            <x-ui.input wire:model="email" id="email" type="email" autocomplete="email" :invalid="$errors->has('email')" />
        </x-ui.field>

        <x-ui.field :label="__('auth.field_password')" error="password">
            <x-ui.input wire:model="password" id="password" type="password" autocomplete="new-password" :invalid="$errors->has('password')" />
        </x-ui.field>

        <x-ui.field :label="__('auth.field_password_confirm')">
            <x-ui.input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" />
        </x-ui.field>

        <x-ui.button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
            {{ __('auth.signup_heading') }}
        </x-ui.button>
    </form>

    <p class="mt-4 text-center text-sm text-neutral-600 dark:text-neutral-400">
        {{ __('auth.has_account') }} <a href="{{ route('login') }}" wire:navigate class="font-medium text-brand-700 hover:underline dark:text-brand-400">{{ __('auth.login_title') }}</a>
    </p>
</x-ui.card>
