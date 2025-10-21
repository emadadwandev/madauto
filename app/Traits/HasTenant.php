<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasTenant
{
    /**
     * Boot the trait.
     */
    protected static function bootHasTenant(): void
    {
        // Add the global scope
        static::addGlobalScope(new TenantScope);

        // Automatically set tenant_id when creating a model
        static::creating(function ($model) {
            if (! $model->tenant_id) {
                $tenantContext = app(TenantContext::class);

                if ($tenant = $tenantContext->get()) {
                    $model->tenant_id = $tenant->id;
                }
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to get all records without tenant filtering (for super admin).
     */
    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Scope to get records for a specific tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId);
    }

    /**
     * Check if the model belongs to the current tenant.
     */
    public function belongsToCurrentTenant(): bool
    {
        $tenantContext = app(TenantContext::class);

        return $this->tenant_id === $tenantContext->id();
    }

    /**
     * Check if the model belongs to a specific tenant.
     */
    public function belongsToTenant($tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }
}
