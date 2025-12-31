<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Transforms Laravel menu structure to Careem catalog format
 *
 * Note: This transformer uses a common catalog structure.
 * Adjust based on actual Careem API documentation.
 */
class CareemMenuTransformer
{
    /**
     * Transform menu to Careem catalog format
     *
     * @param  Menu  $menu  Menu model with loaded relationships
     * @param  string  $catalogId  The catalog ID for this menu
     * @return array Catalog structure ready for API submission
     */
    public function transform(Menu $menu, string $catalogId): array
    {
        Log::info('Starting Careem menu transformation', [
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'catalog_id' => $catalogId,
        ]);

        $menu->load(['items.modifierGroups.modifiers', 'locations']);

        // Transform to Careem's CreateFullCatalogRequest structure
        $categories = $this->transformCategories($menu);
        $items = $this->transformItems($menu);
        $modifierGroups = $this->transformModifierGroups($menu);
        $options = $this->extractOptions($menu); // Pass menu instead of transformed groups

        // Calculate average price from items
        $avgPrice = $items ? array_sum(array_column($items, 'price')) / count($items) : 0;

        // Log transformation statistics
        Log::info('Careem menu transformation completed', [
            'menu_id' => $menu->id,
            'catalog_id' => $catalogId,
            'categories_count' => count($categories),
            'items_count' => count($items),
            'modifier_groups_count' => count($modifierGroups),
            'options_count' => count($options),
            'avg_price' => round($avgPrice, 2),
        ]);

        // Validate for duplicate IDs
        $this->validateUniqueIds($categories, $items, $modifierGroups, $options, $catalogId);

        return [
            'diff' => false, // Full catalog sync
            'catalog' => [
                'id' => $catalogId, // Required by Careem
                'name' => $menu->name,
                'include_tax' => true, // Prices include tax (required by Careem)
                'tax' => 5.0, // Default VAT for UAE (adjust based on location)
                'avg_price' => round($avgPrice, 2),
                'file' => $menu->image_url ? $this->getFullImageUrl($menu->image_url) : null,
                'currency_id' => 1, // 1 = AED for UAE (required by Careem)
                'category_ids' => array_column($categories, 'id'), // Required by Careem
            ],
            'categories' => $categories,
            'sub_categories' => [], // Not used currently
            'items' => $items,
            'groups' => $modifierGroups, // Modifier groups
            'options' => $options, // Individual modifiers
        ];
    }

    /**
     * Transform categories
     */
    protected function transformCategories(Menu $menu): array
    {
        $categories = [];
        $seenIds = [];
        $categoryGroups = $menu->items->groupBy('category');

        Log::debug('Transforming categories', [
            'menu_id' => $menu->id,
            'category_count' => $categoryGroups->count(),
        ]);

        foreach ($categoryGroups as $categoryName => $items) {
            $categoryId = Str::slug($categoryName ?: 'general');

            // Ensure unique category IDs
            if (!in_array($categoryId, $seenIds)) {
                $categories[] = [
                    'id' => $categoryId,
                    'name' => $categoryName ?: 'General',
                    'items' => $items->pluck('id')->map(fn($id) => (string) $id)->toArray(), // Careem requires items array
                ];
                $seenIds[] = $categoryId;
            } else {
                Log::warning('Duplicate category ID detected and skipped', [
                    'category_id' => $categoryId,
                    'category_name' => $categoryName,
                ]);
            }
        }

        return $categories;
    }

    /**
     * Transform menu items
     */
    protected function transformItems(Menu $menu): array
    {
        Log::debug('Transforming menu items', [
            'menu_id' => $menu->id,
            'items_count' => $menu->items->count(),
        ]);

        return $menu->items->map(function ($item) {
            // Use Careem's field names: active, priority, upc, media
            $transformed = [
                'id' => (string) $item->id,
                'name' => $item->name,
                'description' => $item->description ?? '',
                'price' => (float) $item->price,
                'active' => (bool) $item->is_active,
                'priority' => $item->sort_order ?? 0,
                'upc' => $item->sku ?? '',
            ];

            // Add image if exists (Careem uses 'media' not 'image_url')
            if ($item->image_url) {
                $transformed['media'] = $this->getFullImageUrl($item->image_url);
            }

            // Add modifier groups if item has them (Careem uses 'groups' not 'modifier_group_ids')
            if ($item->modifierGroups->isNotEmpty()) {
                $transformed['groups'] = $item->modifierGroups->pluck('id')->map(fn($id) => (string) $id)->toArray();
            }

            return $transformed;
        })->toArray();
    }

    /**
     * Transform modifier groups to match Careem's format
     * Careem expects: multi_select, min, max, priority, options (array of IDs)
     */
    protected function transformModifierGroups(Menu $menu): array
    {
        // Get all unique modifier groups from all items
        $modifierGroups = $menu->items->pluck('modifierGroups')->flatten()->unique('id');

        Log::debug('Transforming modifier groups', [
            'menu_id' => $menu->id,
            'modifier_groups_count' => $modifierGroups->count(),
        ]);

        return $modifierGroups->map(function ($group) {
            return [
                'id' => (string) $group->id,
                'name' => $group->name,
                'description' => $group->description ?? '',
                // Careem uses multi_select (boolean) instead of selection_type
                'multi_select' => $group->selection_type === 'multiple' || ($group->max_selections ?? 1) > 1,
                // Careem uses min/max instead of min_selections/max_selections
                'min' => (int) ($group->min_selections ?? 0),
                'max' => (int) ($group->max_selections ?? 1),
                // Careem uses priority instead of sort_order
                'priority' => $group->sort_order ?? 0,
                // Careem expects options as array of IDs (not nested objects)
                'options' => $group->modifiers->pluck('id')->map(fn($id) => (string) $id)->toArray(),
            ];
        })->toArray();
    }

    /**
     * Extract all options (modifiers) from menu items
     * Careem expects flat options array with: active, price, priority
     * Ensures unique modifier IDs to prevent duplicate ID errors
     */
    protected function extractOptions(Menu $menu): array
    {
        $options = [];
        $seenIds = [];
        $totalModifiers = 0;
        $duplicatesSkipped = 0;

        // Get all modifier groups from all items
        $modifierGroups = $menu->items->pluck('modifierGroups')->flatten()->unique('id');

        foreach ($modifierGroups as $group) {
            foreach ($group->modifiers as $modifier) {
                $totalModifiers++;
                $modifierId = (string) $modifier->id;

                // Only add modifier if we haven't seen this ID before
                if (!in_array($modifierId, $seenIds)) {
                    $options[] = [
                        'id' => $modifierId,
                        'name' => $modifier->name,
                        'description' => $modifier->description ?? '',
                        // Careem uses 'active' not 'is_active'
                        'active' => (bool) $modifier->is_active,
                        // Careem uses 'price' not 'price_adjustment'
                        'price' => (float) $modifier->price_adjustment,
                        // Careem uses 'priority' not 'sort_order'
                        'priority' => $modifier->pivot->sort_order ?? 0,
                        // TODO: Add nested 'groups' array if modifier has sub-modifiers
                        // 'groups' => [...]
                    ];
                    $seenIds[] = $modifierId;
                } else {
                    $duplicatesSkipped++;
                    Log::debug('Duplicate modifier ID skipped in options', [
                        'modifier_id' => $modifierId,
                        'modifier_name' => $modifier->name,
                    ]);
                }
            }
        }

        if ($duplicatesSkipped > 0) {
            Log::info('Duplicate modifiers detected and removed from options', [
                'total_modifiers' => $totalModifiers,
                'unique_modifiers' => count($options),
                'duplicates_removed' => $duplicatesSkipped,
            ]);
        }

        return $options;
    }

    /**
     * Validate all IDs in the payload are unique
     */
    protected function validateUniqueIds(array $categories, array $items, array $groups, array $options, string $catalogId): void
    {
        $allIds = [
            'categories' => array_column($categories, 'id'),
            'items' => array_column($items, 'id'),
            'groups' => array_column($groups, 'id'),
            'options' => array_column($options, 'id'),
        ];

        $totalIds = 0;
        $duplicates = [];

        foreach ($allIds as $type => $ids) {
            $totalIds += count($ids);
            $uniqueIds = array_unique($ids);

            if (count($ids) !== count($uniqueIds)) {
                $duplicateIds = array_diff_assoc($ids, $uniqueIds);
                $duplicates[$type] = array_values($duplicateIds);
            }
        }

        if (!empty($duplicates)) {
            Log::error('Duplicate IDs detected in Careem payload', [
                'catalog_id' => $catalogId,
                'duplicates' => $duplicates,
                'all_ids_count' => $totalIds,
            ]);
        } else {
            Log::debug('All IDs in payload are unique', [
                'catalog_id' => $catalogId,
                'total_ids' => $totalIds,
                'breakdown' => array_map('count', $allIds),
            ]);
        }
    }

    /**
     * Get full image URL from storage path
     */
    protected function getFullImageUrl(string $path): string
    {
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $cdnUrl = config('platforms.image_settings.cdn_url');

        return rtrim($cdnUrl, '/').'/'.ltrim($path, '/');
    }

    /**
     * Transform Location opening hours to Careem operational hours format
     *
     * @param array|null $openingHours Location's opening_hours array
     * @return array Careem operational_hours format
     *
     * Example input format:
     * [
     *   'monday' => ['open' => '11:00', 'close' => '23:00'],
     *   'tuesday' => ['open' => '11:00', 'close' => '02:00'], // closes at 2 AM next day
     * ]
     *
     * Example output format (Careem actual API):
     * [
     *   {
     *     "day_of_week": 1,  // 1=Monday, 7=Sunday
     *     "active": true,
     *     "shifts": [
     *       {"start_time": "11:00", "end_time": "23:00"}
     *     ]
     *   }
     * ]
     */
    public function transformOperationalHours(?array $openingHours): array
    {
        if (empty($openingHours)) {
            Log::info('No opening hours provided, returning default 24/7 operational hours');
            return $this->getDefault24HoursSchedule();
        }

        $operationalHours = [];
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        // Track shifts that span to next day for proper grouping
        $nextDayShifts = [];

        Log::info('Transforming opening hours to Careem operational hours format', [
            'opening_hours_keys' => array_keys($openingHours),
        ]);

        foreach ($daysOfWeek as $index => $day) {
            $dayOfWeek = $index + 1; // 1-7 (Monday=1, Sunday=7)
            $dayHours = $openingHours[$day] ?? null;

            // Check if there's a shift from previous day
            $shifts = [];
            if (isset($nextDayShifts[$dayOfWeek])) {
                $shifts[] = $nextDayShifts[$dayOfWeek];
                unset($nextDayShifts[$dayOfWeek]);
            }

            if ($dayHours && isset($dayHours['open'], $dayHours['close'])) {
                $openTime = $dayHours['open'];
                $closeTime = $dayHours['close'];

                // Check if closing time spans past midnight
                $closeTimeInt = (int) str_replace(':', '', $closeTime);
                $openTimeInt = (int) str_replace(':', '', $openTime);

                if ($closeTimeInt < 600 && $openTimeInt > 600) {
                    // Spans past midnight - add shift for current day ending at 23:59
                    $shifts[] = [
                        'start_time' => $openTime,
                        'end_time' => '23:59',
                    ];

                    // Store shift for next day starting at 00:00
                    $nextDayOfWeek = ($dayOfWeek % 7) + 1;
                    $nextDayShifts[$nextDayOfWeek] = [
                        'start_time' => '00:00',
                        'end_time' => $closeTime,
                    ];

                    Log::debug("Split overnight shift for day {$dayOfWeek}", [
                        'shift1' => "{$openTime}-23:59",
                        'shift2_next_day' => "00:00-{$closeTime}",
                    ]);
                } else {
                    // Normal same-day operation
                    $shifts[] = [
                        'start_time' => $openTime,
                        'end_time' => $closeTime,
                    ];
                }
            }

            // Add entry for this day (even if no shifts, mark as inactive)
            if (!empty($shifts)) {
                $operationalHours[] = [
                    'day_of_week' => $dayOfWeek,
                    'active' => true,
                    'shifts' => $shifts,
                ];

                Log::debug("Added operational hours for day {$dayOfWeek} ({$day})", [
                    'shifts_count' => count($shifts),
                    'shifts' => $shifts,
                ]);
            } else {
                $operationalHours[] = [
                    'day_of_week' => $dayOfWeek,
                    'active' => false,
                    'shifts' => [],
                ];

                Log::debug("Day {$dayOfWeek} ({$day}) marked as inactive (closed)");
            }
        }

        Log::info('Operational hours transformation completed', [
            'total_days' => count($operationalHours),
            'active_days' => count(array_filter($operationalHours, fn($d) => $d['active'])),
        ]);

        return $operationalHours;
    }

    /**
     * Get default 24/7 operational hours
     */
    protected function getDefault24HoursSchedule(): array
    {
        $schedule = [];

        for ($dayOfWeek = 1; $dayOfWeek <= 7; $dayOfWeek++) {
            $schedule[] = [
                'day_of_week' => $dayOfWeek,
                'active' => true,
                'shifts' => [
                    [
                        'start_time' => '00:00',
                        'end_time' => '23:59',
                    ],
                ],
            ];
        }

        return $schedule;
    }
}
