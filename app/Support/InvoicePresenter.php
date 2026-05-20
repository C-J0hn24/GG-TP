<?php

namespace App\Support;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class InvoicePresenter
{
    /**
     * @return array{
     *     invoice_id: string,
     *     order_id: string,
     *     customer_id: string,
     *     customer_name: string,
     *     order_date: string,
     *     pickup_date: string,
     *     subtotal: float,
     *     discount: float,
     *     total: float,
     *     payment_status: string,
     *     is_paid: bool,
     *     lines: list<array{product_name: string, order_id: string, customer_id: string, quantity: int, unit_price: float, line_total: float}>
     * }
     */
    public static function forOrder(Order $order, User $user): array
    {
        $order->loadMissing(['items.product', 'payment', 'collectionSlot']);

        $lines = [];
        $subtotal = 0.0;

        foreach ($order->items as $item) {
            $lineTotal = (float) $item->price * (int) $item->quantity;
            $subtotal += $lineTotal;
            $lines[] = [
                'product_name' => $item->product->product_name ?? 'Product',
                'order_id' => $order->order_id,
                'customer_id' => (string) $order->customer_id,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->price,
                'line_total' => $lineTotal,
            ];
        }

        $total = (float) $order->amount;
        $discount = max(0, round($subtotal - $total, 2));
        $paymentStatus = strtolower((string) ($order->payment->payment_status ?? ''));
        $slot = $order->collectionSlot;

        return [
            'invoice_id' => $order->order_id,
            'order_id' => $order->order_id,
            'customer_id' => (string) $order->customer_id,
            'customer_name' => trim($user->first_name.' '.$user->last_name) ?: ($user->email ?? 'Customer'),
            'order_date' => $order->order_date?->format('d/m/Y') ?? '—',
            'pickup_date' => $slot?->date_?->format('d/m/Y') ?? '—',
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'payment_status' => $paymentStatus !== '' ? ucfirst($paymentStatus) : 'Pending',
            'is_paid' => in_array($paymentStatus, ['paid', 'completed'], true),
            'lines' => $lines,
        ];
    }

    /**
     * @return Collection<int, Order>
     */
    public static function filterOrdersForUser(Collection $orders, ?string $query, ?string $from, ?string $to): Collection
    {
        return $orders->filter(function (Order $order) use ($query, $from, $to): bool {
            if ($query !== null && $query !== '') {
                $needle = strtolower($query);
                $haystack = strtolower((string) $order->order_id);
                if (! str_contains($haystack, $needle)) {
                    return false;
                }
            }

            if ($from !== null && $from !== '' && $order->order_date) {
                if ($order->order_date->copy()->startOfDay()->lt(Carbon::parse($from)->startOfDay())) {
                    return false;
                }
            }

            if ($to !== null && $to !== '' && $order->order_date) {
                if ($order->order_date->copy()->startOfDay()->gt(Carbon::parse($to)->startOfDay())) {
                    return false;
                }
            }

            return true;
        })->values();
    }
}
