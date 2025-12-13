<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Careem Brand Model
 *
 * Represents a restaurant brand registered with Careem (e.g., "KFC", "Subway")
 * A brand can have multiple branches (outlets).
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $careem_brand_id Unique identifier for Careem API
 * @property string $name Brand name
 * @property string $state UNMAPPED or MAPPED
 * @property array|null $metadata Additional data from Careem
 * @property \Carbon\Carbon|null $synced_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CareemBrand extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'careem_brand_id',
        'name',
        'state',
        'metadata',
        'synced_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns this brand
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get all branches for this brand
     */
    public function branches(): HasMany
    {
        return $this->hasMany(CareemBranch::class, 'careem_brand_id');
    }

    /**
     * Check if brand is mapped in Careem system
     */
    public function isMapped(): bool
    {
        return $this->state === 'MAPPED';
    }

    /**
     * Check if brand needs sync with Careem
     */
    public function needsSync(): bool
    {
        if (!$this->synced_at) {
            return true;
        }

        // Consider sync needed if more than 24 hours have passed
        return $this->synced_at->diffInHours(now()) > 24;
    }

    /**
     * Mark brand as synced
     */
    public function markAsSynced(): void
    {
        $this->update(['synced_at' => now()]);
    }
}
