<?php

use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Tenant-specific webhook routes
Route::post('/webhook/careem/{tenant}', [WebhookController::class, 'handleCareem'])->middleware('verify.webhook.signature');
Route::post('/webhook/talabat/{tenant}', [WebhookController::class, 'handleTalabat'])->middleware('verify.talabat.apikey');
