<?php

use App\Services\ChannelVerificationService;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $status = '';

    public function mount(string $token): void
    {
        $this->status = ChannelVerificationService::verify($token)['status'];
    }
}
?>

<div class="rounded-lg border border-neutral-200 bg-white p-6 text-center">
    @if ($status === 'verified')
        <h1 class="mb-2 text-lg font-semibold">Bildirim kanalı doğrulandı.</h1>
    @elseif ($status === 'expired')
        <h1 class="mb-2 text-lg font-semibold text-amber-700">Bu doğrulama bağlantısının süresi dolmuş.</h1>
    @else
        <h1 class="mb-2 text-lg font-semibold text-red-700">Bu doğrulama bağlantısı geçersiz veya daha önce kullanılmış.</h1>
    @endif

    <a href="{{ route(auth()->check() ? 'monitors.index' : 'login') }}" class="mt-4 inline-block text-sm font-medium text-neutral-900 underline">
        Devam et
    </a>
</div>
