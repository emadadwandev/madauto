<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check order 1035 specifically
$order = \App\Models\Order::find(1035);

if (!$order) {
    echo "❌ Order 1035 not found\n";
    exit(1);
}

echo "📦 Order ID: {$order->id}\n";
echo "📅 Created: {$order->created_at}\n";
echo "🏷️  Careem Order ID: {$order->careem_order_id}\n\n";

$orderData = is_string($order->order_data) ? json_decode($order->order_data, true) : $order->order_data;

echo "=== FULL ORDER DATA STRUCTURE ===\n";
echo json_encode($orderData, JSON_PRETTY_PRINT) . "\n";
