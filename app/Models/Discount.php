<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends BaseOracleModel
{
    protected $table = 'DISCOUNT';
    protected $primaryKey = 'discount_id';
    public $timestamps = false;

    protected $fillable = [
        'discount_id',
        'rate',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'float',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function productDiscounts(): HasMany
    {
        return $this->hasMany(ProductDiscount::class, 'discount_id', 'discount_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'PRODUCT_DISCOUNT', 'discount_id', 'product_id')
            ->withPivot('product_discount_id');
    }
}

