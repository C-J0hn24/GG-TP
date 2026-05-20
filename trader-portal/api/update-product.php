<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/product-images.php';
require_once dirname(__DIR__) . '/includes/product-pricing.php';

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['ok' => false, 'error' => 'POST required'], 405);
}

$me = auth_user();
if (!$me || strtolower($me['role']) !== 'trader' || ! trader_has_shop($me)) {
    json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
}

if (!portal_verify_csrf()) {
    json_response(['ok' => false, 'error' => 'CSRF'], 419);
}

$shopId = trader_shop_id($me);
$pid = trim((string) ($_POST['product_id'] ?? ''));
$name = trim((string) ($_POST['product_name'] ?? ''));
$descText = trim((string) ($_POST['description'] ?? ''));
$categoryId = trim((string) ($_POST['category_id'] ?? ''));
$price = round((float) str_replace(',', '.', (string) ($_POST['price'] ?? '0')), 2);
$stock = (int) ($_POST['stock'] ?? 0);
$maxOrder = (int) ($_POST['max_per_order'] ?? 1);

if ($pid === '' || $name === '' || $categoryId === '') {
    json_response(['ok' => false, 'error' => 'Validation failed'], 422);
}

$pricingError = null;
if (! product_validate_pricing_stock($price, $stock, $maxOrder, $pricingError)) {
    json_response(['ok' => false, 'error' => $pricingError], 422);
}

$row = db_fetch_one(
    'SELECT description FROM product WHERE product_id = :pid AND shop_id = :sid',
    ['pid' => $pid, 'sid' => $shopId]
);
if (!$row) {
    json_response(['ok' => false, 'error' => 'Product not found'], 404);
}

$currentDesc = (string) ($row['description'] ?? '');
$previousImages = product_image_filenames($currentDesc);

$keep = [];
foreach ((array) ($_POST['keep_images'] ?? []) as $file) {
    if (!is_string($file) || $file === '') {
        continue;
    }
    $base = basename($file);
    if (in_array($base, $previousImages, true)) {
        $keep[] = $base;
    }
}

$newUploads = [];
if (!empty($_FILES['images'])) {
    $newUploads = product_process_image_uploads($shopId, $pid, $_FILES['images']);
    if ($newUploads === [] && product_normalize_files_input($_FILES['images']) !== null) {
        json_response(['ok' => false, 'error' => 'Image upload failed. Use JPEG, PNG, WebP, or GIF under ' . (int) MAX_UPLOAD_MB . 'MB each.'], 422);
    }
}

$allImages = array_values(array_merge($keep, $newUploads));
$finalDesc = product_set_images_on_description($descText, $currentDesc, $allImages);

$sql = 'UPDATE product SET product_name = :n, description = :d, price = :p,
        product_in_stock = :s, category_id = :c
        WHERE product_id = :pid AND shop_id = :sid';

try {
    $st = db_execute($sql, [
        'n' => $name,
        'd' => $finalDesc,
        'p' => $price,
        's' => $stock,
        'c' => $categoryId,
        'pid' => $pid,
        'sid' => $shopId,
    ]);
    if (!$st) {
        throw new RuntimeException('Update failed');
    }
    oci_free_statement($st);

    product_delete_removed_images($shopId, $previousImages, $allImages);

    db_commit();
    json_response(['ok' => true]);
} catch (Throwable $e) {
    db_rollback();
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
