<?php
/**
 * List trader portal login accounts (email + user id + shop).
 * Run: php scripts/list-trader-logins.php
 */
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::select(
    "SELECT u.user_id, u.email, u.first_name, u.last_name,
            NVL(t.approval_status, 'PENDING') AS approval_status,
            NVL(u.email_verified, 0) AS email_verified,
            s.shop_id, s.shop_name
     FROM users u
     INNER JOIN trader t ON t.trader_id = u.user_id
     LEFT JOIN shop s ON s.trader_id = u.user_id
     ORDER BY u.user_id"
);

echo "Trader accounts (" . count($rows) . "):\n\n";
foreach ($rows as $r) {
    echo "  {$r->user_id} | {$r->email}\n";
    echo "    name: {$r->first_name} {$r->last_name}\n";
    echo "    shop: " . ($r->shop_id ?? '-') . ' ' . ($r->shop_name ?? '') . "\n";
    echo "    approval: {$r->approval_status} | email_verified: {$r->email_verified}\n\n";
}
