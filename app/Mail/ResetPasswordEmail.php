<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Replaces Laravel's built-in Illuminate\Auth\Notifications\ResetPassword notification
 * (see User::sendPasswordResetNotification()) so the email renders in the recipient's
 * stored locale and matches the rest of Upmonia's mail templates, instead of the
 * notification's hardcoded English strings.
 */
class ResetPasswordEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $rawToken) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.reset_password.subject', [], $this->user->locale));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
            with: [
                'user' => $this->user,
                'locale' => $this->user->locale,
                'resetUrl' => route('password.reset', [
                    'token' => $this->rawToken,
                    'email' => $this->user->email,
                ]),
            ],
        );
    }
}
