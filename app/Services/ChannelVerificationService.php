<?php

namespace App\Services;

use App\Mail\ChannelVerifyEmail;
use App\Models\AlertChannel;
use App\Models\ChannelVerificationToken;
use Illuminate\Support\Facades\Mail;

class ChannelVerificationService
{
    public static function send(AlertChannel $channel): void
    {
        [$raw, $hash] = TokenService::generate();

        ChannelVerificationToken::create([
            'id' => $hash,
            'channel_id' => $channel->id,
            'expires_at' => now()->addHours(24),
        ]);

        $locale = $channel->organization->ownerLocale();

        Mail::to($channel->config['email'])->send(new ChannelVerifyEmail($channel, $raw, $channel->config['email'], $locale));
    }

    /**
     * @return array{status: 'verified'|'invalid'|'expired', channel?: AlertChannel}
     */
    public static function verify(string $rawToken): array
    {
        $token = ChannelVerificationToken::find(TokenService::hash($rawToken));

        if (! $token || $token->used_at !== null) {
            return ['status' => 'invalid'];
        }

        if ($token->expires_at->isPast()) {
            return ['status' => 'expired'];
        }

        $channel = $token->channel;
        $channel->update(['verified_at' => now()]);

        $token->used_at = now();
        $token->save();

        return ['status' => 'verified', 'channel' => $channel];
    }
}
