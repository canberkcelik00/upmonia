<?php

namespace App\Checks;

use App\Checks\Checkers\HttpChecker;
use App\Checks\Checkers\SslChecker;
use App\Checks\Checkers\TcpChecker;
use App\Models\Monitor;
use Illuminate\Support\Collection;

/**
 * Dispatches to the right checker by monitor type. `heartbeat` is deliberately not handled
 * here — it's a passive dead-man's-switch with no network call, evaluated only from
 * MaintenanceRun via App\Checks\Checkers\HeartbeatChecker (see its docblock).
 */
class CheckDispatcher
{
    public function __construct(
        private readonly HttpChecker $http = new HttpChecker,
        private readonly SslChecker $ssl = new SslChecker,
        private readonly TcpChecker $tcp = new TcpChecker,
        private readonly ConcurrentHttpChecker $concurrentHttp = new ConcurrentHttpChecker,
    ) {}

    public function run(Monitor $monitor): CheckOutcome
    {
        return match ($monitor->type) {
            'http', 'keyword' => $this->http->check($monitor),
            'ssl' => $this->ssl->check($monitor),
            'tcp_port' => $this->tcp->check($monitor),
            default => throw new \InvalidArgumentException("CheckDispatcher cannot run type: {$monitor->type}"),
        };
    }

    /**
     * ProbeRun's batch entry point. `http`/`keyword` monitors — typically the majority of any
     * account's monitors — run concurrently via curl_multi (ConcurrentHttpChecker); `ssl`/
     * `tcp_port` run sequentially, which is an accepted v1 tradeoff (see ConcurrentHttpChecker's
     * docblock) since a single TLS handshake or TCP connect is fast and they're usually a
     * minority of a batch. `heartbeat` monitors are never passed in here.
     *
     * @param  Collection<int,Monitor>  $monitors
     * @return array<int,CheckOutcome> keyed by monitor id
     */
    public function runBatch(Collection $monitors): array
    {
        $httpMonitors = $monitors->whereIn('type', ['http', 'keyword']);
        $otherMonitors = $monitors->whereNotIn('type', ['http', 'keyword']);

        $outcomes = $httpMonitors->isNotEmpty()
            ? $this->concurrentHttp->runBatch($httpMonitors)
            : [];

        foreach ($otherMonitors as $monitor) {
            $outcomes[$monitor->id] = $this->run($monitor);
        }

        return $outcomes;
    }
}
