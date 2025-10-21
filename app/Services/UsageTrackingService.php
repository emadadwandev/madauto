<?php

namespace App\Services;

use App\Models\SubscriptionUsage;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class UsageTrackingService
{
    /**
     * Record an order for usage tracking.
     */
    public function recordOrder(Tenant $tenant): void
    {
        try {
            $subscription = $tenant->subscription;

            if (! $subscription) {
                Log::warning('Attempted to record usage for tenant without subscription', [
                    'tenant_id' => $tenant->id,
                ]);

                return;
            }

            $currentMonth = now()->month;
            $currentYear = now()->year;

            // Get or create usage record for current month
            $usage = SubscriptionUsage::firstOrCreate(
                [
                    'subscription_id' => $subscription->id,
                    'tenant_id' => $tenant->id,
                    'month' => $currentMonth,
                    'year' => $currentYear,
                ],
                [
                    'order_count' => 0,
                ]
            );

            // Increment order count
            $usage->increment('order_count');
            $usage->update(['last_order_at' => now()]);

            Log::info('Order usage recorded', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'month' => $currentMonth,
                'year' => $currentYear,
                'new_count' => $usage->order_count,
            ]);

            // Check if approaching limit and send notification
            $this->checkUsageLimits($tenant, $usage);

        } catch (\Exception $e) {
            Log::error('Failed to record order usage', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get current usage (order count) for the tenant this month.
     */
    public function getCurrentUsage(Tenant $tenant): int
    {
        $subscription = $tenant->subscription;

        if (! $subscription) {
            return 0;
        }

        $usage = SubscriptionUsage::where('subscription_id', $subscription->id)
            ->where('tenant_id', $tenant->id)
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->first();

        return $usage ? $usage->order_count : 0;
    }

    /**
     * Check if tenant is within usage limits.
     */
    public function withinLimits(Tenant $tenant): bool
    {
        $subscription = $tenant->subscription;

        if (! $subscription || ! $subscription->canUse()) {
            return false;
        }

        // Unlimited plan
        if ($subscription->plan->hasUnlimitedOrders()) {
            return true;
        }

        $currentUsage = $this->getCurrentUsage($tenant);

        return $currentUsage < $subscription->plan->order_limit;
    }

    /**
     * Get usage percentage (0-100).
     */
    public function getUsagePercentage(Tenant $tenant): float
    {
        $subscription = $tenant->subscription;

        if (! $subscription) {
            return 0;
        }

        if ($subscription->plan->hasUnlimitedOrders()) {
            return 0; // No limit
        }

        $currentUsage = $this->getCurrentUsage($tenant);
        $limit = $subscription->plan->order_limit;

        if ($limit == 0) {
            return 100;
        }

        return min(100, ($currentUsage / $limit) * 100);
    }

    /**
     * Get usage statistics for a tenant.
     */
    public function getUsageStats(Tenant $tenant): array
    {
        $subscription = $tenant->subscription;

        if (! $subscription) {
            return [
                'current_usage' => 0,
                'limit' => 0,
                'remaining' => 0,
                'percentage' => 0,
                'unlimited' => false,
            ];
        }

        $currentUsage = $this->getCurrentUsage($tenant);
        $limit = $subscription->plan->order_limit;
        $unlimited = $subscription->plan->hasUnlimitedOrders();

        return [
            'current_usage' => $currentUsage,
            'limit' => $unlimited ? null : $limit,
            'remaining' => $unlimited ? null : max(0, $limit - $currentUsage),
            'percentage' => $this->getUsagePercentage($tenant),
            'unlimited' => $unlimited,
        ];
    }

    /**
     * Get usage history for a tenant (last 12 months).
     */
    public function getUsageHistory(Tenant $tenant, int $months = 12): array
    {
        $subscription = $tenant->subscription;

        if (! $subscription) {
            return [];
        }

        $history = SubscriptionUsage::where('subscription_id', $subscription->id)
            ->where('tenant_id', $tenant->id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit($months)
            ->get();

        return $history->map(function ($usage) {
            return [
                'month' => $usage->month,
                'year' => $usage->year,
                'order_count' => $usage->order_count,
                'last_order_at' => $usage->last_order_at?->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Reset usage for a new billing period.
     * This is typically called by a scheduled task.
     */
    public function resetMonthlyUsage(Tenant $tenant): void
    {
        // Monthly usage is automatically handled by creating new records
        // for each month/year combination. No need to reset.
        // This method is here for future use if needed.

        Log::info('Monthly usage check (auto-handled by month/year records)', [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Check usage limits and send notifications if needed.
     */
    protected function checkUsageLimits(Tenant $tenant, SubscriptionUsage $usage): void
    {
        $subscription = $tenant->subscription;

        if ($subscription->plan->hasUnlimitedOrders()) {
            return;
        }

        $percentage = $this->getUsagePercentage($tenant);
        $limit = $subscription->plan->order_limit;

        // Send warning at 80%
        if ($percentage >= 80 && $percentage < 100) {
            $this->sendUsageWarning($tenant, $usage->order_count, $limit, 80);
        }

        // Send alert at 100%
        if ($percentage >= 100) {
            $this->sendUsageAlert($tenant, $usage->order_count, $limit);
        }
    }

    /**
     * Send usage warning email (80% threshold).
     */
    protected function sendUsageWarning(Tenant $tenant, int $usage, int $limit, int $threshold): void
    {
        // TODO: Implement email notification
        // Mail::to($tenant->adminEmail())->send(new UsageWarningMail($tenant, $usage, $limit, $threshold));

        Log::info('Usage warning threshold reached', [
            'tenant_id' => $tenant->id,
            'usage' => $usage,
            'limit' => $limit,
            'threshold' => $threshold,
        ]);
    }

    /**
     * Send usage alert email (100% reached).
     */
    protected function sendUsageAlert(Tenant $tenant, int $usage, int $limit): void
    {
        // TODO: Implement email notification
        // Mail::to($tenant->adminEmail())->send(new UsageLimitReachedMail($tenant, $usage, $limit));

        Log::warning('Usage limit reached', [
            'tenant_id' => $tenant->id,
            'usage' => $usage,
            'limit' => $limit,
        ]);
    }
}
