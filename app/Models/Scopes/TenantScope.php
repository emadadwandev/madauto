<?php

namespace App\Models\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Get the current tenant from context
        $tenantContext = app(TenantContext::class);

        // Only apply scope if we have a tenant in context
        if ($tenant = $tenantContext->get()) {
            $builder->where($model->getTable().'.tenant_id', $tenant->id);
        }
    }

    /**
     * Extend the query builder with methods to bypass the scope.
     */
    public function extend(Builder $builder): void
    {
        // Add method to bypass tenant scope for super admin operations
        $builder->macro('withoutTenantScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        // Add method to query for a specific tenant
        $builder->macro('forTenant', function (Builder $builder, $tenantId) {
            return $builder->withoutGlobalScope($this)
                ->where('tenant_id', $tenantId);
        });

        // Add method to query all tenants (super admin)
        $builder->macro('allTenants', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
