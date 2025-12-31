<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCareemOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;

    protected $tenantId;

    /**
     * The queue that the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'high';

    /**
     * Create a new job instance.
     */
    public function __construct(array $payload, string $tenantId)
    {
        $this->payload = $payload;
        $this->tenantId = $tenantId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Set tenant context for this job
        $tenant = Tenant::findOrFail($this->tenantId);
        app()->instance('tenant', $tenant);

        // Extract order ID from webhook payload
        // Careem sends either direct order_id or nested in details.id
        $orderId = $this->payload['details']['id'] ?? $this->payload['id'] ?? $this->payload['order_id'] ?? null;

        if (! $orderId) {
            \Log::error('Careem order ID not found in webhook payload', [
                'payload' => $this->payload,
            ]);

            return;
        }

        // Create order record in database
        $order = Order::create([
            'tenant_id' => $this->tenantId,
            'careem_order_id' => $orderId,
            'order_data' => $this->payload,
            'status' => 'pending',
            'platform_status' => 'pending',
            'platform_status_updated_at' => now(),
        ]);

        \Log::info('Careem order created in database', [
            'order_id' => $order->id,
            'careem_order_id' => $orderId,
        ]);

        // AUTO-ACCEPT ORDER TO CAREEM (IF ENABLED)
        if ($tenant->getSetting('auto_accept_careem', false)) {
            try {
                // Get brand and branch IDs from tenant's Careem branch configuration
                $careemBranch = $tenant->careemBranches()
                    ->where('pos_integration_enabled', true)
                    ->first();

                if ($careemBranch) {
                    $brandId = $careemBranch->careem_brand_id;
                    $branchId = $careemBranch->careem_branch_id;

                    // Initialize Careem API service
                    $careemService = new \App\Services\CareemApiService($this->tenantId);

                    // Accept the order
                    $acceptResponse = $careemService->acceptOrder(
                        (string) $orderId,
                        $brandId,
                        $branchId
                    );

                    \Log::info('Order auto-accepted in Careem', [
                        'order_id' => $order->id,
                        'careem_order_id' => $orderId,
                        'response' => $acceptResponse,
                    ]);

                    // Update order with acceptance metadata
                    $order->update([
                        'order_data' => array_merge($this->payload, [
                            'acceptance_response' => $acceptResponse,
                            'acceptance_timestamp' => now()->toIso8601String(),
                        ]),
                        'status' => 'processing',
                        'platform_status' => 'accepted',
                        'platform_status_updated_at' => now(),
                    ]);
                } else {
                    \Log::warning('No active Careem branch found for tenant - cannot auto-accept order', [
                        'tenant_id' => $this->tenantId,
                        'order_id' => $order->id,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to auto-accept order in Careem', [
                    'order_id' => $order->id,
                    'careem_order_id' => $orderId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Don't fail the job - continue with Loyverse sync even if acceptance fails
                // The restaurant can manually accept via Careem dashboard/app
            }
        }

        // Continue with Loyverse sync
        SyncToLoyverseJob::dispatch($order);
    }
}
