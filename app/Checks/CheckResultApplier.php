<?php

namespace App\Checks;

use App\Models\CheckResult;
use App\Models\Incident;
use App\Models\IncidentEvent;
use App\Models\MaintenanceWindow;
use App\Models\Monitor;
use App\Models\MonitorState;
use Illuminate\Support\Facades\DB;

/**
 * Applies one CheckOutcome to a monitor: writes the raw check_results row, runs it through
 * IncidentStateMachine, opens/closes the incidents row, appends an incident_events audit
 * entry, and schedules the monitor's next check — all inside one transaction with a row
 * lock on monitor_states, so two overlapping probe:run invocations (shouldn't happen given
 * the scheduler's withoutOverlapping(), but cheap insurance) can't race each other.
 */
class CheckResultApplier
{
    public function __construct(private readonly IncidentStateMachine $stateMachine = new IncidentStateMachine) {}

    public function apply(Monitor $monitor, CheckOutcome $result): void
    {
        DB::transaction(function () use ($monitor, $result) {
            /** @var MonitorState $state */
            $state = MonitorState::where('monitor_id', $monitor->id)->lockForUpdate()->firstOrFail();

            CheckResult::create([
                'monitor_id' => $monitor->id,
                'region' => $monitor->region,
                'ts' => now(),
                'ok' => $result->ok,
                'status_code' => $result->statusCode,
                'latency_ms' => $result->latencyMs,
                'dns_ms' => $result->dnsMs,
                'tcp_ms' => $result->tcpMs,
                'tls_ms' => $result->tlsMs,
                'ttfb_ms' => $result->ttfbMs,
                'error_class' => $result->errorClass,
                'error_msg' => $result->errorMsg,
                'resolved_ip' => $result->resolvedIp,
                'redirect_chain' => $result->redirectChain,
            ]);

            $transition = $this->stateMachine->evaluate($state, $monitor, $result);

            $incidentId = $state->current_incident_id;
            $suppressNotify = MaintenanceWindow::suppressesNotificationsFor($monitor);

            if ($transition->transition === 'confirmed_down') {
                $incident = new Incident([
                    'organization_id' => $monitor->organization_id,
                    'monitor_id' => $monitor->id,
                    'state' => 'open',
                    'started_at' => now(),
                    'cause_class' => $result->errorClass,
                    'cause_detail' => $result->errorMsg,
                    'flapping' => $transition->flapping,
                    'notify_pending' => ! $suppressNotify,
                ]);
                $incident->markOpen();
                $incident->save();

                IncidentEvent::create([
                    'incident_id' => $incident->id,
                    'ts' => now(),
                    'type' => 'triggered',
                    'payload' => ['error_class' => $result->errorClass, 'error_msg' => $result->errorMsg],
                ]);

                $incidentId = $incident->id;
            } elseif ($transition->transition === 'recovered' && $incidentId) {
                $incident = Incident::find($incidentId);

                if ($incident) {
                    $incident->state = 'resolved';
                    $incident->resolved_at = now();
                    $incident->duration_s = $incident->started_at->diffInSeconds(now());
                    $incident->flapping = $transition->flapping;
                    $incident->notify_pending = ! $suppressNotify;
                    $incident->markResolved();
                    $incident->save();

                    IncidentEvent::create([
                        'incident_id' => $incident->id,
                        'ts' => now(),
                        'type' => 'resolved',
                        'payload' => ['duration_s' => $incident->duration_s],
                    ]);
                }

                $incidentId = null;
            }

            $state->update([
                'status' => $transition->status,
                'last_checked_at' => now(),
                'next_check_at' => $this->jitteredNext($monitor->interval_s),
                'consecutive_fails' => $transition->consecutiveFails,
                'consecutive_ok' => $transition->consecutiveOk,
                'last_latency_ms' => $result->latencyMs,
                'last_error_class' => $result->errorClass,
                'last_error_msg' => $result->errorMsg,
                'last_status_code' => $result->statusCode,
                'recent_transitions' => $transition->recentTransitions,
                'flapping' => $transition->flapping,
                'cert_expires_at' => $result->certExpiresAt,
                'cert_issuer' => $result->certIssuer,
                'current_incident_id' => $incidentId,
                'locked_until' => null,
                'check_requested_at' => null,
            ]);
        });
    }

    /**
     * +/-10% jitter so a batch of monitors imported (or leased) at the same moment don't all
     * come due again in perfect lockstep forever.
     */
    private function jitteredNext(int $intervalS): \DateTimeInterface
    {
        $jitter = (int) round($intervalS * (mt_rand(-10, 10) / 100));

        return now()->addSeconds(max(30, $intervalS + $jitter));
    }
}
