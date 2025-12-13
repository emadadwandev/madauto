<?php

namespace App\Http\Middleware;

use App\Repositories\ApiCredentialRepository;
use Closure;
use Illuminate\Http\Request;

class VerifyTalabatApiKey
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
        // Try to get API key from Authorization header or X-Talabat-API-Key header
        $apiKey = $request->bearerToken() ?? $request->header('X-Talabat-API-Key');

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

        // Set tenant context before getting credentials
        app()->instance('tenant', $tenantModel);

        // Get stored Talabat API key from database for this tenant
        $credentials = $this->apiCredentialRepository->getActiveCredentials('talabat');
        $storedApiKey = $credentials['api_key'] ?? null;

        // If no API key provided or no stored key, reject
        if (! $apiKey || ! $storedApiKey) {
            abort(401, 'API key not provided or not configured for this tenant.');
        }

        // Verify API key matches
        if (! hash_equals($storedApiKey, $apiKey)) {
            abort(401, 'Invalid API key.');
        }

        return $next($request);
    }
}
