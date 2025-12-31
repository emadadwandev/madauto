<?php
/**
 * Generate Careem API Curl Command
 *
 * This script generates the exact curl command and payload that would be sent to Careem API.
 * Usage: php generate_careem_curl.php [menu_id] [tenant_id]
 *
 * Example: php generate_careem_curl.php 6 19
 */

// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Menu;
use App\Models\Tenant;
use App\Services\CareemMenuTransformer;
use App\Services\CareemApiService;
use Illuminate\Support\Facades\DB;

// Get command line arguments
$menuId = $argv[1] ?? null;
$tenantId = $argv[2] ?? null;

if (!$menuId) {
    echo "❌ Error: Menu ID is required\n";
    echo "Usage: php generate_careem_curl.php [menu_id] [tenant_id]\n";
    echo "Example: php generate_careem_curl.php 6 19\n";
    exit(1);
}

try {
    // Set tenant context if provided
    if ($tenantId) {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            echo "❌ Error: Tenant not found\n";
            exit(1);
        }
        app()->instance('tenant', $tenant);
        echo "✅ Tenant: {$tenant->name} (ID: {$tenant->id})\n\n";
    }

    // Load menu with relationships
    $menu = Menu::with(['items.modifierGroups.modifiers', 'locations.careemBranch.brand'])->find($menuId);

    if (!$menu) {
        echo "❌ Error: Menu not found\n";
        exit(1);
    }

    echo "📋 Menu: {$menu->name} (ID: {$menu->id})\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Get brand and branch IDs
    $branch = $menu->locations()->first()?->careemBranch;
    $brandId = $branch?->brand?->careem_brand_id;
    $branchId = $branch?->careem_branch_id;

    if (!$brandId || !$branchId) {
        echo "⚠️  Warning: Brand ID or Branch ID not found\n";
        echo "Brand ID: " . ($brandId ?: 'NOT SET') . "\n";
        echo "Branch ID: " . ($branchId ?: 'NOT SET') . "\n";
        echo "\nUsing placeholder values...\n\n";
        $brandId = $brandId ?: 'YOUR_BRAND_ID';
        $branchId = $branchId ?: 'YOUR_BRANCH_ID';
    } else {
        echo "🏢 Brand ID: {$brandId}\n";
        echo "🏪 Branch ID: {$branchId}\n\n";
    }

    // Generate catalog ID
    $catalogId = $menu->careem_catalog_id ?? 'catalog_' . $menu->id . '_' . ($branchId ?? 'default');
    echo "📦 Catalog ID: {$catalogId}\n\n";

    // Transform menu to Careem format
    echo "🔄 Transforming menu to Careem format...\n";
    $transformer = new CareemMenuTransformer();
    $payload = $transformer->transform($menu, $catalogId);

    echo "✅ Transformation complete!\n";
    echo "   - Categories: " . count($payload['categories']) . "\n";
    echo "   - Items: " . count($payload['items']) . "\n";
    echo "   - Modifier Groups: " . count($payload['groups']) . "\n";
    echo "   - Options: " . count($payload['options']) . "\n";
    echo "   - Payload Size: " . round(strlen(json_encode($payload)) / 1024, 2) . " KB\n\n";

    // Get API configuration
    $apiService = new CareemApiService($tenantId);
    $baseUrl = config('platforms.careem.api_url');
    $tokenUrl = config('platforms.careem.auth.token_url');

    // Get credentials (safely)
    $credentials = DB::table('api_credentials')
        ->where('tenant_id', $tenantId)
        ->where('service', 'careem_catalog')
        ->where('is_active', true)
        ->get()
        ->pluck('credential_value', 'credential_type')
        ->toArray();

    $clientId = $credentials['client_id'] ?? config('platforms.careem.auth.client_id');
    $clientSecret = $credentials['client_secret'] ?? config('platforms.careem.auth.client_secret');
    $userAgent = $credentials['user_agent'] ?? config('platforms.careem.user_agent');

    // Generate the curl commands
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🔐 STEP 1: GET ACCESS TOKEN\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    $tokenCurl = "curl -X POST \"{$tokenUrl}\" \\\n";
    $tokenCurl .= "  -H \"Content-Type: application/x-www-form-urlencoded\" \\\n";
    $tokenCurl .= "  -d \"grant_type=client_credentials\" \\\n";
    $tokenCurl .= "  -d \"client_id={$clientId}\" \\\n";
    $tokenCurl .= "  -d \"client_secret={$clientSecret}\" \\\n";
    $tokenCurl .= "  -d \"scope=pos\"";

    echo $tokenCurl . "\n\n";
    echo "# Save the access_token from the response\n";
    echo "# Example: export ACCESS_TOKEN=\"your_token_here\"\n\n";

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📤 STEP 2: SUBMIT CATALOG TO CAREEM\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    $catalogUrl = $baseUrl . '/catalogs';

    $catalogCurl = "curl -X PUT \"{$catalogUrl}\" \\\n";
    $catalogCurl .= "  -H \"Authorization: Bearer \$ACCESS_TOKEN\" \\\n";
    $catalogCurl .= "  -H \"Content-Type: application/json\" \\\n";
    $catalogCurl .= "  -H \"Accept: application/json\" \\\n";
    $catalogCurl .= "  -H \"User-Agent: {$userAgent}\" \\\n";
    $catalogCurl .= "  -H \"Brand-Id: {$brandId}\" \\\n";
    $catalogCurl .= "  -H \"Branch-Id: {$branchId}\" \\\n";
    $catalogCurl .= "  -d '" . json_encode($payload, JSON_UNESCAPED_SLASHES) . "'";

    echo $catalogCurl . "\n\n";

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📋 STEP 3: CHECK CATALOG STATUS (Optional)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo "# First, get the request_id from step 2 response\n";
    echo "# Example: export REQUEST_ID=\"101519\"\n\n";

    $statusCurl = "curl -X GET \"{$baseUrl}/catalogs/status/\$REQUEST_ID\" \\\n";
    $statusCurl .= "  -H \"Authorization: Bearer \$ACCESS_TOKEN\" \\\n";
    $statusCurl .= "  -H \"Accept: application/json\" \\\n";
    $statusCurl .= "  -H \"User-Agent: {$userAgent}\" \\\n";
    $statusCurl .= "  -H \"Brand-Id: {$brandId}\" \\\n";
    $statusCurl .= "  -H \"Branch-Id: {$branchId}\"";

    echo $statusCurl . "\n\n";

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📄 PAYLOAD (Pretty Printed)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "💾 FILES SAVED\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Save payload to file
    $payloadFile = __DIR__ . "/careem_payload_menu_{$menuId}.json";
    file_put_contents($payloadFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "✅ Payload saved to: {$payloadFile}\n";

    // Save curl commands to file
    $curlFile = __DIR__ . "/careem_curl_menu_{$menuId}.sh";
    $curlScript = "#!/bin/bash\n\n";
    $curlScript .= "# Careem API Integration - Menu ID: {$menuId}\n";
    $curlScript .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $curlScript .= "# Step 1: Get Access Token\n";
    $curlScript .= "echo \"🔐 Getting access token...\"\n";
    $curlScript .= "TOKEN_RESPONSE=\$({$tokenCurl})\n";
    $curlScript .= "ACCESS_TOKEN=\$(echo \$TOKEN_RESPONSE | jq -r '.access_token')\n";
    $curlScript .= "echo \"✅ Token obtained: \$ACCESS_TOKEN\"\n";
    $curlScript .= "echo \"\"\n\n";
    $curlScript .= "# Step 2: Submit Catalog\n";
    $curlScript .= "echo \"📤 Submitting catalog...\"\n";
    $curlScript .= str_replace('$ACCESS_TOKEN', "\$ACCESS_TOKEN", $catalogCurl) . "\n";
    $curlScript .= "echo \"\"\n\n";
    $curlScript .= "# Step 3: Check Status (if you have request_id)\n";
    $curlScript .= "# Uncomment and set REQUEST_ID if needed\n";
    $curlScript .= "# REQUEST_ID=\"your_request_id\"\n";
    $curlScript .= "# " . str_replace('$ACCESS_TOKEN', "\$ACCESS_TOKEN", str_replace('$REQUEST_ID', "\$REQUEST_ID", $statusCurl)) . "\n";

    file_put_contents($curlFile, $curlScript);
    echo "✅ Curl commands saved to: {$curlFile}\n";
    if (PHP_OS_FAMILY !== 'Windows') {
        chmod($curlFile, 0755);
        echo "   (Made executable)\n";
    }

    echo "\n🎉 Done! You can now:\n";
    echo "   1. Review the payload in: careem_payload_menu_{$menuId}.json\n";
    echo "   2. Run the curl commands from: careem_curl_menu_{$menuId}.sh\n";
    echo "   3. Or copy-paste the commands above\n\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
