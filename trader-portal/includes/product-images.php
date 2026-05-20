<?php
/**
 * Product image helpers (uploads + description |IMG: metadata).
 */
declare(strict_types=1);

require_once __DIR__ . '/product-meta.php';

/**
 * @return list<string> Basenames stored in |IMG:...|
 */
function product_image_filenames(?string $description): array
{
    if ($description === null || $description === '') {
        return [];
    }
    if (!preg_match('/\|IMG:([^|\n]+)/', $description, $m)) {
        return [];
    }

    return array_values(array_filter(array_map(
        static fn (string $f) => basename(trim($f)),
        explode(',', $m[1])
    )));
}

function product_image_public_url(string $shopId, string $filename): string
{
    return portal_url('assets/uploads/products/' . rawurlencode($shopId) . '/' . rawurlencode(basename($filename)));
}

function product_image_disk_path(string $shopId, string $filename): string
{
    return dirname(__DIR__) . '/assets/uploads/products/' . $shopId . '/' . basename($filename);
}

function product_extract_meta_block(?string $description): string
{
    if ($description === null || !preg_match('/<!--.+?-->/s', $description, $m)) {
        return '';
    }

    return $m[0];
}

/**
 * @param list<string> $imageFiles
 */
function product_build_description(string $displayText, string $metaBlock, array $imageFiles): string
{
    $out = trim($displayText);
    if ($metaBlock !== '') {
        $out .= ($out !== '' ? "\n\n" : '') . $metaBlock;
    }
    if ($imageFiles !== []) {
        $out .= '|IMG:' . implode(',', $imageFiles);
    }

    return $out;
}

/**
 * @param list<string> $imageFiles
 */
function product_set_images_on_description(string $displayText, ?string $existingFull, array $imageFiles): string
{
    return product_build_description(
        $displayText,
        product_extract_meta_block($existingFull),
        $imageFiles
    );
}

/**
 * Normalize $_FILES entry for single or multiple uploads.
 *
 * @return array{name: list<string>, type: list<string>, tmp_name: list<string>, error: list<int>, size: list<int>}|null
 */
function product_normalize_files_input(array $filesInput): ?array
{
    if (empty($filesInput['name'])) {
        return null;
    }

    if (!is_array($filesInput['name'])) {
        return [
            'name' => [(string) $filesInput['name']],
            'type' => [(string) ($filesInput['type'] ?? '')],
            'tmp_name' => [(string) ($filesInput['tmp_name'] ?? '')],
            'error' => [(int) ($filesInput['error'] ?? UPLOAD_ERR_NO_FILE)],
            'size' => [(int) ($filesInput['size'] ?? 0)],
        ];
    }

    return [
        'name' => array_map('strval', $filesInput['name']),
        'type' => array_map('strval', $filesInput['type'] ?? []),
        'tmp_name' => array_map('strval', $filesInput['tmp_name'] ?? []),
        'error' => array_map('intval', $filesInput['error'] ?? []),
        'size' => array_map('intval', $filesInput['size'] ?? []),
    ];
}

/**
 * @return list<string> Newly saved basenames
 */
function product_process_image_uploads(string $shopId, string $productId, array $filesInput): array
{
    $filesInput = product_normalize_files_input($filesInput);
    if ($filesInput === null) {
        return [];
    }

    $uploadDir = dirname(__DIR__) . '/assets/uploads/products/' . $shopId;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $names = [];
    foreach ($filesInput['name'] as $i => $fname) {
        if (($filesInput['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }
        $tmp = $filesInput['tmp_name'][$i] ?? '';
        if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
            continue;
        }
        $mime = 'application/octet-stream';
        if (class_exists('finfo')) {
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($tmp) ?: $mime;
        }
        if (!in_array($mime, ALLOWED_IMAGE_MIME, true)) {
            continue;
        }
        $safe = safe_filename((string) $fname);
        $target = $uploadDir . '/' . $productId . '_' . $safe;
        if (move_uploaded_file($tmp, $target)) {
            $names[] = basename($target);
        }
    }

    return $names;
}

/**
 * @param list<string> $keepFilenames
 */
function product_delete_removed_images(string $shopId, array $previous, array $keepFilenames): void
{
    $keep = array_map('basename', $keepFilenames);
    foreach ($previous as $file) {
        $file = basename($file);
        if (in_array($file, $keep, true)) {
            continue;
        }
        $path = product_image_disk_path($shopId, $file);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
