<?php
/**
 * Session auth: trader accounts via USERS + TRADER (trader_id = user_id).
 */
declare(strict_types=1);

function auth_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'user_id' => (string) $_SESSION['user_id'],
        'trader_id' => (string) ($_SESSION['trader_id'] ?? ''),
        'shop_id' => (string) ($_SESSION['shop_id'] ?? ''),
        'email' => (string) ($_SESSION['email'] ?? ''),
        'display_name' => (string) ($_SESSION['display_name'] ?? 'Trader'),
        'role' => (string) ($_SESSION['role'] ?? 'trader'),
    ];
}

function require_trader(): array
{
    $u = auth_user();
    if (!$u || $u['trader_id'] === '') {
        flash_set('error', 'Please sign in as a trader.');
        portal_redirect('/login.php');
    }

    return $u;
}

/**
 * Load trader context from DB into session (shop_id, names).
 */
function auth_refresh_from_db(string $userId): void
{
    $sql = 'SELECT u.user_id, u.first_name, u.last_name, u.email,
                   t.trader_id, s.shop_id, s.shop_name
            FROM users u
            INNER JOIN trader t ON t.trader_id = u.user_id
            LEFT JOIN shop s ON s.trader_id = u.user_id
            WHERE u.user_id = :user_id';
    try {
        $row = db_fetch_one($sql, ['user_id' => $userId]);
    } catch (Throwable $e) {
        return;
    }
    if (!$row) {
        return;
    }
    $_SESSION['user_id'] = (string) $row['user_id'];
    $_SESSION['trader_id'] = (string) $row['trader_id'];
    $_SESSION['shop_id'] = (string) ($row['shop_id'] ?? '');
    $_SESSION['email'] = (string) $row['email'];
    $_SESSION['role'] = 'trader';
    $fn = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
    $_SESSION['display_name'] = $fn !== '' ? $fn : (string) ($row['shop_name'] ?? 'Trader');
}

function portal_password_verify(string $plain, string $stored): bool
{
    if ($stored === '' || $plain === '') {
        return false;
    }

    if (str_starts_with($stored, '$2y$')
        || str_starts_with($stored, '$2a$')
        || str_starts_with($stored, '$2b$')
        || str_starts_with($stored, '$argon2')) {
        return password_verify($plain, $stored);
    }

    return hash_equals($stored, $plain);
}

/**
 * @return true|'unverified'|'pending_approval'|false
 */
function login_trader(string $email, string $password): bool|string
{
    require_once __DIR__ . '/verification.php';

    $sql = 'SELECT u.user_id, u.email, u.password, u.first_name, u.last_name,
                   t.trader_id,
                   NVL(t.approval_status, \'PENDING\') AS approval_status
            FROM users u
            INNER JOIN trader t ON t.trader_id = u.user_id
            WHERE LOWER(u.email) = LOWER(:email)';
    try {
        $row = db_fetch_one($sql, ['email' => $email]);
    } catch (Throwable $e) {
        error_log('login_trader query: ' . $e->getMessage());

        return false;
    }
    if (!$row || ! portal_password_verify($password, (string) ($row['password'] ?? ''))) {
        return false;
    }

    if (strtoupper((string) ($row['approval_status'] ?? 'PENDING')) !== 'APPROVED') {
        return 'pending_approval';
    }

    if (!portal_user_email_verified((string) $row['user_id'])) {
        return 'unverified';
    }

    $_SESSION['user_id'] = (string) $row['user_id'];
    $_SESSION['email'] = (string) $row['email'];
    $_SESSION['role'] = 'trader';
    $_SESSION['trader_id'] = (string) $row['trader_id'];
    auth_refresh_from_db((string) $row['user_id']);

    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
