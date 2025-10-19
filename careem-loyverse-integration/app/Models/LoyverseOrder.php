<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyverseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'loyverse_order_id',
        'loyverse_receipt_number',
        'sync_status',
        'sync_response',
        'synced_at',
    ];

    protected $casts = [
        'sync_response' => 'array',
        'synced_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
