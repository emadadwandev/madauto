<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\SyncLog;
use Illuminate\Support\Facades\Log;

class ProcessTalabatOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;

    /**
     * The queue that the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'high';

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if order already exists (prevent duplicates)
            $existingOrder = Order::where('platform', 'talabat')
                ->where('careem_order_id', $this->payload['order_id'])
                ->first();

            if ($existingOrder) {
                Log::info('Talabat order already exists, skipping', [
                    'order_id' => $this->payload['order_id'],
                    'existing_order_id' => $existingOrder->id,
                ]);
                return;
            }

            // Create order record
            $order = Order::create([
                'platform' => 'talabat',
                'careem_order_id' => $this->payload['order_id'], // Reusing this column for platform_order_id
                'order_data' => $this->payload,
                'status' => 'pending',
            ]);

            Log::info('Talabat order created', [
                'order_id' => $order->id,
                'talabat_order_id' => $this->payload['order_id'],
            ]);

            // Create sync log
            SyncLog::logSuccess(
                $order->id,
                'order_received',
                'Talabat order received and stored',
                [
                    'talabat_order_id' => $this->payload['order_id'],
                    'items_count' => count($this->payload['order']['items'] ?? []),
                ]
            );

            // Dispatch to Loyverse sync
            SyncToLoyverseJob::dispatch($order);

        } catch (\Exception $e) {
            Log::error('Failed to process Talabat order', [
                'order_id' => $this->payload['order_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Talabat order processing failed permanently', [
            'order_id' => $this->payload['order_id'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);

        // Try to find the order and mark it as failed
        $order = Order::where('platform', 'talabat')
            ->where('careem_order_id', $this->payload['order_id'])
            ->first();

        if ($order) {
            $order->update(['status' => 'failed']);

            SyncLog::logFailure(
                $order->id,
                'order_processing',
                'Failed to process Talabat order: ' . $exception->getMessage(),
                [
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]
            );
        }
    }
}
