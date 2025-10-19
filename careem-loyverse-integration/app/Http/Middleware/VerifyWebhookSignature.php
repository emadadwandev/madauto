<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use App\Repositories\ApiCredentialRepository;

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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-Careem-Signature');
        $credentials = $this->apiCredentialRepository->getActiveCredentials('careem');
        $secret = $credentials['webhook_secret'];

        if (!$signature || !$secret) {
            abort(401, 'Signature not provided.');
        }

        $payload = $request->getContent();
        $computedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($signature, 'sha256=' . $computedSignature)) {
            abort(401, 'Invalid signature.');
        }

        return $next($request);
    }
}
