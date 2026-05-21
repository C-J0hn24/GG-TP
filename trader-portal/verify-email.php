<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/verification.php';
require_once __DIR__ . '/includes/trader-approval.php';

if (auth_user()) {
    portal_redirect('/trader/dashboard.php');
}

$email = trim((string) ($_GET['email'] ?? $_POST['email'] ?? ''));
$error = '';
$status = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!portal_verify_csrf()) {
        $error = 'Invalid security token. Refresh the page.';
    } elseif (isset($_POST['resend'])) {
        $email = trim((string) ($_POST['email'] ?? ''));
        if ($email === '') {
            $error = 'Enter your email address.';
        } else {
            try {
                portal_resend_signup_code($email);
                $status = 'A new verification code has been sent.';
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $code = trim((string) ($_POST['code'] ?? ''));
        if ($email === '' || $code === '') {
            $error = 'Enter email and verification code.';
        } else {
            try {
                portal_verify_signup_code($email, $code);
                flash_set('pending', portal_trader_pending_approval_message());
                portal_redirect('/login.php');
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Verify email';
$traderLayout = false;
require_once __DIR__ . '/includes/header.php';
?>
      <article class="card auth-card" style="max-width:480px;margin-left:auto;margin-right:auto;">
        <header class="auth-header">
          <h1>Verify your email</h1>
          <p class="text-secondary">Enter the 6-digit code we sent to your inbox. After verification, an administrator must approve your trader account before you can sign in.</p>
        </header>
        <?php if ($status !== ''): ?>
            <div class="alert" style="background:#ecfdf5;color:#166534;padding:12px;border-radius:8px;margin-bottom:12px;"><?= h($status) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" class="auth-form">
            <?= portal_csrf_field() ?>
            <div class="field-group">
                <label for="email">Email</label>
                <input class="input" type="email" id="email" name="email" required value="<?= h($email) ?>">
            </div>
            <div class="field-group">
                <label for="code">Verification code</label>
                <input class="input" type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required>
            </div>
            <button type="submit" class="btn btn-primary auth-submit">Verify email</button>
        </form>
        <form method="post" class="auth-form" style="margin-top:16px;">
            <?= portal_csrf_field() ?>
            <input type="hidden" name="resend" value="1">
            <input type="hidden" name="email" value="<?= h($email) ?>">
            <button type="submit" class="btn auth-secondary-btn" style="width:100%;">Resend code</button>
        </form>
        <p style="margin-top:16px;text-align:center;"><a href="<?= h(portal_url('login.php')) ?>">Back to login</a></p>
      </article>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
