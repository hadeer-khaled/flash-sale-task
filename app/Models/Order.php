<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'product_id',
        'hold_id',
        'quantity',
        'status',
    ];

    public function product(): BelongsTo
     {
        return $this->belongsTo(Product::class); 
    }

    public function hold(): BelongsTo
    {
        return $this->belongsTo(Hold::class);
    }

}
