@php
    $product = $product ?? [];
    $id = $product['id'] ?? 1;
    $name = $product['name'] ?? 'Product';
    $price = (float) ($product['price'] ?? 0);
    $originalPrice = (float) ($product['original_price'] ?? $price);
    $onSale = !empty($product['on_sale']);
    $stock = $product['stock'] ?? ['label' => 'In Stock', 'variant' => 'in'];
    $stockLabel = $stock['label'] ?? 'In Stock';
    $stockVariant = $stock['variant'] ?? 'in';
    $imageUrl = $product['image'] ?? null;
    $imageIsPlaceholder = !empty($product['image_placeholder']);
    $category = $product['category'] ?? null;
@endphp

<a href="{{ route('products.show', $id) }}" class="card product-card product-card--compact card-clickable" aria-label="View {{ $name }}">
    @if (!empty($imageUrl))
        <img src="{{ $imageUrl }}" alt="" class="product-card-image{{ $imageIsPlaceholder ? ' product-card-image--placeholder' : '' }}">
    @else
        <div class="product-image-placeholder" aria-hidden="true">
            <span class="product-image-placeholder-text">Image</span>
        </div>
    @endif

    <h3 class="product-name">{{ $name }}</h3>

    @if (!empty($category))
        <p class="product-card-compact-category">{{ $category }}</p>
    @endif

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
