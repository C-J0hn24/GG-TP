<?php
/**
 * Seed sample products for SH3 (greengrocer) and SH4 (bakery) so they appear on /shops.
 * Run: php scripts/seed-shop-products.php
 */
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\OracleId;
use Illuminate\Support\Facades\DB;

function productDescription(string $text, string $unit, int $max = 10): string
{
    return $text . "\n\n<!--UNIT:{$unit}|MAX:{$max}|STATUS:published|AVAIL:both-->";
}

/** @var list<array{shop_id: string, category_id: string, product_name: string, description: string, price: float, product_in_stock: int}> */
$catalog = [
  // Green Valley Grocers
    ['shop_id' => 'SH3', 'category_id' => 'CA3', 'product_name' => 'British Bramley Apples', 'description' => productDescription('Crisp Bramley apples, ideal for cooking.', '1kg'), 'price' => 2.49, 'product_in_stock' => 25],
    ['shop_id' => 'SH3', 'category_id' => 'CA3', 'product_name' => 'Fairtrade Bananas', 'description' => productDescription('Ripe fairtrade bananas.', 'bunch'), 'price' => 1.29, 'product_in_stock' => 40],
    ['shop_id' => 'SH3', 'category_id' => 'CA3', 'product_name' => 'Vine Tomatoes', 'description' => productDescription('Sweet vine-ripened tomatoes.', '500g'), 'price' => 2.19, 'product_in_stock' => 20],
    ['shop_id' => 'SH3', 'category_id' => 'CA3', 'product_name' => 'Mixed Salad Leaves', 'description' => productDescription('Washed mixed leaves, ready to serve.', '200g'), 'price' => 1.99, 'product_in_stock' => 15],
    ['shop_id' => 'SH3', 'category_id' => 'CA3', 'product_name' => 'New Potatoes', 'description' => productDescription('Waxy new potatoes from local farms.', '1kg'), 'price' => 1.79, 'product_in_stock' => 30],
  // Golden Crust Bakery
    ['shop_id' => 'SH4', 'category_id' => 'CA4', 'product_name' => 'Sourdough Loaf', 'description' => productDescription('Slow-fermented sourdough, baked daily.', 'each'), 'price' => 3.49, 'product_in_stock' => 12],
    ['shop_id' => 'SH4', 'category_id' => 'CA4', 'product_name' => 'Butter Croissants', 'description' => productDescription('All-butter croissants, baked this morning.', 'pack'), 'price' => 4.29, 'product_in_stock' => 10],
    ['shop_id' => 'SH4', 'category_id' => 'CA4', 'product_name' => 'Wholemeal Bloomer', 'description' => productDescription('Traditional wholemeal bloomer loaf.', 'each'), 'price' => 2.79, 'product_in_stock' => 15],
    ['shop_id' => 'SH4', 'category_id' => 'CA4', 'product_name' => 'Chelsea Buns', 'description' => productDescription('Sticky Chelsea buns with currants.', 'pack'), 'price' => 3.99, 'product_in_stock' => 8],
    ['shop_id' => 'SH4', 'category_id' => 'CA4', 'product_name' => 'Cinnamon Swirls', 'description' => productDescription('Soft swirls with cinnamon butter.', 'pack'), 'price' => 3.49, 'product_in_stock' => 8],
];

$inserted = 0;
$skipped = 0;

foreach ($catalog as $row) {
    if (! DB::table('shop')->where('shop_id', $row['shop_id'])->exists()) {
        fwrite(STDERR, "Skip — shop {$row['shop_id']} not found.\n");
        $skipped++;
        continue;
    }

    $exists = DB::table('product')
        ->where('shop_id', $row['shop_id'])
        ->where('product_name', $row['product_name'])
        ->exists();

    if ($exists) {
        echo "Skip — {$row['shop_id']} already has \"{$row['product_name']}\".\n";
        $skipped++;
        continue;
    }

    $productId = OracleId::next('product', 'product_id', 'P');

    DB::table('product')->insert([
        'product_id' => $productId,
        'product_name' => $row['product_name'],
        'description' => $row['description'],
        'price' => $row['price'],
        'product_in_stock' => $row['product_in_stock'],
        'category_id' => $row['category_id'],
        'shop_id' => $row['shop_id'],
    ]);

    echo "Inserted {$productId} — {$row['shop_id']} — {$row['product_name']}\n";
    $inserted++;
}

echo "\nDone. Inserted {$inserted}, skipped {$skipped}.\n\nProducts per shop:\n";
foreach (DB::table('product')->select('shop_id', DB::raw('count(*) as c'))->groupBy('shop_id')->orderBy('shop_id')->get() as $r) {
    echo "  {$r->shop_id}: {$r->c}\n";
}
