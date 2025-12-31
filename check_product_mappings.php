<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductMapping;
use App\Models\Tenant;

$tenant = Tenant::find('019abb4e-9bc7-706e-a1fd-989bdc5c1709');
app()->instance('tenant', $tenant);

$mappings = ProductMapping::where('platform', 'careem')
    ->where('tenant_id', $tenant->id)
    ->get(['platform_product_id', 'platform_name', 'platform_sku']);

echo "Product Mappings for Careem: {$mappings->count()}\n\n";

if ($mappings->count() > 0) {
    foreach ($mappings->take(10) as $m) {
        $name = $m->platform_name ?? 'N/A';
        $sku = $m->platform_sku ?? 'N/A';
        echo "  - ID: {$m->platform_product_id}, Name: {$name}, SKU: {$sku}\n";
    }
} else {
    echo "❌ No product mappings found for Careem platform.\n";
    echo "\nProduct mappings are created when you:\n";
    echo "1. Map Careem items to Loyverse products in the dashboard\n";
    echo "2. OR sync catalog FROM Loyverse TO Careem\n";
}
