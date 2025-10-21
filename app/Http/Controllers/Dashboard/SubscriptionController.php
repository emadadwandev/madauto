<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use App\Services\TenantContext;
use App\Services\UsageTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    protected $subscriptionService;

    protected $usageTrackingService;

    public function __construct(
        SubscriptionService $subscriptionService,
        UsageTrackingService $usageTrackingService
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->usageTrackingService = $usageTrackingService;
    }

    /**
     * Display subscription overview.
     */
    public function index()
    {
        $tenant = app(TenantContext::class)->get();
        $subscription = $tenant->subscription;
        $usageStats = $this->usageTrackingService->getUsageStats($tenant);
        $usageHistory = $this->usageTrackingService->getUsageHistory($tenant, 6);

        return view('dashboard.subscription.index', compact(
            'tenant',
            'subscription',
            'usageStats',
            'usageHistory'
        ));
    }

    /**
     * Display available subscription plans.
     */
    public function plans()
    {
        $tenant = app(TenantContext::class)->get();
        $currentSubscription = $tenant->subscription;
        $plans = SubscriptionPlan::active()->ordered()->get();

        return view('dashboard.subscription.plans', compact(
            'plans',
            'currentSubscription'
        ));
    }

    /**
     * Subscribe to a plan (for new subscriptions).
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $tenant = app(TenantContext::class)->get();
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        try {
            // Check if tenant already has a subscription
            if ($tenant->subscription && $tenant->subscription->canUse()) {
                return redirect()
                    ->route('dashboard.subscription.index')
                    ->with('error', 'You already have an active subscription.');
            }

            // Subscribe the tenant
            $subscription = $this->subscriptionService->subscribe($tenant, $plan, true);

            return redirect()
                ->route('dashboard.subscription.index')
                ->with('success', 'Successfully subscribed to '.$plan->name.' plan! Your 14-day trial has started.');

        } catch (\Exception $e) {
            Log::error('Subscription failed', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to subscribe. Please try again or contact support.');
        }
    }

    /**
     * Change subscription plan (upgrade/downgrade).
     */
    public function changePlan(Request $request)
    {
        $this->authorize('update', $request->user()->tenant);

        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $tenant = app(TenantContext::class)->get();
        $subscription = $tenant->subscription;
        $newPlan = SubscriptionPlan::findOrFail($request->plan_id);

        if (! $subscription) {
            return redirect()
                ->route('dashboard.subscription.plans')
                ->with('error', 'No active subscription found.');
        }

        if ($subscription->subscription_plan_id == $newPlan->id) {
            return back()->with('info', 'You are already on this plan.');
        }

        try {
            $this->subscriptionService->changePlan($subscription, $newPlan);

            $message = $newPlan->price > $subscription->plan->price
                ? 'Successfully upgraded to '.$newPlan->name.' plan!'
                : 'Successfully scheduled downgrade to '.$newPlan->name.' plan (effective at period end).';

            return redirect()
                ->route('dashboard.subscription.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Plan change failed', [
                'tenant_id' => $tenant->id,
                'old_plan_id' => $subscription->subscription_plan_id,
                'new_plan_id' => $newPlan->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to change plan. Please try again or contact support.');
        }
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request)
    {
        $this->authorize('update', $request->user()->tenant);

        $tenant = app(TenantContext::class)->get();
        $subscription = $tenant->subscription;

        if (! $subscription) {
            return back()->with('error', 'No active subscription found.');
        }

        $immediately = $request->input('immediately', false);

        try {
            $this->subscriptionService->cancel($subscription, $immediately);

            $message = $immediately
                ? 'Your subscription has been cancelled immediately.'
                : 'Your subscription will be cancelled at the end of your billing period.';

            return redirect()
                ->route('dashboard.subscription.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Cancellation failed', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to cancel subscription. Please try again or contact support.');
        }
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resume()
    {
        $this->authorize('update', auth()->user()->tenant);

        $tenant = app(TenantContext::class)->get();
        $subscription = $tenant->subscription;

        if (! $subscription || ! $subscription->cancel_at_period_end) {
            return back()->with('error', 'No cancelled subscription to resume.');
        }

        try {
            $this->subscriptionService->resume($subscription);

            return redirect()
                ->route('dashboard.subscription.index')
                ->with('success', 'Your subscription has been resumed!');

        } catch (\Exception $e) {
            Log::error('Resume failed', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to resume subscription. Please try again or contact support.');
        }
    }

    /**
     * Show billing history (invoices).
     */
    public function billingHistory()
    {
        $tenant = app(TenantContext::class)->get();

        // Get invoices from Stripe if available
        $invoices = [];
        if (config('cashier.key') && $tenant->stripe_id) {
            try {
                $invoices = $tenant->invoices();
            } catch (\Exception $e) {
                Log::error('Failed to fetch invoices', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('dashboard.subscription.billing-history', compact('invoices'));
    }

    /**
     * Show payment method management.
     */
    public function paymentMethods()
    {
        $this->authorize('update', auth()->user()->tenant);

        $tenant = app(TenantContext::class)->get();

        // Get payment methods from Stripe
        $paymentMethods = [];
        $defaultPaymentMethod = null;

        if (config('cashier.key') && $tenant->stripe_id) {
            try {
                $paymentMethods = $tenant->paymentMethods();
                $defaultPaymentMethod = $tenant->defaultPaymentMethod();
            } catch (\Exception $e) {
                Log::error('Failed to fetch payment methods', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('dashboard.subscription.payment-methods', compact(
            'paymentMethods',
            'defaultPaymentMethod'
        ));
    }

    /**
     * Get Stripe checkout session for updating payment method.
     */
    public function checkoutSession(Request $request)
    {
        $this->authorize('update', $request->user()->tenant);

        $tenant = app(TenantContext::class)->get();

        try {
            return $tenant->newSubscription('default', $request->plan_id)
                ->checkout([
                    'success_url' => route('dashboard.subscription.index').'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('dashboard.subscription.plans'),
                ]);
        } catch (\Exception $e) {
            Log::error('Checkout session creation failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to create checkout session.');
        }
    }
}
