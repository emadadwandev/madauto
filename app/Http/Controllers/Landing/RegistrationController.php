<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeTenantEmail;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;

class RegistrationController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Show the registration form.
     */
    public function create(Request $request)
    {
        $plans = SubscriptionPlan::active()->ordered()->get();
        $selectedPlan = $request->query('plan');

        return view('landing.register', compact('plans', 'selectedPlan'));
    }

    /**
     * Handle registration.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9-]+$/', 'unique:tenants,subdomain'],
            'plan_id' => ['required', 'exists:subscription_plans,id'],
        ], [
            'subdomain.regex' => 'Subdomain can only contain lowercase letters, numbers, and hyphens.',
            'subdomain.unique' => 'This subdomain is already taken. Please choose another one.',
        ]);

        try {
            DB::beginTransaction();

            // Get the selected plan
            $plan = SubscriptionPlan::findOrFail($request->plan_id);

            // Create the tenant
            $tenant = Tenant::create([
                'name' => $request->company_name,
                'subdomain' => $request->subdomain,
                'status' => 'active',
                'trial_ends_at' => now()->addDays(14), // 14-day trial
                'settings' => [
                    'timezone' => 'UTC',
                    'email_notifications' => true,
                ],
            ]);

            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(), // Auto-verify for simplicity
                'tenant_id' => $tenant->id,
            ]);

            // Assign tenant_admin role
            $tenantAdminRole = Role::where('name', 'tenant_admin')->first();
            if ($tenantAdminRole) {
                $user->roles()->attach($tenantAdminRole->id, [
                    'tenant_id' => $tenant->id,
                ]);
            }

            // Create subscription with trial
            $subscription = $this->subscriptionService->subscribe($tenant, $plan, true);

            DB::commit();

            // Send welcome email
            try {
                Mail::to($user->email)->send(new WelcomeTenantEmail($tenant, $user, $request->password));
            } catch (\Exception $e) {
                \Log::error('Failed to send welcome email', ['error' => $e->getMessage()]);
            }

            // Log the user in
            auth()->login($user);

            // Redirect to onboarding wizard on tenant subdomain
            $domain = config('app.domain', 'localhost');
            $onboardingUrl = "http://{$tenant->subdomain}.{$domain}/dashboard/onboarding";

            return redirect($onboardingUrl)
                ->with('success', 'Welcome! Let\'s get you set up in just a few steps.');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Registration failed. Please try again or contact support.');
        }
    }

    /**
     * Check subdomain availability (AJAX).
     */
    public function checkSubdomain(Request $request)
    {
        $subdomain = $request->input('subdomain');

        if (empty($subdomain)) {
            return response()->json(['available' => false, 'message' => 'Subdomain is required']);
        }

        // Validate format
        if (! preg_match('/^[a-z0-9-]+$/', $subdomain)) {
            return response()->json(['available' => false, 'message' => 'Invalid format. Use only lowercase letters, numbers, and hyphens.']);
        }

        // Check if available
        $exists = Tenant::where('subdomain', $subdomain)->exists();

        if ($exists) {
            return response()->json(['available' => false, 'message' => 'This subdomain is already taken']);
        }

        return response()->json(['available' => true, 'message' => 'Subdomain is available!']);
    }
}
