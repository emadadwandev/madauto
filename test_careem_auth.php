<?php

use App\Services\CareemApiService;
use Illuminate\Support\Facades\Log;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get the first tenant ID to test with
$tenant = \App\Models\Tenant::first();
if (! $tenant) {
    echo "No tenant found.\n";
    exit(1);
}

echo 'Testing with Tenant ID: '.$tenant->id."\n";

try {
    $service = new CareemApiService($tenant->id);

    // We need to access the protected getAccessToken method or just call testConnection which calls it.
    // But testConnection catches the exception.
    // Let's use reflection to call getAccessToken directly to see the error.

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('getAccessToken');
    $method->setAccessible(true);

    echo "Attempting to get access token...\n";
    $token = $method->invoke($service);

    echo 'Success! Token: '.substr($token, 0, 10)."...\n";

} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    if (method_exists($e, 'getResponse')) {
        // echo "Response: " . $e->getResponse()->body() . "\n";
    }

    // Check logs
    // echo "Check laravel.log for details.\n";
}
