<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request)
    {
        $query = Subscription::with(['tenant', 'subscriptionPlan']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan')) {
            $query->whereHas('subscriptionPlan', function ($q) use ($request) {
                $q->where('slug', $request->plan);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('tenant', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subdomain', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $subscriptions = $query->paginate(20)->withQueryString();

        // Get subscription statistics
        $stats = [
            'total' => Subscription::count(),
            'active' => Subscription::where('status', 'active')->count(),
            'trialing' => Subscription::where('status', 'trialing')->count(),
            'cancelled' => Subscription::where('status', 'cancelled')->count(),
            'past_due' => Subscription::where('status', 'past_due')->count(),
        ];

        return view('super-admin.subscriptions.index', compact('subscriptions', 'stats'));
    }

    /**
     * Display the specified subscription.
     */
    public function show(Subscription $subscription)
    {
        $subscription->load(['tenant', 'subscriptionPlan', 'usage']);

        // Get usage history for the last 6 months
        $usageHistory = $subscription->usage()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->take(6)
            ->get();

        return view('super-admin.subscriptions.show', compact('subscription', 'usageHistory'));
    }

    /**
     * Cancel the specified subscription.
     */
    public function cancel(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancel_at_period_end' => true,
        ]);

        // TODO: Send cancellation confirmation email

        return redirect()
            ->back()
            ->with('success', 'Subscription will be cancelled at the end of the billing period.');
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resume(Subscription $subscription)
    {
        if ($subscription->status !== 'cancelled') {
            return redirect()
                ->back()
                ->with('error', 'Only cancelled subscriptions can be resumed.');
        }

        $subscription->update([
            'status' => 'active',
            'cancel_at_period_end' => false,
        ]);

        // TODO: Send resume confirmation email

        return redirect()
            ->back()
            ->with('success', 'Subscription resumed successfully.');
    }

    /**
     * Change the subscription plan.
     */
    public function changePlan(Request $request, Subscription $subscription)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $newPlan = SubscriptionPlan::findOrFail($request->plan_id);

        $subscription->update([
            'subscription_plan_id' => $newPlan->id,
        ]);

        // TODO: Handle proration with Stripe
        // TODO: Send plan change confirmation email

        return redirect()
            ->back()
            ->with('success', 'Subscription plan changed successfully.');
    }

    /**
     * Extend trial period.
     */
    public function extendTrial(Request $request, Subscription $subscription)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:90',
        ]);

        $tenant = $subscription->tenant;
        $currentTrialEnd = $tenant->trial_ends_at ?? now();

        $tenant->update([
            'trial_ends_at' => $currentTrialEnd->addDays($request->days),
        ]);

        // TODO: Send trial extension email

        return redirect()
            ->back()
            ->with('success', "Trial period extended by {$request->days} days.");
    }
}
