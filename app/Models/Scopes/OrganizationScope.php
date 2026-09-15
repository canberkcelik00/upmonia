<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Tenant isolation, enforced as a global scope rather than left to every query author to
 * remember (the Node app's `lib/queries.ts` had at least one query where a missing org
 * filter was a real, shipped bug — see listIncidentEvents in the source app).
 *
 * Only applies inside an authenticated web request. Console commands (probe:run,
 * maintenance:run) operate across all organizations by design and must query with
 * `Model::withoutGlobalScope(OrganizationScope::class)` or go through a model that isn't
 * scoped at all (MonitorState, CheckResult, etc. are intentionally not org-scoped — they're
 * reached only via their owning Monitor, which is).
 */
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && ($orgId = Auth::user()->currentOrganization()?->id)) {
            $builder->where($model->getTable().'.organization_id', $orgId);
        }
    }
}
