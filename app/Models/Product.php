<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

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

    protected static function boot()
    {
        parent::boot();

        static::saved(function (self $product) {
            Cache::forget("product:{$product->id}");
        });
    }
}
