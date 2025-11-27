<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;

class Tenant extends Model
{
    use Billable, HasFactory, HasUuids, SoftDeletes;

    protected static function booted()
    {
        static::creating(function ($tenant) {
            if (empty($tenant->careem_api_key)) {
                $tenant->careem_api_key = 'ck_' . Str::random(32);
            }
        });
    }

    protected $fillable = [
        'name',
        'subdomain',
        'careem_api_key',
        'domain',
        'status',
        'settings',
        'trial_ends_at',
        'onboarding_completed_at',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
    ];

    /**
     * Get the subscription for the tenant.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Get all users for the tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all orders for the tenant.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all product mappings for the tenant.
     */
    public function productMappings(): HasMany
    {
        return $this->hasMany(ProductMapping::class);
    }

    /**
     * Get all locations for the tenant.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if tenant is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if tenant is in trial period.
     */
    public function inTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if onboarding is completed.
     */
    public function hasCompletedOnboarding(): bool
    {
        return ! is_null($this->onboarding_completed_at);
    }

    /**
     * Mark onboarding as completed.
     */
    public function completeOnboarding(): void
    {
        $this->update(['onboarding_completed_at' => now()]);
    }

    /**
     * Check if a platform is enabled for this tenant.
     */
    public function isPlatformEnabled(string $platform): bool
    {
        $enabledPlatforms = $this->settings['enabled_platforms'] ?? [];
        return in_array($platform, $enabledPlatforms);
    }

    /**
     * Check if auto-accept is enabled for a specific platform.
     */
    public function isAutoAcceptEnabled(string $platform): bool
    {
        $key = "auto_accept_{$platform}";
        return $this->settings[$key] ?? false;
    }

    /**
     * Get enabled platforms for this tenant.
     */
    public function getEnabledPlatforms(): array
    {
        return $this->settings['enabled_platforms'] ?? [];
    }

    /**
     * Get a setting value with optional default.
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Update a specific setting.
     */
    public function updateSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->update(['settings' => $settings]);
    }

    /**
     * Get timezone for this tenant.
     */
    public function getTimezone(): string
    {
        return $this->settings['timezone'] ?? 'Asia/Dubai';
    }

    /**
     * Get currency for this tenant.
     */
    public function getCurrency(): string
    {
        return $this->settings['currency'] ?? 'AED';
    }

    /**
     * Get language for this tenant.
     */
    public function getLanguage(): string
    {
        return $this->settings['language'] ?? 'en';
    }
}
