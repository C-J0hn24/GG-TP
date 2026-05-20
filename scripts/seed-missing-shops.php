<?php
/**
 * Insert SH3, SH4, SH5 shops (and traders/users) if missing.
 * Run: php scripts/seed-missing-shops.php
 */
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$shops = [
    [
        'shop_id' => 'SH3',
        'shop_name' => 'Green Valley Grocers',
        'location' => '16 High Street, Cleckheaton',
        'contact_info' => '07700110022',
        'trader_id' => 'U10',
        'first_name' => 'Green',
        'last_name' => 'Valley',
        'email' => 'green.valley@gg-trader.test',
    ],
    [
        'shop_id' => 'SH4',
        'shop_name' => 'Golden Crust Bakery',
        'location' => '18 High Street, Cleckheaton',
        'contact_info' => '07700330044',
        'trader_id' => 'U11',
        'first_name' => 'Golden',
        'last_name' => 'Crust',
        'email' => 'golden.crust@gg-trader.test',
    ],
    [
        'shop_id' => 'SH5',
        'shop_name' => 'The Fine Deli',
        'location' => '20 High Street, Cleckheaton',
        'contact_info' => '07700550066',
        'trader_id' => 'U12',
        'first_name' => 'Fine',
        'last_name' => 'Deli',
        'email' => 'fine.deli@gg-trader.test',
    ],
];

$adminId = (string) (DB::table('trader')->value('admin_id') ?? 'U6');
$defaultPassword = password_hash('Trader123!', PASSWORD_DEFAULT);

foreach ($shops as $row) {
    $shopId = $row['shop_id'];
    $traderId = $row['trader_id'];

    if (DB::table('shop')->where('shop_id', $shopId)->exists()) {
        echo "Skip shop {$shopId} — already exists.\n";
        continue;
    }

    if (! DB::table('users')->where('user_id', $traderId)->exists()) {
        DB::table('users')->insert([
            'user_id' => $traderId,
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'password' => $defaultPassword,
            'phone_num' => $row['contact_info'],
            'address' => $row['location'],
            'created_at' => now(),
            'email_verified' => 1,
        ]);
        echo "Inserted user {$traderId} ({$row['email']}).\n";
    } else {
        echo "User {$traderId} already exists.\n";
    }

    if (! DB::table('trader')->where('trader_id', $traderId)->exists()) {
        DB::table('trader')->insert([
            'trader_id' => $traderId,
            'admin_id' => $adminId,
        ]);
        echo "Inserted trader {$traderId}.\n";
    }

    DB::table('shop')->insert([
        'shop_id' => $shopId,
        'shop_name' => $row['shop_name'],
        'location' => $row['location'],
        'trader_id' => $traderId,
        'contact_info' => $row['contact_info'],
    ]);
    echo "Inserted shop {$shopId} — {$row['shop_name']}.\n";
}

echo "\nCurrent shops:\n";
foreach (DB::table('shop')->orderBy('shop_id')->get(['shop_id', 'shop_name', 'trader_id']) as $s) {
    echo "  {$s->shop_id} | {$s->shop_name} | trader {$s->trader_id}\n";
}
