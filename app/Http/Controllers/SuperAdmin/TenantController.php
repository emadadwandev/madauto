<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
     * Show the form for creating a new tenant.
     */
    public function create()
    {
        $plans = SubscriptionPlan::active()->ordered()->get();

        return view('super-admin.tenants.create', compact('plans'));
    }

    /**
     * Store a newly created tenant.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'subdomain' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9-]+$/',
                'unique:tenants,subdomain',
            ],
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'status' => ['required', 'in:active,inactive'],
            'enabled_platforms' => ['nullable', 'array'],
            'enabled_platforms.*' => ['in:careem,talabat'],
            'auto_accept_careem' => ['nullable', 'boolean'],
            'auto_accept_talabat' => ['nullable', 'boolean'],
            'timezone' => ['nullable', 'string'],
            'currency' => ['nullable', 'string'],
            'language' => ['nullable', 'string'],
            'notify_on_new_order' => ['nullable', 'boolean'],
            'notify_on_failed_sync' => ['nullable', 'boolean'],
            'notify_on_usage_limit' => ['nullable', 'boolean'],
        ]);

        try {
            DB::beginTransaction();

            // Build settings array from form inputs
            $settings = [
                'enabled_platforms' => $validated['enabled_platforms'] ?? [],
                'auto_accept_careem' => $request->boolean('auto_accept_careem'),
                'auto_accept_talabat' => $request->boolean('auto_accept_talabat'),
                'timezone' => $validated['timezone'] ?? 'Asia/Dubai',
                'currency' => $validated['currency'] ?? 'AED',
                'language' => $validated['language'] ?? 'en',
                'notify_on_new_order' => $request->boolean('notify_on_new_order', true),
                'notify_on_failed_sync' => $request->boolean('notify_on_failed_sync', true),
                'notify_on_usage_limit' => $request->boolean('notify_on_usage_limit', true),
            ];

            // Cast trial_days to integer for Carbon date operations
            $trialDays = (int) ($validated['trial_days'] ?? 0);

            // Create tenant
            $tenant = Tenant::create([
                'name' => $validated['name'],
                'subdomain' => $validated['subdomain'],
                'status' => $validated['status'],
                'settings' => $settings,
                'trial_ends_at' => $trialDays > 0
                    ? now()->addDays($trialDays)
                    : null,
            ]);

            // Create tenant admin user
            $user = User::create([
                'name' => $validated['name'] . ' Admin',
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
            ]);

            // Assign tenant admin role with tenant context
            $tenantAdminRole = Role::where('name', Role::TENANT_ADMIN)->first();
            if ($tenantAdminRole) {
                $user->assignRole($tenantAdminRole, $tenant->id);
            }

            // Create subscription
            $plan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);
            Subscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'status' => $trialDays > 0 ? 'trialing' : 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
                'trial_ends_at' => $trialDays > 0
                    ? now()->addDays($trialDays)
                    : null,
            ]);

            DB::commit();

            return redirect()
                ->route('super-admin.tenants.show', $tenant)
                ->with('success', 'Tenant created successfully. Login credentials have been set.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create tenant: ' . $e->getMessage());
        }
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
            'enabled_platforms' => ['nullable', 'array'],
            'enabled_platforms.*' => ['in:careem,talabat'],
            'auto_accept_careem' => ['nullable', 'boolean'],
            'auto_accept_talabat' => ['nullable', 'boolean'],
            'timezone' => ['nullable', 'string'],
            'currency' => ['nullable', 'string'],
            'language' => ['nullable', 'string'],
            'notify_on_new_order' => ['nullable', 'boolean'],
            'notify_on_failed_sync' => ['nullable', 'boolean'],
            'notify_on_usage_limit' => ['nullable', 'boolean'],
        ]);

        // Build settings array from form inputs
        $settings = [
            'enabled_platforms' => $validated['enabled_platforms'] ?? [],
            'auto_accept_careem' => $request->boolean('auto_accept_careem'),
            'auto_accept_talabat' => $request->boolean('auto_accept_talabat'),
            'timezone' => $validated['timezone'] ?? 'Asia/Dubai',
            'currency' => $validated['currency'] ?? 'AED',
            'language' => $validated['language'] ?? 'en',
            'notify_on_new_order' => $request->boolean('notify_on_new_order', true),
            'notify_on_failed_sync' => $request->boolean('notify_on_failed_sync', true),
            'notify_on_usage_limit' => $request->boolean('notify_on_usage_limit', true),
        ];

        // Update tenant with settings
        $tenant->update([
            'name' => $validated['name'],
            'subdomain' => $validated['subdomain'],
            'domain' => $validated['domain'] ?? null,
            'status' => $validated['status'],
            'trial_ends_at' => $validated['trial_ends_at'] ?? null,
            'settings' => $settings,
        ]);

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
