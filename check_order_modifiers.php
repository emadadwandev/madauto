<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find a recent order with items
$order = \App\Models\Order::where('platform', 'careem')
    ->whereNotNull('order_data')
    ->orderBy('created_at', 'desc')
    ->first();

if (!$order) {
    echo "❌ No orders found\n";
    exit(1);
}

echo "📦 Order ID: {$order->id}\n";
echo "📅 Created: {$order->created_at}\n\n";

$orderData = is_string($order->order_data) ? json_decode($order->order_data, true) : $order->order_data;
$items = $orderData['items'] ?? $orderData['details']['items'] ?? [];

echo "🔍 Found " . count($items) . " items\n\n";

foreach ($items as $index => $item) {
    echo "========================================\n";
    echo "Item #" . ($index + 1) . "\n";
    echo "========================================\n";
    echo "ID: " . ($item['id'] ?? 'N/A') . "\n";
    echo "Name: " . ($item['name'] ?? 'N/A') . "\n";
    echo "SKU: " . ($item['sku'] ?? 'N/A') . "\n\n";

    // Check different possible modifier field names
    $modifierFields = ['groups', 'options', 'modifiers', 'modifier_groups'];

    foreach ($modifierFields as $field) {
        if (isset($item[$field]) && !empty($item[$field])) {
            echo "✓ Found modifiers in field: '$field'\n";
            echo json_encode($item[$field], JSON_PRETTY_PRINT) . "\n\n";
        }
    }

    // Show full item structure for first item
    if ($index === 0) {
        echo "Full item structure:\n";
        echo json_encode($item, JSON_PRETTY_PRINT) . "\n\n";
    }
}
