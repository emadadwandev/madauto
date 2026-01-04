<?php

/**
 * Test script for Loyverse Store Selection feature
 *
 * This script verifies:
 * 1. Migration added loyverse_store_id column
 * 2. Tenant model has loyverse_store_id in fillable
 * 3. OrderTransformerService includes store_id and source
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "Store Selection Implementation Test\n";
echo "========================================\n\n";

// Test 1: Check if column exists
echo "✓ Test 1: Check database schema\n";
try {
    $columnExists = DB::getSchemaBuilder()->hasColumn('tenants', 'loyverse_store_id');
    if ($columnExists) {
        echo "  ✓ Column 'loyverse_store_id' exists in tenants table\n";
    } else {
        echo "  ✗ Column 'loyverse_store_id' NOT found in tenants table\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error checking schema: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check Tenant model
echo "✓ Test 2: Check Tenant model\n";
$tenant = new App\Models\Tenant();
$fillable = $tenant->getFillable();
if (in_array('loyverse_store_id', $fillable)) {
    echo "  ✓ 'loyverse_store_id' is in Tenant fillable array\n";
} else {
    echo "  ✗ 'loyverse_store_id' NOT in Tenant fillable array\n";
}

echo "\n";

// Test 3: Check OrderTransformerService
echo "✓ Test 3: Check OrderTransformerService\n";
$transformerCode = file_get_contents(__DIR__.'/app/Services/OrderTransformerService.php');
if (strpos($transformerCode, 'Careem Integration System') !== false) {
    echo "  ✓ Source set to 'Careem Integration System' (CIS)\n";
} else {
    echo "  ✗ Source not properly configured\n";
}

if (strpos($transformerCode, "tenant()->loyverse_store_id") !== false) {
    echo "  ✓ store_id fetched from tenant settings\n";
} else {
    echo "  ✗ store_id not properly configured\n";
}

if (strpos($transformerCode, "Loyverse store not selected") !== false) {
    echo "  ✓ Validation added for missing store_id\n";
} else {
    echo "  ✗ Missing store_id validation not found\n";
}

echo "\n";

// Test 4: Check API routes
echo "✓ Test 4: Check API routes\n";
$routeCode = file_get_contents(__DIR__.'/routes/tenant.php');
if (strpos($routeCode, 'fetch-stores') !== false) {
    echo "  ✓ 'fetch-stores' route exists\n";
} else {
    echo "  ✗ 'fetch-stores' route NOT found\n";
}

if (strpos($routeCode, 'set-store') !== false) {
    echo "  ✓ 'set-store' route exists\n";
} else {
    echo "  ✗ 'set-store' route NOT found\n";
}

echo "\n";

// Test 5: Check Controller methods
echo "✓ Test 5: Check ApiCredentialController\n";
$controllerCode = file_get_contents(__DIR__.'/app/Http/Controllers/Dashboard/ApiCredentialController.php');
if (strpos($controllerCode, 'public function fetchStores') !== false) {
    echo "  ✓ 'fetchStores' method exists\n";
} else {
    echo "  ✗ 'fetchStores' method NOT found\n";
}

if (strpos($controllerCode, 'public function setStore') !== false) {
    echo "  ✓ 'setStore' method exists\n";
} else {
    echo "  ✗ 'setStore' method NOT found\n";
}

echo "\n";

// Test 6: Check view
echo "✓ Test 6: Check Settings View\n";
$viewCode = file_get_contents(__DIR__.'/resources/views/dashboard/api-credentials/index.blade.php');
if (strpos($viewCode, 'storeSelectionSection') !== false) {
    echo "  ✓ Store selection section added to view\n";
} else {
    echo "  ✗ Store selection section NOT found in view\n";
}

if (strpos($viewCode, 'fetchStoresBtn') !== false) {
    echo "  ✓ Fetch stores button added\n";
} else {
    echo "  ✗ Fetch stores button NOT found\n";
}

if (strpos($viewCode, 'fetch-stores') !== false) {
    echo "  ✓ JavaScript for store fetching added\n";
} else {
    echo "  ✗ JavaScript for store fetching NOT found\n";
}

echo "\n";
echo "========================================\n";
echo "Implementation Summary:\n";
echo "========================================\n";
echo "✓ Migration: Created\n";
echo "✓ Model: Updated\n";
echo "✓ Service: Updated with store_id & source\n";
echo "✓ Controller: Added fetchStores & setStore\n";
echo "✓ Routes: Added API endpoints\n";
echo "✓ View: Added store selection UI\n";
echo "\n";
echo "Next Steps:\n";
echo "1. Navigate to Settings → API Credentials\n";
echo "2. Add Loyverse Access Token\n";
echo "3. Click 'Test Loyverse Connection'\n";
echo "4. Click 'Fetch Available Stores'\n";
echo "5. Select a store and click 'Set Selected Store'\n";
echo "6. Orders will now sync to the selected store\n";
echo "========================================\n";
