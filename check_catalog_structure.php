<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tenant = \App\Models\Tenant::where('subdomain', 'dw')->first();
app()->instance('tenant', $tenant);

echo "=== Catalog Modifiers Structure ===\n\n";

// Find catalog items with modifiers
$item = \App\Models\CareemCatalogItem::where('tenant_id', $tenant->id)
    ->whereNotNull('modifiers')
    ->first();

if (!$item) {
    echo "No catalog items with modifiers found\n";
    echo "\nLet's check the catalog table structure:\n";

    $items = \App\Models\CareemCatalogItem::where('tenant_id', $tenant->id)->limit(3)->get();
    foreach ($items as $item) {
        echo "\n--- Item: {$item->name} ---\n";
        echo "ID: {$item->careem_item_id}\n";
        echo "Has modifiers column: " . (isset($item->modifiers) ? 'Yes' : 'No') . "\n";
        if ($item->modifiers) {
            echo "Modifiers value: " . substr($item->modifiers, 0, 200) . "...\n";
        }
    }
    exit;
}

echo "Found item with modifiers: {$item->name}\n\n";
$modifiers = json_decode($item->modifiers, true);
print_r($modifiers);
