<?php

namespace App\Jobs;

use App\Mail\SyncFailedEmail;
use App\Models\Order;
use App\Services\LoyverseApiService;
use App\Services\OrderTransformerService;
use App\Services\UsageTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SyncToLoyverseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $orderId;

    protected string $tenantId;

    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(Order|int $order)
    {
        // Accept either Order model or order ID
        if ($order instanceof Order) {
            $this->orderId = $order->id;
            $this->tenantId = $order->tenant_id;
        } else {
            $this->orderId = $order;
            // Tenant ID will be fetched when loading the order
            $this->tenantId = tenant()->id ?? null;
        }
    }

    /**
     * Execute the job.
     */
    public function handle(LoyverseApiService $loyverseApiService, OrderTransformerService $orderTransformerService): void
    {
        try {
            // Fetch the order from database
            $order = Order::findOrFail($this->orderId);

            // Update order status to processing
            $order->update(['status' => 'processing']);

            // Transform order (this will log transformation steps)
            $transformedOrder = $orderTransformerService->transform($order->order_data, $order->id);

            // Create receipt in Loyverse
            $loyverseOrder = $loyverseApiService->createReceipt($transformedOrder);

            // Store Loyverse order details
            $order->loyverseOrder()->create([
                'loyverse_order_id' => $loyverseOrder['id'],
                'loyverse_receipt_number' => $loyverseOrder['receipt_number'] ?? null,
                'sync_status' => 'success',
                'sync_response' => $loyverseOrder,
                'synced_at' => now(),
            ]);

            // Update order status
            $order->update(['status' => 'synced']);

            // Log success
            \App\Models\SyncLog::logSuccess(
                $order->id,
                'loyverse_sync',
                'Order synced to Loyverse successfully',
                [
                    'loyverse_order_id' => $loyverseOrder['id'],
                    'loyverse_receipt_number' => $loyverseOrder['receipt_number'] ?? null,
                ]
            );

            // Track usage for subscription limits
            if ($order->tenant_id) {
                $usageTrackingService = app(UsageTrackingService::class);
                $usageTrackingService->recordOrder($order->tenant);
            }

            // Mark order as ready in Careem (if auto-mark-ready is enabled)
            $platform = $order->order_data['platform'] ?? null;
            if ($platform === 'careem') {
                // Delay for 15 seconds to give Careem time to process the acceptance
                MarkCareemOrderReadyJob::dispatch($order)->delay(now()->addSeconds(15));
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
        // Fetch order for error handling
        $order = Order::find($this->orderId);
        if (! $order) {
            \Log::error('Order not found in handleLoyverseApiException', ['order_id' => $this->orderId]);

            return;
        }

        // Log the failure
        \App\Models\SyncLog::logFailure(
            $order->id,
            'loyverse_sync',
            'Loyverse API error: '.$e->getMessage(),
            [
                'error_code' => $e->getErrorCode(),
                'status_code' => $e->getCode(),
                'error_data' => $e->getErrorData(),
            ]
        );

        // Store failed sync attempt
        $order->loyverseOrder()->create([
            'sync_status' => 'failed',
            'sync_response' => [
                'error' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'status_code' => $e->getCode(),
            ],
        ]);

        // Update order status
        $order->update(['status' => 'failed']);

        // Send email notification if tenant has notifications enabled
        if ($order->tenant && $order->tenant->getSetting('notify_on_failed_sync', true)) {
            try {
                $recipient = $order->tenant->users()->first();
                if ($recipient) {
                    Mail::to($recipient->email)->send(new SyncFailedEmail($order, $e->getMessage()));
                }
            } catch (\Exception $mailException) {
                \Log::error('Failed to send sync failure email', ['error' => $mailException->getMessage()]);
            }
        }

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
        // Fetch order for error handling
        $order = Order::find($this->orderId);
        if (! $order) {
            \Log::error('Order not found in handleGeneralException', ['order_id' => $this->orderId]);
            throw $e;
        }

        // Log the failure
        \App\Models\SyncLog::logFailure(
            $order->id,
            'loyverse_sync',
            'Sync failed: '.$e->getMessage(),
            [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]
        );

        // Store failed sync attempt
        $order->loyverseOrder()->create([
            'sync_status' => 'failed',
            'sync_response' => [
                'error' => $e->getMessage(),
            ],
        ]);

        // Update order status
        $order->update(['status' => 'failed']);

        // Send email notification if tenant has notifications enabled
        if ($order->tenant && $order->tenant->getSetting('notify_on_failed_sync', true)) {
            try {
                $recipient = $order->tenant->users()->first();
                if ($recipient) {
                    Mail::to($recipient->email)->send(new SyncFailedEmail($order, $e->getMessage()));
                }
            } catch (\Exception $mailException) {
                \Log::error('Failed to send sync failure email', ['error' => $mailException->getMessage()]);
            }
        }

        // Fail the job (will trigger retry if attempts remain)
        throw $e;
    }
}
