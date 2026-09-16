<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckResult extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'monitor_id', 'region', 'ts', 'ok', 'status_code', 'latency_ms', 'dns_ms', 'tcp_ms',
        'tls_ms', 'ttfb_ms', 'error_class', 'error_msg', 'resolved_ip', 'redirect_chain',
        'sample_count', 'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'ts' => 'datetime',
            'updated_at' => 'datetime',
            'ok' => 'boolean',
            'redirect_chain' => 'array',
            'sample_count' => 'integer',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
