<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ProductMapping extends Model
{
    use HasTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'platform',
        'platform_product_id',
        'platform_sku',
        'platform_name',
        'loyverse_item_id',
        'loyverse_variant_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_mappings';

    /**
     * Cache key prefix for product mappings.
     */
    const CACHE_PREFIX = 'product_mapping:';

    /**
     * Cache TTL in seconds (1 hour).
     */
    const CACHE_TTL = 3600;

    /**
     * Scope a query to only include active mappings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by platform.
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope a query to filter by platform product ID.
     */
    public function scopeByPlatformProductId($query, string $productId, ?string $platform = null)
    {
        $query = $query->where('platform_product_id', $productId);

        if ($platform) {
            $query = $query->where('platform', $platform);
        }

        return $query;
    }

    /**
     * Scope a query to filter by platform SKU.
     */
    public function scopeByPlatformSku($query, string $sku, ?string $platform = null)
    {
        $query = $query->where('platform_sku', $sku);

        if ($platform) {
            $query = $query->where('platform', $platform);
        }

        return $query;
    }

    /**
     * Scope a query to filter by Careem product ID (backward compatibility).
     *
     * @deprecated Use scopeByPlatformProductId instead
     */
    public function scopeByCareemProductId($query, string $productId)
    {
        return $query->byPlatformProductId($productId, 'careem');
    }

    /**
     * Scope a query to filter by Careem SKU (backward compatibility).
     *
     * @deprecated Use scopeByPlatformSku instead
     */
    public function scopeByCareemSku($query, string $sku)
    {
        return $query->byPlatformSku($sku, 'careem');
    }

    /**
     * Scope a query to filter by Loyverse item ID.
     */
    public function scopeByLoyverseItemId($query, string $itemId)
    {
        return $query->where('loyverse_item_id', $itemId);
    }

    /**
     * Get mapping by platform and product ID (with caching).
     */
    public static function findByPlatformProductId(string $platform, string $productId): ?self
    {
        $cacheKey = self::CACHE_PREFIX."{$platform}_product:{$productId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($platform, $productId) {
            return self::active()->byPlatformProductId($productId, $platform)->first();
        });
    }

    /**
     * Get mapping by platform and SKU (with caching).
     */
    public static function findByPlatformSku(string $platform, string $sku): ?self
    {
        $cacheKey = self::CACHE_PREFIX."{$platform}_sku:{$sku}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($platform, $sku) {
            return self::active()->byPlatformSku($sku, $platform)->first();
        });
    }

    /**
     * Get Loyverse item ID for a platform product.
     */
    public static function getLoyverseItemId(string $platform, string $productId, ?string $sku = null): ?string
    {
        // Try by product ID first
        $mapping = self::findByPlatformProductId($platform, $productId);

        // If not found and SKU provided, try by SKU
        if (! $mapping && $sku) {
            $mapping = self::findByPlatformSku($platform, $sku);
        }

        return $mapping ? $mapping->loyverse_item_id : null;
    }

    /**
     * Get mapping by Careem product ID (backward compatibility).
     *
     * @deprecated Use findByPlatformProductId instead
     */
    public static function findByCareemProductId(string $productId): ?self
    {
        return self::findByPlatformProductId('careem', $productId);
    }

    /**
     * Get mapping by Careem SKU (backward compatibility).
     *
     * @deprecated Use findByPlatformSku instead
     */
    public static function findByCareemSku(string $sku): ?self
    {
        return self::findByPlatformSku('careem', $sku);
    }

    /**
     * Create or update a product mapping.
     */
    public static function mapProduct(
        string $platform,
        string $platformProductId,
        string $platformName,
        string $loyverseItemId,
        ?string $platformSku = null,
        ?string $loyverseVariantId = null,
        bool $isActive = true
    ): self {
        $mapping = self::updateOrCreate(
            [
                'platform' => $platform,
                'platform_product_id' => $platformProductId,
            ],
            [
                'platform_sku' => $platformSku,
                'platform_name' => $platformName,
                'loyverse_item_id' => $loyverseItemId,
                'loyverse_variant_id' => $loyverseVariantId,
                'is_active' => $isActive,
            ]
        );

        // Clear cache
        self::clearCache($platform, $platformProductId, $platformSku);

        return $mapping;
    }

    /**
     * Deactivate a mapping.
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        $result = $this->save();

        // Clear cache
        self::clearCache($this->platform, $this->platform_product_id, $this->platform_sku);

        return $result;
    }

    /**
     * Activate a mapping.
     */
    public function activate(): bool
    {
        $this->is_active = true;
        $result = $this->save();

        // Clear cache
        self::clearCache($this->platform, $this->platform_product_id, $this->platform_sku);

        return $result;
    }

    /**
     * Clear cache for a product mapping.
     */
    public static function clearCache(string $platform, ?string $productId = null, ?string $sku = null): void
    {
        if ($productId) {
            Cache::forget(self::CACHE_PREFIX."{$platform}_product:{$productId}");
        }

        if ($sku) {
            Cache::forget(self::CACHE_PREFIX."{$platform}_sku:{$sku}");
        }
    }

    /**
     * Clear all product mapping cache.
     */
    public static function clearAllCache(): void
    {
        // This would require a more sophisticated approach in production
        // For now, we'll just document that individual cache entries are cleared on update
        Cache::flush(); // Use with caution in production
    }

    /**
     * Get all active mappings (for admin interface).
     */
    public static function getAllActive(?string $platform = null)
    {
        $query = self::active()->orderBy('platform')->orderBy('platform_name');

        if ($platform) {
            $query->byPlatform($platform);
        }

        return $query->get();
    }

    /**
     * Get unmapped Careem products (products in orders but not mapped).
     * This would need to query orders and find products without mappings.
     */
    public static function getUnmappedProducts(): array
    {
        // This is a placeholder - implement based on your needs
        // You would need to analyze Order data to find unmapped products
        return [];
    }

    /**
     * Bulk import mappings from array.
     */
    public static function bulkImport(array $mappings): int
    {
        $imported = 0;

        foreach ($mappings as $mapping) {
            try {
                self::mapProduct(
                    $mapping['platform'],
                    $mapping['platform_product_id'],
                    $mapping['platform_name'],
                    $mapping['loyverse_item_id'],
                    $mapping['platform_sku'] ?? null,
                    $mapping['loyverse_variant_id'] ?? null,
                    $mapping['is_active'] ?? true
                );
                $imported++;
            } catch (\Exception $e) {
                \Log::error('Failed to import product mapping', [
                    'mapping' => $mapping,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $imported;
    }
}
