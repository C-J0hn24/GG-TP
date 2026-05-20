<?php

namespace App\Services\Checkout;

use App\Models\CollectionSlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Basket\BasketService;
use App\Support\OracleId;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        protected BasketService $basketService,
        protected SlotCapacityService $slotCapacityService,
        protected PaymentService $paymentService,
    ) {
    }

    public function checkout(User $user, array $payload): array
    {
        $basket = $this->basketService->getBasket($user);
        $summary = $this->basketService->summary($basket);

        if (empty($summary['items'])) {
            abort(422, 'Basket is empty');
        }

        $this->slotCapacityService->assertSlotAvailable($payload['slot_date'], $payload['slot_time']);

        return DB::connection('oracle')->transaction(function () use ($user, $basket, $summary, $payload) {
            foreach ($summary['items'] as $line) {
                $product = Product::query()
                    ->where('product_id', $line['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$product || (int) $product->product_in_stock < (int) $line['quantity']) {
                    $name = $product->product_name ?? $line['product_id'];
                    abort(422, "Not enough stock for {$name}.");
                }
            }

            $orderId = OracleId::next('ORDERS', 'order_id', 'O');

            $order = Order::create([
                'order_id' => $orderId,
                'order_date' => now('Asia/Kathmandu'),
                'status' => 'placed',
                'amount' => $summary['total'],
                'customer_id' => $user->user_id,
            ]);

            foreach ($summary['items'] as $line) {
                OrderItem::create([
                    'order_item_id' => OracleId::next('ORDER_ITEM', 'order_item_id', 'OI'),
                    'quantity' => $line['quantity'],
                    'price' => $line['unit_price'],
                    'order_id' => $order->order_id,
                    'product_id' => $line['product_id'],
                ]);

                $decremented = Product::query()
                    ->where('product_id', $line['product_id'])
                    ->where('product_in_stock', '>=', (int) $line['quantity'])
                    ->decrement('product_in_stock', (int) $line['quantity']);

                if (!$decremented) {
                    abort(422, 'Stock changed while checkout was processing. Please review your basket.');
                }
            }

            $pickupDate = $this->slotCapacityService->resolvePickupDate($payload['slot_date']);
            $slotId = OracleId::next('COLLECTION_SLOT', 'slot_id', 'CS');
            $pickupDateSql = $pickupDate->format('Y-m-d');

            $slotRow = [
                'slot_id' => $slotId,
                'date_' => DB::raw("TO_DATE('{$pickupDateSql}', 'YYYY-MM-DD')"),
                'time_' => $payload['slot_time'],
                'order_id' => $order->order_id,
            ];

            $pickupLocation = trim((string) ($payload['location'] ?? ''));
            if ($pickupLocation !== '') {
                $slotRow['pickup_location'] = $pickupLocation;
            }

            try {
                DB::connection('oracle')->table('COLLECTION_SLOT')->insert($slotRow);
            } catch (QueryException $e) {
                if (isset($slotRow['pickup_location'])) {
                    unset($slotRow['pickup_location']);
                    DB::connection('oracle')->table('COLLECTION_SLOT')->insert($slotRow);
                } else {
                    throw $e;
                }
            }

            $slot = CollectionSlot::query()->findOrFail($slotId);

            if (($payload['payment_method'] ?? '') === 'paypal') {
                $payment = $this->paymentService->recordPayPal(
                    $order,
                    (string) ($payload['paypal_order_id'] ?? ''),
                    (string) ($payload['paypal_capture_id'] ?? ''),
                    (float) ($payload['paid_amount'] ?? $order->amount),
                );
            } else {
                $payment = $this->paymentService->mockPay($order, (string) $payload['payment_method']);
            }

            $basket->items()->delete();

            return [
                'order' => $order->fresh(['items.product', 'collectionSlot', 'payment']),
                'collection_slot' => $slot,
                'payment' => $payment,
            ];
        });
    }
}
