<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OrderModifierEnrichmentService
{
    /**
     * Enrich order items with modifier names and details from database.
     *
     * Careem API returns orders with modifier group IDs and option IDs only.
     * This service looks up the actual names and prices from our database.
     */
    public function enrichOrderData(array $orderData, string $tenantId): array
    {
        if (empty($orderData['items'])) {
            return $orderData;
        }

        foreach ($orderData['items'] as $itemIndex => &$item) {
            if (empty($item['groups'])) {
                continue;
            }

            $enrichedModifiers = [];

            foreach ($item['groups'] as $group) {
                $groupId = $group['id'] ?? null;
                if (! $groupId) {
                    continue;
                }

                // Lookup group name
                $groupData = DB::table('modifier_groups')
                    ->where('id', $groupId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                $enrichedGroup = [
                    'id' => $groupId,
                    'name' => $groupData->name ?? "Group #{$groupId}",
                    'options' => [],
                ];

                // Process options
                $options = $group['options'] ?? [];
                foreach ($options as $option) {
                    $optionId = $option['id'] ?? null;
                    if (! $optionId) {
                        continue;
                    }

                    // Lookup option name and price
                    $optionData = DB::table('modifiers')
                        ->where('id', $optionId)
                        ->where('tenant_id', $tenantId)
                        ->first();

                    $enrichedOption = [
                        'id' => $optionId,
                        'name' => $optionData->name ?? "Option #{$optionId}",
                        'price' => $optionData->price_adjustment ?? 0,
                        'quantity' => $option['quantity'] ?? 1,
                        'total_price' => $option['total_price'] ?? 0,
                    ];

                    $enrichedGroup['options'][] = $enrichedOption;

                    // Also add to flat modifiers array for Loyverse
                    $enrichedModifiers[] = [
                        'group_name' => $enrichedGroup['name'],
                        'name' => $enrichedOption['name'],
                        'price' => $enrichedOption['price'],
                        'quantity' => $enrichedOption['quantity'],
                    ];
                }

                $enrichedGroup['options'] = $enrichedGroup['options'];
                $item['groups'][$groupId] = $enrichedGroup;
            }

            // Add flat modifiers array for easier consumption
            $item['modifiers'] = $enrichedModifiers;
        }

        return $orderData;
    }

    /**
     * Extract modifiers from enriched order item for display.
     */
    public function extractModifiersForDisplay(array $item): array
    {
        return $item['modifiers'] ?? [];
    }

    /**
     * Extract modifiers formatted for Loyverse line notes.
     */
    public function formatModifiersForLoyverse(array $item): string
    {
        $modifiers = $item['modifiers'] ?? [];
        if (empty($modifiers)) {
            return '';
        }

        $notes = [];
        foreach ($modifiers as $modifier) {
            $name = $modifier['name'];
            $price = $modifier['price'] ?? 0;
            $priceStr = $price > 0 ? " (+{$price})" : '';
            $notes[] = "{$name}{$priceStr}";
        }

        return implode(', ', $notes);
    }
}
