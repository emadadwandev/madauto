<?php

/**
 * Check if x-careem-api-key is configured for tenants
 * Run: php check_api_key.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking Careem API Key Configuration ===\n\n";

$tenants = DB::table('tenants')->get(['id', 'subdomain', 'name']);

if ($tenants->isEmpty()) {
    echo "❌ No tenants found in database.\n";
    exit(1);
}

echo "Found " . $tenants->count() . " tenant(s):\n\n";

$missingApiKey = [];

foreach ($tenants as $tenant) {
    echo "Tenant: {$tenant->subdomain} ({$tenant->name})\n";
    echo "  ID: {$tenant->id}\n";

    // Check for api_key credential
    $apiKeyCredential = DB::table('api_credentials')
        ->where('tenant_id', $tenant->id)
        ->where('service', 'careem_catalog')
        ->where('credential_type', 'api_key')
        ->first();

    if ($apiKeyCredential) {
        $keyLength = strlen($apiKeyCredential->credential_value ?? '');
        echo "  ✅ API Key: Configured (length: {$keyLength})\n";
        echo "  Status: " . ($apiKeyCredential->is_active ? '✅ Active' : '❌ Inactive') . "\n";
    } else {
        echo "  ❌ API Key: NOT CONFIGURED\n";
        $missingApiKey[] = $tenant;
    }

    // Check other credentials
    $clientId = DB::table('api_credentials')
        ->where('tenant_id', $tenant->id)
        ->where('service', 'careem_catalog')
        ->where('credential_type', 'client_id')
        ->first();

    $clientSecret = DB::table('api_credentials')
        ->where('tenant_id', $tenant->id)
        ->where('service', 'careem_catalog')
        ->where('credential_type', 'client_secret')
        ->first();

    echo "  Client ID: " . ($clientId ? '✅' : '❌') . "\n";
    echo "  Client Secret: " . ($clientSecret ? '✅' : '❌') . "\n";
    echo "\n";
}

if (!empty($missingApiKey)) {
    echo "\n⚠️  WARNING: The following tenants are MISSING the x-careem-api-key:\n\n";

    foreach ($missingApiKey as $tenant) {
        echo "  - {$tenant->subdomain} (ID: {$tenant->id})\n";
    }

    echo "\n📝 To fix this issue, you need to:\n";
    echo "   1. Get the x-careem-api-key from Careem for each tenant\n";
    echo "   2. Add it to the database using this command:\n\n";
    echo "   php artisan tinker\n\n";
    echo "   Then run:\n";
    echo "   >>> \$tenant = App\\Models\\Tenant::where('subdomain', 'YOUR_TENANT_SUBDOMAIN')->first();\n";
    echo "   >>> App\\Models\\ApiCredential::create([\n";
    echo "       'tenant_id' => \$tenant->id,\n";
    echo "       'service' => 'careem_catalog',\n";
    echo "       'credential_type' => 'api_key',\n";
    echo "       'credential_value' => 'YOUR_CAREEM_API_KEY_HERE',\n";
    echo "       'is_active' => true\n";
    echo "   ]);\n\n";

    echo "⚠️  Without this key, you will get the error:\n";
    echo "   'Invalid or missing x-careem-api-key header'\n\n";

    exit(1);
} else {
    echo "✅ All tenants have the x-careem-api-key configured!\n\n";
    exit(0);
}
