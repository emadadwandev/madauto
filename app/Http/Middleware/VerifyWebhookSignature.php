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

        // REQUIRED: Verify x-careem-api-key (as per Careem documentation)
        $apiKey = $request->header('x-careem-api-key');
        if (! $apiKey || $apiKey !== $tenantModel->careem_api_key) {
            abort(401, 'Invalid or missing x-careem-api-key header.');
        }

        // Set tenant context before getting credentials
        app()->instance('tenant', $tenantModel);

        // OPTIONAL: Verify webhook signature if webhook_secret is configured
        // Note: This is additional security not mentioned in Careem's official docs
        $credentials = $this->apiCredentialRepository->getActiveCredentials('careem');

        if ($credentials && isset($credentials['webhook_secret']) && ! empty($credentials['webhook_secret'])) {
            $signature = $request->header('X-Careem-Signature');
            $secret = $credentials['webhook_secret'];

            if (! $signature) {
                \Log::warning('Webhook secret is configured but X-Careem-Signature header is missing', [
                    'tenant' => $tenant,
                    'url' => $request->fullUrl(),
                ]);
                abort(401, 'Webhook signature verification enabled but X-Careem-Signature header not provided.');
            }

            $payload = $request->getContent();
            $computedSignature = hash_hmac('sha256', $payload, $secret);

            if (! hash_equals($signature, 'sha256='.$computedSignature)) {
                \Log::warning('Invalid webhook signature', [
                    'tenant' => $tenant,
                    'expected' => 'sha256='.$computedSignature,
                    'received' => $signature,
                ]);
                abort(401, 'Invalid webhook signature.');
            }

            \Log::info('Webhook signature verified successfully', ['tenant' => $tenant]);
        } else {
            \Log::info('Webhook signature verification skipped (no secret configured)', ['tenant' => $tenant]);
        }

        return $next($request);
    }
}
