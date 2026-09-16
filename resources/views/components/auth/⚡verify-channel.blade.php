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

<x-ui.verification-result
    :status="$status"
    :verified="__('auth.channel_verified')"
    :expired="__('auth.channel_verify_expired')"
    :invalid="__('auth.channel_verify_invalid')"
/>
