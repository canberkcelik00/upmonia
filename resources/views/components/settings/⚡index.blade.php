<?php

use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $name = '';

    public string $locale = 'tr';

    public string $new_email = '';

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public string $delete_org_name = '';

    public string $delete_password = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->locale = Auth::user()->locale;
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'in:tr,en'],
        ]);

        Auth::user()->update(['name' => $this->name, 'locale' => $this->locale]);
        session()->flash('profile-status', __('app.flash_profile_updated'));
    }

    public function requestEmailChange(): void
    {
        $this->validate(['new_email' => ['required', 'email', 'max:255', 'unique:users,email']]);

        $user = Auth::user();
        $user->update(['pending_email' => $this->new_email]);
        EmailVerificationService::send($user, $this->new_email);

        $this->new_email = '';
        session()->flash('email-status', __('app.flash_email_verification_sent'));
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        Auth::user()->update(['password' => $this->new_password]);

        // Revoke every other session — matches the source app's rule that a password
        // change (like a reset) invalidates sessions other than the one making the change.
        DB::table('sessions')->where('user_id', Auth::id())->where('id', '!=', session()->getId())->delete();

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('password-status', __('app.flash_password_updated'));
    }

    public function revokeOtherSessions(): void
    {
        DB::table('sessions')->where('user_id', Auth::id())->where('id', '!=', session()->getId())->delete();
        session()->flash('sessions-status', __('app.flash_sessions_revoked'));
    }

    public function deleteAccount(): void
    {
        $this->validate([
            'delete_password' => ['required', 'current_password'],
            'delete_org_name' => ['required'],
        ]);

        $org = Auth::user()->currentOrganization();

        if (! $org || $this->delete_org_name !== $org->name) {
            $this->addError('delete_org_name', __('app.error_org_name_mismatch'));

            return;
        }

        $user = Auth::user();
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        // Deleting the org cascades to every monitor/incident/channel/client under it (FK
        // ON DELETE CASCADE); deleting the user cascades to their memberships/sessions/tokens.
        $org?->delete();
        $user->delete();

        $this->redirect(route('login'), navigate: true);
    }

    public function with(): array
    {
        return [
            'sessions' => DB::table('sessions')->where('user_id', Auth::id())->orderByDesc('last_activity')->get(),
            'currentSessionId' => session()->getId(),
        ];
    }
};
?>

<div>
    <x-ui.page-header :title="__('app.settings_title')" :description="__('app.settings_description')" class="mb-6" />
    <x-settings.tabs />

    <div>
        <x-ui.form-section :title="__('app.settings_profile')" :description="__('app.settings_profile_description')">
            <x-slot:actions>
                <x-ui.button type="submit" form="profile-form" variant="primary">{{ __('app.save') }}</x-ui.button>
            </x-slot:actions>

            @if (session('profile-status'))
                <x-ui.alert variant="success">{{ session('profile-status') }}</x-ui.alert>
            @endif
            <form id="profile-form" wire:submit="updateProfile" class="grid max-w-[420px] gap-3.5">
                <x-ui.field :label="__('app.settings_full_name')" error="name">
                    <x-ui.input wire:model="name" type="text" :invalid="$errors->has('name')" />
                </x-ui.field>
                <x-ui.field :label="__('app.settings_panel_language')">
                    <x-ui.select wire:model="locale">
                        <option value="tr">Türkçe</option>
                        <option value="en">English</option>
                    </x-ui.select>
                </x-ui.field>
            </form>
        </x-ui.form-section>

        <x-ui.form-section :title="__('app.settings_email')" :description="__('app.settings_email_description')">
            <x-slot:actions>
                <x-ui.button type="submit" form="email-form" variant="secondary">{{ __('app.settings_change') }}</x-ui.button>
            </x-slot:actions>

            <p class="text-[13px] text-ink-2">{{ __('app.settings_email_current') }}: <strong class="text-ink">{{ auth()->user()->email }}</strong>
                @if (auth()->user()->pending_email)
                    <span class="text-warn-text">({{ __('app.settings_email_pending', ['email' => auth()->user()->pending_email]) }})</span>
                @endif
            </p>
            @if (session('email-status'))
                <x-ui.alert variant="success">{{ session('email-status') }}</x-ui.alert>
            @endif
            <form id="email-form" wire:submit="requestEmailChange" class="max-w-[420px]">
                <x-ui.field :label="__('app.settings_new_email')" error="new_email">
                    <x-ui.input wire:model="new_email" type="email" :invalid="$errors->has('new_email')" />
                </x-ui.field>
            </form>
        </x-ui.form-section>

        <x-ui.form-section :title="__('app.settings_password')" :description="__('app.settings_password_description')">
            <x-slot:actions>
                <x-ui.button type="submit" form="password-form" variant="primary">{{ __('app.settings_password_update') }}</x-ui.button>
            </x-slot:actions>

            @if (session('password-status'))
                <x-ui.alert variant="success">{{ session('password-status') }}</x-ui.alert>
            @endif
            <form id="password-form" wire:submit="updatePassword" class="grid max-w-[420px] gap-3.5">
                <x-ui.field :label="__('app.settings_current_password')" error="current_password">
                    <x-ui.input wire:model="current_password" type="password" :invalid="$errors->has('current_password')" />
                </x-ui.field>
                <x-ui.field :label="__('app.settings_new_password')" error="new_password">
                    <x-ui.input wire:model="new_password" type="password" :invalid="$errors->has('new_password')" />
                </x-ui.field>
                <x-ui.field :label="__('app.settings_new_password_confirm')">
                    <x-ui.input wire:model="new_password_confirmation" type="password" />
                </x-ui.field>
            </form>
        </x-ui.form-section>

        <x-ui.form-section :title="__('app.settings_sessions')" :description="__('app.settings_sessions_description')">
            <x-slot:actions>
                <x-ui.button variant="secondary" wire:click="revokeOtherSessions">{{ __('app.settings_revoke_sessions') }}</x-ui.button>
            </x-slot:actions>

            @if (session('sessions-status'))
                <x-ui.alert variant="success">{{ session('sessions-status') }}</x-ui.alert>
            @endif
            <ul class="divide-y divide-line text-sm">
                @foreach ($sessions as $s)
                    <li class="flex items-center justify-between py-2">
                        <div>
                            <span class="font-medium">{{ $s->ip_address ?? __('app.settings_session_unknown') }}</span>
                            @if ($s->id === $currentSessionId)
                                <x-ui.badge color="emerald" class="ml-2">{{ __('app.settings_session_current') }}</x-ui.badge>
                            @endif
                            <div class="text-xs text-muted">{{ \Illuminate\Support\Str::limit($s->user_agent ?? '', 60) }}</div>
                        </div>
                        <span class="text-xs text-muted">{{ \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.form-section>

        <x-ui.form-section :title="__('app.settings_export_title')" :description="__('app.settings_export_description')">
            <x-slot:actions>
                <x-ui.button variant="secondary" href="{{ route('settings.export') }}">
                    <x-phosphor-download-simple class="size-4" />
                    {{ __('app.settings_export_cta') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.form-section>

        <x-ui.form-section :title="__('app.settings_delete_account')" :description="__('app.settings_delete_account_warning')" danger>
            <x-slot:actions>
                <button type="submit" form="delete-account-form" wire:confirm="{{ __('app.settings_delete_confirm_dialog') }}"
                        class="inline-flex h-8 items-center justify-center gap-2 rounded-control bg-down px-3 text-sm font-medium text-on-badge transition-colors duration-150 hover:opacity-90 active:scale-[0.98]">
                    {{ __('app.settings_delete_submit') }}
                </button>
            </x-slot:actions>

            <form id="delete-account-form" wire:submit="deleteAccount" class="grid max-w-[420px] gap-3.5">
                <x-ui.field :label="__('app.settings_delete_confirm_org', ['org' => auth()->user()->currentOrganization()?->name])" error="delete_org_name">
                    <x-ui.input wire:model="delete_org_name" type="text" :invalid="$errors->has('delete_org_name')" />
                </x-ui.field>
                <x-ui.field :label="__('app.settings_delete_password')" error="delete_password">
                    <x-ui.input wire:model="delete_password" type="password" :invalid="$errors->has('delete_password')" />
                </x-ui.field>
            </form>
        </x-ui.form-section>
    </div>
</div>
