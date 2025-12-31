<?php

/**
 * Helper script to check modifiers in the database
 * This helps debug why items don't have modifier groups
 *
 * Run: php check_modifiers.php
 */

require __DIR__.'/vendor/autoload.php';

use App\Models\Tenant;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use App\Models\Modifier;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== Modifier Database Check ===\n\n";

// Get first tenant
$tenant = Tenant::first();
if (!$tenant) {
    echo "❌ No tenant found.\n";
    exit(1);
}

echo "✓ Tenant: {$tenant->name} (ID: {$tenant->id})\n\n";
app()->instance('tenant', $tenant);

// Check modifiers
$modifiers = Modifier::where('tenant_id', $tenant->id)->get();
echo "📋 Modifiers in database: {$modifiers->count()}\n";
if ($modifiers->count() > 0) {
    foreach ($modifiers->take(5) as $modifier) {
        echo "   - ID: {$modifier->id} | Name: {$modifier->name} | Price: {$modifier->price_adjustment}\n";
    }
    if ($modifiers->count() > 5) {
        echo "   ... and " . ($modifiers->count() - 5) . " more\n";
    }
}
echo "\n";

// Check modifier groups
$groups = ModifierGroup::where('tenant_id', $tenant->id)->get();
echo "📦 Modifier Groups in database: {$groups->count()}\n";
if ($groups->count() > 0) {
    foreach ($groups as $group) {
        $modifiersInGroup = $group->modifiers()->count();
        echo "   - ID: {$group->id} | Name: {$group->name} | Modifiers: {$modifiersInGroup}\n";
    }
}
echo "\n";

// Check menu items
$items = MenuItem::where('tenant_id', $tenant->id)->get();
echo "🍽️  Menu Items in database: {$items->count()}\n";
if ($items->count() > 0) {
    foreach ($items as $item) {
        $groupsOnItem = $item->modifierGroups()->count();
        echo "   - ID: {$item->id} | Name: {$item->name} | Modifier Groups: {$groupsOnItem}";

        if ($groupsOnItem > 0) {
            echo " (";
            $groupNames = $item->modifierGroups()->pluck('name')->toArray();
            echo implode(', ', $groupNames);
            echo ")";
        }
        echo "\n";
    }
}
echo "\n";

// Check the pivot table directly
$pivotTable = 'menu_item_modifier_group';
try {
    $pivotRecords = \DB::table($pivotTable)->count();
    echo "🔗 Pivot table '{$pivotTable}' records: {$pivotRecords}\n";

    if ($pivotRecords > 0) {
        $sampleRecords = \DB::table($pivotTable)->limit(5)->get();
        echo "   Sample relationships:\n";
        foreach ($sampleRecords as $record) {
            $item = MenuItem::find($record->menu_item_id);
            $group = ModifierGroup::find($record->modifier_group_id);
            echo "      - Item: '{$item->name}' <-> Group: '{$group->name}'\n";
        }
    } else {
        echo "   ⚠️  No relationships found! Items are not linked to modifier groups.\n";
    }
} catch (\Exception $e) {
    echo "❌ Error checking pivot table: {$e->getMessage()}\n";
}

echo "\n=== Solution ===\n\n";

if ($groups->count() == 0) {
    echo "You need to create modifier groups first:\n";
    echo "1. Go to dashboard -> Modifier Groups\n";
    echo "2. Create groups (e.g., 'Size', 'Extras', 'Toppings')\n";
    echo "3. Add modifiers to each group\n\n";
}

if ($items->count() > 0 && $groups->count() > 0) {
    $itemsWithGroups = $items->filter(fn($i) => $i->modifierGroups()->count() > 0);

    if ($itemsWithGroups->count() == 0) {
        echo "You have items and groups, but they're not linked:\n";
        echo "1. Edit each menu item\n";
        echo "2. Select which modifier groups apply to that item\n";
        echo "3. Save the item\n\n";

        echo "Quick fix example (attach first group to first item):\n";
        $firstItem = $items->first();
        $firstGroup = $groups->first();
        echo "Run this in tinker:\n";
        echo "  \$item = App\\Models\\MenuItem::find({$firstItem->id});\n";
        echo "  \$item->modifierGroups()->attach({$firstGroup->id});\n\n";
    } else {
        echo "✓ Some items already have modifier groups attached!\n";
        echo "  Items with groups: {$itemsWithGroups->count()} / {$items->count()}\n\n";
    }
}

echo "After linking items to modifier groups, run:\n";
echo "  php test_modifier_structure.php\n\n";
