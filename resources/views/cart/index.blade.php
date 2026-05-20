@extends('layouts.app')

@section('title', 'GroceryGo - Cart')

@section('content')
    {{-- Cart page title --}}
    <section class="page-hero cart-hero">
        <div class="container">
            <h1>Your Cart</h1>
            <p>Review your items and checkout in one go.</p>
            @if (!empty($isGuest))
                <p class="text-secondary" style="margin-top:8px;">Sign in to complete checkout. Your basket is saved for this browser session.</p>
            @endif
            @if (session('status'))
                <p class="ok" style="margin-top:8px;">{{ session('status') }}</p>
            @endif
            @if ($errors->has('cart'))
                <p class="alert alert-error" style="margin-top:8px;">{{ $errors->first('cart') }}</p>
            @endif
            <div class="divider"></div>
        </div>
    </section>

    <section class="section cart-section">
        <div class="container cart-layout">
            <article class="card cart-table-card">
                <header class="cart-table-header">
                    <h2>Items</h2>
                </header>

                <div class="cart-table-wrap" role="region" aria-label="Cart items table">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            @php
                                $totalPrice = $total ?? 0;
                            @endphp

                            @forelse($cartItems as $item)
                                @php
                                    $name = $item['product_name'] ?? 'Item';
                                    $price = (float) ($item['unit_price'] ?? 0);
                                    $qty = (int) ($item['quantity'] ?? 1);
                                    $itemTotal = (float) ($item['line_total'] ?? ($price * $qty));
                                @endphp
                                <tr class="cart-row">
                                    <td class="cart-product">
                                        <div class="cart-product-name">{{ $name }}</div>
                                        <form method="post" action="{{ route('cart.remove') }}" class="cart-remove-form">
                                            @csrf
                                            @if (!empty($isGuest))
                                                <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                            @else
                                                <input type="hidden" name="basket_item_id" value="{{ $item['basket_item_id'] }}">
                                            @endif
                                            <button type="submit" class="cart-remove-btn">Remove</button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="cart-cell-strong">
                                            {{ \App\Support\Money::format($price) }}
                                        </div>
                                    </td>
                                    <td>
                                        <form method="post"
                                              action="{{ route('cart.update') }}"
                                              class="cart-qty-form cart-qty-form--main"
                                              data-cart-qty-form>
                                            @csrf
                                            @if (!empty($isGuest))
                                                <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                            @else
                                                <input type="hidden" name="basket_item_id" value="{{ $item['basket_item_id'] }}">
                                            @endif
                                            <div class="cart-qty-controls" aria-label="Quantity for {{ $name }}">
                                                <button type="button"
                                                        class="qty-btn qty-btn--minus"
                                                        aria-label="Decrease quantity"
                                                        @disabled($qty <= 1)>
                                                    −
                                                </button>
                                                <label class="sr-only" for="cart-qty-{{ $item['product_id'] }}">Quantity</label>
                                                <input id="cart-qty-{{ $item['product_id'] }}"
                                                       type="number"
                                                       name="quantity"
                                                       class="qty-input cart-qty-input"
                                                       value="{{ $qty }}"
                                                       min="1"
                                                       max="20"
                                                       inputmode="numeric"
                                                       required>
                                                <button type="button"
                                                        class="qty-btn qty-btn--plus"
                                                        aria-label="Increase quantity"
                                                        @disabled($qty >= 20)>
                                                    +
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="cart-cell-strong cart-cell-total">
                                            {{ \App\Support\Money::format($itemTotal) }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="cart-empty">
                                        Your cart is empty.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <aside class="card cart-summary-card">
                <header class="cart-summary-header">
                    <h2>Summary</h2>
                </header>

                <div class="cart-total-line">
                    <span>Total Price</span>
                    <strong>{{ \App\Support\Money::format($totalPrice) }}</strong>
                </div>

                @auth
                    <a class="btn btn-primary cart-checkout-btn" href="{{ route('checkout.collection-slot') }}">
                        Checkout
                    </a>
                @else
                    <a class="btn btn-primary cart-checkout-btn" href="{{ route('login', ['checkout' => 1]) }}">
                        Sign in to checkout
                    </a>
                    <p class="cart-note" style="margin-top:10px;">You will be asked to sign in before payment.</p>
                @endauth

                <p class="cart-note">Maximum of 20 per product</p>
            </aside>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(function () {
    function clampQty(value) {
        var n = parseInt(value, 10);
        if (Number.isNaN(n)) {
            return 1;
        }
        return Math.min(20, Math.max(1, n));
    }

    document.querySelectorAll('[data-cart-qty-form]').forEach(function (form) {
        var input = form.querySelector('.cart-qty-input');
        var minus = form.querySelector('.qty-btn--minus');
        var plus = form.querySelector('.qty-btn--plus');
        if (!input) {
            return;
        }

        function syncButtons() {
            var qty = clampQty(input.value);
            if (minus) {
                minus.disabled = qty <= 1;
            }
            if (plus) {
                plus.disabled = qty >= 20;
            }
        }

        if (minus) {
            minus.addEventListener('click', function () {
                input.value = clampQty(parseInt(input.value, 10) - 1);
                syncButtons();
                form.requestSubmit();
            });
        }

        if (plus) {
            plus.addEventListener('click', function () {
                input.value = clampQty(parseInt(input.value, 10) + 1);
                syncButtons();
                form.requestSubmit();
            });
        }

        input.addEventListener('change', function () {
            input.value = clampQty(input.value);
            syncButtons();
            form.requestSubmit();
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                input.value = clampQty(input.value);
                syncButtons();
                form.requestSubmit();
            }
        });

        syncButtons();
    });
})();
</script>
@endpush
