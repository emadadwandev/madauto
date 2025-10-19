<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\OrderController;
use App\Http\Controllers\Dashboard\ProductMappingController;
use App\Http\Controllers\Dashboard\SyncLogController;
use App\Http\Controllers\Dashboard\ApiCredentialController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Protected routes - require authentication
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

    // Product Mappings
    Route::prefix('product-mappings')->name('product-mappings.')->group(function () {
        Route::get('/', [ProductMappingController::class, 'index'])->name('index');
        Route::get('/create', [ProductMappingController::class, 'create'])->name('create');
        Route::post('/', [ProductMappingController::class, 'store'])->name('store');
        Route::get('/{productMapping}/edit', [ProductMappingController::class, 'edit'])->name('edit');
        Route::put('/{productMapping}', [ProductMappingController::class, 'update'])->name('update');
        Route::delete('/{productMapping}', [ProductMappingController::class, 'destroy'])->name('destroy');
        Route::post('/{productMapping}/toggle', [ProductMappingController::class, 'toggle'])->name('toggle');
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
        Route::post('/{apiCredential}/toggle', [ApiCredentialController::class, 'toggle'])->name('toggle');
        Route::delete('/{apiCredential}', [ApiCredentialController::class, 'destroy'])->name('destroy');
    });

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
