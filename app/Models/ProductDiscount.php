<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDiscount extends BaseOracleModel
{
    protected $table = 'PRODUCT_DISCOUNT';
    protected $primaryKey = 'product_discount_id';
    public $timestamps = false;

    protected $fillable = [
        'product_discount_id',
        'product_id',
        'discount_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'discount_id', 'discount_id');
    }
}

