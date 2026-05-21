<?php

namespace App\Services\Checkout;

use App\Mail\InvoicePaymentSuccessMail;
use App\Models\Order;
use App\Models\User;
use App\Support\InvoicePresenter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceEmailService
{
    /**
     * @return string|null Notice for UI when mail is skipped or fails
     */
    public function sendForOrder(User $user, Order $order): ?string
    {
        $email = trim((string) $user->email);
        if ($email === '') {
            return 'Email sending is not configured yet (no address on your account).';
        }

        try {
            $order->loadMissing(['items.product', 'collectionSlot', 'payment']);
            $invoice = InvoicePresenter::forOrder($order, $user);
            $invoice['pickup_time'] = (string) ($order->collectionSlot?->time_ ?? '');
            $invoice['payment_method'] = (string) ($order->payment?->payment_method ?? '');
            $name = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'Customer';

            Mail::to($email)->send(new InvoicePaymentSuccessMail($name, $invoice));
        } catch (\Throwable $e) {
            Log::warning('Invoice email failed: '.$e->getMessage(), [
                'order_id' => $order->order_id,
                'email' => $email,
            ]);

            return 'Email sending is not configured yet.';
        }

        return null;
    }
}
