<?php

use App\Http\Controllers\Api\PlatformCallbackController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Tenant-specific webhook routes (incoming orders)
Route::post('/webhook/careem/{tenant}', [WebhookController::class, 'handleCareem'])->middleware('verify.webhook.signature');
Route::post('/webhook/talabat/{tenant}', [WebhookController::class, 'handleTalabat'])->middleware('verify.talabat.apikey');

// Platform callback routes (menu sync status updates)
Route::post('/callbacks/careem', [PlatformCallbackController::class, 'careem']);
Route::post('/callbacks/talabat', [PlatformCallbackController::class, 'talabat']);
