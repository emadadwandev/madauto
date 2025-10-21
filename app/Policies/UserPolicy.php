<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        // Super admins can view all users
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Tenant admins can view users in their tenant
        return $user->tenant_id !== null && $user->isTenantAdmin($user->tenant_id);
    }

    /**
     * Determine if the user can view another user.
     */
    public function view(User $user, User $targetUser): bool
    {
        // Super admins can view all users
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can view themselves
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Tenant admins can view users in their tenant
        return $user->tenant_id === $targetUser->tenant_id && $user->isTenantAdmin($user->tenant_id);
    }

    /**
     * Determine if the user can invite new users.
     */
    public function invite(User $user): bool
    {
        // Super admins can invite users to any tenant
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Tenant admins can invite users to their tenant
        return $user->tenant_id !== null && $user->isTenantAdmin($user->tenant_id);
    }

    /**
     * Determine if the user can update another user.
     */
    public function update(User $user, User $targetUser): bool
    {
        // Super admins can update all users
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can update themselves (basic info only)
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Tenant admins can update users in their tenant
        return $user->tenant_id === $targetUser->tenant_id && $user->isTenantAdmin($user->tenant_id);
    }

    /**
     * Determine if the user can delete another user.
     */
    public function delete(User $user, User $targetUser): bool
    {
        // Users cannot delete themselves
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Super admins can delete any user
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Tenant admins can delete users in their tenant (except other admins)
        if ($user->tenant_id === $targetUser->tenant_id && $user->isTenantAdmin($user->tenant_id)) {
            // Cannot delete other tenant admins
            return ! $targetUser->isTenantAdmin($targetUser->tenant_id);
        }

        return false;
    }

    /**
     * Determine if the user can change roles for another user.
     */
    public function changeRole(User $user, User $targetUser): bool
    {
        // Users cannot change their own role
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Super admins can change any role
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Tenant admins can change roles for users in their tenant
        return $user->tenant_id === $targetUser->tenant_id && $user->isTenantAdmin($user->tenant_id);
    }

    /**
     * Determine if the user can remove another user from a tenant.
     */
    public function remove(User $user, User $targetUser): bool
    {
        // Same as delete for now
        return $this->delete($user, $targetUser);
    }
}
