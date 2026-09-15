<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['incident_id', 'ts', 'type', 'payload'];

    protected function casts(): array
    {
        return [
            'ts' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
