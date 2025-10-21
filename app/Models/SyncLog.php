<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'order_id',
        'action',
        'status',
        'message',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sync_logs';

    /**
     * Get the order that owns the sync log.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope a query to only include successful logs.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Alias for scopeSuccessful
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope a query to only include failed logs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to filter by action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Create a success log entry.
     */
    public static function logSuccess(int $orderId, string $action, string $message, array $metadata = []): self
    {
        return self::create([
            'order_id' => $orderId,
            'action' => $action,
            'status' => 'success',
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create a failure log entry.
     */
    public static function logFailure(int $orderId, string $action, string $message, array $metadata = []): self
    {
        return self::create([
            'order_id' => $orderId,
            'action' => $action,
            'status' => 'failed',
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create a warning log entry.
     */
    public static function logWarning(int $orderId, string $action, string $message, array $metadata = []): self
    {
        return self::create([
            'order_id' => $orderId,
            'action' => $action,
            'status' => 'warning',
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }
}
