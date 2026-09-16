<?php

use App\Services\EmailVerificationService;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $status = '';

    public function mount(string $token): void
    {
        $this->status = EmailVerificationService::verify($token)['status'];
    }
}
?>

<x-ui.verification-result
    :status="$status"
    :verified="__('auth.email_verified')"
    :expired="__('auth.email_verify_expired')"
    :invalid="__('auth.email_verify_invalid')"
/>
