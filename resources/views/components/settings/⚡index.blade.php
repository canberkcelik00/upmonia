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
        session()->flash('profile-status', 'Profil güncellendi.');
    }

    public function requestEmailChange(): void
    {
        $this->validate(['new_email' => ['required', 'email', 'max:255', 'unique:users,email']]);

        $user = Auth::user();
        $user->update(['pending_email' => $this->new_email]);
        EmailVerificationService::send($user, $this->new_email);

        $this->new_email = '';
        session()->flash('email-status', 'Doğrulama bağlantısı yeni adresinize gönderildi.');
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
        session()->flash('password-status', 'Parolanız güncellendi. Diğer oturumlarınız kapatıldı.');
    }

    public function revokeOtherSessions(): void
    {
        DB::table('sessions')->where('user_id', Auth::id())->where('id', '!=', session()->getId())->delete();
        session()->flash('sessions-status', 'Diğer oturumlar kapatıldı.');
    }

    public function deleteAccount(): void
    {
        $this->validate([
            'delete_password' => ['required', 'current_password'],
            'delete_org_name' => ['required'],
        ]);

        $org = Auth::user()->currentOrganization();

        if (! $org || $this->delete_org_name !== $org->name) {
            $this->addError('delete_org_name', 'Organizasyon adı eşleşmiyor.');

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

<div class="max-w-2xl">
    <h1 class="mb-6 text-lg font-semibold">Ayarlar</h1>
    <x-settings.tabs />

    <div class="space-y-6">
        <div class="rounded-lg border border-neutral-200 bg-white p-6">
            <h2 class="mb-4 text-sm font-semibold text-neutral-700">Profil</h2>
            @if (session('profile-status')) <p class="mb-3 text-sm text-emerald-700">{{ session('profile-status') }}</p> @endif
            <form wire:submit="updateProfile" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Ad Soyad</label>
                    <input wire:model="name" type="text" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Panel dili</label>
                    <select wire:model="locale" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                        <option value="tr">Türkçe</option>
                        <option value="en">English</option>
                    </select>
                </div>
                <button type="submit" class="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800">Kaydet</button>
            </form>
        </div>

        <div class="rounded-lg border border-neutral-200 bg-white p-6">
            <h2 class="mb-4 text-sm font-semibold text-neutral-700">E-posta adresi</h2>
            <p class="mb-3 text-sm text-neutral-600">Mevcut: <strong>{{ auth()->user()->email }}</strong>
                @if (auth()->user()->pending_email)
                    <span class="text-amber-600">({{ auth()->user()->pending_email }} doğrulanmayı bekliyor)</span>
                @endif
            </p>
            @if (session('email-status')) <p class="mb-3 text-sm text-emerald-700">{{ session('email-status') }}</p> @endif
            <form wire:submit="requestEmailChange" class="flex items-end gap-3">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-neutral-700">Yeni e-posta</label>
                    <input wire:model="new_email" type="email" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                    @error('new_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="rounded-md border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50">Değiştir</button>
            </form>
        </div>

        <div class="rounded-lg border border-neutral-200 bg-white p-6">
            <h2 class="mb-4 text-sm font-semibold text-neutral-700">Parola değiştir</h2>
            @if (session('password-status')) <p class="mb-3 text-sm text-emerald-700">{{ session('password-status') }}</p> @endif
            <form wire:submit="updatePassword" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Mevcut parola</label>
                    <input wire:model="current_password" type="password" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                    @error('current_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Yeni parola</label>
                    <input wire:model="new_password" type="password" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                    @error('new_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Yeni parola (tekrar)</label>
                    <input wire:model="new_password_confirmation" type="password" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800">Parolayı güncelle</button>
            </form>
        </div>

        <div class="rounded-lg border border-neutral-200 bg-white p-6">
            <h2 class="mb-4 text-sm font-semibold text-neutral-700">Aktif oturumlar</h2>
            @if (session('sessions-status')) <p class="mb-3 text-sm text-emerald-700">{{ session('sessions-status') }}</p> @endif
            <ul class="mb-4 divide-y divide-neutral-100 text-sm">
                @foreach ($sessions as $s)
                    <li class="flex items-center justify-between py-2">
                        <div>
                            <span class="font-medium">{{ $s->ip_address ?? 'bilinmiyor' }}</span>
                            @if ($s->id === $currentSessionId)
                                <span class="ml-2 inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">bu oturum</span>
                            @endif
                            <div class="text-xs text-neutral-500">{{ \Illuminate\Support\Str::limit($s->user_agent ?? '', 60) }}</div>
                        </div>
                        <span class="text-xs text-neutral-500">{{ \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
            <button wire:click="revokeOtherSessions" class="rounded-md border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50">
                Diğer tüm oturumları kapat
            </button>
        </div>

        <div class="rounded-lg border border-neutral-200 bg-white p-6">
            <h2 class="mb-2 text-sm font-semibold text-neutral-700">Verilerimi dışa aktar</h2>
            <p class="mb-4 text-sm text-neutral-600">Monitörleriniz, olaylarınız, bildirim kanallarınız ve müşterilerinizin tek bir JSON dosyası halinde dökümü.</p>
            <a href="{{ route('settings.export') }}" class="inline-block rounded-md border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50">
                JSON olarak indir
            </a>
        </div>

        <div class="rounded-lg border border-red-200 bg-white p-6">
            <h2 class="mb-2 text-sm font-semibold text-red-700">Hesabı sil</h2>
            <p class="mb-4 text-sm text-neutral-600">Bu işlem geri alınamaz. Tüm monitörler, olaylar, bildirim kanalları ve müşteriler kalıcı olarak silinir.</p>
            <form wire:submit="deleteAccount" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Onaylamak için organizasyon adını yazın: <strong>{{ auth()->user()->currentOrganization()?->name }}</strong></label>
                    <input wire:model="delete_org_name" type="text" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                    @error('delete_org_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Parolanız</label>
                    <input wire:model="delete_password" type="password" class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm">
                    @error('delete_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" wire:confirm="Hesabınızı ve tüm verilerinizi kalıcı olarak silmek istediğinize emin misiniz?"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                    Hesabı kalıcı olarak sil
                </button>
            </form>
        </div>
    </div>
</div>
