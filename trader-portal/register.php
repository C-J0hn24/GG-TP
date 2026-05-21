<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/verification.php';
require_once __DIR__ . '/includes/trader-approval.php';

if (auth_user()) {
    portal_redirect('/trader/dashboard.php');
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!portal_verify_csrf()) {
        $error = 'Invalid CSRF token.';
    } else {
        $fn = trim((string) ($_POST['first_name'] ?? ''));
        $ln = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $shop = trim((string) ($_POST['shop_name'] ?? ''));

        if ($fn === '' || $ln === '' || $email === '' || strlen($pass) < 8 || $shop === '') {
            $error = 'Fill all fields; password min 8 chars.';
        } else {
            try {
                $exists = db_fetch_scalar(
                    'SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(:e)',
                    ['e' => $email]
                );
                if ((int) $exists > 0) {
                    $error = 'Email already registered.';
                } else {
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $userId = db_next_prefixed_id('users', 'user_id', 'U');
                    $shopId = db_next_prefixed_id('shop', 'shop_id', 'SH');
                    $adminId = (string) (db_fetch_scalar(
                        'SELECT admin_id FROM trader WHERE ROWNUM = 1'
                    ) ?? 'U6');

                    $userSql = 'INSERT INTO users (user_id, first_name, last_name, email, password, phone_num, address, created_at, email_verified)
                         VALUES (:id, :fn, :ln, :em, :pw, \'-\', \'-\', SYSTIMESTAMP, 0)';
                    try {
                        $st = db_execute($userSql, [
                            'id' => $userId, 'fn' => $fn, 'ln' => $ln, 'em' => $email, 'pw' => $hash,
                        ]);
                    } catch (Throwable) {
                        $st = db_execute(
                            'INSERT INTO users (user_id, first_name, last_name, email, password, phone_num, address, created_at)
                             VALUES (:id, :fn, :ln, :em, :pw, \'-\', \'-\', SYSTIMESTAMP)',
                            ['id' => $userId, 'fn' => $fn, 'ln' => $ln, 'em' => $email, 'pw' => $hash]
                        );
                    }
                    if ($st) {
                        oci_free_statement($st);
                    }

                    portal_trader_insert_row($userId, $adminId);

                    $st = db_execute(
                        'INSERT INTO shop (shop_id, shop_name, location, trader_id, contact_info)
                         VALUES (:sid, :sn, :loc, :tid, \'-\')',
                        ['sid' => $shopId, 'sn' => $shop, 'loc' => 'Registered via trader portal', 'tid' => $userId]
                    );
                    if ($st) {
                        oci_free_statement($st);
                    }

                    db_commit();
                    try {
                        portal_send_signup_verification($userId);
                    } catch (Throwable $mailErr) {
                        error_log('trader verify email: ' . $mailErr->getMessage());
                        flash_set('error', 'Account created but verification email could not be sent. Use resend on the verify page.');
                    }
                    portal_redirect('/verify-email.php?email=' . rawurlencode($email));
                }
            } catch (Throwable $e) {
                db_rollback();
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Register trader';
$traderLayout = false;
require_once __DIR__ . '/includes/header.php';
?>
      <article class="card auth-card" style="max-width:520px;margin-left:auto;margin-right:auto;">
        <header class="auth-header">
          <h1>Create trader account</h1>
        </header>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" class="auth-form">
            <?= portal_csrf_field() ?>
            <div class="field-group">
                <label for="first_name">First name</label>
                <input class="input" id="first_name" name="first_name" required value="<?= h($_POST['first_name'] ?? '') ?>">
            </div>
            <div class="field-group">
                <label for="last_name">Last name</label>
                <input class="input" id="last_name" name="last_name" required value="<?= h($_POST['last_name'] ?? '') ?>">
            </div>
            <div class="field-group">
                <label for="email">Email</label>
                <input class="input" type="email" id="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>">
            </div>
            <div class="field-group">
                <label for="password">Password</label>
                <input class="input" type="password" id="password" name="password" required minlength="8">
            </div>
            <div class="field-group">
                <label for="shop_name">Shop name</label>
                <input class="input" id="shop_name" name="shop_name" required value="<?= h($_POST['shop_name'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary auth-submit">Create account</button>
        </form>
        <p style="margin-top:16px;text-align:center;"><a href="<?= h(portal_url('login.php')) ?>">Already have an account?</a></p>
      </article>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
