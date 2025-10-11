<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hold extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'expires_at',
        'status',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

}
