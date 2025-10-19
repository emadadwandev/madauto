<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'careem_order_id',
        'order_data',
        'status',
    ];

    protected $casts = [
        'order_data' => 'array',
    ];

    public function loyverseOrder()
    {
        return $this->hasOne(LoyverseOrder::class);
    }
}
