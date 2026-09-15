<?php

namespace App\Checks;

/**
 * Immutable result of a single check attempt against one monitor — the return value of
 * every App\Checks\Checkers\* class and CheckDispatcher::run(). Maps 1:1 onto a check_results
 * row (see CheckResultApplier), minus monitor_id/region which the caller attaches.
 */
final readonly class CheckOutcome
{
    public function __construct(
        public bool $ok,
        public ?int $statusCode = null,
        public ?int $latencyMs = null,
        public ?int $dnsMs = null,
        public ?int $tcpMs = null,
        public ?int $tlsMs = null,
        public ?int $ttfbMs = null,
        public ?string $errorClass = null,
        public ?string $errorMsg = null,
        public ?string $resolvedIp = null,
        public array $redirectChain = [],
        public ?\DateTimeImmutable $certExpiresAt = null,
        public ?string $certIssuer = null,
    ) {}
}
