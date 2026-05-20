@extends('layouts.app')

@section('title', 'GroceryGo - Invoice ' . $invoice['invoice_id'])

@section('content')
    <section class="section invoice-page">
        <div class="container">
            <h1 class="invoice-page-title">Invoice Page</h1>

            @if (session('invoice_payment_success'))
                <div class="invoice-success-banner" role="status">
                    <strong>Success</strong>
                    <p>{{ session('invoice_payment_success') }}</p>
                </div>
            @elseif (session('status'))
                <p class="orders-alert ok">{{ session('status') }}</p>
            @elseif (request()->query('paid'))
                <div class="invoice-success-banner" role="status">
                    <strong>Success</strong>
                    <p>Payment successful! Your order has been placed and your invoice is ready below.</p>
                </div>
            @endif

            <form class="invoice-toolbar card" method="get" action="{{ route('invoices.index') }}">
                <label class="invoice-toolbar-field invoice-toolbar-search">
                    <span class="sr-only">Search invoice</span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search invoice">
                </label>
                <label class="invoice-toolbar-field">
                    <span>From:</span>
                    <input type="date" name="from" value="{{ request('from') }}">
                </label>
                <label class="invoice-toolbar-field">
                    <span>To:</span>
                    <input type="date" name="to" value="{{ request('to') }}">
                </label>
                <a class="btn btn-outline invoice-export-btn" href="{{ route('invoices.export', $order->order_id) }}">Export</a>
            </form>

            <div class="invoice-header-grid">
                <article class="card invoice-party-card">
                    <h2>{{ $company['name'] }}</h2>
                    <p>{{ $company['address'] }}</p>
                    <p>Mobile: {{ $company['phone'] }}</p>
                    <p>Email: {{ $company['email'] }}</p>
                </article>

                <article class="card invoice-party-card invoice-party-card--customer">
                    <h2>Order by {{ $invoice['customer_name'] }}</h2>
                    <p>Customer ID: {{ $invoice['customer_id'] }}</p>
                    <p>Email: {{ auth()->user()->email }}</p>
                </article>

                <article class="card invoice-party-card invoice-party-card--logo" aria-hidden="true">
                    <img src="{{ asset('assets/logo/GroceryGo-main.png') }}" alt="" class="invoice-brand-logo">
                </article>
            </div>

            <section class="card invoice-document" aria-label="Invoice details">
                <header class="invoice-document-head">
                    <h2>Invoice List</h2>
                    <div class="invoice-document-meta">
                        <p><strong>Date:</strong> {{ $invoice['order_date'] }}</p>
                        <p><strong>Invoice ID:</strong> {{ $invoice['invoice_id'] }}</p>
                    </div>
                </header>

                <div class="table-scroll invoice-table-wrap">
                    <table class="invoice-table">
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
                </div>

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

            <p class="invoice-back-link">
                <a href="{{ route('invoices.index') }}">← All invoices</a>
                ·
                <a href="{{ route('orders.show', $order->order_id) }}">Order details</a>
                ·
                <a href="{{ route('invoices.print', $order->order_id) }}" target="_blank" rel="noopener">Print</a>
            </p>
        </div>
    </section>
@endsection
