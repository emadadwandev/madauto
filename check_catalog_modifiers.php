<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check if we have catalog items with modifier data
$items = \App\Models\CareemCatalogItem::whereNotNull('modifier_group_ids')
    ->orWhereNotNull('raw_data')
    ->get();

echo "Found " . $items->count() . " catalog items with potential modifier data\n\n";

foreach ($items as $item) {
    echo "=== {$item->name} (ID: {$item->careem_item_id}) ===\n";
    echo "Modifier Group IDs: " . json_encode($item->modifier_group_ids) . "\n";

    if ($item->raw_data) {
        echo "Raw Data:\n";
        echo json_encode($item->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo "\n";
}
