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

if (! function_exists('currency')) {
    /**
     * Get currency configuration for the current tenant or a specific currency code.
     */
    function currency(?string $code = null): array
    {
        $code = $code ?? (tenant() ? tenant()->getCurrency() : config('currencies.default'));

        return config("currencies.supported.{$code}", config('currencies.supported.'.config('currencies.default')));
    }
}

if (! function_exists('currencySymbol')) {
    /**
     * Get the currency symbol for the current tenant or a specific currency code.
     */
    function currencySymbol(?string $code = null): string
    {
        $currencyConfig = currency($code);

        return $currencyConfig['symbol'] ?? '$';
    }
}

if (! function_exists('formatCurrency')) {
    /**
     * Format an amount with the tenant's currency.
     */
    function formatCurrency(float $amount, ?string $code = null, bool $showSymbol = true): string
    {
        $currencyConfig = currency($code);

        $formatted = number_format(
            $amount,
            $currencyConfig['decimals'] ?? 2,
            $currencyConfig['decimal_separator'] ?? '.',
            $currencyConfig['thousands_separator'] ?? ','
        );

        if (! $showSymbol) {
            return $formatted;
        }

        $symbol = $currencyConfig['symbol'] ?? '$';
        $position = $currencyConfig['symbol_position'] ?? 'before';

        if ($position === 'before') {
            return $symbol.' '.$formatted;
        }

        return $formatted.' '.$symbol;
    }
}

if (! function_exists('supportedCurrencies')) {
    /**
     * Get all supported currencies.
     */
    function supportedCurrencies(): array
    {
        return config('currencies.supported', []);
    }
}

if (! function_exists('supportedTimezones')) {
    /**
     * Get list of common timezones for the Middle East region and beyond.
     */
    function supportedTimezones(): array
    {
        return [
            'Asia/Dubai' => 'UAE (Dubai) - UTC+4',
            'Asia/Riyadh' => 'Saudi Arabia (Riyadh) - UTC+3',
            'Asia/Kuwait' => 'Kuwait - UTC+3',
            'Asia/Bahrain' => 'Bahrain - UTC+3',
            'Asia/Qatar' => 'Qatar - UTC+3',
            'Asia/Muscat' => 'Oman - UTC+4',
            'Asia/Amman' => 'Jordan - UTC+3',
            'Asia/Beirut' => 'Lebanon - UTC+2/+3',
            'Asia/Damascus' => 'Syria - UTC+2/+3',
            'Asia/Baghdad' => 'Iraq - UTC+3',
            'Africa/Cairo' => 'Egypt - UTC+2',
            'Asia/Kolkata' => 'India - UTC+5:30',
            'Asia/Karachi' => 'Pakistan - UTC+5',
            'Europe/London' => 'United Kingdom - UTC+0/+1',
            'Europe/Paris' => 'France - UTC+1/+2',
            'America/New_York' => 'US Eastern - UTC-5/-4',
            'America/Chicago' => 'US Central - UTC-6/-5',
            'America/Los_Angeles' => 'US Pacific - UTC-8/-7',
            'UTC' => 'UTC - Coordinated Universal Time',
        ];
    }
}
