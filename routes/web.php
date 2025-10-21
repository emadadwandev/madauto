<?php

use App\Http\Controllers\Dashboard\InvitationController;
use App\Http\Controllers\Landing\LandingController;
use App\Http\Controllers\Landing\RegistrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Web Routes
|--------------------------------------------------------------------------
|
| These routes are loaded for the main domain (localhost, yourapp.test)
| and www subdomain. These are PUBLIC routes accessible to everyone.
| Tenant-specific routes are in routes/tenant.php
| Super admin routes are in routes/super-admin.php
|
*/

// Landing Page Routes (Public)
Route::get('/', [LandingController::class, 'index'])->name('landing.index');
Route::get('/pricing', [LandingController::class, 'pricing'])->name('landing.pricing');

// Registration Routes (Public)
Route::get('/register', [RegistrationController::class, 'create'])->name('landing.register');
Route::post('/register', [RegistrationController::class, 'store'])->name('landing.register.store');

// Subdomain availability check (AJAX)
Route::get('/api/check-subdomain', [RegistrationController::class, 'checkSubdomain'])->name('api.check-subdomain');

// Public invitation routes (no authentication required)
Route::prefix('invitations')->name('invitations.')->group(function () {
    Route::get('/{token}', [InvitationController::class, 'show'])->name('show');
    Route::post('/{token}/accept', [InvitationController::class, 'accept'])->name('accept');
});

// Include authentication routes (login, logout, password reset, etc.)
require __DIR__.'/auth.php';
