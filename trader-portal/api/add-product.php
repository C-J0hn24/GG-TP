<?php
/**
 * AJAX: create product (multipart or JSON body).
 */
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

$csrf = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!is_string($csrf) || !hash_equals($_SESSION['_csrf'] ?? '', $csrf)) {
    json_response(['ok' => false, 'error' => 'CSRF'], 419);
}

$shopId = trader_shop_id($me);

$name = trim((string) ($_POST['product_name'] ?? ''));
$desc = trim((string) ($_POST['description'] ?? ''));
$categoryId = trim((string) ($_POST['category_id'] ?? ''));
$price = round((float) str_replace(',', '.', (string) ($_POST['price'] ?? '0')), 2);
$stock = (int) ($_POST['stock'] ?? 0);
$unit = trim((string) ($_POST['unit'] ?? ''));
$maxOrder = (int) ($_POST['max_per_order'] ?? 1);
$status = strtolower(trim((string) ($_POST['status'] ?? 'draft')));
$availability = trim((string) ($_POST['availability'] ?? 'both'));
$tags = trim((string) ($_POST['tags'] ?? '')); // comma-separated from pills
$subcat = trim((string) ($_POST['subcategory'] ?? ''));

if ($name === '' || $categoryId === '') {
    json_response(['ok' => false, 'error' => 'Validation: name and category are required.'], 422);
}

$pricingError = null;
if (! product_validate_pricing_stock($price, $stock, $maxOrder, $pricingError)) {
    json_response(['ok' => false, 'error' => $pricingError], 422);
}

// Embed meta in description so core ERD stays unchanged without ALTER.
$meta = [];
if ($unit !== '') {
    $meta[] = 'UNIT:' . $unit;
}
if ($maxOrder > 0) {
    $meta[] = 'MAX:' . $maxOrder;
}
if ($tags !== '') {
    $meta[] = 'TAGS:' . $tags;
}
if ($subcat !== '') {
    $meta[] = 'SUBCAT:' . $subcat;
}
$meta[] = 'STATUS:' . $status;
$meta[] = 'AVAIL:' . $availability;
$fullDesc = $desc;
if ($meta !== []) {
    $fullDesc .= "\n\n<!--" . implode('|', $meta) . '-->';
}

$newPid = db_next_prefixed_id('product', 'product_id', 'P');

$sql = 'INSERT INTO product (product_id, product_name, description, price, product_in_stock, category_id, shop_id)
        VALUES (:pid, :pname, :pdesc, :price, :stock, :cid, :sid)';

try {
    $st = db_execute($sql, [
        'pid' => $newPid,
        'pname' => $name,
        'pdesc' => $fullDesc,
        'price' => $price,
        'stock' => $stock,
        'cid' => $categoryId,
        'sid' => $shopId,
    ]);
    if (!$st) {
        $e = oci_error();
        throw new RuntimeException($e['message'] ?? 'Insert failed');
    }
    oci_free_statement($st);

    $newId = $newPid;

    if (!empty($_FILES['images'])) {
        $names = product_process_image_uploads($shopId, $newId, $_FILES['images']);
        if ($names === [] && product_normalize_files_input($_FILES['images']) !== null) {
            throw new RuntimeException('Image upload failed. Use JPEG, PNG, WebP, or GIF under ' . (int) MAX_UPLOAD_MB . 'MB each.');
        }
        if ($names !== []) {
            $fullDesc = product_set_images_on_description(
                product_display_description($fullDesc),
                $fullDesc,
                $names
            );
            $st2 = db_execute(
                'UPDATE product SET description = :d WHERE product_id = :pid AND shop_id = :sid',
                ['d' => $fullDesc, 'pid' => $newId, 'sid' => $shopId]
            );
            if (!$st2) {
                $e = oci_error();
                throw new RuntimeException($e['message'] ?? 'Could not save product images.');
            }
            oci_free_statement($st2);
        }
    }

    db_commit();
    json_response(['ok' => true, 'product_id' => $newId]);
} catch (Throwable $e) {
    db_rollback();
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
