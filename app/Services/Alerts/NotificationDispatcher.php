<?php

namespace App\Services\Alerts;

use App\Mail\IncidentResolvedEmail;
use App\Mail\IncidentTriggeredEmail;
use App\Models\AlertNotification;
use Illuminate\Support\Facades\Mail;

/**
 * Delivers due AlertNotification rows — called each maintenance:run tick, after
 * NotificationEnqueuer::enqueue(). Mirrors the source app's alerts.ts deliverDue(): a
 * DB-driven pending/failed/sent state machine (not Laravel's queue system, since there's no
 * queue worker daemon on shared hosting either — see MaintenanceRun's docblock).
 *
 * v1 simplification: every failure gets the same backoff/retry treatment up to max_attempts
 * (5, ~7 hours total) rather than the source's permanent-vs-transient distinction based on
 * parsing Resend's raw REST response codes — we go through Laravel Mail's Resend transport,
 * which doesn't expose that distinction as cleanly, and a handful of wasted retries for a
 * truly permanent failure (e.g. a bad recipient address) is a fully acceptable v1 cost.
 */
class NotificationDispatcher
{
    private const BACKOFF_S = [60, 300, 900, 3600, 21600]; // 1m, 5m, 15m, 1h, 6h

    private const BATCH_LIMIT = 200;

    public function deliverDue(): array
    {
        $due = AlertNotification::whereIn('status', ['pending', 'failed'])
            ->whereColumn('attempts', '<', 'max_attempts')
            ->where('next_attempt_at', '<=', now())
            ->orderBy('next_attempt_at')
            ->limit(self::BATCH_LIMIT)
            ->with(['incident.monitor.organization', 'channel'])
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($due as $notification) {
            if ($this->deliver($notification)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'total' => $due->count()];
    }

    private function deliver(AlertNotification $notification): bool
    {
        try {
            $channel = $notification->channel;
            $incident = $notification->incident;
            $email = $channel->config['email'] ?? null;

            if (! $email) {
                throw new \RuntimeException('Channel has no email address configured.');
            }

            $locale = $incident->monitor->organization->ownerLocale();

            $mailable = $notification->event_type === 'triggered'
                ? new IncidentTriggeredEmail($incident, $locale)
                : new IncidentResolvedEmail($incident, $locale);

            Mail::to($email)->send($mailable);

            $notification->status = 'sent';
            $notification->sent_at = now();
            $notification->error = null;
            $notification->save();

            return true;
        } catch (\Throwable $e) {
            $notification->attempts++;
            $notification->error = $e->getMessage();
            $notification->status = 'failed';
            $notification->next_attempt_at = now()->addSeconds(
                self::BACKOFF_S[min($notification->attempts - 1, count(self::BACKOFF_S) - 1)]
            );
            $notification->save();

            return false;
        }
    }
}
