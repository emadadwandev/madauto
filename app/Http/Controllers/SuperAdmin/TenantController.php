<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    /**
     * Display a listing of tenants.
     */
    public function index(Request $request)
    {
        $query = Tenant::with(['subscription.subscriptionPlan', 'users']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subdomain', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        if ($request->filled('plan')) {
            $query->whereHas('subscription.subscriptionPlan', function ($q) use ($request) {
                $q->where('slug', $request->plan);
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $tenants = $query->paginate(20)->withQueryString();

        return view('super-admin.tenants.index', compact('tenants'));
    }

    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant)
    {
        $tenant->load([
            'subscription.subscriptionPlan',
            'users.roles',
        ]);

        // Get tenant statistics
        $stats = [
            'total_orders' => $tenant->orders()->count(),
            'orders_this_month' => $tenant->orders()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'failed_orders' => $tenant->orders()->where('status', 'failed')->count(),
            'total_users' => $tenant->users()->count(),
            'product_mappings' => $tenant->productMappings()->count(),
            'recent_orders' => $tenant->orders()->latest()->take(10)->get(),
        ];

        return view('super-admin.tenants.show', compact('tenant', 'stats'));
    }

    /**
     * Show the form for editing the specified tenant.
     */
    public function edit(Tenant $tenant)
    {
        $tenant->load('subscription.subscriptionPlan');

        return view('super-admin.tenants.edit', compact('tenant'));
    }

    /**
     * Update the specified tenant.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('tenants', 'subdomain')->ignore($tenant->id),
            ],
            'domain' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,suspended,inactive'],
            'trial_ends_at' => ['nullable', 'date'],
        ]);

        $tenant->update($validated);

        return redirect()
            ->route('super-admin.tenants.show', $tenant)
            ->with('success', 'Tenant updated successfully.');
    }

    /**
     * Suspend the specified tenant.
     */
    public function suspend(Tenant $tenant)
    {
        $tenant->update(['status' => 'suspended']);

        // TODO: Send notification email to tenant admin

        return redirect()
            ->back()
            ->with('success', 'Tenant suspended successfully.');
    }

    /**
     * Activate the specified tenant.
     */
    public function activate(Tenant $tenant)
    {
        $tenant->update(['status' => 'active']);

        // TODO: Send notification email to tenant admin

        return redirect()
            ->back()
            ->with('success', 'Tenant activated successfully.');
    }

    /**
     * Delete the specified tenant (soft delete).
     */
    public function destroy(Tenant $tenant)
    {
        // Prevent deletion if tenant has active subscription
        if ($tenant->subscription && $tenant->subscription->status === 'active') {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete tenant with active subscription. Please cancel subscription first.');
        }

        $tenant->delete();

        return redirect()
            ->route('super-admin.tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }

    /**
     * Impersonate a tenant admin.
     */
    public function impersonate(Tenant $tenant)
    {
        // Find a tenant admin user
        $adminUser = $tenant->users()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'tenant_admin');
            })
            ->first();

        if (! $adminUser) {
            return redirect()
                ->back()
                ->with('error', 'No tenant admin found for this tenant.');
        }

        // Store original user ID in session
        session(['impersonating_from' => auth()->id()]);

        // Login as tenant admin
        auth()->guard('web')->loginUsingId($adminUser->id);

        // Regenerate session to ensure CSRF tokens are fresh
        request()->session()->regenerate();

        return redirect()
            ->route('dashboard', ['subdomain' => $tenant->subdomain])
            ->with('info', 'You are now impersonating '.$adminUser->name);
    }

    /**
     * Stop impersonating and return to super admin.
     */
    public function stopImpersonating()
    {
        if (! session()->has('impersonating_from')) {
            return redirect()->route('super-admin.dashboard');
        }

        $originalUserId = session('impersonating_from');
        session()->forget('impersonating_from');

        $originalUser = User::findOrFail($originalUserId);
        auth()->guard('web')->loginUsingId($originalUser->id);

        // Regenerate session to ensure CSRF tokens are fresh
        request()->session()->regenerate();

        return redirect()
            ->route('super-admin.dashboard')
            ->with('success', 'Stopped impersonating. Welcome back!');
    }
}
