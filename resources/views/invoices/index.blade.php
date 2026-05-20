@extends('layouts.app')

@section('title', 'GroceryGo - Invoices')

@section('content')
    <section class="section invoice-page">
        <div class="container">
            <h1 class="invoice-page-title">Invoice Page</h1>

            @if (session('status'))
                <p class="orders-alert ok">{{ session('status') }}</p>
            @endif

            <form class="invoice-toolbar card" method="get" action="{{ route('invoices.index') }}">
                <label class="invoice-toolbar-field invoice-toolbar-search">
                    <span class="sr-only">Search invoice</span>
                    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search invoice">
                </label>
                <label class="invoice-toolbar-field">
                    <span>From:</span>
                    <input type="date" name="from" value="{{ $filters['from'] }}">
                </label>
                <label class="invoice-toolbar-field">
                    <span>To:</span>
                    <input type="date" name="to" value="{{ $filters['to'] }}">
                </label>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <section class="card invoice-document" aria-label="Invoice history">
                <header class="invoice-document-head">
                    <h2>Invoice List</h2>
                </header>

                @if ($orders->isEmpty())
                    <p class="invoice-empty muted" style="padding: 20px 22px;">No invoices match your search.</p>
                @else
                    <div class="table-scroll invoice-table-wrap">
                        <table class="invoice-table">
                            <thead>
                                <tr>
                                    <th>Invoice ID</th>
                                    <th>Order date</th>
                                    <th>Pickup date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    @php
                                        $paid = strtolower((string) ($order->payment->payment_status ?? '')) === 'paid';
                                        $pickup = $order->collectionSlot?->date_?->format('d/m/Y') ?? '—';
                                    @endphp
                                    <tr>
                                        <td>{{ $order->order_id }}</td>
                                        <td>{{ $order->order_date?->format('d/m/Y') ?? '—' }}</td>
                                        <td>{{ $pickup }}</td>
                                        <td>{{ \App\Support\Money::format((float) $order->amount) }}</td>
                                        <td>
                                            <span class="invoice-paid-badge{{ $paid ? ' invoice-paid-badge--yes' : '' }}">
                                                {{ $paid ? 'Paid' : ucfirst((string) ($order->payment->payment_status ?? 'Pending')) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline btn-sm" href="{{ route('invoices.show', $order->order_id) }}">View</a>
                                            <a class="btn btn-outline btn-sm" href="{{ route('invoices.export', $order->order_id) }}">Export</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </section>
@endsection
