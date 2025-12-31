<?php

/**
 * Test Careem Order Acceptance Implementation
 *
 * This script tests the new order acceptance functionality
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Services\CareemApiService;

echo "=== CAREEM ORDER ACCEPTANCE TEST ===\n\n";

// Get tenant
echo "Enter tenant subdomain: ";
$subdomain = trim(fgets(STDIN));

$tenant = Tenant::where('subdomain', $subdomain)->first();

if (!$tenant) {
    echo "❌ Tenant not found\n";
    exit(1);
}

echo "✅ Found tenant: {$tenant->name}\n\n";

// Check configuration
echo "📋 TENANT CONFIGURATION:\n";
echo str_repeat("-", 80) . "\n";

$autoAccept = $tenant->getSetting('auto_accept_careem', false);
$autoMarkReady = $tenant->getSetting('auto_mark_ready_careem', false);

echo "Auto-accept Careem orders: " . ($autoAccept ? '✅ Enabled' : '❌ Disabled') . "\n";
echo "Auto-mark-ready Careem orders: " . ($autoMarkReady ? '✅ Enabled' : '❌ Disabled') . "\n\n";

// Check Careem branch
echo "🏪 CAREEM BRANCHES:\n";
echo str_repeat("-", 80) . "\n";

$branches = $tenant->careemBranches()->get();

if ($branches->isEmpty()) {
    echo "⚠️  No Careem branches configured\n";
    echo "   Please configure a branch in Dashboard → Careem Branches\n\n";
} else {
    foreach ($branches as $branch) {
        $posEnabled = $branch->pos_integration_enabled ? '✅' : '❌';
        echo "{$posEnabled} {$branch->name}\n";
        echo "   Brand ID: {$branch->careem_brand_id}\n";
        echo "   Branch ID: {$branch->careem_branch_id}\n";
        echo "   POS Integration: " . ($branch->pos_integration_enabled ? 'Enabled' : 'Disabled') . "\n";
        echo "   State: {$branch->state}\n\n";
    }
}

// Check API credentials
echo "🔑 API CREDENTIALS:\n";
echo str_repeat("-", 80) . "\n";

$credentials = \App\Models\ApiCredential::where('tenant_id', $tenant->id)
    ->where('service', 'careem_catalog')
    ->where('is_active', true)
    ->get();

if ($credentials->isEmpty()) {
    echo "❌ No Careem API credentials configured\n\n";
} else {
    echo "✅ Careem API credentials found:\n";
    foreach ($credentials as $cred) {
        echo "   - {$cred->credential_type}\n";
    }
    echo "\n";
}

// Test API methods availability
echo "🔧 IMPLEMENTED API METHODS:\n";
echo str_repeat("-", 80) . "\n";

try {
    $service = new CareemApiService($tenant->id);
    $reflection = new ReflectionClass($service);
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

    $orderMethods = array_filter($methods, function($method) {
        $name = $method->getName();
        return strpos($name, 'Order') !== false ||
               strpos($name, 'order') !== false ||
               $name === 'listOrders';
    });

    echo "✅ Order Management Methods:\n";
    foreach ($orderMethods as $method) {
        if ($method->class === CareemApiService::class) {
            echo "   - {$method->getName()}()\n";
        }
    }
    echo "\n";

} catch (\Exception $e) {
    echo "❌ Failed to initialize CareemApiService: {$e->getMessage()}\n\n";
}

// Check database schema
echo "💾 DATABASE SCHEMA:\n";
echo str_repeat("-", 80) . "\n";

$columns = \DB::select("SHOW COLUMNS FROM orders WHERE Field IN ('status', 'platform_status', 'platform_status_updated_at')");

foreach ($columns as $column) {
    $icon = $column->Field === 'platform_status' || $column->Field === 'platform_status_updated_at' ? '✅ NEW' : '  ';
    echo "{$icon} {$column->Field} - {$column->Type}\n";
}
echo "\n";

// Check recent orders
echo "📦 RECENT ORDERS (Last 5):\n";
echo str_repeat("-", 80) . "\n";

$orders = \App\Models\Order::where('tenant_id', $tenant->id)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($orders->isEmpty()) {
    echo "No orders found\n\n";
} else {
    foreach ($orders as $order) {
        $statusIcon = $order->status === 'synced' ? '✅' :
                     ($order->status === 'failed' ? '❌' : '⏳');

        $platformStatus = $order->platform_status ?? 'N/A';

        echo "{$statusIcon} Order #{$order->id}\n";
        echo "   Careem ID: {$order->careem_order_id}\n";
        echo "   Sync Status: {$order->status}\n";
        echo "   Platform Status: {$platformStatus}\n";
        echo "   Created: {$order->created_at->format('Y-m-d H:i:s')}\n\n";
    }
}

// Interactive test
echo "🧪 INTERACTIVE TESTING:\n";
echo str_repeat("=", 80) . "\n";
echo "What would you like to test?\n\n";
echo "1. Test order acceptance (requires real order ID)\n";
echo "2. Test mark order as ready (requires real order ID)\n";
echo "3. Test order cancellation (requires real order ID)\n";
echo "4. List orders from Careem API\n";
echo "5. Skip testing\n\n";

echo "Enter choice (1-5): ";
$choice = trim(fgets(STDIN));

if ($choice === '1' || $choice === '2' || $choice === '3') {
    $activeBranch = $branches->where('pos_integration_enabled', true)->first();

    if (!$activeBranch) {
        echo "\n❌ No active branch found. Please enable POS integration for a branch.\n";
        exit(1);
    }

    echo "\nEnter Careem order ID: ";
    $orderId = trim(fgets(STDIN));

    if (empty($orderId)) {
        echo "❌ Order ID is required\n";
        exit(1);
    }

    try {
        $service = new CareemApiService($tenant->id);

        switch ($choice) {
            case '1':
                echo "\n⏳ Accepting order...\n";
                $response = $service->acceptOrder(
                    $orderId,
                    $activeBranch->careem_brand_id,
                    $activeBranch->careem_branch_id
                );
                echo "✅ Order accepted successfully!\n";
                echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
                break;

            case '2':
                echo "\n⏳ Marking order as ready...\n";
                $response = $service->markOrderReady(
                    $orderId,
                    $activeBranch->careem_brand_id,
                    $activeBranch->careem_branch_id
                );
                echo "✅ Order marked as ready!\n";
                echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
                break;

            case '3':
                echo "\nCancellation reasons:\n";
                echo "1. ITEM_TEMPORARILY_UNAVAILABLE\n";
                echo "2. KITCHEN_TOO_BUSY_TO_PREPARE_ORDER\n";
                echo "3. OTHER\n";
                echo "Enter choice (1-3): ";
                $reasonChoice = trim(fgets(STDIN));

                $reasons = [
                    '1' => 'ITEM_TEMPORARILY_UNAVAILABLE',
                    '2' => 'KITCHEN_TOO_BUSY_TO_PREPARE_ORDER',
                    '3' => 'OTHER'
                ];

                $reason = $reasons[$reasonChoice] ?? 'OTHER';

                echo "\n⏳ Cancelling order with reason: {$reason}...\n";
                $response = $service->cancelOrder(
                    $orderId,
                    $activeBranch->careem_brand_id,
                    $activeBranch->careem_branch_id,
                    $reason
                );
                echo "✅ Order cancelled!\n";
                echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
                break;
        }

    } catch (\Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n";
        if (method_exists($e, 'getTraceAsString')) {
            echo "\nStack trace:\n{$e->getTraceAsString()}\n";
        }
    }

} elseif ($choice === '4') {
    $activeBranch = $branches->where('pos_integration_enabled', true)->first();

    if (!$activeBranch) {
        echo "\n❌ No active branch found. Please enable POS integration for a branch.\n";
        exit(1);
    }

    try {
        $service = new CareemApiService($tenant->id);
        echo "\n⏳ Fetching orders from Careem...\n";

        $response = $service->listOrders(
            $activeBranch->careem_brand_id,
            $activeBranch->careem_branch_id,
            1,
            10
        );

        echo "✅ Orders retrieved!\n\n";
        echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";

    } catch (\Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n";
    }
}

echo "\n";
echo "=== TEST COMPLETE ===\n";
