<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionUsage extends Model
{
    protected $table = 'subscription_usage';

    protected $fillable = [
        'subscription_id',
        'tenant_id',
        'month',
        'year',
        'order_count',
        'last_order_at',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'order_count' => 'integer',
        'last_order_at' => 'datetime',
    ];

    /**
     * Get the subscription this usage record belongs to.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the tenant this usage record belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Increment the order count for this usage period.
     */
    public function incrementOrderCount(): void
    {
        $this->increment('order_count');
        $this->update(['last_order_at' => now()]);
    }

    /**
     * Check if the order limit has been reached for this period.
     */
    public function hasReachedLimit(): bool
    {
        $plan = $this->subscription->plan;

        // Unlimited orders
        if ($plan->hasUnlimitedOrders()) {
            return false;
        }

        return $this->order_count >= $plan->order_limit;
    }

    /**
     * Get the percentage of the limit used.
     */
    public function getLimitPercentage(): ?float
    {
        $plan = $this->subscription->plan;

        if ($plan->hasUnlimitedOrders()) {
            return null;
        }

        return ($this->order_count / $plan->order_limit) * 100;
    }

    /**
     * Get remaining orders for this period.
     */
    public function getRemainingOrders(): ?int
    {
        $plan = $this->subscription->plan;

        if ($plan->hasUnlimitedOrders()) {
            return null;
        }

        return max(0, $plan->order_limit - $this->order_count);
    }

    /**
     * Scope for current month.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->where('month', now()->month)
            ->where('year', now()->year);
    }

    /**
     * Scope for specific tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
