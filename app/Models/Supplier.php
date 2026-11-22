<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'company_name',
    ];

    // A supplier can provide multiple products
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
