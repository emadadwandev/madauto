<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $tenantContext = app(TenantContext::class);
        $tenant = $tenantContext->get();

        if (! $tenant) {
            abort(403, 'Tenant context not available.');
        }

        $user = $request->user();

        // Super admins can access all tenant admin areas
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user is tenant admin for this specific tenant
        if (! $user->isTenantAdmin($tenant->id)) {
            abort(403, 'Unauthorized. Tenant admin access required.');
        }

        // Ensure user belongs to this tenant
        if (! $user->belongsToTenant($tenant->id)) {
            abort(403, 'You do not belong to this tenant.');
        }

        return $next($request);
    }
}
