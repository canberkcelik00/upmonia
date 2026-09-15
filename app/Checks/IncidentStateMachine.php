<?php

namespace App\Checks;

use App\Models\Monitor;
use App\Models\MonitorState;
use Carbon\Carbon;

/**
 * v1 simplification (single vantage point — shared hosting is one server, not the source
 * Node app's multi-region fra/iad/sin corroboration voting): a monitor goes down purely on
 * `consecutive_fails >= confirm_threshold` and recovers on `consecutive_ok >= recover_threshold`.
 * No region-health circuit breaker, no cross-region "confirm" requests. Flap detection (last
 * 10 minutes, 4+ confirmed transitions) is kept — it's simple, valuable, and region-independent.
 *
 * States: pending → up → suspect → down → recovering → up (plus paused, which this class
 * never evaluates — a paused monitor is skipped entirely by ProbeRun).
 */
class IncidentStateMachine
{
    private const FLAP_WINDOW_MINUTES = 10;

    private const FLAP_THRESHOLD = 4;

    public function evaluate(MonitorState $state, Monitor $monitor, CheckOutcome $result): StateTransition
    {
        $status = $state->status;
        $recentTransitions = $state->recent_transitions ?? [];

        if ($result->ok) {
            $consecutiveOk = $state->consecutive_ok + 1;

            if (in_array($status, ['down', 'recovering'], true)) {
                if ($consecutiveOk >= $monitor->recover_threshold) {
                    $recentTransitions = $this->recordTransition($recentTransitions);

                    return new StateTransition('up', 'recovered', 0, $consecutiveOk, $this->isFlapping($recentTransitions), $recentTransitions);
                }

                return new StateTransition('recovering', 'none', 0, $consecutiveOk, $this->isFlapping($recentTransitions), $recentTransitions);
            }

            // pending, suspect, up → up. A suspect that recovers before confirm_threshold
            // never had an incident open, so there's nothing to "resolve".
            return new StateTransition('up', 'none', 0, $consecutiveOk, $this->isFlapping($recentTransitions), $recentTransitions);
        }

        $consecutiveFails = $state->consecutive_fails + 1;

        if ($status === 'down') {
            return new StateTransition('down', 'none', $consecutiveFails, 0, $this->isFlapping($recentTransitions), $recentTransitions);
        }

        if ($consecutiveFails >= $monitor->confirm_threshold) {
            $recentTransitions = $this->recordTransition($recentTransitions);

            return new StateTransition('down', 'confirmed_down', $consecutiveFails, 0, $this->isFlapping($recentTransitions), $recentTransitions);
        }

        return new StateTransition('suspect', 'suspected', $consecutiveFails, 0, $this->isFlapping($recentTransitions), $recentTransitions);
    }

    private function recordTransition(array $recentTransitions): array
    {
        $recentTransitions[] = now()->toIso8601String();
        $cutoff = now()->subMinutes(self::FLAP_WINDOW_MINUTES);

        return array_values(array_filter(
            $recentTransitions,
            fn ($ts) => Carbon::parse($ts)->gte($cutoff)
        ));
    }

    private function isFlapping(array $recentTransitions): bool
    {
        return count($recentTransitions) >= self::FLAP_THRESHOLD;
    }
}
