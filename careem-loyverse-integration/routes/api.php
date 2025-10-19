<?php

use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/careem', [WebhookController::class, 'handleCareem'])->middleware('verify.webhook.signature');
Route::post('/webhook/talabat', [WebhookController::class, 'handleTalabat'])->middleware('verify.talabat.apikey');
