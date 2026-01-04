<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Dump Full Order Structure ===\n\n";

// Get tenant
$tenant = \App\Models\Tenant::where('subdomain', 'dw')->first();
app()->instance('tenant', $tenant);

// Get branch
$branch = \App\Models\CareemBranch::where('tenant_id', $tenant->id)
    ->where('pos_integration_enabled', true)
    ->with('brand')
    ->first();

// Initialize service
$careemService = new \App\Services\CareemApiService($tenant->id);

// Get one order
$response = $careemService->listOrders(
    $branch->brand->careem_brand_id,
    $branch->careem_branch_id,
    1,
    1
);

$orders = $response['data'] ?? [];
if (empty($orders)) {
    echo "No orders found\n";
    exit;
}

$orderId = $orders[0]['id'];
$fullOrder = $careemService->getOrder(
    (string)$orderId,
    $branch->brand->careem_brand_id,
    $branch->careem_branch_id
);

// Dump to JSON file
file_put_contents('full_order_structure.json', json_encode($fullOrder, JSON_PRETTY_PRINT));

echo "Full order structure dumped to: full_order_structure.json\n\n";

// Show just the first item with groups
if (!empty($fullOrder['items'])) {
    echo "=== First Item Structure ===\n";
    print_r($fullOrder['items'][0]);
}
