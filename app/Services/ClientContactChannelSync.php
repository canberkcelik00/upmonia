<?php

namespace App\Services;

use App\Models\AlertChannel;
use App\Models\Client;

/**
 * Keeps a client's auto-managed alert channels (client_id set) in sync with its
 * contact_emails list: adds one auto-verified channel per new address, removes channels for
 * addresses no longer listed. These are distinguished from manually created channels purely
 * by having a non-null client_id (see AlertChannel::$client_id docblock in its migration).
 */
class ClientContactChannelSync
{
    public static function sync(Client $client): void
    {
        $wanted = collect($client->contact_emails ?? [])->filter()->unique()->values();

        $existing = AlertChannel::where('client_id', $client->id)->get();

        foreach ($existing as $channel) {
            if (! $wanted->contains($channel->config['email'] ?? null)) {
                $channel->delete();
            }
        }

        $existingEmails = $existing->pluck('config.email')->filter();

        foreach ($wanted as $email) {
            if ($existingEmails->contains($email)) {
                continue;
            }

            AlertChannel::create([
                'organization_id' => $client->organization_id,
                'client_id' => $client->id,
                'type' => 'email',
                'name' => $email,
                'config' => ['email' => $email],
                'enabled' => true,
                'verified_at' => now(), // auto-verified: the client owner already gave us this address directly
            ]);
        }
    }
}
