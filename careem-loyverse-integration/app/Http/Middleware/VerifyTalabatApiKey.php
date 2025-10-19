<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Repositories\ApiCredentialRepository;

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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Try to get API key from Authorization header or X-Talabat-API-Key header
        $apiKey = $request->bearerToken() ?? $request->header('X-Talabat-API-Key');

        // Get stored Talabat API key from database
        $credentials = $this->apiCredentialRepository->getActiveCredentials('talabat');
        $storedApiKey = $credentials['api_key'] ?? null;

        // If no API key provided or no stored key, reject
        if (!$apiKey || !$storedApiKey) {
            abort(401, 'API key not provided or not configured.');
        }

        // Verify API key matches
        if (!hash_equals($storedApiKey, $apiKey)) {
            abort(401, 'Invalid API key.');
        }

        return $next($request);
    }
}
