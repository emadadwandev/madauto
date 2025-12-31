<?php

/**
 * Check Careem API Credentials Configuration
 *
 * This script helps diagnose why order acceptance fails by checking:
 * 1. API credentials are configured
 * 2. Brand and branch associations
 * 3. Token generation works
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Models\ApiCredential;
use App\Models\CareemBrand;
use App\Models\CareemBranch;
use App\Services\CareemApiService;

echo "=== CAREEM ORDER ACCEPTANCE DIAGNOSTIC ===\n\n";

// Get tenant
echo "Enter tenant subdomain: ";
$subdomain = trim(fgets(STDIN));

$tenant = Tenant::where('subdomain', $subdomain)->first();

if (!$tenant) {
    echo "❌ Tenant not found\n";
    exit(1);
}

echo "✅ Found tenant: {$tenant->name} (ID: {$tenant->id})\n\n";

// 1. CHECK API CREDENTIALS
echo "1️⃣  CHECKING API CREDENTIALS\n";
echo str_repeat("-", 80) . "\n";

$credentials = ApiCredential::where('tenant_id', $tenant->id)
    ->where('service', 'careem_catalog')
    ->where('is_active', true)
    ->get();

if ($credentials->isEmpty()) {
    echo "❌ NO API CREDENTIALS FOUND\n";
    echo "   You need to configure Careem OAuth2 credentials:\n";
    echo "   - client_id\n";
    echo "   - client_secret\n";
    echo "   - client_name (optional)\n\n";
    echo "   Add them via: Dashboard → Settings → API Credentials\n";
    echo "   Or manually in database:\n\n";
    echo "   INSERT INTO api_credentials (tenant_id, service, credential_type, credential_value, is_active)\n";
    echo "   VALUES\n";
    echo "     ('{$tenant->id}', 'careem_catalog', 'client_id', 'YOUR_CLIENT_ID', 1),\n";
    echo "     ('{$tenant->id}', 'careem_catalog', 'client_secret', 'YOUR_CLIENT_SECRET', 1);\n\n";
    exit(1);
} else {
    echo "✅ API credentials found:\n";
    foreach ($credentials as $cred) {
        $value = decrypt($cred->credential_value);
        $maskedValue = strlen($value) > 8
            ? substr($value, 0, 4) . '...' . substr($value, -4)
            : '***';
        echo "   ✓ {$cred->credential_type}: {$maskedValue}\n";
    }
    echo "\n";
}

// 2. TEST TOKEN GENERATION
echo "2️⃣  TESTING TOKEN GENERATION\n";
echo str_repeat("-", 80) . "\n";

try {
    $service = new CareemApiService($tenant->id);
    $token = $service->getAccessToken();

    echo "✅ Successfully generated OAuth2 token\n";
    echo "   Token: " . substr($token, 0, 20) . "...\n\n";
} catch (\Exception $e) {
    echo "❌ Failed to generate token\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    echo "   Check that your client_id and client_secret are correct.\n";
    echo "   Contact Careem support if credentials are invalid.\n\n";
    exit(1);
}

// 3. CHECK BRANDS AND BRANCHES
echo "3️⃣  CHECKING BRANDS & BRANCHES\n";
echo str_repeat("-", 80) . "\n";

$brands = CareemBrand::where('tenant_id', $tenant->id)->get();

if ($brands->isEmpty()) {
    echo "⚠️  No Careem brands found\n";
    echo "   Create a brand via Dashboard → Careem Brands\n\n";
} else {
    echo "Brands found: {$brands->count()}\n\n";

    foreach ($brands as $brand) {
        echo "📦 Brand: {$brand->name}\n";
        echo "   Careem Brand ID: {$brand->careem_brand_id}\n";
        echo "   State: {$brand->state}\n";

        $branches = CareemBranch::where('careem_brand_id', $brand->id)->get();
        echo "   Branches: {$branches->count()}\n";

        foreach ($branches as $branch) {
            $posStatus = $branch->pos_integration_enabled ? '✅ Enabled' : '❌ Disabled';
            echo "   └─ {$branch->name} (ID: {$branch->careem_branch_id})\n";
            echo "      POS Integration: {$posStatus}\n";
            echo "      State: {$branch->state}\n";

            if (!$branch->pos_integration_enabled) {
                echo "      ⚠️  WARNING: POS integration must be enabled to receive orders\n";
            }
            if ($branch->state !== 'MAPPED') {
                echo "      ⚠️  WARNING: Branch must be MAPPED by Careem operations team\n";
            }
        }
        echo "\n";
    }
}

// 4. CHECK RECENT ORDERS
echo "4️⃣  CHECKING RECENT ORDERS\n";
echo str_repeat("-", 80) . "\n";

$orders = \App\Models\Order::where('tenant_id', $tenant->id)
    ->latest()
    ->limit(5)
    ->get();

if ($orders->isEmpty()) {
    echo "ℹ️  No orders found yet\n\n";
} else {
    echo "Recent orders: {$orders->count()}\n\n";

    foreach ($orders as $order) {
        $orderData = $order->order_data;
        $branchId = $orderData['branch']['id'] ?? 'N/A';
        $brandId = $orderData['branch']['brand_id'] ?? 'N/A';

        echo "📋 Order #{$order->careem_order_id}\n";
        echo "   Status: {$order->status}\n";
        echo "   Platform Status: " . ($order->platform_status ?? 'not set') . "\n";
        echo "   Branch ID (from webhook): {$branchId}\n";
        echo "   Brand ID (from webhook): {$brandId}\n";

        // Check if we can match this to a local branch
        $matchedBranch = CareemBranch::where('tenant_id', $tenant->id)
            ->where('careem_branch_id', $branchId)
            ->with('brand')
            ->first();

        if ($matchedBranch && $matchedBranch->brand) {
            echo "   ✅ Local branch found: {$matchedBranch->name}\n";
            echo "   ✅ Brand: {$matchedBranch->brand->name} (ID: {$matchedBranch->brand->careem_brand_id})\n";
        } else {
            echo "   ❌ No local branch/brand mapping found\n";
            echo "   ACTION NEEDED: Create/map branch with ID '{$branchId}' and brand '{$brandId}'\n";
        }
        echo "\n";
    }
}

// 5. SUMMARY
echo "5️⃣  SUMMARY & NEXT STEPS\n";
echo str_repeat("=", 80) . "\n";

$hasCredentials = !$credentials->isEmpty();
$canGenerateToken = false;

try {
    $service = new CareemApiService($tenant->id);
    $service->getAccessToken();
    $canGenerateToken = true;
} catch (\Exception $e) {
    // Token generation failed
}

$hasBranches = CareemBranch::where('tenant_id', $tenant->id)->exists();
$hasEnabledBranches = CareemBranch::where('tenant_id', $tenant->id)
    ->where('pos_integration_enabled', true)
    ->exists();

echo "\n";
echo "Checklist:\n";
echo ($hasCredentials ? "✅" : "❌") . " API credentials configured\n";
echo ($canGenerateToken ? "✅" : "❌") . " Can generate OAuth2 token\n";
echo ($hasBranches ? "✅" : "❌") . " Has Careem branches\n";
echo ($hasEnabledBranches ? "✅" : "⚠️ ") . " Has POS-enabled branches\n";

echo "\n";

if (!$hasCredentials || !$canGenerateToken) {
    echo "🔴 CRITICAL: Configure API credentials first\n";
    echo "   1. Get OAuth2 credentials from Careem (client_id & client_secret)\n";
    echo "   2. Add them to api_credentials table for this tenant\n";
    echo "   3. Run this script again to verify\n\n";
    exit(1);
}

if (!$hasBranches) {
    echo "⚠️  WARNING: No branches configured\n";
    echo "   1. Create/sync branches via Dashboard → Careem Branches\n";
    echo "   2. Ensure branches are MAPPED by Careem operations team\n";
    echo "   3. Enable POS integration for each branch\n\n";
}

if ($hasBranches && !$hasEnabledBranches) {
    echo "⚠️  WARNING: No POS-enabled branches\n";
    echo "   Enable POS integration for branches to receive orders\n\n";
}

if ($hasCredentials && $canGenerateToken && $hasBranches && $hasEnabledBranches) {
    echo "✅ ALL CHECKS PASSED!\n";
    echo "   Your system should be able to accept orders.\n\n";
    echo "   If orders still fail:\n";
    echo "   1. Check Laravel logs: storage/logs/laravel.log\n";
    echo "   2. Check api_logs table for detailed error responses\n";
    echo "   3. Verify order's branch_id matches your configured branches\n\n";
}

echo "Done!\n";
