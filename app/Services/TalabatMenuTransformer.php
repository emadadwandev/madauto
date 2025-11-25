<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use App\Models\Modifier;
use Illuminate\Support\Str;

/**
 * Transforms Laravel menu structure to Talabat (Delivery Hero) catalog format
 *
 * Delivery Hero uses a flat dictionary structure with item types:
 * - Product, Variant, Category, Topping, Menu, ScheduleEntry, Image
 */
class TalabatMenuTransformer
{
    /**
     * Transform menu to Talabat catalog format
     *
     * @param Menu $menu Menu model with loaded relationships
     * @return array Catalog structure ready for API submission
     */
    public function transform(Menu $menu): array
    {
        $menu->load(['items.modifierGroups.modifiers', 'locations']);

        $catalogItems = [];

        // 1. Create Categories
        $categories = $this->createCategories($menu, $catalogItems);

        // 2. Create Products (Menu Items)
        $this->createProducts($menu, $catalogItems, $categories);

        // 3. Create Toppings (Modifier Groups)
        $this->createToppings($menu, $catalogItems);

        // 4. Create Menu (Service Period)
        $this->createMenu($menu, $catalogItems, $categories);

        // 5. Create Schedule
        $this->createSchedule($catalogItems);

        // 6. Create Images
        $this->createImages($menu, $catalogItems);

        return [
            'items' => $catalogItems,
        ];
    }

    /**
     * Create category items
     */
    protected function createCategories(Menu $menu, array &$catalogItems): array
    {
        $categories = [];
        $categoryGroups = $menu->items->groupBy('category');

        foreach ($categoryGroups as $categoryName => $items) {
            $categoryId = $this->generateId('cat', $categoryName);

            $catalogItems[$categoryId] = [
                'id' => $categoryId,
                'type' => 'Category',
                'title' => $categoryName ?: 'General',
                'description' => "Items in {$categoryName} category",
                'products' => $items->pluck('id')->map(fn($id) => $this->generateId('prod', $id))->toArray(),
            ];

            $categories[$categoryName] = $categoryId;
        }

        // Ensure at least one category exists (Delivery Hero requirement)
        if (empty($categories)) {
            $categoryId = $this->generateId('cat', 'default');
            $catalogItems[$categoryId] = [
                'id' => $categoryId,
                'type' => 'Category',
                'title' => 'All Items',
                'description' => 'All menu items',
                'products' => [],
            ];
            $categories['default'] = $categoryId;
        }

        return $categories;
    }

    /**
     * Create product items
     */
    protected function createProducts(Menu $menu, array &$catalogItems, array $categories): void
    {
        foreach ($menu->items as $item) {
            $productId = $this->generateId('prod', $item->id);

            $product = [
                'id' => $productId,
                'type' => 'Product',
                'title' => $item->name,
                'description' => $item->description ?? '',
                'price' => (float) $item->price,
            ];

            // Add image reference if exists
            if ($item->image_url) {
                $imageId = $this->generateId('img', $item->id);
                $product['images'] = [$imageId];
            }

            // Add toppings (modifier groups)
            if ($item->modifierGroups->isNotEmpty()) {
                $product['toppings'] = $item->modifierGroups->map(function ($group) {
                    return $this->generateId('topping', $group->id);
                })->toArray();
            }

            // Add tags
            $product['tags'] = [];
            if (!$item->is_available) {
                $product['tags'][] = ['key' => 'outOfStock', 'value' => 'true'];
            }

            $catalogItems[$productId] = $product;
        }
    }

    /**
     * Create topping items (modifier groups)
     */
    protected function createToppings(Menu $menu, array &$catalogItems): void
    {
        // Get all unique modifier groups from all items
        $modifierGroups = $menu->items->pluck('modifierGroups')->flatten()->unique('id');

        foreach ($modifierGroups as $group) {
            $toppingId = $this->generateId('topping', $group->id);

            $topping = [
                'id' => $toppingId,
                'type' => 'Topping',
                'toppingType' => $group->selection_type === 'single' ? 'PRODUCT_OPTION' : 'PANDORA_PRODUCT_GROUP',
                'title' => $group->name,
                'description' => $group->description ?? '',
                'quantity' => [
                    'min' => $group->min_selections ?? 0,
                    'max' => $group->max_selections ?? ($group->selection_type === 'single' ? 1 : 10),
                ],
                'products' => [],
            ];

            // Add modifiers as products within the topping
            foreach ($group->modifiers as $modifier) {
                $modifierId = $this->generateId('modifier', $modifier->id);

                // Create modifier product
                $catalogItems[$modifierId] = [
                    'id' => $modifierId,
                    'type' => 'Product',
                    'title' => $modifier->name,
                    'description' => $modifier->description ?? '',
                    'price' => (float) abs($modifier->price_adjustment),
                ];

                // Reference modifier in topping
                $topping['products'][$modifierId] = [
                    'price' => (float) $modifier->price_adjustment,
                ];
            }

            $catalogItems[$toppingId] = $topping;
        }
    }

    /**
     * Create menu item (service period)
     */
    protected function createMenu(Menu $menu, array &$catalogItems, array $categories): void
    {
        $menuId = $this->generateId('menu', $menu->id);
        $scheduleId = $this->generateId('schedule', $menu->id);

        $menuItem = [
            'id' => $menuId,
            'type' => 'Menu',
            'title' => $menu->name,
            'menuType' => 'DELIVERY',
            'products' => array_values($categories),  // Reference categories
            'schedule' => $scheduleId,
        ];

        $catalogItems[$menuId] = $menuItem;
    }

    /**
     * Create schedule entry (24/7 by default)
     */
    protected function createSchedule(array &$catalogItems): void
    {
        $scheduleId = $this->generateId('schedule', 'default');

        $catalogItems[$scheduleId] = [
            'id' => $scheduleId,
            'type' => 'ScheduleEntry',
            'startTime' => '00:00:00',
            'endTime' => '23:59:59',
            'weekDays' => ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'],
        ];
    }

    /**
     * Create image items
     */
    protected function createImages(Menu $menu, array &$catalogItems): void
    {
        // Menu image
        if ($menu->image_url) {
            $imageId = $this->generateId('img', 'menu_' . $menu->id);
            $catalogItems[$imageId] = [
                'id' => $imageId,
                'type' => 'Image',
                'url' => $this->getFullImageUrl($menu->image_url),
            ];
        }

        // Item images
        foreach ($menu->items as $item) {
            if ($item->image_url) {
                $imageId = $this->generateId('img', $item->id);
                $catalogItems[$imageId] = [
                    'id' => $imageId,
                    'type' => 'Image',
                    'url' => $this->getFullImageUrl($item->image_url),
                ];
            }
        }
    }

    /**
     * Generate consistent IDs for catalog items
     */
    protected function generateId(string $type, mixed $identifier): string
    {
        if (is_string($identifier)) {
            $identifier = Str::slug($identifier);
        }

        return "{$type}_{$identifier}";
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

        return rtrim($cdnUrl, '/') . '/' . ltrim($path, '/');
    }
}
