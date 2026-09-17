<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertChannel extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = ['organization_id', 'client_id', 'type', 'name', 'config', 'enabled'];

    protected function casts(): array
    {
        return [
            // e.g. {"email": "ops@acme.example"}. Encrypted at rest via Laravel's `encrypted`
            // cast (APP_KEY-backed), the Laravel-native equivalent of the source app's
            // hand-rolled AES-256-GCM envelope over APP_KEY.
            'config' => 'encrypted:array',
            'verified_at' => 'datetime',
            'enabled' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class, 'monitor_channels', 'channel_id', 'monitor_id')
            ->withPivot('delay_s');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AlertNotification::class, 'channel_id');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
