<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    /**
     * Determine if the user can view the tenant.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        // Super admins can view all tenants
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can view their own tenant
        return $user->tenant_id === $tenant->id;
    }

    /**
     * Determine if the user can update the tenant.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        // Super admins can update all tenants
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Tenant admins can update their own tenant
        return $user->tenant_id === $tenant->id && $user->isTenantAdmin($tenant->id);
    }

    /**
     * Determine if the user can delete the tenant.
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        // Only super admins can delete tenants
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can suspend the tenant.
     */
    public function suspend(User $user, Tenant $tenant): bool
    {
        // Only super admins can suspend tenants
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can activate the tenant.
     */
    public function activate(User $user, Tenant $tenant): bool
    {
        // Only super admins can activate tenants
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can impersonate tenant users.
     */
    public function impersonate(User $user, Tenant $tenant): bool
    {
        // Only super admins can impersonate
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can manage tenant settings.
     */
    public function manageSettings(User $user, Tenant $tenant): bool
    {
        // Super admins can manage all tenant settings
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Tenant admins can manage their own tenant settings
        return $user->tenant_id === $tenant->id && $user->isTenantAdmin($tenant->id);
    }
}
