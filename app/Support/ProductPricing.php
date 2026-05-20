<?php

namespace App\Support;

use App\Models\Discount;
use App\Models\Product;

final class ProductPricing
{
    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation  $query
     */
    public static function scopeActiveDiscounts($query)
    {
        $today = now()->toDateString();

        return $query
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    public static function activeDiscountRate(Product $product): ?float
    {
        if ($product->relationLoaded('discounts')) {
            $discount = $product->discounts
                ->filter(fn (Discount $d) => self::discountIsActive($d))
                ->sortByDesc('rate')
                ->first();

            return $discount !== null ? (float) $discount->rate : null;
        }

        $discount = self::scopeActiveDiscounts($product->discounts())
            ->orderByDesc('rate')
            ->first();

        return $discount !== null ? (float) $discount->rate : null;
    }

    public static function unitPrice(Product $product): float
    {
        $base = (float) $product->price;
        $rate = self::activeDiscountRate($product);

        if ($rate === null || $rate <= 0) {
            return $base;
        }

        $pct = min(100.0, $rate);

        return round($base * (1 - $pct / 100), 2);
    }

    /**
     * @return array{unit: float, original: float, rate: ?float, on_sale: bool}
     */
    public static function pricePayload(Product $product): array
    {
        $original = (float) $product->price;
        $unit = self::unitPrice($product);
        $rate = self::activeDiscountRate($product);

        return [
            'unit' => $unit,
            'original' => $original,
            'rate' => $rate,
            'on_sale' => $rate !== null && $rate > 0 && $unit < $original,
        ];
    }

    private static function discountIsActive(Discount $discount): bool
    {
        $today = now()->startOfDay();

        return $discount->start_date !== null
            && $discount->end_date !== null
            && $discount->start_date->startOfDay()->lte($today)
            && $discount->end_date->startOfDay()->gte($today);
    }
}
