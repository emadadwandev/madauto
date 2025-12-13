<?php

namespace App\Services;

use App\Models\Menu;
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
     * @return array Catalog structure ready for API submission
     */
    public function transform(Menu $menu): array
    {
        $menu->load(['items.modifierGroups.modifiers', 'locations']);

        return [
            'name' => $menu->name,
            'description' => $menu->description,
            'status' => $menu->is_active ? 'active' : 'inactive',
            'image_url' => $menu->image_url ? $this->getFullImageUrl($menu->image_url) : null,
            'categories' => $this->transformCategories($menu),
            'items' => $this->transformItems($menu),
            'modifier_groups' => $this->transformModifierGroups($menu),
        ];
    }

    /**
     * Transform categories
     */
    protected function transformCategories(Menu $menu): array
    {
        $categories = [];
        $categoryGroups = $menu->items->groupBy('category');

        foreach ($categoryGroups as $categoryName => $items) {
            $categories[] = [
                'id' => Str::slug($categoryName ?: 'general'),
                'name' => $categoryName ?: 'General',
                'description' => "Items in {$categoryName} category",
                'sort_order' => count($categories),
                'item_ids' => $items->pluck('id')->toArray(),
            ];
        }

        return $categories;
    }

    /**
     * Transform menu items
     */
    protected function transformItems(Menu $menu): array
    {
        return $menu->items->map(function ($item) {
            $transformed = [
                'id' => (string) $item->id,
                'name' => $item->name,
                'description' => $item->description ?? '',
                'price' => (float) $item->price,
                'category' => $item->category ?? 'general',
                'sku' => $item->sku,
                'sort_order' => $item->sort_order ?? 0,
                'is_available' => (bool) $item->is_available,
                'is_active' => (bool) $item->is_active,
            ];

            // Add image if exists
            if ($item->image_url) {
                $transformed['image_url'] = $this->getFullImageUrl($item->image_url);
            }

            // Add tax rate if applicable
            if ($item->tax_rate > 0) {
                $transformed['tax_rate'] = (float) $item->tax_rate;
            }

            // Add modifier groups
            if ($item->modifierGroups->isNotEmpty()) {
                $transformed['modifier_group_ids'] = $item->modifierGroups->pluck('id')->toArray();
            }

            // Add Loyverse mapping if available
            if ($item->loyverse_item_id) {
                $transformed['external_id'] = $item->loyverse_item_id;
            }

            return $transformed;
        })->toArray();
    }

    /**
     * Transform modifier groups
     */
    protected function transformModifierGroups(Menu $menu): array
    {
        // Get all unique modifier groups from all items
        $modifierGroups = $menu->items->pluck('modifierGroups')->flatten()->unique('id');

        return $modifierGroups->map(function ($group) {
            return [
                'id' => (string) $group->id,
                'name' => $group->name,
                'description' => $group->description ?? '',
                'selection_type' => $group->selection_type, // 'single' or 'multiple'
                'is_required' => (bool) $group->is_required,
                'min_selections' => (int) ($group->min_selections ?? 0),
                'max_selections' => (int) ($group->max_selections ?? 10),
                'sort_order' => $group->sort_order ?? 0,
                'modifiers' => $this->transformModifiers($group),
            ];
        })->toArray();
    }

    /**
     * Transform modifiers within a group
     */
    protected function transformModifiers($modifierGroup): array
    {
        return $modifierGroup->modifiers->map(function ($modifier) {
            return [
                'id' => (string) $modifier->id,
                'name' => $modifier->name,
                'description' => $modifier->description ?? '',
                'price_adjustment' => (float) $modifier->price_adjustment,
                'sku' => $modifier->sku,
                'is_active' => (bool) $modifier->is_active,
                'is_available' => (bool) $modifier->is_available,
                'sort_order' => $modifier->pivot->sort_order ?? 0,
                'is_default' => (bool) ($modifier->pivot->is_default ?? false),
            ];
        })->toArray();
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
}
