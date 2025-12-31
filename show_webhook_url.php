<?php

/**
 * Show Webhook URL Configuration
 *
 * This script shows the webhook URL that needs to be configured in Careem Dashboard
 *
 * Usage: php show_webhook_url.php <subdomain>
 * Example: php show_webhook_url.php dw
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;

// Get subdomain from command line
$subdomain = $argv[1] ?? null;

if (!$subdomain) {
    echo "❌ Error: Subdomain is required\n";
    echo "Usage: php show_webhook_url.php <subdomain>\n";
    echo "Example: php show_webhook_url.php dw\n";
    exit(1);
}

echo "🔍 Looking for tenant: {$subdomain}\n";

// Find tenant
$tenant = Tenant::where('subdomain', $subdomain)->first();

if (!$tenant) {
    echo "❌ Error: Tenant not found with subdomain '{$subdomain}'\n";
    exit(1);
}

echo "✅ Found tenant: {$tenant->name} (ID: {$tenant->id})\n\n";

// Get the base URL
$baseUrl = config('app.url');
if (str_contains($baseUrl, 'localhost') || str_contains($baseUrl, '127.0.0.1')) {
    echo "⚠️  WARNING: You're using localhost. Careem webhooks require a public URL!\n";
    echo "   Consider using ngrok or deploying to production first.\n\n";
}

// Generate webhook URLs
$webhookUrl = "{$baseUrl}/api/webhook/careem/{$subdomain}";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📍 WEBHOOK CONFIGURATION FOR CAREEM\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "🔗 Webhook URL:\n";
echo "   {$webhookUrl}\n\n";

echo "📋 Steps to Configure in Careem Dashboard:\n";
echo "   1. Log in to Careem Partner Dashboard\n";
echo "   2. Go to Settings → Webhooks/Integrations\n";
echo "   3. Add the webhook URL above\n";
echo "   4. Subscribe to these events:\n";
echo "      • ORDER_CREATED (new orders)\n";
echo "      • ORDER_STATUS_UPDATED (status changes)\n";
echo "      • ORDER_CANCELLED (cancellations)\n";
echo "   5. Save the configuration\n\n";

echo "🔐 Authentication:\n";
echo "   • Careem signs webhooks with a signature header\n";
echo "   • Your system validates this signature automatically\n";
echo "   • No additional configuration needed\n\n";

echo "✅ Test Webhook:\n";
echo "   After configuring in Careem, place a test order:\n";
echo "   1. Open Careem app\n";
echo "   2. Order from your restaurant\n";
echo "   3. Check logs: tail -f storage/logs/laravel-*.log\n";
echo "   4. Check orders: php artisan tinker\n";
echo "      >>> \\App\\Models\\Order::latest()->first()\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "💡 Current Status Check:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Check queue worker
echo "📊 Queue Status:\n";
$queueConnection = config('queue.default');
echo "   Connection: {$queueConnection}\n";

if ($queueConnection === 'database') {
    $pendingJobs = DB::table('jobs')->count();
    $failedJobs = DB::table('failed_jobs')->count();
    echo "   Pending Jobs: {$pendingJobs}\n";
    echo "   Failed Jobs: {$failedJobs}\n";

    if ($pendingJobs > 0) {
        echo "   ⚠️  You have pending jobs. Make sure queue worker is running!\n";
        echo "      Run: php artisan queue:work database --queue=high,default\n";
    }
}

echo "\n";

// Check auto-accept settings
$autoAccept = $tenant->getSetting('auto_accept_careem', false);
$autoMarkReady = $tenant->getSetting('auto_mark_ready_careem', false);

echo "⚙️  Order Settings:\n";
echo "   Auto-Accept Orders: " . ($autoAccept ? '✅ Enabled' : '❌ Disabled') . "\n";
echo "   Auto-Mark Ready: " . ($autoMarkReady ? '✅ Enabled' : '❌ Disabled') . "\n\n";

if (!$autoAccept) {
    echo "   💡 Tip: Enable auto-accept with:\n";
    echo "      php artisan tinker\n";
    echo "      >>> \$t = App\\Models\\Tenant::where('subdomain', '{$subdomain}')->first();\n";
    echo "      >>> \$t->setSetting('auto_accept_careem', true);\n\n";
}

if (!$autoMarkReady) {
    echo "   💡 Tip: Enable auto-mark-ready with:\n";
    echo "      php artisan tinker\n";
    echo "      >>> \$t = App\\Models\\Tenant::where('subdomain', '{$subdomain}')->first();\n";
    echo "      >>> \$t->setSetting('auto_mark_ready_careem', true);\n\n";
}

// Check branches
$branches = \App\Models\CareemBranch::where('tenant_id', $tenant->id)
    ->where('pos_integration_enabled', true)
    ->count();

echo "🏪 Branch Configuration:\n";
echo "   Active Branches: {$branches}\n";

if ($branches === 0) {
    echo "   ⚠️  No active branches! Enable POS integration for at least one branch.\n";
}

echo "\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Next Steps:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "1. Copy the webhook URL above\n";
echo "2. Configure it in Careem Partner Dashboard\n";
echo "3. Ensure queue worker is running\n";
echo "4. Place a test order from Careem app\n";
echo "5. Monitor logs for incoming webhooks\n\n";

echo "📞 If webhooks still don't work:\n";
echo "   • Contact Careem support to verify webhook subscription\n";
echo "   • Check if your server can receive public requests\n";
echo "   • Verify SSL certificate is valid (Careem requires HTTPS)\n";
echo "   • Check firewall/security rules aren't blocking Careem IPs\n\n";
