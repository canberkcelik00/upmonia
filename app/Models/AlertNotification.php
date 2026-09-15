<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The `notifications` table: one row per (incident, channel, event_type), delivered by
 * maintenance:run's dispatch step. Named AlertNotification, not Notification, so it isn't
 * confused with Illuminate\Notifications\Notification — this table is a hand-rolled
 * dedupe/retry queue, not a Laravel notification.
 */
class AlertNotification extends Model
{
    protected $table = 'notifications';

    public $timestamps = false;

    protected $fillable = [
        'incident_id', 'channel_id', 'event_type', 'status', 'attempts', 'max_attempts',
        'next_attempt_at', 'provider_msg_id', 'error', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'next_attempt_at' => 'datetime',
            'created_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(AlertChannel::class, 'channel_id');
    }
}
