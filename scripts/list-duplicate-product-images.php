<?php
/**
 * List products that share the same stock photo (need a dedicated image).
 * Run: php scripts/list-duplicate-product-images.php
 */
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

/** @var array<string, list<array{product_id: string, product_name: string, shop_id: string, image: string}>> */
$byStock = [];

foreach (DB::table('product')->orderBy('product_id')->get() as $p) {
    $desc = (string) ($p->description ?? '');
    if (! preg_match('/\|IMG:([^|\n]+)/', $desc, $m)) {
        continue;
    }
    $basename = trim(explode(',', $m[1])[0]);
    $stockKey = $basename;
    if (preg_match('/^P\d+_(.+)\.[^.]+$/i', $basename, $parts)) {
        $stockKey = $parts[1];
    }

    $byStock[$stockKey][] = [
        'product_id' => (string) $p->product_id,
        'product_name' => (string) $p->product_name,
        'shop_id' => (string) $p->shop_id,
        'image' => $basename,
    ];
}

$duplicates = array_filter($byStock, fn (array $rows): bool => count($rows) > 1);
$noImage = DB::table('product')
    ->where(function ($q): void {
        $q->whereNull('description')
            ->orWhereRaw("description NOT LIKE '%|IMG:%'");
    })
    ->orderBy('product_id')
    ->get(['product_id', 'product_name', 'shop_id']);

if ($duplicates === [] && $noImage->isEmpty()) {
    echo "No duplicate stock images and no products missing images.\n";
    exit(0);
}

if ($duplicates !== []) {
    echo "Products sharing the same stock photo (need a new image):\n\n";
    foreach ($duplicates as $stockKey => $rows) {
        echo "Stock photo: {$stockKey}\n";
        foreach ($rows as $r) {
            echo "  - {$r['product_id']} | {$r['shop_id']} | {$r['product_name']} ({$r['image']})\n";
        }
        echo "\n";
    }
    echo 'Duplicate groups: '.count($duplicates)."\n\n";
}

if ($noImage->isNotEmpty()) {
    echo "Products with no uploaded image:\n";
    foreach ($noImage as $p) {
        echo "  - {$p->product_id} | {$p->shop_id} | {$p->product_name}\n";
    }
}
