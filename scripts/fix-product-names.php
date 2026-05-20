<?php
/**
 * Remove quantity numbers from product names, e.g. "Croissants (6)" → "Croissants".
 * Run: php scripts/fix-product-names.php
 */
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$renames = [
    'P30' => 'Butter Croissants',
    'P32' => 'Chelsea Buns',
    'P33' => 'Cinnamon Swirls',
];

foreach ($renames as $productId => $name) {
    $updated = DB::table('product')
        ->where('product_id', $productId)
        ->update(['product_name' => $name]);

    if ($updated) {
        echo "Renamed {$productId} → {$name}\n";
    } else {
        echo "Skip {$productId} (not found or unchanged).\n";
    }
}
