<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuSyncLog extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'menu_id',
        'platform',
        'action',
        'status',
        'message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function getPlatformBadgeColorAttribute(): string
    {
        return match ($this->platform) {
            'careem' => 'bg-green-500',
            'talabat' => 'bg-orange-500',
            default => 'bg-gray-500',
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'success' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
