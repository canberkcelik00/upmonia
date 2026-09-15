<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorState extends Model
{
    protected $table = 'monitor_states';

    protected $primaryKey = 'monitor_id';

    public $incrementing = false;

    const CREATED_AT = null;

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'monitor_id', 'status', 'last_checked_at', 'next_check_at', 'consecutive_fails',
        'consecutive_ok', 'last_latency_ms', 'last_error_class', 'last_error_msg',
        'last_status_code', 'recent_transitions', 'flapping', 'cert_expires_at', 'cert_issuer',
        'last_heartbeat_at', 'current_incident_id', 'check_requested_at', 'locked_until',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
            'next_check_at' => 'datetime',
            'recent_transitions' => 'array',
            'flapping' => 'boolean',
            'cert_expires_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'check_requested_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function currentIncident(): BelongsTo
    {
        return $this->belongsTo(Incident::class, 'current_incident_id');
    }
}
