<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\CareemApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Mark a Careem order as ready for pickup
 *
 * This job is dispatched after an order has been successfully synced to Loyverse
 * and the tenant has auto_mark_ready_careem setting enabled
 */
class MarkCareemOrderReadyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * The queue that the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'default';

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if order is from Careem
        if (! isset($this->order->order_data['platform']) || $this->order->order_data['platform'] !== 'careem') {
            \Log::warning('Attempted to mark non-Careem order as ready', [
                'order_id' => $this->order->id,
            ]);

            return;
        }

        $tenant = $this->order->tenant;

        // Check if auto-mark-ready is enabled
        if (! $tenant->getSetting('auto_mark_ready_careem', false)) {
            return;
        }

        try {
            // Get brand and branch IDs
            $careemBranch = $tenant->careemBranches()
                ->where('pos_integration_enabled', true)
                ->first();

            if (! $careemBranch) {
                \Log::warning('No active Careem branch found for tenant', [
                    'tenant_id' => $tenant->id,
                    'order_id' => $this->order->id,
                ]);

                return;
            }

            $brandId = $careemBranch->careem_brand_id;
            $branchId = $careemBranch->careem_branch_id;

            // Extract order ID
            $orderId = $this->order->order_data['details']['id']
                ?? $this->order->order_data['id']
                ?? $this->order->careem_order_id;

            // Initialize Careem API service
            $careemService = new CareemApiService($tenant->id);

            // Mark order as ready
            $response = $careemService->markOrderReady(
                (string) $orderId,
                $brandId,
                $branchId
            );

            \Log::info('Order marked as ready in Careem', [
                'order_id' => $this->order->id,
                'careem_order_id' => $orderId,
                'response' => $response,
            ]);

            // Update order data with ready status
            $this->order->update([
                'order_data' => array_merge($this->order->order_data, [
                    'ready_response' => $response,
                    'ready_timestamp' => now()->toIso8601String(),
                ]),
                'platform_status' => 'ready',
                'platform_status_updated_at' => now(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to mark order as ready in Careem', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't fail the job - this is not critical
            // The restaurant can manually mark as ready via Careem dashboard/app
        }
    }
}
