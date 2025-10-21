<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\LoyverseApiService;
use App\Services\OrderTransformerService;
use App\Services\UsageTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncToLoyverseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    public $timeout = 60;

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
    public function handle(LoyverseApiService $loyverseApiService, OrderTransformerService $orderTransformerService): void
    {
        try {
            // Update order status to processing
            $this->order->update(['status' => 'processing']);

            // Transform order (this will log transformation steps)
            $transformedOrder = $orderTransformerService->transform($this->order->order_data, $this->order->id);

            // Create receipt in Loyverse
            $loyverseOrder = $loyverseApiService->createReceipt($transformedOrder);

            // Store Loyverse order details
            $this->order->loyverseOrder()->create([
                'loyverse_order_id' => $loyverseOrder['id'],
                'loyverse_receipt_number' => $loyverseOrder['receipt_number'] ?? null,
                'sync_status' => 'success',
                'sync_response' => $loyverseOrder,
                'synced_at' => now(),
            ]);

            // Update order status
            $this->order->update(['status' => 'synced']);

            // Log success
            \App\Models\SyncLog::logSuccess(
                $this->order->id,
                'loyverse_sync',
                'Order synced to Loyverse successfully',
                [
                    'loyverse_order_id' => $loyverseOrder['id'],
                    'loyverse_receipt_number' => $loyverseOrder['receipt_number'] ?? null,
                ]
            );

            // Track usage for subscription limits
            if ($this->order->tenant_id) {
                $usageTrackingService = app(UsageTrackingService::class);
                $usageTrackingService->recordOrder($this->order->tenant);
            }

        } catch (\App\Exceptions\LoyverseApiException $e) {
            // Handle Loyverse API specific errors
            $this->handleLoyverseApiException($e);
        } catch (\Exception $e) {
            // Handle general exceptions
            $this->handleGeneralException($e);
        }
    }

    /**
     * Handle Loyverse API exceptions.
     */
    protected function handleLoyverseApiException(\App\Exceptions\LoyverseApiException $e): void
    {
        // Log the failure
        \App\Models\SyncLog::logFailure(
            $this->order->id,
            'loyverse_sync',
            'Loyverse API error: '.$e->getMessage(),
            [
                'error_code' => $e->getErrorCode(),
                'status_code' => $e->getCode(),
                'error_data' => $e->getErrorData(),
            ]
        );

        // Store failed sync attempt
        $this->order->loyverseOrder()->create([
            'sync_status' => 'failed',
            'sync_response' => [
                'error' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'status_code' => $e->getCode(),
            ],
        ]);

        // Update order status
        $this->order->update(['status' => 'failed']);

        // If it's a rate limit error, release job back to queue
        if ($e->isRateLimitError()) {
            $retryAfter = $e->getRetryAfter() ?? 60;
            $this->release($retryAfter);

            return;
        }

        // If it's a server error, retry with backoff
        if ($e->isServerError()) {
            throw $e; // Let Laravel's retry mechanism handle it
        }

        // For validation and auth errors, fail the job permanently
        $this->fail($e);
    }

    /**
     * Handle general exceptions.
     */
    protected function handleGeneralException(\Exception $e): void
    {
        // Log the failure
        \App\Models\SyncLog::logFailure(
            $this->order->id,
            'loyverse_sync',
            'Sync failed: '.$e->getMessage(),
            [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]
        );

        // Store failed sync attempt
        $this->order->loyverseOrder()->create([
            'sync_status' => 'failed',
            'sync_response' => [
                'error' => $e->getMessage(),
            ],
        ]);

        // Update order status
        $this->order->update(['status' => 'failed']);

        // Fail the job (will trigger retry if attempts remain)
        throw $e;
    }
}
