<?php
/**
 * Debug a trader login account. Usage: php scripts/check-trader-account.php lisa.white@email.com
 */
declare(strict_types=1);

$email = $argv[1] ?? 'lisa.white@email.com';

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$row = DB::selectOne(
    "SELECT u.user_id, u.email, u.password,
            NVL(t.approval_status, 'PENDING') AS approval_status,
            NVL(u.email_verified, 0) AS email_verified
     FROM users u
     INNER JOIN trader t ON t.trader_id = u.user_id
     WHERE LOWER(u.email) = LOWER(?)",
    [$email]
);

if (!$row) {
    echo "No trader found for {$email}\n";
    exit(1);
}

echo "user_id: {$row->user_id}\n";
echo "email: {$row->email}\n";
echo "approval_status: {$row->approval_status}\n";
echo "email_verified: {$row->email_verified}\n";

$stored = (string) $row->password;
$isBcrypt = str_starts_with($stored, '$2');
echo 'password_type: ' . ($isBcrypt ? 'bcrypt' : 'plain') . "\n";

foreach (['trader456', 'Trader123!', 'trader123', 'password'] as $plain) {
    $ok = $isBcrypt ? password_verify($plain, $stored) : hash_equals($stored, $plain);
    echo ($ok ? 'MATCH' : 'no   ') . " {$plain}\n";
}
