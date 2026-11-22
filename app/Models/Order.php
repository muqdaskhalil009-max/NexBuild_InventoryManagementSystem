<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'shipping_cost',
        'total_amount',
    ];

    // An order belongs to one customer
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // An order belongs to the staff member who created it
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // An order can contain multiple products
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // An order can have multiple status history records
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
}
