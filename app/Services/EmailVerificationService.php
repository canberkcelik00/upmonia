<?php

namespace App\Services;

use App\Mail\VerifyEmail;
use App\Models\EmailVerificationToken;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    /**
     * @param  string|null  $email  The address being proven. Defaults to the user's current
     *                              email; pass an explicit address during an email-change flow.
     */
    public static function send(User $user, ?string $email = null): void
    {
        [$raw, $hash] = TokenService::generate();

        $email ??= $user->email;

        $token = EmailVerificationToken::create([
            'id' => $hash,
            'user_id' => $user->id,
            'email' => $email,
            'expires_at' => now()->addHours(24),
        ]);

        Mail::to($email)->send(new VerifyEmail($user, $raw, $token));
    }

    /**
     * @return array{status: 'verified'|'invalid'|'expired', user?: User}
     */
    public static function verify(string $rawToken): array
    {
        $token = EmailVerificationToken::find(TokenService::hash($rawToken));

        if (! $token || $token->used_at !== null) {
            return ['status' => 'invalid'];
        }

        if ($token->expires_at->isPast()) {
            return ['status' => 'expired'];
        }

        $user = $token->user;

        // Email-change flow: the token proves a *different* address than users.email.
        if ($token->email !== $user->email) {
            $user->email = $token->email;
            $user->pending_email = null;
        }

        $user->email_verified_at = now();
        $user->save();

        $token->used_at = now();
        $token->save();

        return ['status' => 'verified', 'user' => $user];
    }
}
