<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Services\Catalog\CatalogService;
use App\Services\Review\ReviewService;
use App\Support\ShopMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CatalogWebController extends Controller
{
    public function __construct(
        protected CatalogService $catalogService,
        protected ReviewService $reviewService,
    ) {
    }

    public function categories(Request $request): View
    {
        $filters = [
            'q' => $request->query('q'),
            'category_id' => array_values(array_filter((array) $request->query('category_id', []))),
            'shop_id' => array_values(array_filter((array) $request->query('shop_id', []))),
            'sort' => $request->query('sort', 'name'),
            'min_rating' => $request->query('min_rating'),
        ];

        $shops = Shop::query()->orderBy('shop_name')->get();

        return view('categories.index', [
            'products' => $this->catalogService->products($filters),
            'categories' => $this->catalogService->categories(),
            'shops' => $shops,
            'shopLogos' => $shops->mapWithKeys(
                fn (Shop $shop) => [$shop->shop_id => ShopMedia::forShop($shop)]
            ),
            'filters' => $filters,
        ]);
    }

    public function show(string $id): View
    {
        $product = $this->catalogService->productDetail($id);
        $user = Auth::user();
        $canReview = $user
            && $this->reviewService->canLeaveReview($user, $id);
        $canCommentOnReviews = $user
            && $this->reviewService->hasPurchasedProduct($user, $id);

        $similarProducts = $this->catalogService
            ->similarProducts($product)
            ->map(fn ($p) => $this->catalogService->productCardPayload($p));

        return view('products.show', [
            'product' => $product,
            'productId' => $id,
            'canReview' => $canReview,
            'canCommentOnReviews' => $canCommentOnReviews,
            'similarProducts' => $similarProducts,
        ]);
    }

    public function shops(): View
    {
        $shops = Shop::query()
            ->whereHas('products', function ($q): void {
                $q->where('product_in_stock', '>', 0)
                    ->where(function ($inner): void {
                        $inner->whereNull('description')
                            ->orWhereRaw("description NOT LIKE '%STATUS:draft%'");
                    });
            })
            ->orderBy('shop_name')
            ->get();

        return view('traders.index', [
            'traders' => $shops,
            'shopLogos' => $shops->mapWithKeys(
                fn (Shop $shop) => [$shop->shop_id => ShopMedia::forShop($shop)]
            ),
        ]);
    }
}
