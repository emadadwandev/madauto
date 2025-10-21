<?php

use App\Models\Tenant;
use App\Services\TenantContext;

if (! function_exists('tenant')) {
    /**
     * Get the current tenant instance.
     */
    function tenant(): ?Tenant
    {
        return app(TenantContext::class)->get();
    }
}

if (! function_exists('tenantId')) {
    /**
     * Get the current tenant ID.
     */
    function tenantId(): ?string
    {
        return app(TenantContext::class)->id();
    }
}

if (! function_exists('hasTenant')) {
    /**
     * Check if a tenant is set in context.
     */
    function hasTenant(): bool
    {
        return app(TenantContext::class)->has();
    }
}

if (! function_exists('isSuperAdmin')) {
    /**
     * Check if the current user is a super admin.
     */
    function isSuperAdmin(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }
}

if (! function_exists('isTenantAdmin')) {
    /**
     * Check if the current user is a tenant admin.
     */
    function isTenantAdmin($tenantId = null): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $tenantId = $tenantId ?? tenantId();

        return auth()->user()->isTenantAdmin($tenantId);
    }
}

if (! function_exists('isTenantUser')) {
    /**
     * Check if the current user is a tenant user (read-only).
     */
    function isTenantUser($tenantId = null): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $tenantId = $tenantId ?? tenantId();

        return auth()->user()->isTenantUser($tenantId);
    }
}

if (! function_exists('canManageTenant')) {
    /**
     * Check if the current user can manage the tenant (super admin or tenant admin).
     */
    function canManageTenant($tenantId = null): bool
    {
        if (! auth()->check()) {
            return false;
        }

        if (isSuperAdmin()) {
            return true;
        }

        return isTenantAdmin($tenantId);
    }
}

if (! function_exists('tenantUrl')) {
    /**
     * Generate a URL for the current tenant's subdomain.
     */
    function tenantUrl(string $path = '', $tenant = null): string
    {
        $tenant = $tenant ?? tenant();

        if (! $tenant) {
            return url($path);
        }

        $appDomain = config('app.domain', 'localhost');
        $protocol = config('app.url_scheme', 'https');

        $url = "{$protocol}://{$tenant->subdomain}.{$appDomain}";

        if ($path) {
            $url .= '/'.ltrim($path, '/');
        }

        return $url;
    }
}

if (! function_exists('superAdminUrl')) {
    /**
     * Generate a URL for the super admin subdomain.
     */
    function superAdminUrl(string $path = ''): string
    {
        $appDomain = config('app.domain', 'localhost');
        $protocol = config('app.url_scheme', 'https');

        $url = "{$protocol}://admin.{$appDomain}";

        if ($path) {
            $url .= '/'.ltrim($path, '/');
        }

        return $url;
    }
}

if (! function_exists('landingUrl')) {
    /**
     * Generate a URL for the landing page.
     */
    function landingUrl(string $path = ''): string
    {
        $appDomain = config('app.domain', 'localhost');
        $protocol = config('app.url_scheme', 'https');

        $url = "{$protocol}://{$appDomain}";

        if ($path) {
            $url .= '/'.ltrim($path, '/');
        }

        return $url;
    }
}
