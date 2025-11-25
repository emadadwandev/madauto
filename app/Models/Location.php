<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Location extends Model
{
    use HasTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'platforms',
        'opening_hours',
        'is_busy',
        'is_active',
        'loyverse_store_id',
        'careem_store_id',
        'talabat_vendor_id',
        'platform_sync_status',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'platforms' => 'array',
        'opening_hours' => 'array',
        'is_busy' => 'boolean',
        'is_active' => 'boolean',
        'platform_sync_status' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to only include active locations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include non-busy locations.
     */
    public function scopeNotBusy($query)
    {
        return $query->where('is_busy', false);
    }

    /**
     * Scope a query to filter by platform.
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->whereJsonContains('platforms', $platform);
    }

    /**
     * Get the menus assigned to this location.
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'menu_location')
            ->withPivot('is_active', 'override_settings')
            ->withTimestamps();
    }

    /**
     * Get active menus for this location.
     */
    public function activeMenus(): BelongsToMany
    {
        return $this->menus()
            ->wherePivot('is_active', true)
            ->where('menus.is_active', true);
    }

    /**
     * Check if location supports a platform.
     */
    public function supportsPlatform(string $platform): bool
    {
        return in_array($platform, $this->platforms ?? []);
    }

    /**
     * Get full address as string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if location is currently open.
     */
    public function isOpenNow(): bool
    {
        if (! $this->is_active || $this->is_busy) {
            return false;
        }

        if (empty($this->opening_hours)) {
            return true; // If no hours set, assume always open
        }

        $dayOfWeek = strtolower(now()->format('l')); // monday, tuesday, etc.
        $currentTime = now()->format('H:i');

        $hours = $this->opening_hours[$dayOfWeek] ?? null;

        if (! $hours || ! isset($hours['open'], $hours['close'])) {
            return false; // Closed if no hours defined for this day
        }

        return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
    }

    /**
     * Get today's opening hours.
     */
    public function getTodayHoursAttribute(): ?array
    {
        if (empty($this->opening_hours)) {
            return null;
        }

        $dayOfWeek = strtolower(now()->format('l'));

        return $this->opening_hours[$dayOfWeek] ?? null;
    }

    /**
     * Toggle busy mode.
     */
    public function toggleBusyMode(): bool
    {
        $this->is_busy = ! $this->is_busy;

        return $this->save();
    }

    /**
     * Set busy mode.
     */
    public function setBusy(bool $busy = true): bool
    {
        $this->is_busy = $busy;

        return $this->save();
    }

    /**
     * Add platform to location.
     */
    public function addPlatform(string $platform): bool
    {
        $platforms = $this->platforms ?? [];

        if (! in_array($platform, $platforms)) {
            $platforms[] = $platform;
            $this->platforms = $platforms;

            return $this->save();
        }

        return false;
    }

    /**
     * Remove platform from location.
     */
    public function removePlatform(string $platform): bool
    {
        $platforms = $this->platforms ?? [];
        $key = array_search($platform, $platforms);

        if ($key !== false) {
            unset($platforms[$key]);
            $this->platforms = array_values($platforms);

            return $this->save();
        }

        return false;
    }
}
