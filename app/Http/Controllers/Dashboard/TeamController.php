<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index()
    {
        $tenant = tenant();
        
        Gate::authorize('viewTeam', $tenant);
        
        $users = User::where('tenant_id', $tenant->id)
            ->with('roles')
            ->paginate(10);
            
        $roles = Role::whereIn('name', ['tenant_admin', 'tenant_user'])->get();
        
        // Count admins and users for stats
        $adminCount = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', function($query) use ($tenant) {
                $query->where('name', 'tenant_admin')
                      ->where('role_user.tenant_id', $tenant->id);
            })->count();
            
        $userCount = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', function($query) use ($tenant) {
                $query->where('name', 'tenant_user')
                      ->where('role_user.tenant_id', $tenant->id);
            })->count();
        
        return view('dashboard.team.index', compact('users', 'roles', 'adminCount', 'userCount'));
    }
    
    public function editRole(Request $request, User $user)
    {
        $tenant = tenant();
        
        Gate::authorize('updateUserRole', [$tenant, $user]);
        
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(['tenant_admin', 'tenant_user'])],
        ]);
        
        // Remove existing tenant roles
        $user->roles()->wherePivot('tenant_id', $tenant->id)->detach();
        
        // Assign new role
        $role = Role::where('name', $validated['role'])->first();
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        
        // Log the role change
        \App\Services\UserActivityService::log(
            'user.role_changed',
            $user,
            ['role' => $validated['role'], 'changed_by' => auth()->id()]
        );
        
        return back()->with('success', "User role updated to {$validated['role']}");
    }
    
    public function removeUser(User $user)
    {
        $tenant = tenant();
        
        Gate::authorize('removeUser', [$tenant, $user]);
        
        // Check if this is the last tenant admin
        if ($user->hasRole('tenant_admin', $tenant)) {
            $adminCount = User::where('tenant_id', $tenant->id)
                ->whereHas('roles', function($query) use ($tenant) {
                    $query->where('name', 'tenant_admin')
                          ->where('role_user.tenant_id', $tenant->id);
                })
                ->count();
                
            if ($adminCount <= 1) {
                return back()->with('error', 'Cannot remove the last tenant admin. Assign admin role to another user first.');
            }
        }
        
        // Remove from tenant
        $user->roles()->wherePivot('tenant_id', $tenant->id)->detach();
        $user->update(['tenant_id' => null]);
        $user->tokens()->delete(); // Revoke all API tokens
        
        // Log the removal
        \App\Services\UserActivityService::log(
            'user.removed_from_tenant',
            $user,
            ['removed_by' => auth()->id()]
        );
        
        return back()->with('success', 'User removed from team successfully');
    }
    
    public function resendInvitation($invitationId)
    {
        $tenant = tenant();
        
        Gate::authorize('inviteUsers', $tenant);
        
        // Find the invitation
        $invitation = \App\Models\Invitation::findOrFail($invitationId);
        
        if ($invitation->tenant_id !== $tenant->id) {
            abort(404);
        }
        
        if (!$invitation->isExpired()) {
            // Extend expiration by 7 days
            $invitation->update(['expires_at' => now()->addDays(7)]);
            
            // Resend invitation email
            \App\Mail\InvitationMail::dispatch($invitation);
            
            // Log the resend
            \App\Services\UserActivityService::log(
                'invitation.resent',
                $invitation,
                ['resent_by' => auth()->id()]
            );
            
            return back()->with('success', 'Invitation resent successfully');
        }
        
        return back()->with('error', 'This invitation has expired and cannot be resent');
    }
    
    public function getUserActivity(string $subdomain, User $user)
    {
        $tenant = tenant();
        
        Gate::authorize('viewUserData', [$tenant, $user]);
        
        $activities = \App\Models\UserActivity::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('dashboard.team.activity', compact('user', 'activities'));
    }
    
    public function getActivityFeed()
    {
        Gate::authorize('viewTeam', tenant());
        
        return view('dashboard.team.activity-feed');
    }
}
