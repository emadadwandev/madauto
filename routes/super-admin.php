<?php

use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\SystemController;
use App\Http\Controllers\SuperAdmin\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
|
| These routes are loaded for the super admin subdomain (admin.yourapp.com)
| and protected by the EnsureSuperAdmin middleware.
|
*/

Route::middleware(['auth', 'verified', 'super-admin'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('super-admin.dashboard');

    // Tenant Management
    Route::prefix('tenants')->name('super-admin.tenants.')->group(function () {
        Route::get('/', [TenantController::class, 'index'])->name('index');
        Route::get('/create', [TenantController::class, 'create'])->name('create');
        Route::post('/', [TenantController::class, 'store'])->name('store');
        Route::get('/{tenant}', [TenantController::class, 'show'])->name('show');
        Route::get('/{tenant}/edit', [TenantController::class, 'edit'])->name('edit');
        Route::put('/{tenant}', [TenantController::class, 'update'])->name('update');
        Route::post('/{tenant}/suspend', [TenantController::class, 'suspend'])->name('suspend');
        Route::post('/{tenant}/activate', [TenantController::class, 'activate'])->name('activate');
        Route::delete('/{tenant}', [TenantController::class, 'destroy'])->name('destroy');
        Route::post('/{tenant}/impersonate', [TenantController::class, 'impersonate'])->name('impersonate');
        Route::post('/stop-impersonating', [TenantController::class, 'stopImpersonating'])->name('stop-impersonating');
    });

    // Subscription Management
    Route::prefix('subscriptions')->name('super-admin.subscriptions.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('show');
        Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('resume');
        Route::post('/{subscription}/change-plan', [SubscriptionController::class, 'changePlan'])->name('change-plan');
        Route::post('/{subscription}/extend-trial', [SubscriptionController::class, 'extendTrial'])->name('extend-trial');
    });

    // System Monitoring & Health
    Route::prefix('system')->name('super-admin.system.')->group(function () {
        Route::get('/', [SystemController::class, 'index'])->name('index');

        // Failed Jobs
        Route::get('/failed-jobs', [SystemController::class, 'failedJobs'])->name('failed-jobs');
        Route::post('/failed-jobs/retry', [SystemController::class, 'retryJob'])->name('retry-job');
        Route::delete('/failed-jobs/delete', [SystemController::class, 'deleteJob'])->name('delete-job');

        // Logs
        Route::get('/sync-logs', [SystemController::class, 'syncLogs'])->name('sync-logs');
        Route::get('/webhook-logs', [SystemController::class, 'webhookLogs'])->name('webhook-logs');
        Route::get('/logs', [SystemController::class, 'applicationLogs'])->name('logs');

        // Actions
        Route::post('/clear-cache', [SystemController::class, 'clearCache'])->name('clear-cache');
        Route::get('/queue-status', [SystemController::class, 'queueStatus'])->name('queue-status');
    });
});
