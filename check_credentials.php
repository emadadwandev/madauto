<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ApiCredential;

$tenantId = '019abb4e-9bc7-706e-a1fd-989bdc5c1709';

echo "API Credentials for tenant:\n\n";
$credentials = ApiCredential::where('tenant_id', $tenantId)->get();

if ($credentials->isEmpty()) {
    echo "❌ No credentials found!\n";
} else {
    foreach ($credentials as $cred) {
        $value = $cred->is_active ? '✓' : '✗';
        echo "  {$value} Service: {$cred->service}, Type: {$cred->credential_type}, Active: " . ($cred->is_active ? 'Yes' : 'No') . "\n";
    }
}
