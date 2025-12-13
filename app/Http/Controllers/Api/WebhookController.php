<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CareemOrderRequest;
use App\Http\Requests\TalabatOrderRequest;
use App\Jobs\ProcessCareemOrderJob;
use App\Jobs\ProcessTalabatOrderJob;
use App\Models\Tenant;
use App\Models\WebhookLog;

class WebhookController extends Controller
{
    /**
     * Handle incoming Careem Now webhook
     */
    public function handleCareem(CareemOrderRequest $request, string $tenant)
    {
        // Find tenant by subdomain
        $tenantModel = Tenant::where('subdomain', $tenant)->firstOrFail();

        // Set tenant context for this request
        app()->instance('tenant', $tenantModel);

        WebhookLog::create([
            'tenant_id' => $tenantModel->id,
            'payload' => array_merge($request->all(), ['platform' => 'careem']),
            'headers' => $request->header(),
            'status' => 'received',
        ]);

        ProcessCareemOrderJob::dispatch($request->validated(), $tenantModel->id);

        return response()->json(['success' => true, 'message' => 'Careem order received and queued for processing']);
    }

    /**
     * Handle incoming Talabat webhook
     */
    public function handleTalabat(TalabatOrderRequest $request, string $tenant)
    {
        // Find tenant by subdomain
        $tenantModel = Tenant::where('subdomain', $tenant)->firstOrFail();

        // Set tenant context for this request
        app()->instance('tenant', $tenantModel);

        WebhookLog::create([
            'tenant_id' => $tenantModel->id,
            'payload' => array_merge($request->all(), ['platform' => 'talabat']),
            'headers' => $request->header(),
            'status' => 'received',
        ]);

        ProcessTalabatOrderJob::dispatch($request->validated(), $tenantModel->id);

        return response()->json(['success' => true, 'message' => 'Talabat order received and queued for processing']);
    }
}
