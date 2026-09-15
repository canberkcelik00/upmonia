<?php

namespace App\Checks;

final readonly class StateTransition
{
    /**
     * @param  string  $status  pending|up|suspect|down|recovering|paused
     * @param  string  $transition  none|suspected|confirmed_down|recovering|recovered
     */
    public function __construct(
        public string $status,
        public string $transition,
        public int $consecutiveFails,
        public int $consecutiveOk,
        public bool $flapping,
        public array $recentTransitions,
    ) {}
}
