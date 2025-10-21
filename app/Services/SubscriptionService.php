<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Subscribe a tenant to a plan.
     */
    public function subscribe(Tenant $tenant, SubscriptionPlan $plan, bool $trial = true): Subscription
    {
        try {
            DB::beginTransaction();

            // Create or update subscription in our database
            $subscription = Subscription::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'subscription_plan_id' => $plan->id,
                    'status' => $trial ? 'trialing' : 'active',
                    'trial_ends_at' => $trial ? now()->addDays(14) : null,
                    'current_period_start' => now(),
                    'current_period_end' => now()->addMonth(),
                    'cancel_at_period_end' => false,
                ]
            );

            // If Stripe is configured, create Stripe subscription
            if (config('cashier.key')) {
                $stripeSubscription = $tenant->newSubscription('default', $plan->stripe_price_id ?? '')
                    ->create();

                $subscription->update([
                    'stripe_subscription_id' => $stripeSubscription->id,
                ]);
            }

            DB::commit();

            Log::info('Subscription created', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'subscription_id' => $subscription->id,
            ]);

            return $subscription;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create subscription', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(Subscription $subscription, bool $immediately = false): void
    {
        try {
            DB::beginTransaction();

            if ($immediately) {
                // Cancel immediately
                $subscription->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancel_at_period_end' => false,
                ]);

                // Cancel in Stripe if exists
                if ($subscription->stripe_subscription_id && config('cashier.key')) {
                    $subscription->tenant->subscription('default')->cancelNow();
                }
            } else {
                // Cancel at period end
                $subscription->update([
                    'cancel_at_period_end' => true,
                ]);

                // Cancel at period end in Stripe
                if ($subscription->stripe_subscription_id && config('cashier.key')) {
                    $subscription->tenant->subscription('default')->cancel();
                }
            }

            DB::commit();

            Log::info('Subscription cancelled', [
                'subscription_id' => $subscription->id,
                'immediately' => $immediately,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resume(Subscription $subscription): void
    {
        try {
            DB::beginTransaction();

            $subscription->update([
                'cancel_at_period_end' => false,
                'cancelled_at' => null,
                'status' => 'active',
            ]);

            // Resume in Stripe
            if ($subscription->stripe_subscription_id && config('cashier.key')) {
                $subscription->tenant->subscription('default')->resume();
            }

            DB::commit();

            Log::info('Subscription resumed', [
                'subscription_id' => $subscription->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to resume subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Upgrade to a higher plan.
     */
    public function upgrade(Subscription $subscription, SubscriptionPlan $newPlan): void
    {
        try {
            DB::beginTransaction();

            $oldPlanId = $subscription->subscription_plan_id;

            $subscription->update([
                'subscription_plan_id' => $newPlan->id,
            ]);

            // Swap plan in Stripe
            if ($subscription->stripe_subscription_id && config('cashier.key')) {
                $subscription->tenant->subscription('default')
                    ->swap($newPlan->stripe_price_id ?? '');
            }

            DB::commit();

            Log::info('Subscription upgraded', [
                'subscription_id' => $subscription->id,
                'old_plan_id' => $oldPlanId,
                'new_plan_id' => $newPlan->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to upgrade subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Downgrade to a lower plan (effective at period end).
     */
    public function downgrade(Subscription $subscription, SubscriptionPlan $newPlan): void
    {
        try {
            DB::beginTransaction();

            $oldPlanId = $subscription->subscription_plan_id;

            // Downgrade takes effect at the end of the billing period
            $subscription->update([
                'subscription_plan_id' => $newPlan->id,
            ]);

            // Schedule downgrade in Stripe
            if ($subscription->stripe_subscription_id && config('cashier.key')) {
                $subscription->tenant->subscription('default')
                    ->swapNextCycle($newPlan->stripe_price_id ?? '');
            }

            DB::commit();

            Log::info('Subscription downgraded (effective at period end)', [
                'subscription_id' => $subscription->id,
                'old_plan_id' => $oldPlanId,
                'new_plan_id' => $newPlan->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to downgrade subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Change subscription plan (immediate swap).
     */
    public function changePlan(Subscription $subscription, SubscriptionPlan $newPlan): void
    {
        // Determine if it's an upgrade or downgrade based on price
        if ($newPlan->price > $subscription->plan->price) {
            $this->upgrade($subscription, $newPlan);
        } else {
            $this->downgrade($subscription, $newPlan);
        }
    }

    /**
     * Check if tenant can create more orders based on their plan limits.
     */
    public function canProcessOrder(Tenant $tenant): bool
    {
        $subscription = $tenant->subscription;

        if (! $subscription || ! $subscription->canUse()) {
            return false;
        }

        // If plan has unlimited orders
        if ($subscription->plan->hasUnlimitedOrders()) {
            return true;
        }

        // Check current usage
        $usageService = app(UsageTrackingService::class);
        $currentUsage = $usageService->getCurrentUsage($tenant);

        return $currentUsage < $subscription->plan->order_limit;
    }

    /**
     * Get remaining orders for the current billing period.
     */
    public function getRemainingOrders(Tenant $tenant): ?int
    {
        $subscription = $tenant->subscription;

        if (! $subscription) {
            return 0;
        }

        if ($subscription->plan->hasUnlimitedOrders()) {
            return null; // Unlimited
        }

        $usageService = app(UsageTrackingService::class);
        $currentUsage = $usageService->getCurrentUsage($tenant);

        return max(0, $subscription->plan->order_limit - $currentUsage);
    }

    /**
     * Get usage percentage for the current billing period.
     */
    public function getUsagePercentage(Tenant $tenant): float
    {
        $subscription = $tenant->subscription;

        if (! $subscription || $subscription->plan->hasUnlimitedOrders()) {
            return 0;
        }

        $usageService = app(UsageTrackingService::class);
        $currentUsage = $usageService->getCurrentUsage($tenant);

        if ($subscription->plan->order_limit == 0) {
            return 100;
        }

        return min(100, ($currentUsage / $subscription->plan->order_limit) * 100);
    }
}
