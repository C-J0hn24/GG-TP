<?php
/**
 * Copy catalog photos into product uploads, shop logos, and public category icons;
 * update PRODUCT.description with |IMG:...| references.
 *
 * Usage: php scripts/apply-catalog-images.php [--dry-run]
 */
declare(strict_types=1);

$dryRun = in_array('--dry-run', $argv ?? [], true);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$root = dirname(__DIR__);
$sourceDir = $root . '/storage/catalog-images';
$cursorAssets = getenv('CURSOR_PROJECT_ASSETS')
    ?: (is_dir($root . '/../.cursor/projects/c-xampp-htdocs-GG-TP/assets')
        ? realpath($root . '/../.cursor/projects/c-xampp-htdocs-GG-TP/assets')
        : null);
if ($cursorAssets === null) {
    $home = getenv('USERPROFILE') ?: getenv('HOME') ?: '';
    $fallback = $home !== ''
        ? $home . '/.cursor/projects/c-xampp-htdocs-GG-TP/assets'
        : '';
    $cursorAssets = is_dir($fallback) ? $fallback : '';
}

/** @var array<string, string> logical name => filename in storage/catalog-images */
$stockFiles = [
    'steak' => 'steak.png',
    'chicken-fillet' => 'chicken-fillet.png',
    'butcher' => 'butcher.png',
    'ham' => 'ham.png',
    'salmon' => 'salmon.png',
    'cod-loin' => 'cod-loin.png',
    'king-prawns' => 'king-prawns.png',
    'fishmonger' => 'fishmonger.png',
    'brie-cheese' => 'brie-cheese.png',
    'hummus' => 'hummus.png',
    'mature-cheddar' => 'mature-cheddar.png',
    'olives' => 'olives.png',
    'deli' => 'deli.png',
    'bakery' => 'bakery.png',
    'greengrocer' => 'greengrocer.png',
    'apples' => 'apples.png',
    'bananas' => 'bananas.png',
    'tomatoes' => 'tomatoes.png',
    'potatoes' => 'potatoes.png',
    'pork-sausages' => 'pork-sausages.png',
    'parma-ham' => 'parma-ham.png',
    'smoked-haddock' => 'smoked-haddock.png',
    'sourdough-loaf' => 'sourdough-loaf.png',
    'butter-croissants' => 'butter-croissants.png',
    'wholemeal-bloomer' => 'wholemeal-bloomer.png',
    'chelsea-buns' => 'chelsea-buns.png',
    'cinnamon-swirls' => 'cinnamon-swirls.png',
    'mixed-salad-leaves' => 'mixed-salad-leaves.png',
];

/** @var array<string, string> stock key => cursor asset filename needle */
$userProductSources = [
    'apples' => 'images_Apples',
    'bananas' => 'images_Bananas',
    'tomatoes' => 'images_Tomatoes',
    'potatoes' => 'images_Potatoes',
    'pork-sausages' => 'images_Pork_Sausages',
    'parma-ham' => 'images_Parma_Ham',
    'smoked-haddock' => 'images_Smoked_Haddock',
    'sourdough-loaf' => 'images_Sourdough_loaf',
    'butter-croissants' => 'images_butter_croissants',
    'wholemeal-bloomer' => 'images_Wholemeal_bloomer',
    'chelsea-buns' => 'images_Chelsea_Buns',
    'cinnamon-swirls' => 'images_Cinnamon_Swirls',
    'mixed-salad-leaves' => 'images_Mixed_Salad_Leaves',
];

/** @var array<string, string> product_id => stock key */
$productMap = [
    'P1' => 'steak',
    'P2' => 'butcher',
    'P3' => 'pork-sausages',
    'P4' => 'chicken-fillet',
    'P5' => 'salmon',
    'P6' => 'cod-loin',
    'P7' => 'king-prawns',
    'P8' => 'smoked-haddock',
    'P18' => 'mature-cheddar',
    'P19' => 'parma-ham',
    'P20' => 'olives',
    'P21' => 'brie-cheese',
    'P22' => 'hummus',
    'P24' => 'apples',
    'P25' => 'bananas',
    'P26' => 'tomatoes',
    'P27' => 'mixed-salad-leaves',
    'P28' => 'potatoes',
    'P29' => 'sourdough-loaf',
    'P30' => 'butter-croissants',
    'P31' => 'wholemeal-bloomer',
    'P32' => 'chelsea-buns',
    'P33' => 'cinnamon-swirls',
];

/** @var array<string, string> shop_id => stock key */
$shopMap = [
    'SH1' => 'butcher',
    'SH2' => 'fishmonger',
    'SH3' => 'greengrocer',
    'SH4' => 'bakery',
    'SH5' => 'deli',
];

/** @var array<string, string> public/assets/icons filename => stock key */
$iconMap = [
    'butcher.png' => 'butcher',
    'fishmonger.png' => 'fishmonger',
    'bakery.png' => 'bakery',
    'delicatessen.png' => 'deli',
    'greengrocer.png' => 'greengrocer',
];

function find_cursor_asset(string $cursorDir, string $needle): ?string
{
    if ($cursorDir === '' || ! is_dir($cursorDir)) {
        return null;
    }
    $needle = strtolower($needle);
    foreach (scandir($cursorDir) ?: [] as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        if (str_contains(strtolower($file), $needle)) {
            return $cursorDir . '/' . $file;
        }
    }

    return null;
}

function import_user_product_assets(string $sourceDir, string $cursorDir, array $stockFiles, array $userSources): void
{
    if ($cursorDir === '' || ! is_dir($cursorDir)) {
        return;
    }
    if (! is_dir($sourceDir) && ! mkdir($sourceDir, 0775, true) && ! is_dir($sourceDir)) {
        throw new RuntimeException('Cannot create ' . $sourceDir);
    }

    foreach ($userSources as $key => $needle) {
        $destName = $stockFiles[$key] ?? null;
        if ($destName === null) {
            continue;
        }
        $src = find_cursor_asset($cursorDir, $needle);
        if ($src === null) {
            fwrite(STDERR, "User image not found for {$key} (needle: {$needle})\n");
            continue;
        }
        $dest = $sourceDir . '/' . $destName;
        if (! copy($src, $dest)) {
            throw new RuntimeException("Failed to copy {$src} -> {$dest}");
        }
        echo "Imported {$destName} from user assets\n";
    }
}

function ensure_stock_files(string $sourceDir, string $cursorDir, array $stockFiles): void
{
    if (! is_dir($sourceDir) && ! mkdir($sourceDir, 0775, true) && ! is_dir($sourceDir)) {
        throw new RuntimeException('Cannot create ' . $sourceDir);
    }

    foreach ($stockFiles as $key => $destName) {
        $dest = $sourceDir . '/' . $destName;
        if (is_file($dest)) {
            continue;
        }

        $needles = match ($key) {
            'steak' => 'images_steak',
            'salmon' => 'images_salmon',
            'olives' => 'images_olives',
            'cod-loin' => 'cod_loin',
            'chicken-fillet' => 'chicken-fillet',
            'king-prawns' => 'king_prawns',
            'mature-cheddar' => 'mature_cheddar',
            'brie-cheese' => 'brie_cheese',
            'greengrocer' => 'grocery_png',
            default => str_replace('-', '_', $key) . '_png',
        };

        $src = find_cursor_asset($cursorDir, $needles);
        if ($src === null) {
            fwrite(STDERR, "Missing source for {$key} (needle: {$needles})\n");
            continue;
        }
        if (! copy($src, $dest)) {
            throw new RuntimeException("Failed to copy {$src} -> {$dest}");
        }
        echo "Staged {$destName} from cursor assets\n";
    }
}

function copy_file(string $from, string $to, bool $dryRun): void
{
    if ($dryRun) {
        echo "[dry-run] copy {$from} -> {$to}\n";

        return;
    }
    $dir = dirname($to);
    if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
        throw new RuntimeException('Cannot create ' . $dir);
    }
    if (! copy($from, $to)) {
        throw new RuntimeException("Copy failed: {$from} -> {$to}");
    }
}

function product_set_image_ref(string $description, string $imageBasename): string
{
    $description = preg_replace('/\|IMG:[^|\n]*/', '', $description ?? '') ?? '';
    $description = rtrim($description);

    return $description . '|IMG:' . $imageBasename;
}

import_user_product_assets($sourceDir, $cursorAssets, $stockFiles, $userProductSources);
ensure_stock_files($sourceDir, $cursorAssets, $stockFiles);

$updated = 0;
foreach ($productMap as $productId => $stockKey) {
    $file = $stockFiles[$stockKey] ?? null;
    if ($file === null || ! is_file($sourceDir . '/' . $file)) {
        fwrite(STDERR, "Skip product {$productId}: no stock file for {$stockKey}\n");
        continue;
    }

    $row = Illuminate\Support\Facades\DB::table('product')
        ->where('product_id', $productId)
        ->first(['product_id', 'shop_id', 'description']);
    if (! $row) {
        fwrite(STDERR, "Product {$productId} not in database\n");
        continue;
    }

    $shopId = (string) $row->shop_id;
    $basename = $productId . '_' . $file;
    $dest = $root . '/trader-portal/assets/uploads/products/' . $shopId . '/' . $basename;
    copy_file($sourceDir . '/' . $file, $dest, $dryRun);

    $newDesc = product_set_image_ref((string) ($row->description ?? ''), $basename);
    if ($dryRun) {
        echo "[dry-run] UPDATE product {$productId} |IMG:{$basename}\n";
    } else {
        Illuminate\Support\Facades\DB::table('product')
            ->where('product_id', $productId)
            ->update(['description' => $newDesc]);
    }
    $updated++;
    echo "Product {$productId} -> {$basename}\n";
}

foreach ($shopMap as $shopId => $stockKey) {
    $file = $stockFiles[$stockKey] ?? null;
    if ($file === null || ! is_file($sourceDir . '/' . $file)) {
        continue;
    }
    $dest = $root . '/trader-portal/assets/uploads/shop/' . $shopId . '/logo.png';
    copy_file($sourceDir . '/' . $file, $dest, $dryRun);
    echo "Shop {$shopId} logo\n";
}

foreach ($iconMap as $iconName => $stockKey) {
    $file = $stockFiles[$stockKey] ?? null;
    if ($file === null || ! is_file($sourceDir . '/' . $file)) {
        continue;
    }
    $dest = $root . '/public/assets/icons/' . $iconName;
    copy_file($sourceDir . '/' . $file, $dest, $dryRun);
    echo "Icon {$iconName}\n";
}

echo $dryRun
    ? "Dry run complete ({$updated} products).\n"
    : "Done. Updated {$updated} products.\n";
