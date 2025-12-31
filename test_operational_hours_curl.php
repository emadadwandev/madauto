<?php

require __DIR__.'/vendor/autoload.php';

use App\Services\CareemMenuTransformer;
use App\Services\CareemApiService;
use App\Models\Location;
use App\Models\Tenant;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set tenant context
$tenantId = 19;
$tenant = Tenant::find($tenantId);
app()->instance('tenant', $tenant);

// Get location
$location = Location::find(3);
$brandId = 'DW';
$branchId = 'main_branch';

if (!$location) {
    echo "Location not found\n";
    exit(1);
}

// Transform hours
$transformer = new CareemMenuTransformer();
$operationalHours = $transformer->transformOperationalHours($location->opening_hours);

$payload = ['operational_hours' => $operationalHours];

// Get token - use reflection to access protected method for testing
$apiService = new CareemApiService($tenantId);
$reflection = new ReflectionClass($apiService);
$method = $reflection->getMethod('getAccessToken');
$method->setAccessible(true);
$token = $method->invoke($apiService);

$baseUrl = config('platforms.careem.api_url');
$endpoint = config('platforms.careem.endpoints.operational_hours');
$url = $baseUrl . $endpoint;

echo "=== Operational Hours API Test ===\n\n";
echo "Base URL: {$baseUrl}\n";
echo "Endpoint: {$endpoint}\n";
echo "Full URL: {$url}\n";
echo "Brand-Id: {$brandId}\n";
echo "Branch-Id: {$branchId}\n";
echo "Token: " . substr($token, 0, 20) . "...\n\n";

echo "Payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Generate curl command
$curlCommand = "curl -X PUT '{$url}' \\\n";
$curlCommand .= "  -H 'Authorization: Bearer {$token}' \\\n";
$curlCommand .= "  -H 'User-Agent: Careem-Loyverse-Integration/1.0' \\\n";
$curlCommand .= "  -H 'Brand-Id: {$brandId}' \\\n";
$curlCommand .= "  -H 'Branch-Id: {$branchId}' \\\n";
$curlCommand .= "  -H 'Content-Type: application/json' \\\n";
$curlCommand .= "  -d '" . json_encode($payload) . "'\n";

echo "=== CURL Command ===\n\n";
echo $curlCommand . "\n\n";

// Save to file
file_put_contents('operational_hours_curl.sh', $curlCommand);
echo "Curl command saved to: operational_hours_curl.sh\n\n";

// Execute via HTTP
echo "=== Executing API Call ===\n\n";
try {
    $response = \Illuminate\Support\Facades\Http::timeout(30)
        ->withToken($token)
        ->withHeaders([
            'User-Agent' => 'Careem-Loyverse-Integration/1.0',
            'Brand-Id' => $brandId,
            'Branch-Id' => $branchId,
        ])
        ->put($url, $payload);

    echo "Status Code: " . $response->status() . "\n";
    echo "Headers:\n";
    foreach ($response->headers() as $key => $values) {
        echo "  {$key}: " . implode(', ', $values) . "\n";
    }
    echo "\nResponse Body:\n";
    echo $response->body() . "\n\n";

    if ($response->successful()) {
        echo "✅ SUCCESS!\n";
        echo json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ FAILED!\n";
        echo "Raw response: " . $response->body() . "\n";

        // Try to decode error
        $errorData = $response->json();
        if ($errorData) {
            echo "\nError details:\n";
            echo json_encode($errorData, JSON_PRETTY_PRINT) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
