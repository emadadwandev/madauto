<?php

/**
 * Test Webhook Authentication
 *
 * This script tests webhook authentication with and without webhook secret
 *
 * Usage: php test_webhook_authentication.php <subdomain>
 * Example: php test_webhook_authentication.php dw
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Repositories\ApiCredentialRepository;

// Get subdomain from command line
$subdomain = $argv[1] ?? null;

if (!$subdomain) {
    echo "❌ Error: Subdomain is required\n";
    echo "Usage: php test_webhook_authentication.php <subdomain>\n";
    echo "Example: php test_webhook_authentication.php dw\n";
    exit(1);
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🔐 TESTING WEBHOOK AUTHENTICATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Find tenant
$tenant = Tenant::where('subdomain', $subdomain)->first();

if (!$tenant) {
    echo "❌ Tenant not found with subdomain '{$subdomain}'\n";
    exit(1);
}

echo "Tenant: {$tenant->name} (ID: {$tenant->id})\n";
echo "API Key: {$tenant->careem_api_key}\n\n";

// Check webhook secret
app()->instance('tenant', $tenant);
$apiCredentialRepo = new ApiCredentialRepository();
$credentials = $apiCredentialRepo->getActiveCredentials('careem');

$hasSecret = $credentials && isset($credentials['webhook_secret']) && !empty($credentials['webhook_secret']);

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📋 CURRENT CONFIGURATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if ($hasSecret) {
    $secret = $credentials['webhook_secret'];
    echo "⚠️  Webhook Secret: CONFIGURED (" . substr($secret, 0, 10) . "...)\n";
    echo "   Authentication: API Key + Signature (both required)\n";
} else {
    echo "✅ Webhook Secret: NOT CONFIGURED\n";
    echo "   Authentication: API Key only (recommended for Careem)\n";
}

echo "\n";

// Generate webhook URL
$appDomain = config('app.domain');
$protocol = parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https';
$webhookUrl = "{$protocol}://{$subdomain}.{$appDomain}/api/webhook/careem/{$subdomain}";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🧪 TEST CASES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$testPayload = json_encode([
    'event_type' => 'ORDER_CREATED',
    'details' => [
        'id' => 'TEST-' . time(),
        'status' => 'pending',
        'items' => []
    ]
]);

echo "Test Payload:\n{$testPayload}\n\n";

// Test Case 1: Only API Key (Should work if no secret configured)
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Test 1: API Key Only (Careem Standard)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "curl -X POST \\\n";
echo "  '{$webhookUrl}' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'x-careem-api-key: {$tenant->careem_api_key}' \\\n";
echo "  -d '{$testPayload}'\n\n";

if (!$hasSecret) {
    echo "✅ Expected Result: SUCCESS (200 OK)\n";
    echo "   Reason: No webhook secret configured, only API key required\n";
} else {
    echo "❌ Expected Result: FAILURE (401 Unauthorized)\n";
    echo "   Reason: Webhook secret configured but signature not provided\n";
}

echo "\n";

// Test Case 2: API Key + Signature (Required if secret configured)
if ($hasSecret) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Test 2: API Key + Signature (With Secret)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    $signature = 'sha256=' . hash_hmac('sha256', $testPayload, $secret);

    echo "curl -X POST \\\n";
    echo "  '{$webhookUrl}' \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -H 'x-careem-api-key: {$tenant->careem_api_key}' \\\n";
    echo "  -H 'X-Careem-Signature: {$signature}' \\\n";
    echo "  -d '{$testPayload}'\n\n";

    echo "✅ Expected Result: SUCCESS (200 OK)\n";
    echo "   Reason: Both API key and valid signature provided\n\n";
}

// Test Case 3: Wrong API Key (Should always fail)
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Test 3: Wrong API Key (Should Fail)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "curl -X POST \\\n";
echo "  '{$webhookUrl}' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'x-careem-api-key: wrong_api_key' \\\n";
echo "  -d '{$testPayload}'\n\n";

echo "❌ Expected Result: FAILURE (401 Unauthorized)\n";
echo "   Reason: Invalid API key\n\n";

// Recommendations
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "💡 RECOMMENDATIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if ($hasSecret) {
    echo "⚠️  You have a webhook secret configured!\n\n";
    echo "   Careem's official documentation does NOT mention signature verification.\n";
    echo "   They only authenticate via x-careem-api-key header.\n\n";
    echo "   RECOMMENDED ACTIONS:\n";
    echo "   1. Test with real Careem webhooks\n";
    echo "   2. If webhooks fail with '401 Unauthorized', remove the secret\n";
    echo "   3. To remove: Go to dashboard and clear the webhook secret field\n\n";
    echo "   OR via tinker:\n";
    echo "   php artisan tinker\n";
    echo "   >>> \$tenant = App\\Models\\Tenant::where('subdomain', '{$subdomain}')->first();\n";
    echo "   >>> app()->instance('tenant', \$tenant);\n";
    echo "   >>> app(App\\Repositories\\ApiCredentialRepository::class)->createOrUpdate('careem', ['webhook_secret' => null]);\n\n";
} else {
    echo "✅ Your configuration matches Careem's documentation!\n\n";
    echo "   Authentication: x-careem-api-key only\n";
    echo "   This is the standard Careem webhook authentication method.\n\n";
    echo "   NEXT STEPS:\n";
    echo "   1. Provide webhook URL to Careem: {$webhookUrl}\n";
    echo "   2. Provide API key to Careem: {$tenant->careem_api_key}\n";
    echo "   3. Test with the CURL command above\n";
    echo "   4. Monitor webhook logs after real order\n\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 MONITORING\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Check webhook logs:\n";
echo "  php artisan tinker\n";
echo "  >>> App\\Models\\WebhookLog::latest()->first()\n\n";

echo "Check application logs:\n";
echo "  tail -f storage/logs/laravel-*.log\n\n";

echo "Monitor queue:\n";
echo "  php artisan queue:work database --queue=high,default\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
