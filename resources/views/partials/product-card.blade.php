@php
    $product = $product ?? [];
    $id = $product['id'] ?? 1;
    $trader = $product['trader'] ?? 'Local Trader';
    $category = $product['category'] ?? null;
    $name = $product['name'] ?? 'Product';
    $price = (float) ($product['price'] ?? 0);
    $originalPrice = (float) ($product['original_price'] ?? $price);
    $onSale = !empty($product['on_sale']);
    $stock = $product['stock'] ?? ['label' => 'In Stock', 'variant' => 'in'];
    $stockLabel = $stock['label'] ?? 'In Stock';
    $stockVariant = $stock['variant'] ?? 'in';
    $imageUrl = $product['image'] ?? null;
    $imageIsPlaceholder = !empty($product['image_placeholder']);
@endphp

{{-- Shared product card (used in Categories + You May Like) — entire card links to product page --}}
<a href="{{ route('products.show', $id) }}" class="card product-card card-clickable" aria-label="View {{ $name }}">
    @if (!empty($imageUrl))
        <img src="{{ $imageUrl }}" alt="" class="product-card-image{{ $imageIsPlaceholder ? ' product-card-image--placeholder' : '' }}">
    @else
        <div class="product-image-placeholder" aria-hidden="true">
            <span class="product-image-placeholder-text">Image</span>
        </div>
    @endif

    <div class="product-meta">
        <span class="product-meta-pill">{{ $trader }}</span>
        @if (!empty($category))
            <span class="product-meta-sep">|</span>
            <span class="product-meta-sub">{{ $category }}</span>
        @endif
    </div>

    <h3 class="product-name">{{ $name }}</h3>

    <div class="product-bottom-row">
        <div class="product-price">
            @if ($onSale)
                <span class="product-price-was">{{ \App\Support\Money::format($originalPrice) }}</span>
            @endif
            <span class="product-price-now">{{ \App\Support\Money::format($price) }}</span>
        </div>
        @include('partials.status-badge', ['label' => $stockLabel, 'variant' => $stockVariant])
    </div>
</a>
