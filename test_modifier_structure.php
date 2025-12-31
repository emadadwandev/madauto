<?php

/**
 * Test script to verify modifier/group structure matches Careem's format
 *
 * Run: php test_modifier_structure.php
 *
 * This will show the transformed payload structure for modifiers/groups/options
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use App\Models\Menu;
use App\Services\CareemMenuTransformer;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== Careem Modifier Structure Test ===\n\n";

// Get first tenant
$tenant = Tenant::first();
if (!$tenant) {
    echo "❌ No tenant found. Create a tenant first.\n";
    exit(1);
}

echo "✓ Using tenant: {$tenant->name} (ID: {$tenant->id})\n";

// Set tenant context
app()->instance('tenant', $tenant);

// First, let's check what menus are available
$allMenus = Menu::where('tenant_id', $tenant->id)->get();
echo "  Available menus: {$allMenus->count()}\n";

// Get first menu with modifiers (try with and without strict requirement)
$menu = Menu::with(['items.modifierGroups.modifiers'])
    ->where('tenant_id', $tenant->id)
    ->whereHas('items.modifierGroups') // Check for items with ANY modifier groups
    ->first();

if (!$menu) {
    // Fallback to any menu
    echo "⚠️  No menu with modifier groups found, trying first available menu...\n";
    $menu = Menu::with(['items.modifierGroups.modifiers'])
        ->where('tenant_id', $tenant->id)
        ->first();

    if (!$menu) {
        echo "❌ No menu found at all. Create a menu first.\n";
        exit(1);
    }
}

echo "✓ Using menu: {$menu->name} (ID: {$menu->id})\n";
echo "  Items: {$menu->items->count()}\n";

// Debug: Check modifier groups relationship
$itemsWithGroups = $menu->items->filter(fn($item) => $item->modifierGroups->isNotEmpty());
echo "  Items with modifier groups: " . $itemsWithGroups->count() . "\n";

if ($itemsWithGroups->count() > 0) {
    $sampleItem = $itemsWithGroups->first();
    echo "  Sample item: '{$sampleItem->name}' has {$sampleItem->modifierGroups->count()} modifier groups\n";

    foreach ($sampleItem->modifierGroups as $group) {
        echo "    - Group: '{$group->name}' with {$group->modifiers->count()} modifiers\n";
    }
}

echo "\n";

// Transform menu
$transformer = new CareemMenuTransformer();
$catalogId = 'test-catalog-' . time();
$payload = $transformer->transform($menu, $catalogId);

echo "=== Transformed Structure ===\n\n";
echo "Summary:\n";
echo "  - Categories: " . count($payload['categories']) . "\n";
echo "  - Items: " . count($payload['items']) . "\n";
echo "  - Groups: " . count($payload['groups']) . "\n";
echo "  - Options: " . count($payload['options']) . "\n\n";

// Debug: Check all items to see which have groups
$itemsWithGroupsInPayload = array_filter($payload['items'], fn($item) => isset($item['groups']) && !empty($item['groups']));
echo "  Items with 'groups' field in payload: " . count($itemsWithGroupsInPayload) . "\n\n";

// Show first item with modifiers
$itemWithModifiers = collect($payload['items'])->first(fn($item) => isset($item['groups']));
if ($itemWithModifiers) {
    echo "📦 Sample Item with Modifiers:\n";
    echo json_encode($itemWithModifiers, JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "⚠️  No items with modifiers found in transformed payload\n\n";
}

// Show first modifier group
if (!empty($payload['groups'])) {
    echo "🔧 Sample Modifier Group:\n";
    echo json_encode($payload['groups'][0], JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "⚠️  No modifier groups found in transformed payload\n\n";
}

// Show first option
if (!empty($payload['options'])) {
    echo "⚙️  Sample Option (Modifier):\n";
    echo json_encode($payload['options'][0], JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "⚠️  No options found in transformed payload\n\n";
}

// Validation checks
echo "=== Structure Validation ===\n\n";

$issues = [];

// Check items have 'groups' not 'modifier_group_ids'
foreach ($payload['items'] as $item) {
    if (isset($item['modifier_group_ids'])) {
        $issues[] = "❌ Item '{$item['name']}' uses 'modifier_group_ids' (should be 'groups')";
    }
    if (isset($item['groups']) && is_array($item['groups'])) {
        echo "✓ Item '{$item['name']}' has 'groups' field (array of IDs)\n";
        break; // Just check first one
    }
}

// Check groups have correct fields
if (!empty($payload['groups'])) {
    $group = $payload['groups'][0];

    $requiredFields = ['multi_select', 'min', 'max', 'priority', 'options'];
    $wrongFields = ['min_selections', 'max_selections', 'selection_type', 'modifiers'];

    foreach ($requiredFields as $field) {
        if (!isset($group[$field])) {
            $issues[] = "❌ Group missing required field: '$field'";
        }
    }

    foreach ($wrongFields as $field) {
        if (isset($group[$field])) {
            $issues[] = "❌ Group has wrong field: '$field' (should be removed)";
        }
    }

    if (isset($group['options']) && is_array($group['options'])) {
        echo "✓ Groups have 'options' as array of IDs\n";
    }
}

// Check options have correct fields
if (!empty($payload['options'])) {
    $option = $payload['options'][0];

    $requiredFields = ['active', 'price', 'priority'];
    $wrongFields = ['is_active', 'price_adjustment', 'sort_order'];

    foreach ($requiredFields as $field) {
        if (!isset($option[$field])) {
            $issues[] = "❌ Option missing required field: '$field'";
        }
    }

    foreach ($wrongFields as $field) {
        if (isset($option[$field])) {
            $issues[] = "❌ Option has wrong field: '$field' (should be removed)";
        }
    }

    if (isset($option['price'])) {
        echo "✓ Options use 'price' field (not 'price_adjustment')\n";
    }
    if (isset($option['active'])) {
        echo "✓ Options use 'active' field (not 'is_active')\n";
    }
}

echo "\n";

if (empty($issues)) {
    echo "✅ All validation checks passed!\n";
    echo "\n📋 Summary:\n";
    echo "   - Items: " . count($payload['items']) . "\n";
    echo "   - Groups: " . count($payload['groups']) . "\n";
    echo "   - Options: " . count($payload['options']) . "\n";
} else {
    echo "⚠️  Issues found:\n";
    foreach ($issues as $issue) {
        echo "   $issue\n";
    }
}

echo "\n=== Full Payload (for debugging) ===\n";
echo "Catalog ID: {$payload['catalog']['id']}\n";
echo "Categories: " . count($payload['categories']) . "\n";
echo "Items: " . count($payload['items']) . "\n";
echo "Groups: " . count($payload['groups']) . "\n";
echo "Options: " . count($payload['options']) . "\n\n";

// Show full JSON structure (truncated for readability)
echo "Full JSON preview:\n";
$json = json_encode($payload, JSON_PRETTY_PRINT);
$lines = explode("\n", $json);
if (count($lines) > 100) {
    echo implode("\n", array_slice($lines, 0, 100)) . "\n... (truncated, total " . count($lines) . " lines)\n";
} else {
    echo $json . "\n";
}

echo "\n✓ Test complete\n\n";
