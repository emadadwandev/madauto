<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view the team.
     */
    public function viewTeam(User $user, Tenant $tenant): bool
    {
        return $user->tenant_id === $tenant->id && (
            $user->hasRole('tenant_admin', $tenant) ||
            $user->hasRole('tenant_user', $tenant)
        );
    }

    /**
     * Determine if the user can invite users.
     */
    public function inviteUsers(User $user, Tenant $tenant): bool
    {
        return $user->tenant_id === $tenant->id &&
               $user->hasRole('tenant_admin', $tenant);
    }

    /**
     * Determine if the user can update another user's role.
     */
    public function updateUserRole(User $user, Tenant $tenant, User $targetUser): bool
    {
        // User can update roles if:
        // 1. They are tenant admin
        // 2. They belong to same tenant
        // 3. They are not trying to update themselves
        // 4. Target user is not super admin
        return $user->tenant_id === $tenant->id &&
               $user->hasRole('tenant_admin', $tenant) &&
               $targetUser->id !== $user->id &&
               ! $targetUser->hasRole('super_admin');
    }

    /**
     * Determine if the user can remove a user from tenant.
     */
    public function removeUser(User $user, Tenant $tenant, User $targetUser): bool
    {
        return $this->updateUserRole($user, $tenant, $targetUser);
    }

    /**
     * Determine if the user can view user data.
     */
    public function viewUserData(User $user, Tenant $tenant, User $targetUser): bool
    {
        return $user->tenant_id === $tenant->id && (
            ($user->hasRole('tenant_admin', $tenant)) ||
            $user->id === $targetUser->id
        );
    }

    /**
     * Determine if the user can resend invitations.
     */
    public function resendInvitation(User $user, Tenant $tenant, \App\Models\Invitation $invitation): bool
    {
        return $user->tenant_id === $tenant->id &&
               $invitation->tenant_id === $tenant->id &&
               $user->hasRole('tenant_admin', $tenant);
    }
}
