<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'code',
        'name',
        'price',
        'available_stock',
        'total_stock',
    ];

    public function holds(): HasMany
    {
        return $this->hasMany(Hold::class);
    }

}
