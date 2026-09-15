<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'monitor_id', 'state', 'started_at', 'resolved_at', 'duration_s',
        'cause_class', 'cause_detail', 'flapping', 'acknowledged_by', 'acknowledged_at',
        'notify_pending', 'open_marker',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
            'flapping' => 'boolean',
            'acknowledged_at' => 'datetime',
            'notify_pending' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(IncidentEvent::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AlertNotification::class);
    }

    public function isOpen(): bool
    {
        return $this->state === 'open';
    }

    /**
     * Sets open_marker = monitor_id so the incidents_one_open_per_monitor unique index
     * rejects a second concurrently-open incident for the same monitor. See the comment on
     * that column in the create_incidents_table migration for why this is app-managed
     * instead of a DB-generated column.
     */
    public function markOpen(): void
    {
        $this->open_marker = $this->monitor_id;
    }

    public function markResolved(): void
    {
        $this->open_marker = null;
    }
}
