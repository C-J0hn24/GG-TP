<div class="invoice-header-grid invoice-header-grid--print">
    <article class="card invoice-party-card">
        <h2>{{ $company['name'] }}</h2>
        <p>{{ $company['address'] }}</p>
        <p>Mobile: {{ $company['phone'] }}</p>
        <p>Email: {{ $company['email'] }}</p>
    </article>

    <article class="card invoice-party-card invoice-party-card--customer">
        <h2>Order by {{ $invoice['customer_name'] }}</h2>
        <p>Customer ID: {{ $invoice['customer_id'] }}</p>
        @if (!empty($customerEmail))
            <p>Email: {{ $customerEmail }}</p>
        @endif
    </article>

    <article class="card invoice-party-card invoice-party-card--logo" aria-hidden="true">
        <img src="{{ asset('assets/logo/GroceryGo-main.png') }}" alt="" class="invoice-brand-logo">
    </article>
</div>

<section class="card invoice-document" aria-label="Invoice">
    <header class="invoice-document-head">
        <h2>Invoice List</h2>
        <div class="invoice-document-meta">
            <p><strong>Date:</strong> {{ $invoice['order_date'] }}</p>
            <p><strong>Invoice ID:</strong> {{ $invoice['invoice_id'] }}</p>
        </div>
    </header>

    <table class="invoice-table invoice-table--print">
        <thead>
            <tr>
                <th>Product</th>
                <th>Order ID</th>
                <th>Customer ID</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice['lines'] as $line)
                <tr>
                    <td>{{ $line['product_name'] }}</td>
                    <td>{{ $line['order_id'] }}</td>
                    <td>{{ $line['customer_id'] }}</td>
                    <td>{{ $line['quantity'] }}</td>
                    <td>{{ \App\Support\Money::format($line['unit_price']) }}</td>
                    <td>{{ \App\Support\Money::format($line['line_total']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <footer class="invoice-document-foot">
        <div class="invoice-dates">
            <p><strong>Pick Up Date:</strong> {{ $invoice['pickup_date'] }}</p>
            <p><strong>Order Date:</strong> {{ $invoice['order_date'] }}</p>
        </div>
        <div class="invoice-totals">
            <p><span>Discount:</span> <strong>{{ \App\Support\Money::format($invoice['discount']) }}</strong></p>
            <p class="invoice-total-row"><span>Total:</span> <strong>{{ \App\Support\Money::format($invoice['total']) }}</strong></p>
            <span class="invoice-paid-badge{{ $invoice['is_paid'] ? ' invoice-paid-badge--yes' : '' }}">
                {{ $invoice['is_paid'] ? 'Paid' : $invoice['payment_status'] }}
            </span>
        </div>
    </footer>
</section>
