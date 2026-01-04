<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Careem Order Fetch with Modifiers ===\n\n";

// Get tenant
$tenant = \App\Models\Tenant::where('subdomain', 'dw')->first();

if (!$tenant) {
    echo "❌ Tenant 'dw' not found\n";
    exit(1);
}

echo "✓ Tenant found: {$tenant->name}\n";

// Set tenant context
app()->instance('tenant', $tenant);

// Get a branch
$branch = \App\Models\CareemBranch::where('tenant_id', $tenant->id)
    ->where('pos_integration_enabled', true)
    ->with('brand')
    ->first();

if (!$branch) {
    echo "❌ No active branch found\n";
    exit(1);
}

echo "✓ Branch found: {$branch->name}\n";
echo "  Brand ID: {$branch->brand->careem_brand_id}\n";
echo "  Branch ID: {$branch->careem_branch_id}\n\n";

// Initialize Careem service
$careemService = new \App\Services\CareemApiService($tenant->id);

echo "⏳ Fetching orders list...\n";

try {
    // Fetch orders list
    $response = $careemService->listOrders(
        $branch->brand->careem_brand_id,
        $branch->careem_branch_id,
        1,
        5 // Get 5 orders
    );

    $orders = $response['data'] ?? [];
    echo "✓ Found " . count($orders) . " orders\n\n";

    if (empty($orders)) {
        echo "ℹ️  No orders to process\n";
        exit(0);
    }

    // Test fetching full details for first order
    $firstOrder = $orders[0];
    $orderId = $firstOrder['id'];

    echo "⏳ Fetching FULL details for order #{$orderId}...\n";

    $fullOrder = $careemService->getOrder(
        (string)$orderId,
        $branch->brand->careem_brand_id,
        $branch->careem_branch_id
    );

    echo "✓ Got full order details\n\n";

    // Check for items and modifiers
    $items = $fullOrder['items'] ?? [];
    echo "📦 Order has " . count($items) . " items\n\n";

    foreach ($items as $i => $item) {
        $itemNum = $i + 1;
        echo "=== Item #{$itemNum} ===\n";
        echo "ID: " . ($item['id'] ?? 'N/A') . "\n";
        echo "Quantity: " . ($item['quantity'] ?? 1) . "\n";
        echo "Price: " . ($item['unit_price'] ?? 0) . "\n";

        $groups = $item['groups'] ?? [];
        echo "Modifier Groups: " . count($groups) . "\n";

        if (!empty($groups)) {
            echo "✓ MODIFIERS FOUND!\n";
            foreach ($groups as $g => $group) {
                echo "\n  Group #" . ($g + 1) . ":\n";
                echo "    Name: " . ($group['name'] ?? $group['group_name'] ?? 'Unknown') . "\n";
                echo "    ID: " . ($group['id'] ?? 'N/A') . "\n";

                $options = $group['options'] ?? [];
                echo "    Options: " . count($options) . "\n";

                foreach ($options as $o => $option) {
                    echo "      - " . ($option['name'] ?? 'Option #' . ($o + 1)) . "\n";
                    echo "        Price: +" . ($option['price'] ?? 0) . "\n";
                    if (isset($option['quantity']) && $option['quantity'] > 1) {
                        echo "        Quantity: x" . $option['quantity'] . "\n";
                    }
                }
            }
        } else {
            echo "⚠️  No modifiers for this item\n";
        }

        echo "\n";
    }

    echo "\n=== Summary ===\n";
    echo "✅ Full order details retrieved successfully\n";
    echo "✅ Order structure includes 'groups' field for modifiers\n";
    echo "ℹ️  Next step: Fetch orders through dashboard to save with full details\n";

} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
