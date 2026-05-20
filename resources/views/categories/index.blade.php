@extends('layouts.app')

@section('title', 'GroceryGo - Categories')

@section('content')
    @include('partials.page-hero', [
        'title' => 'Categories',
        'subtitle' => 'Browse and filter products from local shops',
        'show_search' => true,
        'search_query' => $filters['q'] ?? '',
    ])

    <section class="section">
        <div class="container categories-layout">
            <aside class="card filters-sidebar">
                <h2 class="filters-title">Filters</h2>

                <form method="get" action="{{ route('categories') }}" class="filters-form">
                    @if (!empty($filters['q']))
                        <input type="hidden" name="q" value="{{ $filters['q'] }}">
                    @endif

                    <div class="filters-block">
                        <h3>Sort by</h3>
                        <select class="input" name="sort" style="width:100%;">
                            <option value="name" @selected(($filters['sort'] ?? 'name') === 'name')>Name (A-Z)</option>
                            <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Price: low to high</option>
                            <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Price: high to low</option>
                            <option value="rating_desc" @selected(($filters['sort'] ?? '') === 'rating_desc')>Rating: highest first</option>
                        </select>
                    </div>

                    <div class="filters-block">
                        <h3>Minimum rating</h3>
                        <select class="input" name="min_rating" style="width:100%;">
                            <option value="">Any rating</option>
                            @foreach ([5, 4, 3] as $r)
                                <option value="{{ $r }}" @selected((string) ($filters['min_rating'] ?? '') === (string) $r)">{{ $r }}+ stars</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filters-block">
                        <h3>Categories</h3>
                        <div class="filters-options">
                            @foreach ($categories as $cat)
                                <label class="filter-option">
                                    <input type="checkbox"
                                           name="category_id[]"
                                           value="{{ $cat->category_id }}"
                                           @checked(in_array($cat->category_id, $filters['category_id'] ?? [], true))>
                                    <span>{{ $cat->category_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="filters-block">
                        <h3>Trader / Shop</h3>
                        <div class="filters-options">
                            @foreach ($shops as $shop)
                                <label class="filter-option">
                                    <input type="checkbox"
                                           name="shop_id[]"
                                           value="{{ $shop->shop_id }}"
                                           @checked(in_array($shop->shop_id, $filters['shop_id'] ?? [], true))>
                                    <span>{{ $shop->shop_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary filters-apply-btn">Apply Filters</button>
                    <a href="{{ route('categories') }}" class="btn btn-outline" style="margin-top:8px;display:block;text-align:center;">Clear</a>
                </form>
            </aside>

            <div class="categories-main">
                @if (!empty($filters['q']))
                    <p class="text-secondary" style="margin-bottom:12px;">Search: "{{ $filters['q'] }}"</p>
                @endif

                <div class="product-grid">
                    @forelse($products as $p)
                        @php
                            $stock = (int) ($p->product_in_stock ?? 0);
                            $uploadedImage = \App\Support\ProductMeta::primaryImageUrl($p->shop_id, $p->description);
                            $displayImage = $p->customerPrimaryImageUrl();
                            $product = [
                                'id' => $p->product_id,
                                'trader' => $p->shop->shop_name ?? 'Shop',
                                'category' => $p->category->category_name ?? '',
                                'name' => $p->product_name,
                                'image' => $displayImage,
                                'image_placeholder' => $uploadedImage === null && $displayImage !== null,
                                'price' => (float) $p->price,
                                'stock' => [
                                    'label' => $stock <= 0 ? 'Out of Stock' : ($stock <= 5 ? 'Low Stock' : 'In Stock'),
                                    'variant' => $stock <= 0 ? 'out' : ($stock <= 5 ? 'low' : 'in'),
                                ],
                            ];
                        @endphp
                        @include('partials.product-card', ['product' => $product])
                    @empty
                        <p class="muted">No products match your filters.</p>
                    @endforelse
                </div>

                @if ($products->hasPages())
                    <div class="pagination-wrap" style="margin-top:20px;">
                        {{ $products->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
