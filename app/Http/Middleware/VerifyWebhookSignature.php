<?php

namespace App\Http\Middleware;

use App\Repositories\ApiCredentialRepository;
use Closure;
use Illuminate\Http\Request;

class VerifyWebhookSignature
{
    protected $apiCredentialRepository;

    public function __construct(ApiCredentialRepository $apiCredentialRepository)
    {
        $this->apiCredentialRepository = $apiCredentialRepository;
    }

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-Careem-Signature');

        // Get tenant from route parameter
        $tenant = $request->route('tenant');
        if (! $tenant) {
            abort(400, 'Tenant not specified in webhook URL.');
        }

        // Find and set tenant context
        $tenantModel = \App\Models\Tenant::where('subdomain', $tenant)->first();
        if (! $tenantModel) {
            abort(404, 'Tenant not found.');
        }

        // Verify x-careem-api-key
        $apiKey = $request->header('x-careem-api-key');
        if (! $apiKey || $apiKey !== $tenantModel->careem_api_key) {
            abort(401, 'Invalid or missing x-careem-api-key header.');
        }

        // Set tenant context before getting credentials
        app()->instance('tenant', $tenantModel);

        // Get tenant-specific credentials
        $credentials = $this->apiCredentialRepository->getActiveCredentials('careem');
        if (! $credentials || ! isset($credentials['webhook_secret'])) {
            abort(401, 'Webhook secret not configured for this tenant.');
        }

        $secret = $credentials['webhook_secret'];

        if (! $signature || ! $secret) {
            abort(401, 'Signature not provided.');
        }

        $payload = $request->getContent();
        $computedSignature = hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($signature, 'sha256='.$computedSignature)) {
            abort(401, 'Invalid signature.');
        }

        return $next($request);
    }
}
