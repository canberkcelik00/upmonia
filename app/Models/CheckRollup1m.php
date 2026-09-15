<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Composite key (monitor_id, region, bucket) — Eloquent doesn't model composite primary keys,
 * so maintenance:run writes these via the query builder's upsert(), not $model->save(). This
 * class exists for the read side (dashboard charts), which only ever does where()->get().
 */
class CheckRollup1m extends Model
{
    protected $table = 'check_rollups_1m';

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
