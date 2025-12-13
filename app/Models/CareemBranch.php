<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Careem Branch Model
 *
 * Represents a branch/outlet registered with Careem (e.g., "KFC, Marina Mall")
 * A branch belongs to a brand and can be mapped to a local Location.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $careem_brand_id
 * @property int|null $location_id Local location mapping
 * @property string $careem_branch_id Unique identifier for Careem API
 * @property string $name Branch name
 * @property string $state UNMAPPED or MAPPED
 * @property bool $pos_integration_enabled
 * @property int $visibility_status 1=Active, 2=Inactive
 * @property array|null $metadata Additional data from Careem
 * @property \Carbon\Carbon|null $synced_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CareemBranch extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'careem_brand_id',
        'location_id',
        'careem_branch_id',
        'name',
        'state',
        'pos_integration_enabled',
        'visibility_status',
        'metadata',
        'synced_at',
    ];

    protected $casts = [
        'pos_integration_enabled' => 'boolean',
        'visibility_status' => 'integer',
        'metadata' => 'array',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns this branch
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the brand that owns this branch
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(CareemBrand::class, 'careem_brand_id');
    }

    /**
     * Get the mapped local location (if any)
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Check if branch is mapped in Careem system
     */
    public function isMapped(): bool
    {
        return $this->state === 'MAPPED';
    }

    /**
     * Check if POS integration is enabled
     */
    public function isPosIntegrationEnabled(): bool
    {
        return $this->pos_integration_enabled;
    }

    /**
     * Check if branch is active on SuperApp
     */
    public function isActive(): bool
    {
        return $this->visibility_status === 1;
    }

    /**
     * Check if branch is mapped to a local location
     */
    public function hasLocation(): bool
    {
        return $this->location_id !== null;
    }

    /**
     * Check if branch needs sync with Careem
     */
    public function needsSync(): bool
    {
        if (!$this->synced_at) {
            return true;
        }

        // Consider sync needed if more than 6 hours have passed
        return $this->synced_at->diffInHours(now()) > 6;
    }

    /**
     * Mark branch as synced
     */
    public function markAsSynced(): void
    {
        $this->update(['synced_at' => now()]);
    }

    /**
     * Get visibility status label
     */
    public function getVisibilityStatusLabelAttribute(): string
    {
        return match($this->visibility_status) {
            1 => 'Active',
            2 => 'Inactive',
            default => 'Unknown',
        };
    }

    /**
     * Get state badge color
     */
    public function getStateBadgeColorAttribute(): string
    {
        return match($this->state) {
            'MAPPED' => 'success',
            'UNMAPPED' => 'warning',
            default => 'secondary',
        };
    }
}
