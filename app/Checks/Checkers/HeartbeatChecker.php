<?php

namespace App\Checks\Checkers;

use App\Checks\CheckOutcome;
use App\Models\Monitor;

/**
 * `heartbeat` monitor type: a passive dead-man's-switch. No network call — the customer's
 * own cron pings GET/POST /heartbeat/{token} (see HeartbeatController), and this just checks
 * whether that ping is overdue against monitor_states.last_heartbeat_at. Evaluated only from
 * MaintenanceRun (App\Console\Commands\MaintenanceRun), never from ProbeRun.
 */
class HeartbeatChecker
{
    public function check(Monitor $monitor, ?\DateTimeInterface $lastHeartbeatAt): CheckOutcome
    {
        if ($lastHeartbeatAt === null) {
            return new CheckOutcome(ok: false, errorClass: 'heartbeat_missed', errorMsg: 'No heartbeat received yet');
        }

        $graceS = $monitor->heartbeat_grace_s ?? $monitor->interval_s;
        $overdueBy = time() - $lastHeartbeatAt->getTimestamp();

        if ($overdueBy > $graceS) {
            return new CheckOutcome(ok: false, errorClass: 'heartbeat_missed', errorMsg: "No heartbeat for {$overdueBy}s (grace: {$graceS}s)");
        }

        return new CheckOutcome(ok: true);
    }
}
