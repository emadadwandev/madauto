<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'careem_order_id',
        'order_data',
        'status',
        'platform_status',
        'platform_status_updated_at',
    ];

    protected $casts = [
        'order_data' => 'array',
        'platform_status_updated_at' => 'datetime',
    ];

    public function loyverseOrder()
    {
        return $this->hasOne(LoyverseOrder::class);
    }
}
