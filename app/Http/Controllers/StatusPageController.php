<?php

namespace App\Http\Controllers;

use App\Models\StatusPage;
use Illuminate\View\View;

class StatusPageController extends Controller
{
    /**
     * Deliberately narrow: only ever selects fields safe to expose publicly (name, status,
     * uptime history). Never url/host/port/error_msg — those stay internal even though the
     * monitor row technically has them, matching the source app's getPublicStatusPage().
     */
    public function show(string $slug): View
    {
        $page = StatusPage::where('slug', $slug)->where('enabled', true)->firstOrFail();

        $monitors = $page->monitors()
            ->with('state:monitor_id,status,last_checked_at')
            ->get(['monitors.id', 'monitors.name'])
            ->map(fn ($m) => [
                'name' => $m->pivot->display_name ?: $m->name,
                'status' => $m->state->status,
                'last_checked_at' => $m->state->last_checked_at,
            ]);

        $overallStatus = $monitors->contains(fn ($m) => in_array($m['status'], ['down', 'suspect'], true))
            ? 'degraded'
            : 'operational';

        $incidents = $page->show_history
            ? \App\Models\Incident::withoutGlobalScopes()
                ->whereIn('monitor_id', $page->monitors()->pluck('monitors.id'))
                ->orderByDesc('started_at')
                ->limit(20)
                ->get(['id', 'monitor_id', 'state', 'started_at', 'resolved_at', 'cause_class'])
                ->load('monitor:id,name')
            : collect();

        return view('status-page.show', compact('page', 'monitors', 'overallStatus', 'incidents'));
    }
}
