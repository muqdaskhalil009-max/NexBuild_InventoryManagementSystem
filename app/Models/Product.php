<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'supplier_id',
        'name',
        'sku',
        'description',
        'purchase_price',
        'selling_price',
        'quantity',
        'minimum_stock',
    ];

    // A product belongs to one supplier
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    // A product can have many stock movements
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // A product can appear in many order items
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Check whether product stock is low
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->minimum_stock;
    }
}
