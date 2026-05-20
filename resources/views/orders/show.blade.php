@extends('layouts.app')

@section('title', 'GroceryGo - Order ' . $order->order_id)

@section('content')
    @include('partials.page-hero', ['title' => 'Order #' . $order->order_id])

    <section class="section">
        <div class="container orders-detail-page">
            @if (session('status'))
                <p class="orders-alert ok">{{ session('status') }}</p>
            @endif

            <p class="orders-detail-back">
                <a href="{{ route('orders.index') }}">← Back to my orders</a>
                ·
                <a href="{{ \App\Support\AppUrl::invoicePageUrl(['order_id' => $order->order_id]) }}">View invoice</a>
            </p>

            @php
                $slot = $order->collectionSlot;
                $canCancel = ! in_array(strtolower((string) $order->status), ['completed', 'cancelled'], true);
                $statusClass = \App\Support\OrderUi::statusPillClass((string) $order->status);
            @endphp

            <article class="card orders-detail-card">
                <header class="order-card-head" style="border-bottom:none;margin-bottom:0;padding-bottom:0;">
                    <div>
                        <h2 style="margin:0 0 6px;font-size:1.25rem;">Order summary</h2>
                        <p class="order-card-meta">Placed {{ $order->order_date?->format('d M Y H:i') ?? $order->order_date }}</p>
                    </div>
                    <span class="status-pill {{ $statusClass }}">{{ ucfirst((string) $order->status) }}</span>
                </header>

                <div class="profile-details-grid" style="margin-top:18px;">
                    <div class="profile-detail-item">
                        <span>Total</span>
                        <strong>{{ \App\Support\Money::format((float) $order->amount) }}</strong>
                    </div>
                    @if ($order->payment)
                        <div class="profile-detail-item">
                            <span>Payment</span>
                            <strong>{{ ucfirst((string) $order->payment->payment_status) }} · {{ \App\Support\Money::format((float) $order->payment->paid_amount) }}</strong>
                        </div>
                    @endif
                    @if ($slot)
                        <div class="profile-detail-item">
                            <span>Pickup location</span>
                            <strong>{{ $slot->displayLocation() }}</strong>
                        </div>
                        <div class="profile-detail-item">
                            <span>Collection</span>
                            <strong>{{ $slot->displayDate() }} · {{ $slot->displayTime() }}</strong>
                        </div>
                    @endif
                </div>

                @if ($canCancel)
                    <form method="post" action="{{ route('orders.cancel', $order->order_id) }}" style="margin-top:20px;" onsubmit="return confirm('Cancel this order?');">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-danger-outline">Cancel order</button>
                    </form>
                @endif
            </article>

            <section class="orders-items-panel card" aria-label="Order items">
                <h2>Items</h2>
                <div class="table-scroll orders-table-wrap" style="display:block;padding-top:12px;">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Shop</th>
                                <th>Qty</th>
                                <th>Unit price</th>
                                <th>Line total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                @php
                                    $lineTotal = (float) $item->price * (int) $item->quantity;
                                @endphp
                                <tr>
                                    <td>{{ $item->product->product_name ?? 'Product' }}</td>
                                    <td>{{ $item->product->shop->shop_name ?? '—' }}</td>
                                    <td>{{ (int) $item->quantity }}</td>
                                    <td>{{ \App\Support\Money::format((float) $item->price) }}</td>
                                    <td><strong>{{ \App\Support\Money::format($lineTotal) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </section>
@endsection
