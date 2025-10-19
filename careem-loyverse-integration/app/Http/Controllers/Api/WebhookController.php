<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CareemOrderRequest;
use App\Http\Requests\TalabatOrderRequest;
use Illuminate\Http\Request;

use App\Models\WebhookLog;

use App\Jobs\ProcessCareemOrderJob;
use App\Jobs\ProcessTalabatOrderJob;

class WebhookController extends Controller
{
    /**
     * Handle incoming Careem Now webhook
     */
    public function handleCareem(CareemOrderRequest $request)
    {
        WebhookLog::create([
            'payload' => array_merge($request->all(), ['platform' => 'careem']),
            'headers' => $request->header(),
            'status' => 'received',
        ]);

        ProcessCareemOrderJob::dispatch($request->validated());

        return response()->json(['success' => true, 'message' => 'Careem order received and queued for processing']);
    }

    /**
     * Handle incoming Talabat webhook
     */
    public function handleTalabat(TalabatOrderRequest $request)
    {
        WebhookLog::create([
            'payload' => array_merge($request->all(), ['platform' => 'talabat']),
            'headers' => $request->header(),
            'status' => 'received',
        ]);

        ProcessTalabatOrderJob::dispatch($request->validated());

        return response()->json(['success' => true, 'message' => 'Talabat order received and queued for processing']);
    }
}
