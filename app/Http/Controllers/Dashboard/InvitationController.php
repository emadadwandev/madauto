<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;

class InvitationController extends Controller
{
    /**
     * Display a listing of invitations for the current tenant.
     */
    public function index(Request $request)
    {
        $this->authorize('invite', User::class);

        $tenant = app(TenantContext::class)->get();

        $invitations = Invitation::forTenant($tenant->id)
            ->with(['role', 'invitedBy'])
            ->latest()
            ->paginate(15);

        return view('dashboard.invitations.index', compact('invitations'));
    }

    /**
     * Show the form for sending a new invitation.
     */
    public function create()
    {
        $this->authorize('invite', User::class);

        $tenant = app(TenantContext::class)->get();

        // Get only tenant-specific roles (exclude super_admin)
        $roles = Role::tenantRoles()->get();

        return view('dashboard.invitations.create', compact('roles'));
    }

    /**
     * Store a newly created invitation in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('invite', User::class);

        $tenant = app(TenantContext::class)->get();

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        // Check if user already exists in this tenant
        $existingUser = User::where('email', $validated['email'])
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($existingUser) {
            return back()->withErrors([
                'email' => 'A user with this email already exists in your organization.',
            ])->withInput();
        }

        // Check if there's already a pending invitation
        $pendingInvitation = Invitation::byEmail($validated['email'])
            ->forTenant($tenant->id)
            ->valid()
            ->first();

        if ($pendingInvitation) {
            return back()->withErrors([
                'email' => 'An invitation has already been sent to this email address.',
            ])->withInput();
        }

        // Create the invitation
        $invitation = Invitation::create([
            'tenant_id' => $tenant->id,
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'invited_by' => $request->user()->id,
        ]);

        // Send the invitation email
        Mail::to($validated['email'])->send(new InvitationMail($invitation));

        // Log activity
        \App\Services\UserActivityService::logInvitationSent($invitation);

        return redirect()
            ->route('dashboard.invitations.index', ['subdomain' => request()->route('subdomain')])
            ->with('success', 'Invitation sent successfully!');
    }

    /**
     * Display the invitation acceptance form.
     */
    public function show(string $token)
    {
        $invitation = Invitation::byToken($token)
            ->with(['tenant', 'role'])
            ->firstOrFail();

        // Check if invitation is still valid
        if (! $invitation->isValid()) {
            if ($invitation->isAccepted()) {
                return view('invitations.already-accepted');
            } else {
                return view('invitations.expired', compact('invitation'));
            }
        }

        return view('invitations.accept', compact('invitation'));
    }

    /**
     * Accept an invitation and create the user account.
     */
    public function accept(Request $request, string $token)
    {
        $invitation = Invitation::byToken($token)
            ->with(['tenant', 'role'])
            ->firstOrFail();

        // Check if invitation is still valid
        if (! $invitation->isValid()) {
            return redirect()
                ->route('invitations.show', $token)
                ->withErrors(['error' => 'This invitation is no longer valid.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            DB::beginTransaction();

            // Create the user
            $user = User::create([
                'tenant_id' => $invitation->tenant_id,
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(), // Auto-verify email
            ]);

            // Assign the role to the user
            $user->assignRole($invitation->role, $invitation->tenant_id);

            // Mark invitation as accepted
            $invitation->markAsAccepted();

            DB::commit();

            // Log activity
            \App\Services\UserActivityService::logInvitationAccepted($user, $invitation);

            // Log the user in
            auth()->login($user);

            // Redirect to tenant subdomain dashboard
            $tenant = $invitation->tenant;
            $domain = config('app.domain', 'localhost');
            $dashboardUrl = "http://{$tenant->subdomain}.{$domain}/dashboard";

            return redirect($dashboardUrl)
                ->with('success', 'Welcome! Your account has been created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withErrors(['error' => 'An error occurred while creating your account. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Resend an invitation email.
     */
    public function resend($invitationId)
    {
        $this->authorize('invite', User::class);

        $tenant = app(TenantContext::class)->get();
        
        // Find the invitation
        $invitation = Invitation::findOrFail($invitationId);

        // Ensure invitation belongs to current tenant
        if ($invitation->tenant_id !== $tenant->id) {
            abort(403);
        }

        // Check if invitation is already accepted
        if ($invitation->isAccepted()) {
            return back()->withErrors([
                'error' => 'This invitation has already been accepted.',
            ]);
        }

        // Update expiration date if expired
        if ($invitation->isExpired()) {
            $invitation->update([
                'expires_at' => now()->addDays(7),
            ]);
        }

        // Resend the email
        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        return back()->with('success', 'Invitation resent successfully!');
    }

    /**
     * Cancel (delete) an invitation.
     */
    public function destroy($invitationId)
    {
        $this->authorize('invite', User::class);

        $tenant = app(TenantContext::class)->get();
        
        // Find the invitation
        $invitation = Invitation::findOrFail($invitationId);

        // Ensure invitation belongs to current tenant
        if ($invitation->tenant_id !== $tenant->id) {
            abort(403);
        }

        $invitation->delete();

        return back()->with('success', 'Invitation cancelled successfully.');
    }
}
