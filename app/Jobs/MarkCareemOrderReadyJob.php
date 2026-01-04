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

    protected int $orderId;

    /**
     * The queue that the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'default';

    /**
     * Create a new job instance.
     */
    public function __construct(Order|int $order)
    {
        $this->orderId = $order instanceof Order ? $order->id : $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Fetch the order from database
        $order = Order::findOrFail($this->orderId);

        // Check if order is from Careem
        if (! isset($order->order_data['platform']) || $order->order_data['platform'] !== 'careem') {
            \Log::warning('Attempted to mark non-Careem order as ready', [
                'order_id' => $order->id,
            ]);

            return;
        }

        $tenant = $order->tenant;

        // Check if auto-mark-ready is enabled
        if (! $tenant->getSetting('auto_mark_ready_careem', false)) {
            return;
        }

        try {
            // Get branch information from order data (same as accept method)
            $orderData = is_string($order->order_data) ? json_decode($order->order_data, true) : $order->order_data;
            $orderBranchId = $orderData['branch']['id'] ?? null;

            if (! $orderBranchId) {
                \Log::warning('Order does not have branch information', [
                    'order_id' => $order->id,
                    'tenant_id' => $tenant->id,
                ]);

                return;
            }

            // Find the branch that matches the order's branch
            $careemBranch = \App\Models\CareemBranch::where('tenant_id', $tenant->id)
                ->where('careem_branch_id', $orderBranchId)
                ->first();

            if (! $careemBranch) {
                \Log::warning('Branch not found for order', [
                    'tenant_id' => $tenant->id,
                    'order_id' => $order->id,
                    'branch_id_from_order' => $orderBranchId,
                ]);

                return;
            }

            if (! $careemBranch->brand || ! $careemBranch->brand->careem_brand_id) {
                \Log::warning('Branch does not have a brand associated', [
                    'tenant_id' => $tenant->id,
                    'order_id' => $order->id,
                    'branch_id' => $careemBranch->id,
                ]);

                return;
            }

            $brandId = $careemBranch->brand->careem_brand_id;
            $branchId = $careemBranch->careem_branch_id;

            // Extract order ID
            $orderId = $order->order_data['details']['id']
                ?? $order->order_data['id']
                ?? $order->careem_order_id;

            // Initialize Careem API service
            $careemService = new CareemApiService($tenant->id);

            // Mark order as ready
            $response = $careemService->markOrderReady(
                (string) $orderId,
                $brandId,
                $branchId
            );

            \Log::info('Order marked as ready in Careem', [
                'order_id' => $order->id,
                'careem_order_id' => $orderId,
                'response' => $response,
            ]);

            // Update order data with ready status
            $order->update([
                'order_data' => array_merge($order->order_data, [
                    'ready_response' => $response,
                    'ready_timestamp' => now()->toIso8601String(),
                ]),
                'platform_status' => 'ready',
                'platform_status_updated_at' => now(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to mark order as ready in Careem', [
                'order_id' => $order->id,
                'careem_order_id' => $orderId ?? 'unknown',
                'brand_id' => $brandId ?? 'unknown',
                'branch_id' => $branchId ?? 'unknown',
                'current_platform_status' => $order->platform_status,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            // Check if it's a "bad request" error (order might already be ready or invalid state)
            if (str_contains($e->getMessage(), 'BADREQUEST_ERROR') ||
                str_contains($e->getMessage(), 'bad request')) {
                // This is likely because:
                // 1. Order is already ready
                // 2. Order state doesn't allow this transition
                // 3. Order acceptance hasn't propagated yet

                // If the local status shows it's already ready, skip it
                if ($order->platform_status === 'ready') {
                    \Log::info('Order already marked as ready locally, skipping Careem API call', [
                        'order_id' => $order->id,
                    ]);

                    return;
                }

                // For other bad request errors, log and don't retry
                \Log::warning('Careem rejected mark-ready request - order might be in invalid state', [
                    'order_id' => $order->id,
                    'suggestion' => 'Check if order is already ready in Careem dashboard or if state transition is invalid',
                ]);

                return;
            }

            // Don't fail the job - this is not critical
            // The restaurant can manually mark as ready via Careem dashboard/app
        }
    }
}
