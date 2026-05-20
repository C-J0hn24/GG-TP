<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice['invoice_id'] }} — GroceryGo</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        body { background: #fff; margin: 24px; }
        .invoice-print-actions { margin-bottom: 16px; }
        @media print {
            .invoice-print-actions { display: none; }
        }
    </style>
</head>
<body class="invoice-print-body">
    <p class="invoice-print-actions">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
        <a class="btn btn-outline" href="{{ route('invoices.show', $order->order_id) }}">Back to invoice</a>
    </p>

    @include('invoices.partials.document', [
        'invoice' => $invoice,
        'company' => $company,
        'customerEmail' => auth()->user()->email,
    ])
</body>
</html>
