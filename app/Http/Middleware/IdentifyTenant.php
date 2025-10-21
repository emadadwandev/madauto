<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(TenantContext::class);

        // Extract subdomain from request
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        // Log tenant identification process
        \Log::info('IdentifyTenant Debug', [
            'host' => $host,
            'extracted_subdomain' => $subdomain,
            'url' => $request->url(),
            'method' => $request->method(),
        ]);

        // Skip tenant detection for main/www/admin subdomains
        if (in_array($subdomain, ['www', 'admin', null])) {
            \Log::info('IdentifyTenant: Skipping tenant detection', ['subdomain' => $subdomain]);
            return $next($request);
        }

        // Find tenant by subdomain
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        if (! $tenant) {
            \Log::error('IdentifyTenant: Tenant not found', ['subdomain' => $subdomain]);
            abort(404, 'Tenant not found');
        }

        // Check if tenant is active
        if ($tenant->isSuspended()) {
            \Log::error('IdentifyTenant: Tenant suspended', ['tenant_id' => $tenant->id]);
            abort(403, 'This account has been suspended. Please contact support.');
        }

        if ($tenant->status === 'cancelled') {
            \Log::error('IdentifyTenant: Tenant cancelled', ['tenant_id' => $tenant->id]);
            abort(403, 'This account has been cancelled.');
        }

        // Set tenant in context
        $tenantContext->set($tenant);

        // Share tenant with views
        view()->share('tenant', $tenant);

        \Log::info('IdentifyTenant: Tenant context set successfully', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'tenant_subdomain' => $tenant->subdomain,
        ]);

        return $next($request);
    }

    /**
     * Extract subdomain from host.
     */
    public function extractSubdomain(string $host): ?string
    {
        // Remove port number if present
        $hostWithoutPort = explode(':', $host)[0];

        // Get the configured app domain
        $appDomain = config('app.domain', 'localhost');

        // Remove app domain from host
        $subdomain = str_replace('.'.$appDomain, '', $hostWithoutPort);

        // If subdomain equals host, no subdomain was found
        if ($subdomain === $hostWithoutPort || $subdomain === $appDomain) {
            return null;
        }

        return $subdomain;
    }
}
