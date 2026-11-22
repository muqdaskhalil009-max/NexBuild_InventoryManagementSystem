<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    // An order item belongs to one order
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // An order item belongs to one product
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
