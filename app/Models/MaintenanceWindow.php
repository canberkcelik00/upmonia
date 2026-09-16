<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MaintenanceWindow extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = ['organization_id', 'name', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class, 'maintenance_window_monitors');
    }

    public function coversNow(): bool
    {
        return $this->starts_at->isPast() && $this->ends_at->isFuture();
    }

    /**
     * A window with no attached monitors covers the whole organization; one with specific
     * monitors attached covers only those (mirrors the source app's optional `monitor_ids`).
     */
    public function appliesTo(Monitor $monitor): bool
    {
        if ($monitor->organization_id !== $this->organization_id) {
            return false;
        }

        return ! $this->monitors()->exists() || $this->monitors()->where('monitors.id', $monitor->id)->exists();
    }

    /**
     * Deliberate design decision, not an oversight: a maintenance window only suppresses
     * outbound notifications (see CheckResultApplier's notify_pending logic). Checks still
     * run and incidents are still opened/closed as normal during a window, so uptime history
     * and rollups stay accurate — only the customer-facing alert noise is silenced.
     */
    public static function suppressesNotificationsFor(Monitor $monitor): bool
    {
        return static::query()
            ->where('organization_id', $monitor->organization_id)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->get()
            ->contains(fn (self $window) => $window->appliesTo($monitor));
    }
}
