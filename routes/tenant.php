<?php

use App\Http\Controllers\Dashboard\ApiCredentialController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\InvitationController;
use App\Http\Controllers\Dashboard\LocationController;
use App\Http\Controllers\Dashboard\MenuController;
use App\Http\Controllers\Dashboard\MenuItemController;
use App\Http\Controllers\Dashboard\ModifierController;
use App\Http\Controllers\Dashboard\ModifierGroupController;
use App\Http\Controllers\Dashboard\OnboardingController;
use App\Http\Controllers\Dashboard\OrderController;
use App\Http\Controllers\Dashboard\ProductMappingController;
use App\Http\Controllers\Dashboard\SubscriptionController;
use App\Http\Controllers\Dashboard\SyncLogController;
use App\Http\Controllers\Dashboard\TeamController;
use App\Http\Controllers\Dashboard\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| These routes are loaded for tenant subdomains (demo.localhost, etc.)
| All routes here are automatically scoped to the tenant via middleware.
|
*/

// Redirect root to dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});

// Health check route to verify tenant routing is working
Route::get('/health', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'status' => 'ok',
        'message' => 'Tenant routing is working',
        'host' => $request->getHost(),
        'timestamp' => now()->toISOString(),
    ]);
});

// Test route to check authentication requirements
Route::get('/test-auth', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'status' => 'ok',
        'message' => 'This route requires no authentication',
        'user' => auth()->user() ? [
            'id' => auth()->user()->id,
            'email' => auth()->user()->email,
            'email_verified' => auth()->user()->email_verified_at ? 'YES' : 'NO',
        ] : null,
        'is_authenticated' => auth()->check(),
        'host' => $request->getHost(),
        'timestamp' => now()->toISOString(),
    ]);
});

// Test route to check auth middleware
Route::middleware(['auth'])->get('/test-auth-required', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'status' => 'ok',
        'message' => 'This route requires auth only',
        'user' => [
            'id' => auth()->user()->id,
            'email' => auth()->user()->email,
            'email_verified' => auth()->user()->email_verified_at ? 'YES' : 'NO',
        ],
        'host' => $request->getHost(),
        'timestamp' => now()->toISOString(),
    ]);
});

// Test route to check auth + verified middleware
Route::middleware(['auth', 'verified'])->get('/test-verified-required', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'status' => 'ok',
        'message' => 'This route requires auth + verified',
        'user' => [
            'id' => auth()->user()->id,
            'email' => auth()->user()->email,
            'email_verified' => auth()->user()->email_verified_at ? 'YES' : 'NO',
        ],
        'host' => $request->getHost(),
        'timestamp' => now()->toISOString(),
    ]);
});

// Debug route to check session and tenant context
Route::get('/debug-session', function (\Illuminate\Http\Request $request) {
    $middleware = new \App\Http\Middleware\IdentifyTenant();

    $tenantContext = app()->bound(\App\Services\TenantContext::class) ?
        app(\App\Services\TenantContext::class)->get() : null;

    return response()->json([
        'status' => 'success',
        'host' => $request->getHost(),
        'url' => $request->url(),
        'session_id' => session()->getId(),
        'csrf_token' => csrf_token(),
        'session_domain' => config('session.domain'),
        'session_driver' => config('session.driver'),
        'subdomain_detection' => $middleware->extractSubdomain($request->getHost()),
        'tenant_context' => $tenantContext ? [
            'id' => $tenantContext->id,
            'name' => $tenantContext->name,
            'subdomain' => $tenantContext->subdomain,
            'status' => $tenantContext->status,
        ] : null,
        'user' => auth()->user() ? [
            'id' => auth()->user()->id,
            'email' => auth()->user()->email,
            'tenant_id' => auth()->user()->tenant_id,
        ] : null,
        'route_info' => [
            'name' => $request->route()?->getName(),
            'parameters' => $request->route()?->parameters(),
        ],
        'session_data' => [
            'intended_url' => session('url.intended'),
            'previous_url' => session('_previous.url'),
        ],
    ], 200, [], JSON_PRETTY_PRINT);
});

// Manual authentication test route
Route::get('/test-manual-auth/{userId}', function (\Illuminate\Http\Request $request) {
    try {
        // Get userId from route parameters
        $userId = $request->route('userId');

        // Debug: Show all users first
        $allUsers = \App\Models\User::all(['id', 'email', 'tenant_id']);

        // Find the user
        $user = \App\Models\User::find((int)$userId);
        if (!$user) {
            return response()->json([
                'error' => 'User not found',
                'user_id_received' => $userId,
                'user_id_as_int' => (int)$userId,
                'route_params' => $request->route()->parameters(),
                'all_users' => $allUsers->toArray(),
            ]);
        }

        // Get current auth status before
        $beforeAuth = [
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
        ];

        // Manual login
        \Auth::login($user);

        // Get auth status after
        $afterAuth = [
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
        ];

        // Save session
        session()->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Manual authentication attempted',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'tenant_id' => $user->tenant_id,
            ],
            'before_auth' => $beforeAuth,
            'after_auth' => $afterAuth,
        ], 200, [], JSON_PRETTY_PRINT);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Exception during manual auth',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500, [], JSON_PRETTY_PRINT);
    }
});

// Test route to check auth status after manual login
Route::get('/test-auth-status', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'status' => 'success',
        'authenticated' => auth()->check(),
        'user_id' => auth()->id(),
        'user' => auth()->user() ? [
            'id' => auth()->user()->id,
            'email' => auth()->user()->email,
            'tenant_id' => auth()->user()->tenant_id,
        ] : null,
        'session_id' => session()->getId(),
        'timestamp' => now()->toISOString(),
    ], 200, [], JSON_PRETTY_PRINT);
});

// Test route to check session storage
Route::get('/test-session-debug', function (\Illuminate\Http\Request $request) {
    $sessionId = session()->getId();

    // Check if session exists in database
    $sessionInDb = \DB::table('sessions')->where('id', $sessionId)->first();

    // Get recent sessions
    $recentSessions = \DB::table('sessions')
        ->orderBy('last_activity', 'desc')
        ->take(5)
        ->get(['id', 'user_id', 'ip_address', 'last_activity'])
        ->map(function($s) {
            return [
                'id' => $s->id,
                'user_id' => $s->user_id,
                'ip_address' => $s->ip_address,
                'last_activity' => date('Y-m-d H:i:s', $s->last_activity),
            ];
        });

    return response()->json([
        'status' => 'success',
        'current_session_id' => $sessionId,
        'session_exists_in_db' => $sessionInDb ? 'YES' : 'NO',
        'session_data_in_db' => $sessionInDb,
        'total_sessions_in_db' => \DB::table('sessions')->count(),
        'recent_sessions' => $recentSessions->toArray(),
        'authenticated' => auth()->check(),
        'user_id' => auth()->id(),
        'request_cookies' => $request->cookies->all(),
        'session_config' => [
            'driver' => config('session.driver'),
            'domain' => config('session.domain'),
            'path' => config('session.path'),
            'cookie' => config('session.cookie'),
            'secure' => config('session.secure'),
            'http_only' => config('session.http_only'),
            'same_site' => config('session.same_site'),
        ],
    ], 200, [], JSON_PRETTY_PRINT);
});

// Test session persistence with simple data
Route::get('/test-session-set/{value}', function (\Illuminate\Http\Request $request, $value) {
    session(['test_value' => $value]);
    session()->save(); // Force save

    return response()->json([
        'status' => 'success',
        'message' => 'Session value set',
        'session_id' => session()->getId(),
        'value_set' => $value,
        'value_retrieved' => session('test_value'),
        'all_session_data' => session()->all(),
    ], 200, [], JSON_PRETTY_PRINT);
});

Route::get('/test-session-get', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'status' => 'success',
        'session_id' => session()->getId(),
        'test_value' => session('test_value'),
        'all_session_data' => session()->all(),
        'request_cookies' => $request->cookies->all(),
    ], 200, [], JSON_PRETTY_PRINT);
});

// Protected routes - require authentication AND tenant context
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

    // Locations
    Route::prefix('dashboard/locations')->name('dashboard.locations.')->group(function () {
        Route::get('/', [LocationController::class, 'index'])->name('index');
        Route::get('/create', [LocationController::class, 'create'])->name('create');
        Route::post('/', [LocationController::class, 'store'])->name('store');
        Route::get('/{location}/edit', [LocationController::class, 'edit'])->name('edit');
        Route::put('/{location}', [LocationController::class, 'update'])->name('update');
        Route::delete('/{location}', [LocationController::class, 'destroy'])->name('destroy');
        Route::patch('/{location}/toggle', [LocationController::class, 'toggle'])->name('toggle');
        Route::patch('/{location}/toggle-busy', [LocationController::class, 'toggleBusy'])->name('toggle-busy');

        // Platform sync routes
        Route::post('/{location}/sync-status', [LocationController::class, 'syncStatus'])->name('sync-status');
        Route::post('/{location}/sync-hours', [LocationController::class, 'syncHours'])->name('sync-hours');
    });

    // Product Mappings
    Route::prefix('product-mappings')->name('product-mappings.')->group(function () {
        Route::get('/', [ProductMappingController::class, 'index'])->name('index');
        Route::get('/create', [ProductMappingController::class, 'create'])->name('create');
        Route::post('/', [ProductMappingController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ProductMappingController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ProductMappingController::class, 'update'])->name('update');
        // Use {id} instead of {productMapping} to bypass implicit route model binding which causes 404s with global scopes
        Route::delete('/{id}', [ProductMappingController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle', [ProductMappingController::class, 'toggle'])->name('toggle');
        Route::post('/auto-map', [ProductMappingController::class, 'autoMap'])->name('auto-map');
        Route::post('/import', [ProductMappingController::class, 'import'])->name('import');
        Route::get('/export', [ProductMappingController::class, 'export'])->name('export');
        Route::post('/clear-cache', [ProductMappingController::class, 'clearCache'])->name('clear-cache');
    });

    // Sync Logs
    Route::prefix('sync-logs')->name('sync-logs.')->group(function () {
        Route::get('/', [SyncLogController::class, 'index'])->name('index');
        Route::get('/{syncLog}', [SyncLogController::class, 'show'])->name('show');
        Route::post('/retry/{order}', [SyncLogController::class, 'retry'])->name('retry');
        Route::post('/retry-all', [SyncLogController::class, 'retryAll'])->name('retry-all');
    });

    // API Credentials
    Route::prefix('api-credentials')->name('api-credentials.')->group(function () {
        Route::get('/', [ApiCredentialController::class, 'index'])->name('index');
        Route::post('/', [ApiCredentialController::class, 'store'])->name('store');
        Route::post('/test-connection', [ApiCredentialController::class, 'testConnection'])->name('test-connection');

        // Platform Catalog API credentials
        Route::post('/careem-catalog', [ApiCredentialController::class, 'storeCareemCatalog'])->name('careem-catalog.store');
        Route::post('/careem-catalog/test', [ApiCredentialController::class, 'testCareemConnection'])->name('careem-catalog.test');
        Route::post('/talabat-catalog', [ApiCredentialController::class, 'storeTalabatCatalog'])->name('talabat-catalog.store');
        Route::post('/talabat-catalog/test', [ApiCredentialController::class, 'testTalabatConnection'])->name('talabat-catalog.test');

        Route::post('/{apiCredential}/toggle', [ApiCredentialController::class, 'toggle'])->name('toggle');
        Route::delete('/{apiCredential}', [ApiCredentialController::class, 'destroy'])->name('destroy');
    });

    // Team Invitations
    Route::prefix('dashboard/invitations')->name('dashboard.invitations.')->group(function () {
        Route::get('/', [InvitationController::class, 'index'])->name('index');
        Route::get('/create', [InvitationController::class, 'create'])->name('create');
        Route::post('/', [InvitationController::class, 'store'])->name('store');
        Route::post('/{invitation}/resend', [InvitationController::class, 'resend'])->name('resend');
        Route::delete('/{invitation}', [InvitationController::class, 'destroy'])->name('destroy');
    });

    // Subscription & Billing
    Route::prefix('dashboard/subscription')->name('dashboard.subscription.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/plans', [SubscriptionController::class, 'plans'])->name('plans');
        Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscribe');
        Route::post('/change-plan', [SubscriptionController::class, 'changePlan'])->name('change-plan');
        Route::post('/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/resume', [SubscriptionController::class, 'resume'])->name('resume');
        Route::get('/billing-history', [SubscriptionController::class, 'billingHistory'])->name('billing-history');
        Route::get('/payment-methods', [SubscriptionController::class, 'paymentMethods'])->name('payment-methods');
        Route::post('/checkout-session', [SubscriptionController::class, 'checkoutSession'])->name('checkout-session');
    });

    // Onboarding Wizard
    Route::prefix('dashboard/onboarding')->name('dashboard.onboarding.')->group(function () {
        Route::get('/', [OnboardingController::class, 'index'])->name('index');
        Route::post('/account-settings/save', [OnboardingController::class, 'saveAccountSettings'])->name('account-settings.save');
        Route::post('/location/save', [OnboardingController::class, 'saveLocation'])->name('location.save');
        Route::post('/loyverse/save', [OnboardingController::class, 'saveLoyverseToken'])->name('loyverse.save');
        Route::post('/webhook/generate', [OnboardingController::class, 'generateWebhookSecret'])->name('webhook.generate');
        Route::post('/careem-catalog/save', [OnboardingController::class, 'saveCareemCatalogCredentials'])->name('careem-catalog.save');
        Route::post('/talabat/save', [OnboardingController::class, 'saveTalabatCredentials'])->name('talabat.save');
        Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
        Route::get('/skip', [OnboardingController::class, 'skip'])->name('skip');
    });

    // Modifiers Management
    Route::prefix('dashboard/modifiers')->name('dashboard.modifiers.')->group(function () {
        Route::get('/', [ModifierController::class, 'index'])->name('index');
        Route::get('/create', [ModifierController::class, 'create'])->name('create');
        Route::post('/', [ModifierController::class, 'store'])->name('store');
        Route::get('/{modifier}/edit', [ModifierController::class, 'edit'])->name('edit');
        Route::put('/{modifier}', [ModifierController::class, 'update'])->name('update');
        Route::delete('/{modifier}', [ModifierController::class, 'destroy'])->name('destroy');
        Route::patch('/{modifier}/toggle', [ModifierController::class, 'toggle'])->name('toggle');
        Route::get('/sync-loyverse', [ModifierController::class, 'syncFromLoyverse'])->name('sync-loyverse');
    });

    // Modifier Groups Management
    Route::prefix('dashboard/modifier-groups')->name('dashboard.modifier-groups.')->group(function () {
        Route::get('/', [ModifierGroupController::class, 'index'])->name('index');
        Route::get('/create', [ModifierGroupController::class, 'create'])->name('create');
        Route::post('/', [ModifierGroupController::class, 'store'])->name('store');
        Route::get('/{modifierGroup}/edit', [ModifierGroupController::class, 'edit'])->name('edit');
        Route::put('/{modifierGroup}', [ModifierGroupController::class, 'update'])->name('update');
        Route::delete('/{modifierGroup}', [ModifierGroupController::class, 'destroy'])->name('destroy');
        Route::patch('/{modifierGroup}/toggle', [ModifierGroupController::class, 'toggle'])->name('toggle');
        Route::post('/reorder', [ModifierGroupController::class, 'reorder'])->name('reorder');
    });

    // Menu Management
    Route::prefix('dashboard/menus')->name('dashboard.menus.')->group(function () {
        Route::get('/', [MenuController::class, 'index'])->name('index');
        Route::get('/create', [MenuController::class, 'create'])->name('create');
        Route::post('/', [MenuController::class, 'store'])->name('store');
        Route::get('/{menu}', [MenuController::class, 'show'])->name('show');
        Route::get('/{menu}/edit', [MenuController::class, 'edit'])->name('edit');
        Route::put('/{menu}', [MenuController::class, 'update'])->name('update');
        Route::delete('/{menu}', [MenuController::class, 'destroy'])->name('destroy');
        Route::patch('/{menu}/toggle', [MenuController::class, 'toggle'])->name('toggle');
        Route::post('/{menu}/publish', [MenuController::class, 'publish'])->name('publish');
        Route::post('/{menu}/unpublish', [MenuController::class, 'unpublish'])->name('unpublish');
        Route::post('/{menu}/duplicate', [MenuController::class, 'duplicate'])->name('duplicate');

        // Menu Items
        Route::get('/{menu}/items/create', [MenuItemController::class, 'create'])->name('items.create');
        Route::post('/{menu}/items', [MenuItemController::class, 'store'])->name('items.store');
        Route::get('/{menu}/items/{menuItem}/edit', [MenuItemController::class, 'edit'])->name('items.edit');
        Route::put('/{menu}/items/{menuItem}', [MenuItemController::class, 'update'])->name('items.update');
        Route::delete('/{menu}/items/{menuItem}', [MenuItemController::class, 'destroy'])->name('items.destroy');
        Route::patch('/{menu}/items/{menuItem}/toggle-availability', [MenuItemController::class, 'toggleAvailability'])->name('items.toggle-availability');
        Route::post('/{menu}/items/reorder', [MenuItemController::class, 'reorder'])->name('items.reorder');
        Route::post('/{menu}/items/{menuItem}/duplicate', [MenuItemController::class, 'duplicate'])->name('items.duplicate');
    });

    // Notification Settings
    Route::prefix('dashboard/notifications')->name('dashboard.notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'show'])->name('show');
        Route::post('/update', [NotificationController::class, 'update'])->name('update');
    });

    // Team Management
    Route::prefix('dashboard/team')->name('dashboard.team.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::get('/activity-feed', [TeamController::class, 'getActivityFeed'])->name('activity-feed');
        Route::get('/{user}/activity', [TeamController::class, 'getUserActivity'])->name('activity');
        Route::patch('/{user}/edit-role', [TeamController::class, 'editRole'])->name('edit-role');
        Route::delete('/{user}/remove', [TeamController::class, 'removeUser'])->name('remove-user');
        Route::post('/invitations/{invitation}/resend', [TeamController::class, 'resendInvitation'])->name('invitations.resend');
    });

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Debug route to inspect DB directly
Route::get('/debug-db/{id}', function ($subdomain, $id) {
    $mapping = \App\Models\ProductMapping::withoutGlobalScopes()->find($id);
    $tenant = app(\App\Services\TenantContext::class)->get();

    return [
        'id_requested' => $id,
        'subdomain_param' => $subdomain,
        'mapping_found' => $mapping ? 'YES' : 'NO',
        'mapping_data' => $mapping,
        'current_tenant' => $tenant,
        'tenant_match' => ($mapping && $tenant && $mapping->tenant_id === $tenant->id) ? 'YES' : 'NO',
        'all_mappings_count' => \App\Models\ProductMapping::withoutGlobalScopes()->count(),
    ];
});// Include authentication routes for tenant subdomains
require __DIR__.'/auth.php';
