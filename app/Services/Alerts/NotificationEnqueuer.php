<?php

namespace App\Services\Alerts;

use App\Models\AlertNotification;
use App\Models\Incident;

/**
 * Turns every `incidents.notify_pending = true` row into one AlertNotification per attached,
 * enabled, verified email channel — called each maintenance:run tick, mirroring the source
 * app's alerts.ts enqueue(). The notifications table's unique(incident_id, channel_id,
 * event_type) index is the hard guarantee a channel never gets the same event twice, so this
 * can safely run every tick without its own dedupe bookkeeping.
 */
class NotificationEnqueuer
{
    public function enqueue(): int
    {
        $incidents = Incident::withoutGlobalScopes()
            ->where('notify_pending', true)
            ->with('monitor.alertChannels')
            ->get();

        $created = 0;

        foreach ($incidents as $incident) {
            $eventType = $incident->state === 'open' ? 'triggered' : 'resolved';

            $channels = $incident->monitor->alertChannels
                ->where('type', 'email')
                ->where('enabled', true)
                ->whereNotNull('verified_at');

            foreach ($channels as $channel) {
                // Good news isn't delayed, even if the channel has a delay configured for alerts.
                $delayS = $eventType === 'triggered' ? (int) $channel->pivot->delay_s : 0;

                $notification = AlertNotification::firstOrNew([
                    'incident_id' => $incident->id,
                    'channel_id' => $channel->id,
                    'event_type' => $eventType,
                ]);

                if (! $notification->exists) {
                    $notification->status = 'pending';
                    $notification->next_attempt_at = now()->addSeconds($delayS);
                    $notification->save();
                    $created++;
                }
            }

            $incident->notify_pending = false;
            $incident->save();
        }

        return $created;
    }
}
