<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class CareemCatalogItem extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'careem_item_id',
        'careem_catalog_id',
        'name',
        'description',
        'sku',
        'price',
        'currency',
        'category_id',
        'is_available',
        'is_active',
        'image_url',
        'modifier_group_ids',
        'external_id',
        'raw_data',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'is_active' => 'boolean',
        'modifier_group_ids' => 'array',
        'raw_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Find item by Careem item ID
     */
    public static function findByItemId(string $itemId): ?self
    {
        return static::where('careem_item_id', $itemId)
            ->where('tenant_id', tenant()->id)
            ->first();
    }

    /**
     * Find item by SKU
     */
    public static function findBySku(string $sku): ?self
    {
        return static::where('sku', $sku)
            ->where('tenant_id', tenant()->id)
            ->first();
    }

    /**
     * Get product mapping for this item
     */
    public function productMapping()
    {
        return $this->hasOne(ProductMapping::class, 'platform_product_id', 'careem_item_id')
            ->where('platform', 'careem')
            ->where('tenant_id', tenant()->id);
    }
}
