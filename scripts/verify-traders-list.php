<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$shops = App\Models\Shop::query()
    ->whereHas('products', function ($q): void {
        $q->where('product_in_stock', '>', 0)
            ->where(function ($inner): void {
                $inner->whereNull('description')
                    ->orWhereRaw("description NOT LIKE '%STATUS:draft%'");
            });
    })
    ->orderBy('shop_name')
    ->get(['shop_id', 'shop_name']);

foreach ($shops as $s) {
    echo "{$s->shop_id} | {$s->shop_name}\n";
}
echo 'Total: '.$shops->count()."\n";
