<?php

/**
 * Fix "main_branch" Issue
 *
 * This script updates branches that have "main_branch" as their careem_branch_id
 * by fetching the correct UUID from Careem API and updating the record.
 *
 * Usage: php fix_main_branch.php <subdomain>
 * Example: php fix_main_branch.php dw
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Models\CareemBranch;
use App\Models\CareemBrand;
use App\Services\CareemApiService;
use Illuminate\Support\Facades\Log;

// Get subdomain from command line
$subdomain = $argv[1] ?? null;

if (!$subdomain) {
    echo "❌ Error: Subdomain is required\n";
    echo "Usage: php fix_main_branch.php <subdomain>\n";
    echo "Example: php fix_main_branch.php dw\n";
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

// Set tenant context
app()->instance('tenant', $tenant);

// Find branches with "main_branch"
$brokenBranches = CareemBranch::where('tenant_id', $tenant->id)
    ->where('careem_branch_id', 'main_branch')
    ->get();

if ($brokenBranches->isEmpty()) {
    echo "✅ No branches found with 'main_branch' ID. Everything looks good!\n";
    exit(0);
}

echo "🔧 Found " . $brokenBranches->count() . " branch(es) with 'main_branch' ID\n\n";

$careemService = new CareemApiService($tenant->id);

foreach ($brokenBranches as $branch) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 Branch: {$branch->name}\n";
    echo "   Local ID: {$branch->id}\n";
    echo "   Brand: {$branch->brand->name}\n";
    echo "   Careem Brand ID: {$branch->brand->careem_brand_id}\n";
    echo "   Current Branch ID: {$branch->careem_branch_id}\n\n";

    try {
        // Fetch branches from Careem API for this brand
        echo "🔄 Fetching branches from Careem API...\n";
        $response = $careemService->listBranches($branch->brand->careem_brand_id, 1, 20);

        if (!isset($response['data']) || empty($response['data'])) {
            echo "⚠️  No branches returned from Careem API\n\n";
            continue;
        }

        echo "✅ Found " . count($response['data']) . " branch(es) from Careem\n\n";

        // If there's only one branch, assume it's the correct one
        if (count($response['data']) === 1) {
            $careemBranch = $response['data'][0];
            $newBranchId = $careemBranch['id'];

            echo "💡 Only one branch found, updating with ID: {$newBranchId}\n";

            // Check if another branch already has this ID
            $existingBranch = CareemBranch::where('tenant_id', $tenant->id)
                ->where('careem_branch_id', $newBranchId)
                ->where('id', '!=', $branch->id)
                ->first();

            if ($existingBranch) {
                echo "⚠️  Another branch already has this ID. Deleting duplicate...\n";
                $branch->delete();
                echo "✅ Duplicate removed\n\n";
            } else {
                $branch->update([
                    'careem_branch_id' => $newBranchId,
                    'name' => $careemBranch['name'] ?? $branch->name,
                    'state' => $careemBranch['state'] ?? $branch->state,
                    'metadata' => $careemBranch,
                    'synced_at' => now(),
                ]);
                echo "✅ Updated successfully!\n";
                echo "   New Branch ID: {$newBranchId}\n\n";
            }
        } else {
            // Multiple branches - show options
            echo "📋 Multiple branches available:\n\n";
            foreach ($response['data'] as $index => $careemBranch) {
                echo "   " . ($index + 1) . ". {$careemBranch['name']}\n";
                echo "      ID: {$careemBranch['id']}\n";
                echo "      State: " . ($careemBranch['state'] ?? 'unknown') . "\n\n";
            }

            echo "❓ Which branch matches '{$branch->name}'? Enter number (or 's' to skip): ";
            $choice = trim(fgets(STDIN));

            if ($choice === 's' || $choice === 'S') {
                echo "⏭️  Skipped\n\n";
                continue;
            }

            $index = intval($choice) - 1;
            if (isset($response['data'][$index])) {
                $careemBranch = $response['data'][$index];
                $newBranchId = $careemBranch['id'];

                // Check for duplicates
                $existingBranch = CareemBranch::where('tenant_id', $tenant->id)
                    ->where('careem_branch_id', $newBranchId)
                    ->where('id', '!=', $branch->id)
                    ->first();

                if ($existingBranch) {
                    echo "⚠️  Another branch already has this ID. Deleting duplicate...\n";
                    $branch->delete();
                    echo "✅ Duplicate removed\n\n";
                } else {
                    $branch->update([
                        'careem_branch_id' => $newBranchId,
                        'name' => $careemBranch['name'],
                        'state' => $careemBranch['state'] ?? 'UNMAPPED',
                        'metadata' => $careemBranch,
                        'synced_at' => now(),
                    ]);
                    echo "✅ Updated successfully!\n";
                    echo "   New Branch ID: {$newBranchId}\n\n";
                }
            } else {
                echo "❌ Invalid choice\n\n";
            }
        }
    } catch (\Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n\n";
        Log::error('Fix main_branch failed', [
            'branch_id' => $branch->id,
            'error' => $e->getMessage(),
        ]);
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Done! All branches have been processed.\n";
echo "\n💡 Tip: Now test order acceptance - it should work with proper branch IDs!\n";
