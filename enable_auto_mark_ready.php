<?php

/**
 * Enable Auto-Mark-Ready for Careem Orders
 *
 * This script enables automatic "ready for pickup" notification to Careem
 * after orders are successfully synced to Loyverse POS.
 *
 * Usage: php enable_auto_mark_ready.php <subdomain>
 * Example: php enable_auto_mark_ready.php dw
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;

// Get subdomain from command line
$subdomain = $argv[1] ?? null;

if (!$subdomain) {
    echo "❌ Error: Subdomain is required\n";
    echo "Usage: php enable_auto_mark_ready.php <subdomain>\n";
    echo "Example: php enable_auto_mark_ready.php dw\n";
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

// Check current settings
$autoAccept = $tenant->getSetting('auto_accept_careem', false);
$autoMarkReady = $tenant->getSetting('auto_mark_ready_careem', false);

echo "Current Settings:\n";
echo "  Auto-Accept: " . ($autoAccept ? '✅ Enabled' : '❌ Disabled') . "\n";
echo "  Auto-Mark-Ready: " . ($autoMarkReady ? '✅ Enabled' : '❌ Disabled') . "\n\n";

if ($autoMarkReady) {
    echo "✅ Auto-mark-ready is already enabled!\n";
    exit(0);
}

// Enable auto-mark-ready
echo "🔄 Enabling auto-mark-ready...\n";
$tenant->setSetting('auto_mark_ready_careem', true);

echo "✅ Auto-mark-ready has been enabled!\n\n";

echo "📋 How it works:\n";
echo "  1. Order received via webhook → ProcessCareemOrderJob\n";
echo "  2. Order auto-accepted (if auto_accept_careem is enabled)\n";
echo "  3. Order synced to Loyverse → SyncToLoyverseJob\n";
echo "  4. After successful sync → MarkCareemOrderReadyJob\n";
echo "  5. Careem notified that order is ready for pickup\n\n";

echo "💡 Next Steps:\n";
echo "  • Make sure queue worker is running\n";
echo "  • Place a test order from Careem app\n";
echo "  • Monitor logs: tail -f storage/logs/laravel-*.log\n";
echo "  • Check that Careem receives 'ready' status\n\n";
