<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
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
        'description',
        'image_url',
        'status',
        'is_active',
        'published_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to only include active menus.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include published menus.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include draft menus.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Get the menu items for this menu.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    /**
     * Get active menu items.
     */
    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true)->where('is_available', true);
    }

    /**
     * Get the locations this menu is assigned to.
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'menu_location')
            ->withPivot('is_active', 'override_settings')
            ->withTimestamps();
    }

    /**
     * Get active locations for this menu.
     */
    public function activeLocations(): BelongsToMany
    {
        return $this->locations()->wherePivot('is_active', true);
    }

    /**
     * Get the platforms this menu is published to.
     */
    public function platforms(): array
    {
        return \DB::table('menu_platform')
            ->where('menu_id', $this->id)
            ->pluck('platform')
            ->toArray();
    }

    /**
     * Get platform sync records.
     */
    public function platformSyncs()
    {
        return \DB::table('menu_platform')->where('menu_id', $this->id)->get();
    }

    /**
     * Check if menu is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if menu is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Publish the menu.
     */
    public function publish(): bool
    {
        $this->status = 'published';
        $this->published_at = now();

        return $this->save();
    }

    /**
     * Unpublish the menu (set to draft).
     */
    public function unpublish(): bool
    {
        $this->status = 'draft';

        return $this->save();
    }

    /**
     * Assign menu to platform.
     */
    public function assignToPlatform(string $platform): void
    {
        \DB::table('menu_platform')->updateOrInsert(
            [
                'menu_id' => $this->id,
                'platform' => $platform,
            ],
            [
                'sync_status' => 'pending',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Remove menu from platform.
     */
    public function removeFromPlatform(string $platform): void
    {
        \DB::table('menu_platform')
            ->where('menu_id', $this->id)
            ->where('platform', $platform)
            ->delete();
    }

    /**
     * Update platform sync status.
     */
    public function updatePlatformSync(string $platform, string $status, ?string $error = null, ?array $platformMenuId = null): void
    {
        \DB::table('menu_platform')
            ->where('menu_id', $this->id)
            ->where('platform', $platform)
            ->update([
                'sync_status' => $status,
                'last_synced_at' => $status === 'synced' ? now() : null,
                'sync_error' => $error,
                'platform_menu_id' => $platformMenuId ? json_encode($platformMenuId) : null,
                'updated_at' => now(),
            ]);
    }
}
