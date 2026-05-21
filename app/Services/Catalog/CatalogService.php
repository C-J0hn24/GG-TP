<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Support\ProductMeta;
use App\Support\ProductPricing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CatalogService
{
    public function categories()
    {
        return Category::query()->orderBy('cat_name')->get();
    }

    /**
     * Home page category tiles → filtered shopping (categories) URLs.
     *
     * @return list<array{label: string, icon: string, url: string}>
     */
    public function homeCategoryCards(): array
    {
        $dbCategories = Category::query()->orderBy('cat_name')->get();

        $definitions = [
            ['label' => 'Butcher', 'icon' => 'butcher.png', 'names' => ['meat', 'butcher']],
            ['label' => 'Bakery', 'icon' => 'bakery.png', 'names' => ['bakery']],
            ['label' => 'Greengrocer', 'icon' => 'greengrocer.png', 'names' => ['greengrocer', 'produce', 'fruit', 'vegetable']],
            ['label' => 'Fishmonger', 'icon' => 'fishmonger.png', 'names' => ['fish', 'fishmonger', 'seafood']],
            ['label' => 'Delicatessen', 'icon' => 'delicatessen.png', 'names' => ['delicatessen', 'deli']],
        ];

        return array_map(function (array $def) use ($dbCategories): array {
            $match = $dbCategories->first(function (Category $cat) use ($def): bool {
                $name = strtolower(trim($cat->category_name));

                return in_array($name, $def['names'], true);
            });

            $url = $match
                ? route('categories', ['category_id' => [$match->category_id]])
                : route('categories', ['q' => $def['label']]);

            return [
                'label' => $def['label'],
                'icon' => $def['icon'],
                'url' => $url,
            ];
        }, $definitions);
    }

    public function products(array $filters)
    {
        $query = Product::query()
            ->with(['shop', 'category', 'discounts' => fn ($q) => ProductPricing::scopeActiveDiscounts($q)])
            ->withAvg('reviews', 'rating');

        $this->applyCustomerVisibility($query);
        $this->applyFilters($query, $filters);
        $this->applySort($query, (string) ($filters['sort'] ?? 'name'));

        return $query->paginate(20);
    }

    public function productDetail(string $productId): Product
    {
        $query = Product::query()
            ->withAvg('reviews', 'rating')
            ->with([
                'shop.trader.user',
                'category',
                'discounts' => fn ($q) => ProductPricing::scopeActiveDiscounts($q),
                'reviews' => fn ($q) => $q->orderByDesc('review_date'),
                'reviews.customer.user',
                'reviews.comments.customer.user',
            ]);

        $this->applyCustomerVisibility($query);

        return $query->findOrFail($productId);
    }

    /**
     * Products similar to the one being viewed (same category, then same shop).
     */
    public function similarProducts(Product $product, int $limit = 6): Collection
    {
        $base = Product::query()
            ->with(['shop', 'category', 'discounts' => fn ($q) => ProductPricing::scopeActiveDiscounts($q)])
            ->withAvg('reviews', 'rating')
            ->where('product_id', '!=', $product->product_id);

        $this->applyCustomerVisibility($base);

        $picked = collect();

        if ($product->category_id) {
            $picked = (clone $base)
                ->where('category_id', $product->category_id)
                ->orderBy('product_name')
                ->limit($limit)
                ->get();
        }

        if ($picked->count() < $limit && $product->shop_id) {
            $exclude = $picked->pluck('product_id')->push($product->product_id)->all();
            $more = (clone $base)
                ->where('shop_id', $product->shop_id)
                ->whereNotIn('product_id', $exclude)
                ->orderBy('product_name')
                ->limit($limit - $picked->count())
                ->get();
            $picked = $picked->concat($more);
        }

        if ($picked->count() < $limit) {
            $exclude = $picked->pluck('product_id')->push($product->product_id)->all();
            $more = (clone $base)
                ->whereNotIn('product_id', $exclude)
                ->orderBy('product_name')
                ->limit($limit - $picked->count())
                ->get();
            $picked = $picked->concat($more);
        }

        return $picked->take($limit)->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function productCardPayload(Product $p): array
    {
        $stock = (int) ($p->product_in_stock ?? 0);
        $uploadedImage = ProductMeta::primaryImageUrl($p->shop_id, $p->description);
        $displayImage = $p->customerPrimaryImageUrl();

        $pricing = ProductPricing::pricePayload($p);

        return [
            'id' => $p->product_id,
            'trader' => $p->shop->shop_name ?? 'Shop',
            'category' => $p->category->category_name ?? '',
            'name' => $p->product_name,
            'image' => $displayImage,
            'image_placeholder' => $uploadedImage === null && $displayImage !== null,
            'price' => $pricing['unit'],
            'original_price' => $pricing['original'],
            'on_sale' => $pricing['on_sale'],
            'discount_rate' => $pricing['rate'],
            'stock' => [
                'label' => $stock <= 0 ? 'Out of Stock' : ($stock <= 5 ? 'Low Stock' : 'In Stock'),
                'variant' => $stock <= 0 ? 'out' : ($stock <= 5 ? 'low' : 'in'),
            ],
        ];
    }

    protected function applyCustomerVisibility(Builder $query): void
    {
        $query->where('product_in_stock', '>', 0)
            ->where(function (Builder $q): void {
                $q->whereNull('description')
                    ->orWhereRaw("description NOT LIKE '%STATUS:draft%'");
            });
    }

    protected function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy('price')->orderBy('product_name'),
            'price_desc' => $query->orderByDesc('price')->orderBy('product_name'),
            'rating_desc' => $query->orderByDesc('reviews_avg_rating')->orderBy('product_name'),
            default => $query->orderBy('product_name'),
        };
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'] ?? null, fn (Builder $q, $v) => $q->whereRaw('LOWER(product_name) LIKE ?', ['%'.strtolower($v).'%']))
            ->when(! empty($filters['category_id']), function (Builder $q) use ($filters): void {
                $q->whereIn('category_id', (array) $filters['category_id']);
            })
            ->when(! empty($filters['shop_id']), function (Builder $q) use ($filters): void {
                $q->whereIn('shop_id', (array) $filters['shop_id']);
            })
            ->when($filters['min_price'] ?? null, fn (Builder $q, $v) => $q->where('price', '>=', $v))
            ->when($filters['max_price'] ?? null, fn (Builder $q, $v) => $q->where('price', '<=', $v))
            ->when($filters['min_rating'] ?? null, function (Builder $q, $v): void {
                $q->whereExists(function ($sub) use ($v) {
                    $sub->select(DB::raw(1))
                        ->from('REVIEW')
                        ->whereColumn('REVIEW.product_id', 'PRODUCT.product_id')
                        ->groupBy('REVIEW.product_id')
                        ->havingRaw('AVG(REVIEW.rating) >= ?', [$v]);
                });
            });
    }
}

