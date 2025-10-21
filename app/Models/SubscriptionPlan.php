<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'currency',
        'billing_interval',
        'order_limit',
        'location_limit',
        'user_limit',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'order_limit' => 'integer',
        'location_limit' => 'integer',
        'user_limit' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get all subscriptions using this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Check if plan has unlimited orders.
     */
    public function hasUnlimitedOrders(): bool
    {
        return is_null($this->order_limit);
    }

    /**
     * Get the features array.
     */
    public function getFeatures(): array
    {
        return $this->features ?? [];
    }

    /**
     * Check if plan has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->getFeatures());
    }

    /**
     * Scope for active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordering by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
