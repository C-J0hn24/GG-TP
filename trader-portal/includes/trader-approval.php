<?php
/**
 * Trader admin-approval helpers (TRADER.APPROVAL_STATUS).
 */
declare(strict_types=1);

function portal_trader_insert_row(string $userId, string $adminId): void
{
    $st = db_execute(
        'INSERT INTO trader (trader_id, admin_id, approval_status) VALUES (:tid, :aid, :status)',
        ['tid' => $userId, 'aid' => $adminId, 'status' => 'PENDING']
    );
    if ($st) {
        oci_free_statement($st);

        return;
    }

    $st = db_execute(
        'INSERT INTO trader (trader_id, admin_id) VALUES (:tid, :aid)',
        ['tid' => $userId, 'aid' => $adminId]
    );
    if ($st) {
        oci_free_statement($st);
    }
}

function portal_trader_pending_approval_message(): string
{
    return 'Email verified. Please wait for admin approval before you can sign in.';
}
