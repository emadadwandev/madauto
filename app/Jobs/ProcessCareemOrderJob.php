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

        $order = Order::create([
            'tenant_id' => $this->tenantId,
            'careem_order_id' => $this->payload['order_id'],
            'order_data' => $this->payload,
            'status' => 'pending',
        ]);

        SyncToLoyverseJob::dispatch($order);
    }
}
