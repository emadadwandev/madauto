<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = \App\Models\Order::orderBy('id', 'desc')->first();

if (!$order) {
    echo "No orders found\n";
    exit;
}

echo "Order #{$order->id} - Careem Order #{$order->careem_order_id}\n\n";

$orderData = is_string($order->order_data) ? json_decode($order->order_data, true) : $order->order_data;

// Dump the COMPLETE order data
file_put_contents(__DIR__ . '/order_data_dump.json', json_encode($orderData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Order data saved to: order_data_dump.json\n";
echo "File size: " . filesize(__DIR__ . '/order_data_dump.json') . " bytes\n\n";

// Show a preview
echo "=== PREVIEW ===\n";
echo substr(json_encode($orderData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 0, 2000) . "\n...\n";
