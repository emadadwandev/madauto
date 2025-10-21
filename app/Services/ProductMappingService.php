<?php

namespace App\Services;

use App\Models\ProductMapping;
use App\Models\SyncLog;
use Illuminate\Support\Facades\Log;

class ProductMappingService
{
    protected $loyverseApiService;

    public function __construct(LoyverseApiService $loyverseApiService)
    {
        $this->loyverseApiService = $loyverseApiService;
    }

    /**
     * Get Loyverse item ID for a platform product.
     * Returns null if no mapping exists.
     */
    public function getLoyverseItemId(string $platform, string $productId, ?string $sku = null, ?int $orderId = null): ?string
    {
        $loyverseItemId = ProductMapping::getLoyverseItemId($platform, $productId, $sku);

        if (! $loyverseItemId && $orderId) {
            // Log missing mapping
            SyncLog::logFailure(
                $orderId,
                'product_mapping',
                "Product mapping not found for {$platform} product: {$productId}".($sku ? " (SKU: {$sku})" : ''),
                [
                    'platform' => $platform,
                    'product_id' => $productId,
                    'sku' => $sku,
                ]
            );

            Log::warning('Product mapping not found', [
                'order_id' => $orderId,
                'platform' => $platform,
                'product_id' => $productId,
                'sku' => $sku,
            ]);
        }

        return $loyverseItemId;
    }

    /**
     * Get Loyverse variant ID for a platform product.
     */
    public function getLoyverseVariantId(string $platform, string $productId, ?string $sku = null): ?string
    {
        // Try by product ID first
        $mapping = ProductMapping::findByPlatformProductId($platform, $productId);

        // If not found and SKU provided, try by SKU
        if (! $mapping && $sku) {
            $mapping = ProductMapping::findByPlatformSku($platform, $sku);
        }

        return $mapping ? $mapping->loyverse_variant_id : null;
    }

    /**
     * Map order items to Loyverse format (platform-agnostic).
     * Returns array of mapped items and array of unmapped items.
     */
    public function mapOrderItems(array $items, ?int $orderId = null, string $platform = 'careem'): array
    {
        $mappedItems = [];
        $unmappedItems = [];

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? $item['id'] ?? null;
            $sku = $item['sku'] ?? null;

            if (! $productId) {
                $unmappedItems[] = [
                    'item' => $item,
                    'reason' => "Missing product_id in {$platform} order",
                ];

                continue;
            }

            $loyverseItemId = $this->getLoyverseItemId($platform, $productId, $sku, $orderId);

            if ($loyverseItemId) {
                $mappedItems[] = [
                    'platform' => $platform,
                    'platform_product_id' => $productId,
                    'platform_sku' => $sku,
                    'loyverse_item_id' => $loyverseItemId,
                    'loyverse_variant_id' => $this->getLoyverseVariantId($platform, $productId, $sku),
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['unit_price'] ?? $item['price'] ?? 0,
                    'original_item' => $item,
                ];
            } else {
                $unmappedItems[] = [
                    'item' => $item,
                    'reason' => 'No product mapping found',
                    'platform' => $platform,
                    'platform_product_id' => $productId,
                    'platform_sku' => $sku,
                ];
            }
        }

        return [
            'mapped' => $mappedItems,
            'unmapped' => $unmappedItems,
        ];
    }

    /**
     * Create a new product mapping.
     */
    public function createMapping(
        string $platform,
        string $platformProductId,
        string $platformName,
        string $loyverseItemId,
        ?string $platformSku = null,
        ?string $loyverseVariantId = null
    ): ProductMapping {
        return ProductMapping::mapProduct(
            $platform,
            $platformProductId,
            $platformName,
            $loyverseItemId,
            $platformSku,
            $loyverseVariantId
        );
    }

    /**
     * Auto-map products by SKU matching.
     * Attempts to match Careem products to Loyverse items by SKU.
     */
    public function autoMapBySku(bool $dryRun = false): array
    {
        $results = [
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            // Check if API credentials are configured
            if (! $this->loyverseApiService->hasCredentials()) {
                $results['errors'][] = 'Loyverse API credentials not configured';

                return $results;
            }

            // Get all Loyverse items
            $loyverseItems = $this->loyverseApiService->getAllItems();

            // Build SKU index
            $skuIndex = [];
            foreach ($loyverseItems as $item) {
                if (isset($item['variants'])) {
                    foreach ($item['variants'] as $variant) {
                        if (! empty($variant['sku'])) {
                            $sku = strtolower(trim($variant['sku']));
                            $skuIndex[$sku] = [
                                'item_id' => $item['id'],
                                'variant_id' => $variant['variant_id'],
                                'item_name' => $item['item_name'],
                                'variant_name' => $variant['variant_name'] ?? null,
                            ];
                        }
                    }
                }
            }

            // Note: Auto-mapping requires existing orders with products
            // This is a basic implementation that can be extended
            if (empty($skuIndex)) {
                $results['errors'][] = 'No Loyverse items with SKUs found';
                Log::warning('Auto-mapping: No Loyverse items with SKUs found');
            } else {
                Log::info('Auto-mapping by SKU: Found '.count($skuIndex).' Loyverse items with SKUs');
                $results['skipped'] = count($skuIndex);
            }

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('Auto-mapping by SKU failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $results;
    }

    /**
     * Sync all Loyverse items for mapping interface.
     * Returns list of all Loyverse items for admin to map.
     */
    public function getAllLoyverseItemsForMapping(): array
    {
        // Check if API credentials are configured
        if (! $this->loyverseApiService->hasCredentials()) {
            Log::warning('Loyverse API credentials not configured');

            return [];
        }

        try {
            $items = $this->loyverseApiService->getAllItems();

            $formattedItems = [];
            foreach ($items as $item) {
                $variants = $item['variants'] ?? [];

                foreach ($variants as $variant) {
                    // Build variant name if it exists
                    $variantName = ! empty($variant['variant_name']) ? " - {$variant['variant_name']}" : '';

                    $formattedItems[] = [
                        'id' => $item['id'],
                        'item_id' => $item['id'],
                        'variant_id' => $variant['variant_id'] ?? null,
                        'name' => $item['item_name'].$variantName,
                        'sku' => $variant['sku'] ?? null,
                        'price' => $variant['price'] ?? null,
                        'category' => $item['category_name'] ?? null,
                    ];
                }
            }

            return $formattedItems;

        } catch (\Exception $e) {
            Log::error('Failed to fetch Loyverse items for mapping', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get all existing mappings.
     */
    public function getAllMappings(): array
    {
        return ProductMapping::getAllActive()->toArray();
    }

    /**
     * Get unmapped products from recent orders.
     * This analyzes recent orders and returns products that don't have mappings.
     */
    public function getUnmappedProducts(int $limit = 100): array
    {
        // TODO: Implement this by querying Order model
        // Extract unique products from order_data JSON
        // Filter out already mapped products
        // Return list of unmapped products

        return [];
    }

    /**
     * Delete a mapping.
     */
    public function deleteMapping(int $mappingId): bool
    {
        $mapping = ProductMapping::find($mappingId);

        if (! $mapping) {
            return false;
        }

        return $mapping->delete();
    }

    /**
     * Deactivate a mapping.
     */
    public function deactivateMapping(int $mappingId): bool
    {
        $mapping = ProductMapping::find($mappingId);

        if (! $mapping) {
            return false;
        }

        return $mapping->deactivate();
    }

    /**
     * Activate a mapping.
     */
    public function activateMapping(int $mappingId): bool
    {
        $mapping = ProductMapping::find($mappingId);

        if (! $mapping) {
            return false;
        }

        return $mapping->activate();
    }

    /**
     * Bulk import mappings from CSV or array.
     */
    public function bulkImport(array $mappings): array
    {
        $results = [
            'imported' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($mappings as $index => $mapping) {
            try {
                // Validate required fields
                $platform = $mapping['platform'] ?? 'careem';
                $productId = $mapping['platform_product_id'] ?? $mapping['careem_product_id'] ?? null;
                $productName = $mapping['platform_name'] ?? $mapping['careem_name'] ?? null;

                if (empty($productId) || empty($mapping['loyverse_item_id']) || empty($productName)) {
                    $results['failed']++;
                    $results['errors'][] = "Row {$index}: Missing required fields";

                    continue;
                }

                ProductMapping::mapProduct(
                    $platform,
                    $productId,
                    $productName,
                    $mapping['loyverse_item_id'],
                    $mapping['platform_sku'] ?? $mapping['careem_sku'] ?? null,
                    $mapping['loyverse_variant_id'] ?? null,
                    $mapping['is_active'] ?? true
                );

                $results['imported']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Row {$index}: {$e->getMessage()}";
            }
        }

        return $results;
    }

    /**
     * Export all mappings to array (for CSV export).
     */
    public function exportMappings(): array
    {
        $mappings = ProductMapping::active()->get();

        return $mappings->map(function ($mapping) {
            return [
                'platform' => $mapping->platform,
                'platform_product_id' => $mapping->platform_product_id,
                'platform_sku' => $mapping->platform_sku,
                'platform_name' => $mapping->platform_name,
                'loyverse_item_id' => $mapping->loyverse_item_id,
                'loyverse_variant_id' => $mapping->loyverse_variant_id,
                'is_active' => $mapping->is_active ? 'yes' : 'no',
                'created_at' => $mapping->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    /**
     * Export mappings to CSV format.
     */
    public function exportToCsv(): string
    {
        $mappings = $this->exportMappings();

        $csv = "platform,platform_product_id,platform_sku,platform_name,loyverse_item_id,loyverse_variant_id,is_active,created_at\n";

        foreach ($mappings as $mapping) {
            $csv .= implode(',', [
                $this->escapeCsv($mapping['platform']),
                $this->escapeCsv($mapping['platform_product_id']),
                $this->escapeCsv($mapping['platform_sku'] ?? ''),
                $this->escapeCsv($mapping['platform_name']),
                $this->escapeCsv($mapping['loyverse_item_id']),
                $this->escapeCsv($mapping['loyverse_variant_id'] ?? ''),
                $mapping['is_active'],
                $mapping['created_at'],
            ])."\n";
        }

        return $csv;
    }

    /**
     * Import mappings from CSV file.
     */
    public function importFromCsv(string $filePath): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        if (! file_exists($filePath)) {
            $results['errors'][] = 'File not found';

            return $results;
        }

        $file = fopen($filePath, 'r');

        // Skip header row
        $header = fgetcsv($file);

        $rowNumber = 1;
        while (($row = fgetcsv($file)) !== false) {
            $rowNumber++;

            try {
                // Parse CSV row - support both old and new format
                $platform = $row[0] ?? 'careem';
                $productId = $row[1] ?? null;
                $sku = $row[2] ?? null;
                $productName = $row[3] ?? null;
                $loyverseItemId = $row[4] ?? null;
                $loyverseVariantId = $row[5] ?? null;

                // Validate required fields
                if (empty($productId) || empty($loyverseItemId) || empty($productName)) {
                    $results['skipped']++;
                    $results['errors'][] = "Row {$rowNumber}: Missing required fields";

                    continue;
                }

                // Check if mapping exists
                $existingMapping = ProductMapping::where('platform', $platform)
                    ->where('platform_product_id', $productId)
                    ->first();

                if ($existingMapping) {
                    // Update existing
                    $existingMapping->update([
                        'platform_sku' => $sku,
                        'platform_name' => $productName,
                        'loyverse_item_id' => $loyverseItemId,
                        'loyverse_variant_id' => $loyverseVariantId,
                    ]);
                    $results['updated']++;
                } else {
                    // Create new
                    ProductMapping::mapProduct(
                        $platform,
                        $productId,
                        $productName,
                        $loyverseItemId,
                        $sku,
                        $loyverseVariantId,
                        true
                    );
                    $results['created']++;
                }

            } catch (\Exception $e) {
                $results['skipped']++;
                $results['errors'][] = "Row {$rowNumber}: {$e->getMessage()}";
            }
        }

        fclose($file);

        // Clear cache after import
        $this->clearCache();

        return $results;
    }

    /**
     * Escape CSV value.
     */
    protected function escapeCsv(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        // Escape quotes and wrap in quotes if contains comma, quote, or newline
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }

    /**
     * Clear product mapping cache.
     */
    public function clearCache(): void
    {
        ProductMapping::clearAllCache();
    }
}
