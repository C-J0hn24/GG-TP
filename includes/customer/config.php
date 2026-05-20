<?php
/**
 * Customer PHP pages (invoice.php) — paths and company details.
 */
declare(strict_types=1);

$envFile = dirname(__DIR__, 2) . '/.env';
$appBase = '/GG-TP';
if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), 'APP_URL=')) {
            $url = trim(substr($line, 8), " \t\"'");
            $path = parse_url($url, PHP_URL_PATH);
            if (is_string($path) && $path !== '' && $path !== '/') {
                $appBase = rtrim($path, '/');
            }
            break;
        }
    }
}

define('CUSTOMER_APP_BASE', $appBase);

$CUSTOMER_COMPANY = [
    'name' => 'GroceryGo',
    'address' => '12 Market Street, Cleckheaton BD19 3AH',
    'phone' => '01274 000 000',
    'email' => 'hello@grocerygo.co.uk',
];
