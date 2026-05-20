@extends('layouts.app')

@section('title', 'GroceryGo - ' . ($product->product_name ?? 'Product'))

@section('content')
    @include('partials.page-hero', ['title' => 'Product Details'])

    <section class="section">
        <div class="container product-details-layout">
            <div class="product-details-aside">
            <article class="card product-details-media">
                @php
                    $galleryUrls = $product->customerGalleryUrls();
                    $isPlaceholder = $product->customerUsesPlaceholderImage();
                @endphp
                @if ($galleryUrls !== [])
                    <div class="product-gallery" data-product-gallery>
                        <div class="product-gallery-main">
                            <img id="productGalleryMain" src="{{ $galleryUrls[0] }}" alt="{{ $product->product_name }}" class="product-image-large product-image-large--photo{{ $isPlaceholder ? ' product-card-image--placeholder' : '' }}">
                        </div>
                        @if (count($galleryUrls) > 1)
                            <div class="product-gallery-thumbs" role="list" aria-label="Product images">
                                @foreach ($galleryUrls as $index => $url)
                                    <button type="button" class="product-gallery-thumb{{ $index === 0 ? ' is-active' : '' }}" data-gallery-src="{{ $url }}" role="listitem" aria-label="View image {{ $index + 1 }} of {{ count($galleryUrls) }}" aria-pressed="{{ $index === 0 ? 'true' : 'false' }}">
                                        <img src="{{ $url }}" alt="">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="product-image-large" aria-hidden="true">
                        <span>Product Image</span>
                    </div>
                @endif
            </article>

            @include('partials.you-may-like-carousel', ['similarProducts' => $similarProducts])

            </div>

            <article class="card product-details-panel">
                @php
                    $stock = (int) ($product->product_in_stock ?? 0);
                    $stockVariant = $stock <= 0 ? 'out' : ($stock <= 5 ? 'low' : 'in');
                    [$stockLabel, $stockDetail] = match ($stockVariant) {
                        'out' => ['Out of stock', null],
                        'low' => ['Low stock', $stock . ' left'],
                        default => ['Stock available', $stock . ' in stock'],
                    };
                    $shop = $product->shop;
                    $shopName = $shop->shop_name ?? 'Local shop';
                    $shopUrl = $shop?->shop_id
                        ? route('categories', ['shop_id' => [$shop->shop_id]])
                        : route('shops.index');
                @endphp

                <div class="product-overview-strip">
                    <a href="{{ $shopUrl }}" class="product-seller-link">
                        <span class="product-seller-copy">
                            <span class="product-seller-label">Sold by</span>
                            <span class="product-seller-name">{{ $shopName }}</span>
                        </span>
                    </a>
                    <div class="product-stock-status product-stock-status--{{ $stockVariant }}" role="status">
                        <span class="product-stock-status-label">{{ $stockLabel }}</span>
                        @if ($stockDetail)
                            <span class="product-stock-status-detail">{{ $stockDetail }}</span>
                        @endif
                    </div>
                </div>

                <h2 class="product-details-name">{{ $product->product_name }}</h2>

                <div class="product-details-price-row">
                    <div class="product-price-large">{{ \App\Support\Money::format((float) $product->price) }}</div>
                    @if ($product->category?->category_name)
                        <span class="product-category-tag">{{ $product->category->category_name }}</span>
                    @endif
                </div>

                @if ($product->reviews->isNotEmpty())
                    @php
                        $avgRating = round((float) ($product->reviews_avg_rating ?? $product->reviews->avg('rating')), 1);
                        $reviewCount = $product->reviews->count();
                        $fullStars = (int) round($avgRating);
                    @endphp
                    <p class="product-rating-summary">
                        <span class="stars-gold" aria-hidden="true">{{ str_repeat('★', $fullStars) }}{{ str_repeat('☆', 5 - $fullStars) }}</span>
                        <span class="text-secondary">{{ $avgRating }} / 5 ({{ $reviewCount }} {{ $reviewCount === 1 ? 'review' : 'reviews' }})</span>
                    </p>
                @endif

                <div class="product-details-description">
                    <h3>Description</h3>
                    <p class="text-secondary">{{ $product->customerDescription() }}</p>
                </div>

                @if (session('status'))
                    <p class="ok" style="margin-bottom:12px;">{{ session('status') }}</p>
                @endif

                <form method="post" action="{{ route('cart.add') }}" class="product-qty-add">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->product_id }}">
                    <div class="qty-field">
                        <label for="quantity">Quantity</label>
                        <input id="quantity" class="qty-input" type="number" name="quantity" value="1" min="1" max="20">
                    </div>
                    <button type="submit" class="btn btn-primary product-add-btn" @disabled($stock <= 0)>Add to Basket</button>
                </form>
                @guest
                    <p class="text-secondary" style="margin-top:10px;font-size:14px;">
                        You can shop without signing in. <a href="{{ route('login') }}">Sign in</a> when you are ready to checkout.
                    </p>
                @endguest

                <section class="reviews-section" aria-labelledby="reviews-section-title">
                    <h3 id="reviews-section-title" class="reviews-section-title">Reviews</h3>

                    @auth
                        @if ($canReview)
                            <form method="post" action="{{ route('products.reviews.store', $product->product_id) }}" class="card review-form">
                                @csrf
                                <p class="review-form-label">Write a review</p>
                                <div class="star-rating" role="radiogroup" aria-label="Your rating">
                                    @for ($i = 5; $i >= 1; $i--)
                                        <input
                                            type="radio"
                                            name="rating"
                                            id="rating-star-{{ $i }}"
                                            value="{{ $i }}"
                                            class="star-rating__input"
                                            @checked((int) old('rating') === $i)
                                            required
                                        >
                                        <label for="rating-star-{{ $i }}" class="star-rating__star" title="{{ $i }} out of 5">★</label>
                                    @endfor
                                </div>
                                @if ($errors->has('rating') || $errors->has('review'))
                                    <div class="alert alert-error" style="margin-bottom:12px;">
                                        @foreach (['rating', 'review'] as $field)
                                            @error($field)
                                                <p>{{ $message }}</p>
                                            @enderror
                                        @endforeach
                                    </div>
                                @endif

                                <div class="form-field">
                                    <label for="review_body">Your review <span class="text-secondary">(optional)</span></label>
                                    <textarea id="review_body" name="review_body" rows="4" maxlength="1000" placeholder="Share your experience with this product…">{{ old('review_body') }}</textarea>
                                </div>
                                @error('review_body')
                                    <p class="alert alert-error" style="margin-top:8px;">{{ $message }}</p>
                                @enderror

                                <button type="submit" class="btn btn-primary">Submit review</button>
                            </form>
                        @elseif (auth()->user()->customer)
                            <p class="text-secondary review-note">Purchase this product once to leave a review and comment on others.</p>
                        @else
                            <p class="text-secondary review-note">Customer accounts can leave reviews after signing in.</p>
                        @endif
                    @else
                        <p class="text-secondary review-note"><a href="{{ route('login') }}">Sign in</a> to leave a rating and review.</p>
                    @endauth

                    @if ($product->reviews->isNotEmpty())
                        <div class="reviews-grid">
                            @foreach ($product->reviews as $review)
                                <article class="card review-card" id="review-{{ $review->review_id }}">
                                    <div class="review-head">
                                        <strong>{{ trim(($review->customer->user->first_name ?? '').' '.($review->customer->user->last_name ?? '')) ?: 'Customer' }}</strong>
                                        <span class="stars-gold" aria-label="{{ $review->rating }} out of 5">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', 5 - (int) $review->rating) }}</span>
                                    </div>
                                    @if (filled($review->review_body))
                                        <p class="review-body-text">{{ $review->review_body }}</p>
                                    @endif
                                    @if ($review->review_date)
                                        <time class="review-date text-secondary" datetime="{{ $review->review_date->toDateString() }}">{{ $review->review_date->format('j M Y') }}</time>
                                    @endif

                                    @if (!empty($review->trader_reply))
                                        <div class="review-trader-reply">
                                            <p class="review-reply-label">Shop reply</p>
                                            <p>{{ $review->trader_reply }}</p>
                                            @if ($review->trader_reply_date)
                                                <time class="review-date text-secondary" datetime="{{ $review->trader_reply_date->toDateString() }}">{{ $review->trader_reply_date->format('j M Y') }}</time>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($review->comments->isNotEmpty())
                                        <ul class="review-comments">
                                            @foreach ($review->comments as $comment)
                                                <li class="review-comment">
                                                    <strong>{{ trim(($comment->customer->user->first_name ?? '').' '.($comment->customer->user->last_name ?? '')) ?: 'Customer' }}</strong>
                                                    <p>{{ $comment->comment_body }}</p>
                                                    @if ($comment->comment_date)
                                                        <time class="review-date text-secondary" datetime="{{ $comment->comment_date->toDateString() }}">{{ $comment->comment_date->format('j M Y') }}</time>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @auth
                                        @if ($canReview)
                                            <form method="post" action="{{ route('reviews.comments.store', $review->review_id) }}" class="review-comment-form">
                                                @csrf
                                                <label for="comment-{{ $review->review_id }}" class="sr-only">Comment on this review</label>
                                                <textarea id="comment-{{ $review->review_id }}" name="comment_body" rows="2" maxlength="500" required placeholder="Add a comment…"></textarea>
                                                @error('comment')
                                                    <p class="alert alert-error" style="margin-top:8px;">{{ $message }}</p>
                                                @enderror
                                                @error('comment_body')
                                                    <p class="alert alert-error" style="margin-top:8px;">{{ $message }}</p>
                                                @enderror
                                                <button type="submit" class="btn btn-outline btn-sm">Post comment</button>
                                            </form>
                                        @endif
                                    @endauth
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p class="text-secondary">No reviews yet. Be the first to share your thoughts after you purchase.</p>
                    @endif
                </section>
            </article>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-product-gallery] .product-gallery-thumb').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var src = btn.getAttribute('data-gallery-src');
                var main = document.getElementById('productGalleryMain');
                if (!src || !main) return;
                main.src = src;
                document.querySelectorAll('[data-product-gallery] .product-gallery-thumb').forEach(function (b) {
                    b.classList.remove('is-active');
                    b.setAttribute('aria-pressed', 'false');
                });
                btn.classList.add('is-active');
                btn.setAttribute('aria-pressed', 'true');
            });
        });

        document.querySelectorAll('[data-you-may-like-carousel]').forEach(function (root) {
            var track = root.querySelector('[data-you-may-like-track]');
            var prev = root.querySelector('.you-may-like-arrow--prev');
            var next = root.querySelector('.you-may-like-arrow--next');
            if (!track || !prev || !next) return;

            function slideStep(direction) {
                var slide = track.querySelector('.you-may-like-slide');
                var gap = 10;
                var amount = slide ? slide.offsetWidth + gap : track.clientWidth;
                track.scrollBy({ left: direction * amount, behavior: 'smooth' });
            }

            function updateArrows() {
                var maxScroll = track.scrollWidth - track.clientWidth - 2;
                prev.disabled = track.scrollLeft <= 2;
                next.disabled = track.scrollLeft >= maxScroll;
            }

            prev.addEventListener('click', function () { slideStep(-1); });
            next.addEventListener('click', function () { slideStep(1); });
            track.addEventListener('scroll', updateArrows, { passive: true });
            window.addEventListener('resize', updateArrows);
            updateArrows();
        });
    </script>
@endpush
