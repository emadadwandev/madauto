<?php

/**
 * Sync Careem Branches from API
 *
 * This script fetches the actual branch IDs from Careem API
 * and updates your local CareemBranch records
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Models\CareemBranch;
use App\Services\CareemApiService;

echo "=== CAREEM BRANCH SYNC TOOL ===\n\n";

// Get tenant
echo "Enter tenant subdomain: ";
$subdomain = trim(fgets(STDIN));

$tenant = Tenant::where('subdomain', $subdomain)->first();

if (!$tenant) {
    echo "❌ Tenant not found\n";
    exit(1);
}

echo "✅ Found tenant: {$tenant->name}\n\n";

// Get local branches
$localBranches = $tenant->careemBranches()->get();

if ($localBranches->isEmpty()) {
    echo "❌ No Careem branches configured locally\n";
    echo "   Please create branches first in Dashboard → Careem Branches\n";
    exit(1);
}

echo "📋 LOCAL BRANCHES:\n";
echo str_repeat("-", 80) . "\n";
foreach ($localBranches as $branch) {
    echo "• {$branch->name}\n";
    echo "  Brand ID: {$branch->careem_brand_id}\n";
    echo "  Branch ID: {$branch->careem_branch_id}\n";
    echo "  State: {$branch->state}\n\n";
}

// Try to fetch branches from Careem API
echo "🔄 FETCHING FROM CAREEM API:\n";
echo str_repeat("-", 80) . "\n";

try {
    $service = new CareemApiService($tenant->id);

    // Get unique brand IDs
    $brandIds = $localBranches->pluck('careem_brand_id')->unique();

    foreach ($brandIds as $brandId) {
        echo "\n📦 Fetching branches for Brand ID: {$brandId}\n\n";

        try {
            // Fetch branches from Careem
            $response = $service->listBranches($brandId, 1, 20);

            if (isset($response['data']) && is_array($response['data'])) {
                echo "✅ Found " . count($response['data']) . " branches in Careem:\n\n";

                foreach ($response['data'] as $careemBranch) {
                    $branchId = $careemBranch['id'] ?? 'N/A';
                    $branchName = $careemBranch['name'] ?? 'N/A';
                    $state = $careemBranch['state'] ?? 'N/A';
                    $active = isset($careemBranch['active']) ? ($careemBranch['active'] ? 'Yes' : 'No') : 'N/A';

                    echo "  • {$branchName}\n";
                    echo "    ID: {$branchId}\n";
                    echo "    State: {$state}\n";
                    echo "    Active: {$active}\n\n";

                    // Check if we have this branch locally
                    $localBranch = $localBranches->first(function($lb) use ($branchName, $branchId) {
                        return stripos($lb->name, $branchName) !== false || $lb->careem_branch_id === $branchId;
                    });

                    if ($localBranch && $localBranch->careem_branch_id !== $branchId) {
                        echo "    ⚠️  Local branch '{$localBranch->name}' has incorrect ID: '{$localBranch->careem_branch_id}'\n";
                        echo "    ✅ Correct ID should be: '{$branchId}'\n\n";

                        // Ask to update
                        echo "    Update local branch with correct ID? (y/n): ";
                        $confirm = strtolower(trim(fgets(STDIN)));

                        if ($confirm === 'y' || $confirm === 'yes') {
                            $localBranch->update([
                                'careem_branch_id' => $branchId,
                                'state' => $state === 'MAPPED' ? 'MAPPED' : 'UNMAPPED',
                                'metadata' => $careemBranch,
                                'synced_at' => now(),
                            ]);

                            echo "    ✅ Updated successfully!\n\n";
                        } else {
                            echo "    ⏭️  Skipped\n\n";
                        }
                    } elseif ($localBranch) {
                        echo "    ✅ Local branch already has correct ID\n\n";
                    } else {
                        echo "    ℹ️  Not found in local database\n";
                        echo "    Create local branch? (y/n): ";
                        $confirm = strtolower(trim(fgets(STDIN)));

                        if ($confirm === 'y' || $confirm === 'yes') {
                            CareemBranch::create([
                                'tenant_id' => $tenant->id,
                                'careem_brand_id' => $brandId,
                                'careem_branch_id' => $branchId,
                                'name' => $branchName,
                                'state' => $state === 'MAPPED' ? 'MAPPED' : 'UNMAPPED',
                                'pos_integration_enabled' => $active === 'Yes',
                                'visibility_status' => 1,
                                'metadata' => $careemBranch,
                                'synced_at' => now(),
                            ]);

                            echo "    ✅ Created successfully!\n\n";
                        } else {
                            echo "    ⏭️  Skipped\n\n";
                        }
                    }
                }
            } else {
                echo "⚠️  No branches returned from API\n";
                echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
            }

        } catch (\Exception $e) {
            echo "❌ Failed to fetch branches for brand {$brandId}: {$e->getMessage()}\n\n";

            // If brand not found, try to list all brands
            if (strpos($e->getMessage(), 'not found') !== false || strpos($e->getMessage(), 'NOT_FOUND') !== false) {
                echo "💡 Brand ID '{$brandId}' not found. Let's list all your brands:\n\n";

                try {
                    $brandsResponse = $service->listBrands(1, 20);

                    if (isset($brandsResponse['data']) && is_array($brandsResponse['data'])) {
                        echo "✅ Found " . count($brandsResponse['data']) . " brands:\n\n";

                        foreach ($brandsResponse['data'] as $brand) {
                            $id = $brand['id'] ?? 'N/A';
                            $name = $brand['name'] ?? 'N/A';

                            echo "  • {$name} (ID: {$id})\n";
                        }

                        echo "\n💡 Update your local CareemBranch records with correct Brand ID from above list\n\n";
                    }
                } catch (\Exception $e2) {
                    echo "❌ Could not list brands: {$e2->getMessage()}\n\n";
                }
            }
        }
    }

    echo "\n";
    echo str_repeat("=", 80) . "\n";
    echo "✅ SYNC COMPLETE\n";
    echo str_repeat("=", 80) . "\n\n";

    // Show updated branches
    echo "📋 UPDATED LOCAL BRANCHES:\n";
    echo str_repeat("-", 80) . "\n";

    $updatedBranches = $tenant->careemBranches()->get();
    foreach ($updatedBranches as $branch) {
        echo "• {$branch->name}\n";
        echo "  Brand ID: {$branch->careem_brand_id}\n";
        echo "  Branch ID: {$branch->careem_branch_id}\n";
        echo "  State: {$branch->state}\n";
        echo "  POS Integration: " . ($branch->pos_integration_enabled ? 'Enabled' : 'Disabled') . "\n";
        echo "  Last Synced: " . ($branch->synced_at ? $branch->synced_at->format('Y-m-d H:i:s') : 'Never') . "\n\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n💡 NEXT STEPS:\n";
echo "1. Verify branch IDs are correct (they should be UUIDs)\n";
echo "2. Test order listing again with correct branch IDs\n";
echo "3. Place a test order on Careem app\n\n";
