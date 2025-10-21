<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class TenantContext
{
    /**
     * The current tenant instance.
     */
    protected ?Tenant $tenant = null;

    /**
     * Whether the context has been initialized.
     */
    protected bool $initialized = false;

    /**
     * Set the current tenant.
     */
    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->initialized = true;

        // Store tenant ID in cache for quick access during the request
        if ($tenant) {
            Cache::put('current_tenant_id', $tenant->id, now()->addMinutes(5));
        } else {
            Cache::forget('current_tenant_id');
        }
    }

    /**
     * Get the current tenant.
     */
    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    /**
     * Get the current tenant ID.
     */
    public function id(): ?string
    {
        return $this->tenant?->id;
    }

    /**
     * Check if a tenant is set.
     */
    public function has(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * Check if the context has been initialized.
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Clear the current tenant.
     */
    public function clear(): void
    {
        $this->tenant = null;
        $this->initialized = false;
        Cache::forget('current_tenant_id');
    }

    /**
     * Check if the current tenant is active.
     */
    public function isActive(): bool
    {
        return $this->has() && $this->tenant->isActive();
    }

    /**
     * Check if the current tenant is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->has() && $this->tenant->isSuspended();
    }

    /**
     * Check if the current tenant is in trial.
     */
    public function inTrial(): bool
    {
        return $this->has() && $this->tenant->inTrial();
    }

    /**
     * Get the current tenant's subscription.
     */
    public function subscription()
    {
        return $this->tenant?->subscription;
    }

    /**
     * Execute a callback with a specific tenant context.
     */
    public function executeAs(?Tenant $tenant, callable $callback)
    {
        $originalTenant = $this->tenant;

        try {
            $this->set($tenant);

            return $callback();
        } finally {
            $this->set($originalTenant);
        }
    }

    /**
     * Execute a callback without any tenant context (for super admin operations).
     */
    public function executeWithoutTenant(callable $callback)
    {
        return $this->executeAs(null, $callback);
    }
}
