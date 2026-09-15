<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * The dead-man's-switch ping endpoint a heartbeat monitor's own external cron hits. Just
 * stamps last_heartbeat_at — going down is entirely a matter of that stamp going stale,
 * evaluated separately by MaintenanceRun (see HeartbeatChecker's docblock).
 */
class HeartbeatController extends Controller
{
    public function __invoke(Request $request, string $token): Response
    {
        if (! preg_match('/^[a-zA-Z0-9]{32}$/', $token)) {
            return response('Not found', 404);
        }

        try {
            $monitor = Monitor::where('heartbeat_token', $token)->where('type', 'heartbeat')->first();
        } catch (\Throwable $e) {
            Log::error('Heartbeat ping failed: '.$e->getMessage());

            // 503 so the customer's own cron treats this as transient and retries, rather
            // than a permanent "your token is wrong" signal.
            return response('Service unavailable', 503);
        }

        if (! $monitor) {
            return response('Not found', 404);
        }

        $monitor->state->update(['last_heartbeat_at' => now()]);

        return response('OK', 200);
    }
}
