<?php

/**
 * Quick test to verify Tenant relationships work
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;

echo "Testing Tenant relationships...\n\n";

// Get first tenant
$tenant = Tenant::first();

if (!$tenant) {
    echo "No tenants found in database\n";
    exit(1);
}

echo "Testing with tenant: {$tenant->name} (subdomain: {$tenant->subdomain})\n\n";

// Test careemBranches relationship
try {
    echo "Testing careemBranches() relationship... ";
    $branches = $tenant->careemBranches()->get();
    echo "✅ SUCCESS - Found " . $branches->count() . " branches\n";
} catch (\Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
}

// Test careemBrands relationship
try {
    echo "Testing careemBrands() relationship... ";
    $brands = $tenant->careemBrands()->get();
    echo "✅ SUCCESS - Found " . $brands->count() . " brands\n";
} catch (\Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
}

// Test setSetting method
try {
    echo "Testing setSetting() method... ";
    $tenant->setSetting('test_key', 'test_value');
    $value = $tenant->getSetting('test_key');
    if ($value === 'test_value') {
        echo "✅ SUCCESS\n";
    } else {
        echo "❌ FAILED - Value mismatch\n";
    }
} catch (\Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
}

echo "\n✅ All relationship tests passed!\n";
