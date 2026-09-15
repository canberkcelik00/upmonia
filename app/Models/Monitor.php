<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Monitor extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'client_id', 'name', 'type', 'url', 'host', 'port', 'method',
        'headers', 'body', 'expected_status', 'keyword', 'keyword_mode', 'interval_s',
        'timeout_ms', 'region', 'follow_redirects', 'max_redirects', 'verify_ssl',
        'ssl_warn_days', 'confirm_threshold', 'recover_threshold', 'heartbeat_grace_s',
        'heartbeat_token', 'enabled',
    ];

    protected function casts(): array
    {
        return [
            'expected_status' => 'array',
            // Custom request headers, e.g. {"Authorization": "Bearer ..."}. Encrypted at rest
            // via Laravel's APP_KEY-backed cipher — the Laravel-native equivalent of the source
            // app's hand-rolled AES-256-GCM envelope over UPTIK_SECRET_KEY.
            'headers' => 'encrypted:array',
            'follow_redirects' => 'boolean',
            'verify_ssl' => 'boolean',
            'enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Monitor $monitor) {
            $monitor->state()->create(['status' => 'pending']);
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function state(): HasOne
    {
        return $this->hasOne(MonitorState::class);
    }

    public function checkResults(): HasMany
    {
        return $this->hasMany(CheckResult::class);
    }

    public function rollups1m(): HasMany
    {
        return $this->hasMany(CheckRollup1m::class);
    }

    public function rollups1h(): HasMany
    {
        return $this->hasMany(CheckRollup1h::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function alertChannels(): BelongsToMany
    {
        return $this->belongsToMany(AlertChannel::class, 'monitor_channels', 'monitor_id', 'channel_id')
            ->withPivot('delay_s');
    }

    public function maintenanceWindows(): BelongsToMany
    {
        return $this->belongsToMany(MaintenanceWindow::class, 'maintenance_window_monitors');
    }

    public function statusPages(): BelongsToMany
    {
        return $this->belongsToMany(StatusPage::class, 'status_page_monitors')
            ->withPivot('display_name', 'sort_order');
    }
}
