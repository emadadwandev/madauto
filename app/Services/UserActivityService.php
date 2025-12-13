<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Database\Eloquent\Model;

class UserActivityService
{
    /**
     * Log user activity with optional context
     */
    public static function log(
        string $action,
        ?User $subject = null,
        array $properties = [],
        ?Model $causer = null,
        ?string $description = null
    ): UserActivity {

        $tenant = tenant();
        $user = auth()->user();

        // Auto-generate description if not provided
        $description ??= self::generateDescription($action, $subject, $causer, $properties);

        return UserActivity::create([
            'tenant_id' => $tenant->id,
            'user_id' => $subject?->id ?? $user?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'properties' => $properties,
            'causer_id' => $causer?->id ?? $user?->id,
            'causer_type' => $causer
                ? get_class($causer)
                : ($user ? get_class($user) : null),
        ]);
    }

    /**
     * Get user's recent activities
     */
    public static function getRecentActivities(User $user, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return UserActivity::where('tenant_id', tenant()->id)
            ->where('user_id', $user->id)
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get tenant-wide activity feed
     */
    public static function getActivityFeed(int $limit = 20): \Illuminate\Pagination\LengthAwarePaginator
    {
        return UserActivity::where('tenant_id', tenant()->id)
            ->with(['user', 'causer'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Clean up old activity logs (keep last 90 days)
     */
    public static function cleanup(): int
    {
        $deleted = UserActivity::where('tenant_id', tenant()->id)
            ->where('created_at', '<', now()->subDays(90))
            ->delete();

        return $deleted;
    }

    /**
     * Generate human-readable activity description
     */
    private static function generateDescription(string $action, ?User $subject, ?Model $causer, array $properties = []): string
    {
        $actorName = $causer?->name ?? 'System';
        $subjectName = $subject?->name ?? 'A user';

        return match ($action) {
            'user.invited' => "$actorName invited $subjectName to the team",
            'user.accepted_invitation' => "$subjectName accepted invitation to join the team",
            'user.role_changed' => "$actorName changed $subjectName's role to ".($properties['role'] ?? 'unknown'),
            'user.removed_from_tenant' => "$actorName removed $subjectName from the team",
            'user.login' => "$subjectName logged in",
            'user.logout' => "$subjectName logged out",
            'invitation.resent' => "$actorName resent invitation to $subjectName",
            'order.processed' => "$actorName processed order '".($properties['order_id'] ?? 'unknown')."'",
            'menu.created' => "$actorName created menu '".($properties['menu_name'] ?? 'unknown')."'",
            'menu.updated' => "$actorName updated menu '".($properties['menu_name'] ?? 'unknown')."'",
            'menu.published' => "$actorName published menu '".($properties['menu_name'] ?? 'unknown')."'",
            'menu.deleted' => "$actorName deleted menu '".($properties['menu_name'] ?? 'unknown')."'",
            'location.created' => "$actorName created location '".($properties['location_name'] ?? 'unknown')."'",
            'location.updated' => "$actorName updated location '".($properties['location_name'] ?? 'unknown')."'",
            'location.deleted' => "$actorName deleted location '".($properties['location_name'] ?? 'unknown')."'",
            'location.busy_toggled' => "$actorName marked location as ".($properties['is_busy'] ? 'busy' : 'available'),
            default => "$actorName performed action: $action",
        };
    }

    /**
     * Log user login (call from authenticated event)
     */
    public static function logLogin(User $user): UserActivity
    {
        return self::log('user.login', $user);
    }

    /**
     * Log user logout (call from logout event)
     */
    public static function logLogout(User $user): UserActivity
    {
        return self::log('user.logout', $user);
    }

    /**
     * Log invitation sent
     */
    public static function logInvitationSent(\App\Models\Invitation $invitation): UserActivity
    {
        return self::log('user.invited', null, [
            'invitation_id' => $invitation->id,
            'email' => $invitation->email,
            'role' => $invitation->role->display_name,
        ]);
    }

    /**
     * Log invitation accepted
     */
    public static function logInvitationAccepted(User $user, \App\Models\Invitation $invitation): UserActivity
    {
        return self::log('user.accepted_invitation', $user, [
            'invitation_id' => $invitation->id,
            'email' => $invitation->email,
        ]);
    }
}
