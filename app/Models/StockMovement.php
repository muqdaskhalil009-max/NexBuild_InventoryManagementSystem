<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity',
        'reason',
    ];

    // A stock movement belongs to one product
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // A stock movement belongs to a staff member
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
