<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment successful</title>
</head>
<body style="font-family: Inter, Arial, sans-serif; line-height: 1.5; color: #1a1a1a;">
    <p>Hello {{ $recipientName }},</p>
    <p>Your payment was successful and your GroceryGo order is confirmed.</p>
    <p><strong>Order ID:</strong> {{ $invoice['order_id'] ?? '' }}</p>
    <p><strong>Total:</strong> {{ config('shop.symbol', '$') }}{{ number_format((float) ($invoice['total'] ?? 0), 2) }}</p>
    <p><strong>Pickup date:</strong> {{ $invoice['pickup_date'] ?? '—' }}
        @if (!empty($invoice['pickup_time']))
            · {{ $invoice['pickup_time'] }}
        @endif
    </p>
    <p><strong>Payment status:</strong> {{ !empty($invoice['is_paid']) ? 'Paid' : ($invoice['payment_status'] ?? 'Pending') }}</p>
    @if (!empty($invoice['lines']))
        <p><strong>Items:</strong></p>
        <ul>
            @foreach ($invoice['lines'] as $line)
                <li>{{ $line['product_name'] ?? 'Product' }} × {{ (int) ($line['quantity'] ?? 0) }} — {{ config('shop.symbol', '$') }}{{ number_format((float) ($line['line_total'] ?? 0), 2) }}</li>
            @endforeach
        </ul>
    @endif
    <p>Thank you for shopping with GroceryGo.</p>
</body>
</html>
