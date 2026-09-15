<?php

namespace App\Mail;

use App\Models\EmailVerificationToken;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $rawToken, public EmailVerificationToken $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.verify_email.subject', [], $this->user->locale));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email',
            with: [
                'user' => $this->user,
                'locale' => $this->user->locale,
                'verifyUrl' => route('verify-email', ['token' => $this->rawToken]),
            ],
        );
    }
}
