<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Composite key (monitor_id, region, bucket) — see App\Models\CheckRollup1m for why writes
 * go through the query builder's upsert() rather than Eloquent save().
 */
class CheckRollup1h extends Model
{
    protected $table = 'check_rollups_1h';

    public $timestamps = false;

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'bucket' => 'datetime',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
