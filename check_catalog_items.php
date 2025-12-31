<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CareemCatalogItem;

$tenantId = '019abb4e-9bc7-706e-a1fd-989bdc5c1709';

$count = CareemCatalogItem::where('tenant_id', $tenantId)->count();
echo "Total catalog items for tenant: {$count}\n\n";

if ($count > 0) {
    echo "Sample items:\n";
    $items = CareemCatalogItem::where('tenant_id', $tenantId)
        ->take(10)
        ->get(['careem_item_id', 'name', 'sku', 'price']);

    foreach ($items as $item) {
        $sku = $item->sku ?? 'N/A';
        echo "  - Item #{$item->careem_item_id}: {$item->name} (SKU: {$sku}, Price: \${$item->price})\n";
    }
} else {
    echo "❌ No catalog items found. Run: php artisan careem:sync-catalog --tenant=dw\n";
}
