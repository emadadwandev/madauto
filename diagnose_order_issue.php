<?php

/**
 * Diagnostic Script for Order Reception Issues
 *
 * This script helps diagnose why Careem orders are not showing up in the system.
 * It checks:
 * 1. Recent webhook logs
 * 2. Recent orders in database
 * 3. Queue jobs status
 * 4. Failed jobs
 * 5. Tenant configuration (auto-accept settings)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WebhookLog;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

echo "=== ORDER RECEPTION DIAGNOSTIC TOOL ===\n\n";

// Get tenant subdomain from user
echo "Enter tenant subdomain (or press Enter to check all tenants): ";
$tenantSubdomain = trim(fgets(STDIN));

$tenant = null;
if (!empty($tenantSubdomain)) {
    $tenant = Tenant::where('subdomain', $tenantSubdomain)->first();
    if (!$tenant) {
        echo "❌ Tenant not found with subdomain: {$tenantSubdomain}\n";
        exit(1);
    }
    echo "✅ Found tenant: {$tenant->name} (ID: {$tenant->id})\n\n";
}

// 1. Check Recent Webhook Logs
echo "📥 RECENT WEBHOOK LOGS (Last 10):\n";
echo str_repeat("-", 80) . "\n";

$webhookQuery = WebhookLog::orderBy('created_at', 'desc')->limit(10);
if ($tenant) {
    $webhookQuery->where('tenant_id', $tenant->id);
}

$webhookLogs = $webhookQuery->get();

if ($webhookLogs->isEmpty()) {
    echo "❌ No webhook logs found. This means:\n";
    echo "   - No orders have been received from Careem\n";
    echo "   - Check if webhook URL is correctly configured in Careem dashboard\n";
    echo "   - Expected URL format: https://yourdomain.com/api/webhook/careem/{tenant_subdomain}\n\n";
} else {
    foreach ($webhookLogs as $log) {
        $platform = $log->payload['platform'] ?? 'unknown';
        $status = $log->status;
        $orderId = $log->payload['order_id'] ?? 'N/A';
        $timestamp = $log->created_at->format('Y-m-d H:i:s');

        $statusIcon = $status === 'received' ? '✅' : '❌';
        echo "{$statusIcon} [{$timestamp}] Platform: {$platform} | Order ID: {$orderId} | Status: {$status}\n";

        if ($status === 'failed') {
            echo "   Error: " . ($log->error_message ?? 'Unknown error') . "\n";
        }
    }
    echo "\n";
}

// 2. Check Recent Orders
echo "📦 RECENT ORDERS (Last 10):\n";
echo str_repeat("-", 80) . "\n";

$orderQuery = Order::orderBy('created_at', 'desc')->limit(10);
if ($tenant) {
    $orderQuery->where('tenant_id', $tenant->id);
}

$orders = $orderQuery->get();

if ($orders->isEmpty()) {
    echo "❌ No orders found in database. This means:\n";
    echo "   - Webhooks may not be reaching the system\n";
    echo "   - ProcessCareemOrderJob may be failing\n";
    echo "   - Check queue worker is running\n\n";
} else {
    foreach ($orders as $order) {
        $status = $order->status;
        $orderId = $order->careem_order_id;
        $timestamp = $order->created_at->format('Y-m-d H:i:s');

        $statusIcon = $status === 'synced' ? '✅' :
                     ($status === 'failed' ? '❌' : '⏳');

        echo "{$statusIcon} [{$timestamp}] Order: {$orderId} | Status: {$status}\n";

        if ($status === 'failed') {
            $loyverseOrder = $order->loyverseOrder;
            if ($loyverseOrder && isset($loyverseOrder->sync_response['error'])) {
                echo "   Error: " . $loyverseOrder->sync_response['error'] . "\n";
            }
        }
    }
    echo "\n";
}

// 3. Check Queue Jobs
echo "⚙️  QUEUE JOBS STATUS:\n";
echo str_repeat("-", 80) . "\n";

$pendingJobs = DB::table('jobs')->count();
echo "Pending jobs in queue: {$pendingJobs}\n";

if ($pendingJobs > 0) {
    echo "⚠️  Warning: {$pendingJobs} jobs are waiting in queue. Make sure queue worker is running!\n";
    echo "   Run: php artisan queue:work\n\n";
} else {
    echo "✅ No pending jobs\n\n";
}

// 4. Check Failed Jobs
echo "❌ FAILED JOBS (Last 10):\n";
echo str_repeat("-", 80) . "\n";

$failedJobs = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->limit(10)->get();

if ($failedJobs->isEmpty()) {
    echo "✅ No failed jobs\n\n";
} else {
    foreach ($failedJobs as $job) {
        $payload = json_decode($job->payload, true);
        $jobClass = $payload['displayName'] ?? 'Unknown';
        $exception = substr($job->exception, 0, 150) . '...';
        $timestamp = $job->failed_at;

        echo "❌ [{$timestamp}] Job: {$jobClass}\n";
        echo "   Exception: {$exception}\n\n";
    }
}

// 5. Check Tenant Configuration
if ($tenant) {
    echo "⚙️  TENANT CONFIGURATION:\n";
    echo str_repeat("-", 80) . "\n";

    $settings = $tenant->settings ?? [];
    $autoAcceptCareem = $settings['auto_accept_careem'] ?? false;
    $autoAcceptTalabat = $settings['auto_accept_talabat'] ?? false;

    echo "Auto-accept Careem orders: " . ($autoAcceptCareem ? '✅ Enabled' : '❌ Disabled') . "\n";
    echo "Auto-accept Talabat orders: " . ($autoAcceptTalabat ? '✅ Enabled' : '❌ Disabled') . "\n\n";

    // Check API credentials
    echo "API CREDENTIALS:\n";

    $loyverseCredentials = \App\Models\ApiCredential::where('tenant_id', $tenant->id)
        ->where('service', 'loyverse')
        ->where('is_active', true)
        ->exists();

    echo "Loyverse API Token: " . ($loyverseCredentials ? '✅ Configured' : '❌ Not configured') . "\n";

    $careemCredentials = \App\Models\ApiCredential::where('tenant_id', $tenant->id)
        ->where('service', 'careem_catalog')
        ->where('is_active', true)
        ->exists();

    echo "Careem API Credentials: " . ($careemCredentials ? '✅ Configured' : '❌ Not configured') . "\n\n";
}

// 6. Check Queue Worker Status
echo "🔍 QUEUE WORKER CHECK:\n";
echo str_repeat("-", 80) . "\n";

$queueWorkerRunning = false;
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows
    $output = shell_exec('tasklist /FI "IMAGENAME eq php.exe" 2>NUL');
    if ($output && strpos($output, 'queue:work') !== false) {
        $queueWorkerRunning = true;
    }
} else {
    // Linux/Unix
    $output = shell_exec('ps aux | grep "queue:work"');
    if ($output && strpos($output, 'artisan queue:work') !== false) {
        $queueWorkerRunning = true;
    }
}

if ($queueWorkerRunning) {
    echo "✅ Queue worker appears to be running\n";
} else {
    echo "⚠️  Queue worker may not be running. Start it with:\n";
    echo "   php artisan queue:work database --queue=high,default\n";
}

echo "\n";

// 7. Recommendations
echo "💡 RECOMMENDATIONS:\n";
echo str_repeat("=", 80) . "\n";

if ($webhookLogs->isEmpty()) {
    echo "1. ⚠️  No webhooks received - verify webhook URL is configured in Careem\n";
    echo "   URL format: https://yourdomain.com/api/webhook/careem/" . ($tenant ? $tenant->subdomain : '{subdomain}') . "\n";
}

if ($orders->isEmpty() && !$webhookLogs->isEmpty()) {
    echo "2. ⚠️  Webhooks received but no orders created - check ProcessCareemOrderJob\n";
    echo "   Check logs: storage/logs/laravel.log\n";
}

if ($pendingJobs > 0) {
    echo "3. ⚠️  Jobs stuck in queue - ensure queue worker is running\n";
}

if ($tenant && !($settings['auto_accept_careem'] ?? false)) {
    echo "4. ℹ️  Auto-accept is DISABLED for Careem orders\n";
    echo "   Note: Currently, the system syncs orders to Loyverse but doesn't send acceptance back to Careem\n";
    echo "   If Careem requires order acceptance via API, additional implementation is needed\n";
}

if (!$failedJobs->isEmpty()) {
    echo "5. ⚠️  Failed jobs detected - check error messages above\n";
    echo "   Retry failed jobs: php artisan queue:retry all\n";
}

echo "\n";
echo "=== DIAGNOSTIC COMPLETE ===\n";
