<?php

namespace App\Services\Basket;

use App\Models\Basket;
use App\Models\BasketItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Support\OracleId;
use App\Support\ProductPricing;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class BasketService
{
    public function getBasket(User $user): Basket
    {
        $customer = $this->resolveCustomer($user);

        $basket = Basket::query()
            ->where('customer_id', $customer->customer_id)
            ->first();

        if ($basket) {
            return $basket->load(['items.product.discounts' => fn ($q) => ProductPricing::scopeActiveDiscounts($q)]);
        }

        try {
            return Basket::query()->create([
                'basket_id' => OracleId::next('BASKET', 'basket_id', 'BA'),
                'customer_id' => $customer->customer_id,
                'created_date' => now(),
            ]);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'ORA-00001')) {
                $existing = Basket::query()
                    ->where('customer_id', $customer->customer_id)
                    ->first();

                if ($existing) {
                    return $existing->load(['items.product.discounts' => fn ($q) => ProductPricing::scopeActiveDiscounts($q)]);
                }
            }

            throw $e;
        }
    }

    public function addItem(User $user, string $productId): Basket
    {
        $basket = $this->getBasket($user);
        $product = Product::query()->findOrFail($productId);

        if ((int) $product->product_in_stock <= 0) {
            abort(422, 'This product is out of stock.');
        }

        DB::connection('oracle')->transaction(function () use ($basket, $productId, $product): void {
            $existing = BasketItem::query()
                ->where('basket_id', $basket->basket_id)
                ->where('product_id', $productId)
                ->first();

            $nextQty = ($existing ? (int) $existing->quantity : 0) + 1;
            if ($nextQty > (int) $product->product_in_stock) {
                abort(422, 'Not enough stock available for this product.');
            }

            if ($existing) {
                $existing->quantity = (int) $existing->quantity + 1;
                $existing->save();
            } else {
                BasketItem::query()->create([
                    'basket_item_id' => OracleId::next('BASKET_ITEM', 'basket_item_id', 'BI'),
                    'basket_id' => $basket->basket_id,
                    'product_id' => $productId,
                    'quantity' => 1,
                ]);
            }
        });

        return $this->hydrateBasket($basket->basket_id);
    }

    public function updateItemQuantity(User $user, string $basketItemId, int $quantity): Basket
    {
        $basket = $this->getBasket($user);
        $item = BasketItem::query()
            ->where('basket_item_id', $basketItemId)
            ->where('basket_id', $basket->basket_id)
            ->firstOrFail();

        $item->quantity = max(0, $quantity);
        if ($item->quantity === 0) {
            $item->delete();
        } else {
            $product = $item->product ?? Product::query()->find($item->product_id);
            if ($product && $item->quantity > (int) $product->product_in_stock) {
                abort(422, 'Not enough stock available for this product.');
            }
            $item->save();
        }

        return $this->hydrateBasket($basket->basket_id);
    }

    public function removeItem(User $user, string $basketItemId): Basket
    {
        $basket = $this->getBasket($user);

        BasketItem::query()
            ->where('basket_item_id', $basketItemId)
            ->where('basket_id', $basket->basket_id)
            ->delete();

        return $this->hydrateBasket($basket->basket_id);
    }

    public function summary(Basket $basket): array
    {
        $basket->load(['items.product.discounts' => fn ($q) => ProductPricing::scopeActiveDiscounts($q)]);

        $lines = [];
        $total = 0.0;

        foreach ($basket->items as $item) {
            $product = $item->product;
            if (!$product) {
                continue;
            }
            $qty = (int) $item->quantity;
            $pricing = ProductPricing::pricePayload($product);
            $unit = $pricing['unit'];
            $lineTotal = $unit * $qty;
            $total += $lineTotal;

            $lines[] = [
                'basket_item_id' => $item->basket_item_id,
                'product_id' => $item->product_id,
                'product_name' => $product->product_name,
                'unit_price' => $unit,
                'original_unit_price' => $pricing['original'],
                'on_sale' => $pricing['on_sale'],
                'quantity' => $qty,
                'line_total' => $lineTotal,
            ];
        }

        return [
            'basket_id' => $basket->basket_id,
            'items' => $lines,
            'total' => $total,
        ];
    }

    protected function resolveCustomer(User $user): Customer
    {
        $customer = Customer::query()
            ->where('customer_id', $user->user_id)
            ->first();

        abort_if(!$customer, 403, 'Customer profile required. Register or sign in as a customer (e.g. john.smith@email.com).');

        return $customer;
    }

    protected function hydrateBasket(string $basketId): Basket
    {
        return Basket::query()
            ->with(['items.product.discounts' => fn ($q) => ProductPricing::scopeActiveDiscounts($q)])
            ->findOrFail($basketId);
    }
}
