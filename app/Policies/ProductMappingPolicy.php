<?php

namespace App\Policies;

use App\Models\ProductMapping;
use App\Models\User;

class ProductMappingPolicy
{
    /**
     * Determine if the user can view any product mappings.
     */
    public function viewAny(User $user): bool
    {
        // Super admins can view all product mappings
        if ($user->isSuperAdmin()) {
            return true;
        }

        // All tenant users can view product mappings (scoped by tenant automatically)
        return $user->tenant_id !== null;
    }

    /**
     * Determine if the user can view the product mapping.
     */
    public function view(User $user, ProductMapping $productMapping): bool
    {
        // Super admins can view all product mappings
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can only view product mappings from their own tenant
        return $user->tenant_id === $productMapping->tenant_id;
    }

    /**
     * Determine if the user can create product mappings.
     */
    public function create(User $user): bool
    {
        // Super admins can create product mappings for any tenant
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Tenant admins can create product mappings for their tenant
        return $user->tenant_id !== null && $user->isTenantAdmin($user->tenant_id);
    }

    /**
     * Determine if the user can update the product mapping.
     */
    public function update(User $user, ProductMapping $productMapping): bool
    {
        // Super admins can update all product mappings
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Tenant admins can update product mappings from their own tenant
        return $user->tenant_id === $productMapping->tenant_id && $user->isTenantAdmin($productMapping->tenant_id);
    }

    /**
     * Determine if the user can delete the product mapping.
     */
    public function delete(User $user, ProductMapping $productMapping): bool
    {
        // Super admins can delete all product mappings
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Tenant admins can delete product mappings from their own tenant
        return $user->tenant_id === $productMapping->tenant_id && $user->isTenantAdmin($productMapping->tenant_id);
    }

    /**
     * Determine if the user can import product mappings.
     */
    public function import(User $user): bool
    {
        // Super admins can import for any tenant
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Tenant admins can import for their own tenant
        return $user->tenant_id !== null && $user->isTenantAdmin($user->tenant_id);
    }

    /**
     * Determine if the user can export product mappings.
     */
    public function export(User $user): bool
    {
        // Super admins can export from any tenant
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Tenant admins can export their own product mappings
        return $user->tenant_id !== null && $user->isTenantAdmin($user->tenant_id);
    }
}
