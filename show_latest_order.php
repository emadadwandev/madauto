<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get the latest order
$order = \App\Models\Order::where('platform', 'careem')
    ->orderBy('id', 'desc')
    ->first();

if (!$order) {
    echo "❌ No orders found\n";
    exit(1);
}

echo "📦 Latest Order ID: {$order->id}\n";
echo "📅 Created: {$order->created_at}\n";
echo "🏷️  Careem Order ID: {$order->careem_order_id}\n\n";

$orderData = is_string($order->order_data) ? json_decode($order->order_data, true) : $order->order_data;

// Get items
$items = $orderData['items'] ?? $orderData['details']['items'] ?? [];

echo "Found " . count($items) . " items\n\n";

foreach ($items as $i => $item) {
    echo "=== ITEM " . ($i + 1) . " ===\n";
    echo json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
}
