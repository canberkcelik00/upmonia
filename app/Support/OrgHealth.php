<?php

namespace App\Support;

use App\Models\Incident;
use App\Models\Monitor;

/**
 * Drives the nav logo's mark colour and the browser tab's favicon/title — the "canlı favicon"
 * rule from the brand guide: the tab reads as a status indicator even when the user is looking
 * at another tab. Both queries are already organization-scoped via OrganizationScope, since
 * this only ever runs inside an authenticated web request (see layouts.app's view composer).
 */
class OrgHealth
{
    public static function current(): array
    {
        $statuses = Monitor::query()
            ->join('monitor_states', 'monitor_states.monitor_id', '=', 'monitors.id')
            ->pluck('monitor_states.status');

        $status = match (true) {
            $statuses->contains('down') => 'down',
            $statuses->contains('suspect') || $statuses->contains('recovering') => 'warn',
            default => 'up',
        };

        return [
            'status' => $status,
            'openIncidents' => Incident::where('state', 'open')->count(),
        ];
    }
}
