<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Modifier extends Model
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
        'price_adjustment',
        'loyverse_modifier_id',
        'sku',
        'is_active',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to only include active modifiers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by Loyverse modifier ID.
     */
    public function scopeByLoyverseId($query, string $loyverseId)
    {
        return $query->where('loyverse_modifier_id', $loyverseId);
    }

    /**
     * Get the modifier groups this modifier belongs to.
     */
    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'modifier_group_modifier')
            ->withPivot('sort_order', 'is_default')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Check if modifier has a price adjustment.
     */
    public function hasPriceAdjustment(): bool
    {
        return $this->price_adjustment != 0;
    }

    /**
     * Get formatted price adjustment with sign and tenant currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->price_adjustment > 0) {
            return '+'.formatCurrency($this->price_adjustment);
        } elseif ($this->price_adjustment < 0) {
            return formatCurrency($this->price_adjustment);
        }

        return 'Free';
    }

    /**
     * Sync with Loyverse modifier.
     */
    public static function syncFromLoyverse(array $loyverseModifier): self
    {
        return self::updateOrCreate(
            [
                'loyverse_modifier_id' => $loyverseModifier['id'],
            ],
            [
                'name' => $loyverseModifier['name'] ?? $loyverseModifier['modifier_name'],
                'price_adjustment' => $loyverseModifier['price'] ?? 0,
                'is_active' => true,
            ]
        );
    }
}
