<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // API routes (no domain restriction)
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            $domain = config('app.domain', 'localhost');
            $adminSubdomain = config('app.admin_subdomain', 'admin');

            // Super Admin Routes - admin.localhost (ONLY)
            Route::domain("{$adminSubdomain}.{$domain}")
                ->middleware('web')
                ->group(function () {
                    // Include auth routes for super admin domain
                    require base_path('routes/auth.php');
                    // Include super admin specific routes
                    require base_path('routes/super-admin.php');
                });

            // Public/Landing Routes - main domain and www (NO tenant subdomains, NO admin)
            Route::domain($domain)
                ->middleware('web')
                ->group(base_path('routes/web.php'));

            // Allow 127.0.0.1 for local development to access landing page
            if ($domain === 'localhost') {
                Route::domain('127.0.0.1')
                    ->middleware('web')
                    ->group(base_path('routes/web.php'));
            }

            Route::domain("www.{$domain}")
                ->middleware('web')
                ->group(base_path('routes/web.php'));

            // Tenant Routes - {subdomain}.localhost
            // Will match any subdomain that hasn't been matched above (admin, www already matched)
            Route::domain("{subdomain}.{$domain}")
                ->middleware(['web', 'identify.tenant', 'debug.auth'])
                ->group(base_path('routes/tenant.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verify.webhook.signature' => \App\Http\Middleware\VerifyWebhookSignature::class,
            'verify.talabat.apikey' => \App\Http\Middleware\VerifyTalabatApiKey::class,
            'super-admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'identify.tenant' => \App\Http\Middleware\IdentifyTenant::class,
            'debug.auth' => \App\Http\Middleware\DebugAuthentication::class,
        ]);

        // Redirect unauthenticated users to /login (relative path, preserves subdomain)
        $middleware->redirectGuestsTo(fn () => '/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
