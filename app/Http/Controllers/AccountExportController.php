<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * A single JSON export of everything the account owns — monitors, incidents, alert channels,
 * clients — the source app's exportAccountData(), useful both as a GDPR/KVKK-style courtesy
 * and as a manual backup since there's no admin-facing DB export tool on shared hosting.
 */
class AccountExportController extends Controller
{
    public function __invoke(): Response
    {
        $org = Auth::user()->currentOrganization();

        $data = [
            'exported_at' => now()->toIso8601String(),
            'organization' => $org?->only(['id', 'name', 'slug', 'plan']),
            'monitors' => Monitor::with('client:id,name')->get()->map->only([
                'id', 'name', 'type', 'url', 'host', 'port', 'method', 'interval_s',
                'timeout_ms', 'region', 'enabled', 'created_at',
            ]),
            'incidents' => Incident::with('monitor:id,name')->get()->map(fn ($i) => [
                'monitor' => $i->monitor->name,
                'state' => $i->state,
                'started_at' => $i->started_at,
                'resolved_at' => $i->resolved_at,
                'cause_class' => $i->cause_class,
            ]),
            'alert_channels' => \App\Models\AlertChannel::whereNull('client_id')->get()->map->only(['id', 'name', 'type', 'enabled', 'verified_at']),
            'clients' => \App\Models\Client::get()->map->only(['id', 'name', 'contact_emails']),
        ];

        $filename = 'upmonia-export-'.now()->format('Y-m-d').'.json';

        return response(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
