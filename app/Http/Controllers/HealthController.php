<?php

namespace App\Http\Controllers;

use App\Models\CheckResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Liveness (/api/health) and deep (/api/health/deep) checks. The source Node app's version
 * reasoned about per-region worker heartbeats; that concept doesn't exist in v1's single-
 * vantage-point design (see IncidentStateMachine's docblock), so "deep" here just means
 * "can we reach the database, and is probe:run actually landing rows" — the two things that
 * would silently break monitoring on a shared host without anyone noticing.
 */
class HealthController extends Controller
{
    public function liveness(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function deep(): JsonResponse
    {
        try {
            DB::select('select 1');
            $dbOk = true;
        } catch (\Throwable) {
            $dbOk = false;
        }

        // max('updated_at'), not max('ts'): check_results is a compacted segment log
        // (CheckResultApplier) — ts stops advancing for a monitor that stays stable while
        // updated_at keeps advancing every tick it's still actively checked. updated_at is
        // always >= ts by construction, so it alone is a correct "is probing still landing
        // data" signal.
        $lastCheckAt = $dbOk ? CheckResult::max('updated_at') : null;
        $lastCheckAgeS = $lastCheckAt ? now()->diffInSeconds($lastCheckAt) : null;

        // Monitors run on their own interval_s (min 60s); anything over 5 minutes since the
        // last check landed anywhere suggests probe:run's cron entry has stopped firing.
        $probeHealthy = $lastCheckAgeS !== null && $lastCheckAgeS < 300;

        $healthy = $dbOk && $probeHealthy;

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'database' => $dbOk,
            'last_check_at' => $lastCheckAt,
            'last_check_age_s' => $lastCheckAgeS,
            'probe_healthy' => $probeHealthy,
        ], $healthy ? 200 : 503);
    }
}
