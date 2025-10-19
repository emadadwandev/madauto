<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Order;

class ProcessCareemOrderJob implements ShouldQueue
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
        $order = Order::create([
            'careem_order_id' => $this->payload['order_id'],
            'order_data' => $this->payload,
            'status' => 'pending',
        ]);

        SyncToLoyverseJob::dispatch($order);
    }
}
