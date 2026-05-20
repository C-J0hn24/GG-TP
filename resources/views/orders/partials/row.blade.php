@php
    $slot = $order->collectionSlot;
    $canCancel = ! in_array(strtolower((string) $order->status), ['completed', 'cancelled'], true);
    $statusClass = \App\Support\OrderUi::statusPillClass((string) $order->status);
    $statusLabel = ucfirst((string) $order->status);
    $placedAt = $order->order_date?->format('d M Y H:i') ?? $order->order_date;
    $pickup = $slot ? $slot->displayLocation() : '—';
    $collectDate = $slot ? $slot->displayDate() : '—';
    $collectTime = $slot ? $slot->displayTime() : '—';
    $amount = \App\Support\Money::format((float) $order->amount);
@endphp

@if (($layout ?? 'table') === 'table')
    <tr>
        <td>
            <a class="orders-order-link" href="{{ route('orders.show', $order->order_id) }}">#{{ $order->order_id }}</a>
        </td>
        <td>{{ $placedAt }}</td>
        <td>{{ $pickup }}</td>
        <td>{{ $collectDate }}</td>
        <td>{{ $collectTime }}</td>
        <td><span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span></td>
        <td><strong>{{ $amount }}</strong></td>
        <td class="orders-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('invoices.show', $order->order_id) }}">Invoice</a>
            <a class="btn btn-outline btn-sm" href="{{ route('orders.show', $order->order_id) }}">View</a>
            @if ($canCancel)
                <form method="post" action="{{ route('orders.cancel', $order->order_id) }}" class="orders-inline-form" onsubmit="return confirm('Cancel this order?');">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm btn-danger-outline">Cancel</button>
                </form>
            @endif
        </td>
    </tr>
@else
    <article class="order-card">
        <header class="order-card-head">
            <div>
                <a class="orders-order-link" href="{{ route('orders.show', $order->order_id) }}">Order #{{ $order->order_id }}</a>
                <p class="order-card-meta">{{ $placedAt }}</p>
            </div>
            <span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
        </header>

        <dl class="order-card-details">
            <div>
                <dt>Pickup</dt>
                <dd>{{ $pickup }}</dd>
            </div>
            <div>
                <dt>Collection date</dt>
                <dd>{{ $collectDate }}</dd>
            </div>
            <div>
                <dt>Collection time</dt>
                <dd>{{ $collectTime }}</dd>
            </div>
            <div class="order-card-amount">
                <dt>Amount</dt>
                <dd><strong>{{ $amount }}</strong></dd>
            </div>
        </dl>

        <footer class="order-card-actions">
            <a class="btn btn-primary btn-sm" href="{{ route('invoices.show', $order->order_id) }}">Invoice</a>
            <a class="btn btn-outline btn-sm" href="{{ route('orders.show', $order->order_id) }}">Details</a>
            @if ($canCancel)
                <form method="post" action="{{ route('orders.cancel', $order->order_id) }}" class="orders-inline-form" onsubmit="return confirm('Cancel this order?');">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm btn-danger-outline">Cancel</button>
                </form>
            @endif
        </footer>
    </article>
@endif
