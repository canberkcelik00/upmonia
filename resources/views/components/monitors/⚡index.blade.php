<?php

use App\Models\Client;
use App\Models\Incident;
use App\Models\Monitor;
use App\Support\Format;
use App\Support\MonitorPulse;
use App\Support\OrgHealth;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = ''; // '' | 'issues' | 'paused'

    #[Url]
    public string $client = '';

    #[Url]
    public bool $expanded = false;

    // Monitors whose whole "problem" is not being measured at all — never mixed in with a
    // real incident's severity ordering.
    private const PROBLEM_STATUSES = ['down', 'suspect', 'recovering'];

    private const SEVERITY_ORDER = "FIELD(monitor_states.status,'down','suspect','recovering','pending','up','paused')";

    public function acknowledge(int $incidentId): void
    {
        Incident::whereKey($incidentId)->update([
            'acknowledged_by' => Auth::id(),
            'acknowledged_at' => now(),
        ]);
    }

    private function baseQuery()
    {
        return Monitor::query()
            ->with('state.currentIncident', 'client')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->client, fn ($q) => $q->where('client_id', $this->client))
            ->when($this->status === 'issues', fn ($q) => $q->whereHas('state', fn ($s) => $s->whereIn('status', self::PROBLEM_STATUSES)))
            ->when($this->status === 'paused', fn ($q) => $q->whereHas('state', fn ($s) => $s->where('status', 'paused')));
    }

    public function with(): array
    {
        // Refreshed on every render (including the wire:poll tick from the table below), so
        // the tab's favicon/title/nav badge stay live without a full page reload — see
        // resources/js/app.js's Livewire.on('org-health', ...) listener.
        $this->dispatch('org-health', ...OrgHealth::current());

        // Unfiltered counts for the segmented control and the summary sentence — reflect the
        // whole org regardless of the active search/status/client filter.
        $statusCounts = Monitor::query()
            ->join('monitor_states', 'monitor_states.monitor_id', '=', 'monitors.id')
            ->selectRaw('monitor_states.status, count(*) as count')
            ->groupBy('monitor_states.status')
            ->pluck('count', 'status');

        $issueCount = collect(self::PROBLEM_STATUSES)->sum(fn ($s) => $statusCounts[$s] ?? 0);
        $pausedCount = $statusCounts['paused'] ?? 0;
        $totalCount = $statusCounts->sum();
        $lastCheckedAt = Monitor::query()
            ->join('monitor_states', 'monitor_states.monitor_id', '=', 'monitors.id')
            ->max('monitor_states.last_checked_at');

        $sortedQuery = $this->baseQuery()
            ->join('monitor_states', 'monitor_states.monitor_id', '=', 'monitors.id')
            ->orderByRaw(self::SEVERITY_ORDER)
            ->orderBy('monitors.name')
            ->select('monitors.*');

        // Collapsed by default (no filters, first page): every problem monitor plus a taste
        // of the healthy ones, so a healthy 40-monitor org doesn't scroll past a wall of
        // green to find the two that need attention. Any filter, search, or "Tümünü göster"
        // switches to a normal paginated list.
        $collapsible = $this->expanded === false && $this->search === '' && $this->status === '' && $this->client === '';

        if ($collapsible) {
            $all = $sortedQuery->get();
            // Only a genuinely "up" monitor is safe to silently tuck away — the footer says
            // "hepsi çalışıyor" (all up), so pending/paused monitors (not a problem, but not
            // up either) always stay visible rather than being miscounted into that claim.
            $isCollapsibleUp = fn ($m) => $m->state->status === 'up';
            $alwaysShown = $all->reject($isCollapsibleUp)->values();
            $collapsibleUp = $all->filter($isCollapsibleUp)->values();
            $monitors = $alwaysShown->concat($collapsibleUp->take(5))->values();
            $remainingHealthy = $collapsibleUp->count() - min(5, $collapsibleUp->count());
        } else {
            $monitors = $sortedQuery->paginate(20);
            $remainingHealthy = 0;
        }

        $rows = $collapsible ? $monitors : $monitors->getCollection();
        $ids = $rows->pluck('id');
        $statusByMonitor = $rows->mapWithKeys(fn ($m) => [$m->id => $m->state->status])->all();
        $ticks = MonitorPulse::ticks($ids, $statusByMonitor);
        $uptime24h = MonitorPulse::uptimePercents($ids, now()->subDay());

        return [
            'monitors' => $monitors,
            'rows' => $rows,
            'statusCounts' => $statusCounts,
            'collapsible' => $collapsible,
            'remainingHealthy' => $remainingHealthy,
            'ticks' => $ticks,
            'uptime24h' => $uptime24h,
            'issueCount' => $issueCount,
            'pausedCount' => $pausedCount,
            'totalCount' => $totalCount,
            'lastCheckedAt' => $lastCheckedAt,
            'openIncidents' => Incident::where('state', 'open')->with('monitor.state')->orderBy('started_at')->limit(3)->get(),
            'clients' => Client::orderBy('name')->get(),
        ];
    }
};
?>

<div>
    <x-ui.page-header :title="__('app.monitors_title')" :description="__('app.monitors_description')">
        <x-slot:actions>
            <x-ui.button variant="primary" href="{{ route('monitors.create') }}" wire:navigate>
                <x-phosphor-plus class="size-4" />
                {{ __('app.monitors_new') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($openIncidents->isNotEmpty())
        <div class="mb-6 flex flex-col gap-2">
            @foreach ($openIncidents as $incident)
                <x-ui.incident-strip :incident="$incident" acknowledge-action="acknowledge({{ $incident->id }})" wire:key="open-incident-{{ $incident->id }}" />
            @endforeach
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <div class="text-[27px] font-bold leading-[1.1] tracking-[-0.025em] text-ink">
                {{ $issueCount > 0 ? __('app.monitors_statement_issues', ['count' => $issueCount]) : __('app.monitors_statement_ok') }}
            </div>
            <div class="mt-2 font-mono text-xs text-muted">
                {{ $totalCount }} {{ __('app.stat_total') }}
                · {{ $statusCounts['up'] ?? 0 }} {{ __('app.stat_up') }}
                · {{ $issueCount }} {{ __('app.stat_issues') }}
                · {{ $pausedCount }} {{ __('app.stat_paused') }}
                @if ($lastCheckedAt)
                    · {!! str_replace(':time', \Illuminate\Support\Carbon::parse($lastCheckedAt)->toDisplayHtml('time'), e(__('app.monitors_last_checked', ['time' => ':time']))) !!}
                @endif
            </div>
        </div>
    </div>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative sm:w-64">
            {{-- Magnifier swaps to a spinner while the debounced search round-trips. --}}
            <x-phosphor-magnifying-glass wire:loading.remove wire:target="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-faint" />
            <x-phosphor-spinner-gap wire:loading wire:target="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 animate-spin text-muted" />
            <x-ui.input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('app.monitors_search_placeholder') }}" class="pl-9" />
        </div>

        <x-ui.segmented
            :options="['' => __('app.monitors_filter_all'), 'issues' => __('app.stat_issues'), 'paused' => __('app.stat_paused')]"
            :active="$status"
            action="$set('status', '%s')"
            :counts="['' => $totalCount, 'issues' => $issueCount, 'paused' => $pausedCount]"
        />

        @if ($clients->isNotEmpty())
            <x-ui.select wire:model.live="client" class="sm:ml-auto sm:w-48">
                <option value="">{{ __('app.monitors_filter_all_clients') }}</option>
                @foreach ($clients as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </x-ui.select>
        @endif
    </div>

    {{-- Refresh feedback: the table dims rather than being replaced by a skeleton, because
         rows are swapped in place on an already-painted list (no layout jump). --}}
    <x-ui.table wire:loading.delay.class="opacity-50" wire:target="search,status,client,expanded,gotoPage,previousPage,nextPage" class="transition-opacity duration-150" wire:poll.30s>
        <x-slot:head>
            <th>{{ __('app.monitors_col_name') }}</th>
            <th>{{ __('app.monitors_col_recent_checks') }}</th>
            <th>{{ __('app.monitors_col_status') }}</th>
            <th class="text-right">{{ __('app.monitors_col_latency') }}</th>
            <th class="text-right">{{ __('app.monitors_col_24h') }}</th>
        </x-slot:head>

        @forelse ($rows as $monitor)
            {{-- The whole row opens the monitor; the name stays a real link so keyboard focus,
                 middle-click and "open in new tab" keep working. --}}
            <tr
                wire:key="monitor-{{ $monitor->id }}"
                x-on:click="if (! $event.target.closest('a, button')) { $event.ctrlKey || $event.metaKey ? window.open('{{ route('monitors.show', $monitor) }}', '_blank') : Livewire.navigate('{{ route('monitors.show', $monitor) }}') }"
                @class(['cursor-pointer transition-colors duration-150 hover:bg-surface-2', 'bg-down-soft/45' => in_array($monitor->state->status, ['down', 'suspect', 'recovering'])])
            >
                <td>
                    <a href="{{ route('monitors.show', $monitor) }}" wire:navigate class="font-medium text-ink hover:underline">{{ $monitor->name }}</a>
                    <div class="mt-0.5 text-xs text-muted">
                        {{ $monitor->client?->name ?? __('app.monitor_type_'.$monitor->type) }}
                        @if ($monitor->client)
                            · {{ __('app.monitor_type_'.$monitor->type) }}
                        @endif
                        @if ($monitor->type === 'ssl' && $monitor->state->cert_expires_at && now()->diffInDays($monitor->state->cert_expires_at, false) <= $monitor->ssl_warn_days)
                            · <span class="text-warn-text">{{ __('app.monitor_cert_expiring', ['days' => max(0, (int) now()->diffInDays($monitor->state->cert_expires_at, false))]) }}</span>
                        @endif
                    </div>
                </td>
                <td class="w-[190px]">
                    <x-ui.tick-strip :tones="$ticks[$monitor->id]['tones'] ?? []" :label="__('app.chart_uptime_aria')" />
                </td>
                <td>
                    <div class="flex items-center gap-2">
                        <x-ui.status-pill :status="$monitor->state->status" :flapping="$monitor->state->flapping" />
                        @if ($monitor->state->status === 'down' && $monitor->state->currentIncident)
                            <span class="font-mono text-xs text-muted">{{ Format::shortDuration($monitor->state->currentIncident->started_at->diffInSeconds(now())) }}</span>
                        @endif
                    </div>
                </td>
                <td class="text-right font-mono text-xs text-muted">{{ Format::ms($monitor->state->last_latency_ms) }}</td>
                <td class="text-right font-mono text-xs text-muted">{{ $uptime24h[$monitor->id] ?? '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5">
                    <x-ui.empty-state icon="pulse" :title="__('app.monitors_empty_title')" :description="__('app.monitors_empty_description')">
                        <x-slot:action>
                            <x-ui.button variant="secondary" size="sm" href="{{ route('monitors.create') }}" wire:navigate>
                                <x-phosphor-plus class="size-4" /> {{ __('app.monitors_empty_cta') }}
                            </x-ui.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                </td>
            </tr>
        @endforelse

        @if ($collapsible && $remainingHealthy > 0)
            <tr>
                <td colspan="5" class="bg-surface-2 text-[13px] text-muted">
                    {{ __('app.monitors_more_healthy', ['count' => $remainingHealthy]) }}
                    <button type="button" wire:click="$set('expanded', true)" class="ml-2 font-medium text-ink underline decoration-line-strong underline-offset-[3px] hover:decoration-ink">{{ __('app.monitors_show_all') }}</button>
                </td>
            </tr>
        @endif
    </x-ui.table>

    @unless ($collapsible)
        <div class="mt-4">{{ $monitors->links() }}</div>
    @endunless
</div>
