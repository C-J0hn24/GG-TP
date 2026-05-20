<?php

/**
 * Apply pending Oracle schema/data updates for GroceryGo (safe to re-run).
 * Usage: php scripts/apply-oracle-updates.php
 *        php scripts/apply-oracle-updates.php --skip-prices
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$skipPrices = in_array('--skip-prices', $argv ?? [], true);
$conn = DB::connection('oracle');

function columnExists($conn, string $table, string $column): bool
{
    $row = $conn->selectOne(
        'SELECT COUNT(*) AS cnt FROM user_tab_columns WHERE table_name = ? AND column_name = ?',
        [strtoupper($table), strtoupper($column)]
    );

    return (int) ($row->cnt ?? $row->CNT ?? 0) > 0;
}

function addColumn($conn, string $table, string $column, string $definition): void
{
    if (columnExists($conn, $table, $column)) {
        echo "  skip {$table}.{$column} (already exists)\n";

        return;
    }

    $conn->statement("ALTER TABLE {$table} ADD ({$column} {$definition})");
    echo "  added {$table}.{$column}\n";
}

function tableExists($conn, string $table): bool
{
    $row = $conn->selectOne(
        'SELECT COUNT(*) AS cnt FROM user_tables WHERE table_name = ?',
        [strtoupper($table)]
    );

    return (int) ($row->cnt ?? $row->CNT ?? 0) > 0;
}

echo "=== GroceryGo Oracle updates ===\n\n";

echo "1) Email verification columns\n";
addColumn($conn, 'USERS', 'EMAIL_VERIFIED', 'NUMBER(1) DEFAULT 0 NOT NULL');
addColumn($conn, 'USERS', 'EMAIL_VERIFIED_AT', 'DATE');
addColumn($conn, 'VERIFICATION', 'VERIFICATION_CODE', 'VARCHAR2(6)');
addColumn($conn, 'VERIFICATION', 'EMAIL', 'VARCHAR2(100)');
addColumn($conn, 'VERIFICATION', 'PURPOSE', 'VARCHAR2(30)');
addColumn($conn, 'VERIFICATION', 'STATUS', 'VARCHAR2(20)');

if (columnExists($conn, 'USERS', 'EMAIL_VERIFIED')) {
    $updated = $conn->update(
        'UPDATE users SET email_verified = 1, email_verified_at = SYSDATE WHERE NVL(email_verified, 0) = 0'
    );
    echo "  backfilled EMAIL_VERIFIED on {$updated} user(s)\n";
}

echo "\n2) Collection slot pickup location\n";
addColumn($conn, 'COLLECTION_SLOT', 'PICKUP_LOCATION', 'VARCHAR2(120)');

if (! $skipPrices) {
    echo "\n3) Product prices → approximate USD (×1.27)\n";
    $before = $conn->selectOne('SELECT MIN(price) AS min_p, MAX(price) AS max_p, COUNT(*) AS cnt FROM product');
    echo '  before: min='.($before->min_p ?? '?').' max='.($before->max_p ?? '?').' products='.($before->cnt ?? '?')."\n";

    $rows = $conn->update('UPDATE product SET price = ROUND(price * 1.27, 2) WHERE price > 0');
    echo "  updated {$rows} product row(s)\n";

    $after = $conn->selectOne('SELECT MIN(price) AS min_p, MAX(price) AS max_p FROM product');
    echo '  after: min='.($after->min_p ?? '?').' max='.($after->max_p ?? '?')."\n";
    echo "  (Re-run with --skip-prices to avoid converting again.)\n";
} else {
    echo "\n3) Product prices skipped (--skip-prices)\n";
}

echo "\n4) Review comments and trader replies\n";
addColumn($conn, 'REVIEW', 'TRADER_REPLY', 'VARCHAR2(1000)');
addColumn($conn, 'REVIEW', 'TRADER_REPLY_DATE', 'DATE');

if (! tableExists($conn, 'REVIEW_COMMENT')) {
    $conn->statement(
        'CREATE TABLE review_comment (
            comment_id VARCHAR2(20) NOT NULL,
            review_id VARCHAR2(20) NOT NULL,
            comment_body VARCHAR2(1000) NOT NULL,
            comment_date DATE DEFAULT SYSDATE NOT NULL,
            customer_id VARCHAR2(20) NOT NULL,
            CONSTRAINT pk_review_comment PRIMARY KEY (comment_id)
        )'
    );
    echo "  created REVIEW_COMMENT table\n";
} else {
    echo "  skip REVIEW_COMMENT (already exists)\n";
}

echo "\n5) Trader approval status\n";
addColumn($conn, 'TRADER', 'APPROVAL_STATUS', "VARCHAR2(20) DEFAULT 'APPROVED' NOT NULL");
if (columnExists($conn, 'TRADER', 'APPROVAL_STATUS')) {
    $approved = $conn->update(
        "UPDATE trader SET approval_status = 'APPROVED' WHERE UPPER(NVL(approval_status, 'PENDING')) <> 'APPROVED'"
    );
    echo "  set APPROVED on {$approved} trader(s) that were not approved\n";
}

$conn->statement('COMMIT');

echo "\nDone. All changes committed.\n";
