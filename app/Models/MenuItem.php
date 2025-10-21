<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MenuItem extends Model
{
    use HasTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'menu_id',
        'tenant_id',
        'name',
        'description',
        'image_url',
        'sku',
        'default_quantity',
        'price',
        'tax_rate',
        'loyverse_item_id',
        'loyverse_variant_id',
        'category',
        'sort_order',
        'is_available',
        'is_active',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'default_quantity' => 'integer',
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'sort_order' => 'integer',
        'is_available' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to only include active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include available items.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope a query by category.
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the menu this item belongs to.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the modifier groups for this item.
     */
    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'menu_item_modifier_group')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get active modifier groups with their active modifiers.
     */
    public function activeModifierGroups(): BelongsToMany
    {
        return $this->modifierGroups()
            ->where('modifier_groups.is_active', true)
            ->with(['activeModifiers']);
    }

    /**
     * Calculate price with modifiers.
     */
    public function calculatePrice(array $selectedModifiers = []): float
    {
        $total = $this->price;

        foreach ($selectedModifiers as $modifierId) {
            $modifier = Modifier::find($modifierId);
            if ($modifier) {
                $total += $modifier->price_adjustment;
            }
        }

        return round($total, 2);
    }

    /**
     * Calculate tax amount.
     */
    public function calculateTax(float $price = null): float
    {
        $basePrice = $price ?? $this->price;

        return round($basePrice * ($this->tax_rate / 100), 2);
    }

    /**
     * Get total price including tax.
     */
    public function getTotalPriceWithTax(array $selectedModifiers = []): float
    {
        $price = $this->calculatePrice($selectedModifiers);
        $tax = $this->calculateTax($price);

        return round($price + $tax, 2);
    }

    /**
     * Check if item is mapped to Loyverse.
     */
    public function isMappedToLoyverse(): bool
    {
        return ! empty($this->loyverse_item_id);
    }

    /**
     * Mark as unavailable.
     */
    public function markUnavailable(): bool
    {
        $this->is_available = false;

        return $this->save();
    }

    /**
     * Mark as available.
     */
    public function markAvailable(): bool
    {
        $this->is_available = true;

        return $this->save();
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2).' AED';
    }
}
