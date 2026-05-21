<?php
/**
 * Payment success confirmation (PHP + Oracle OCI8).
 * URL: /GG-TP/payment-success.php?order_id=O123&token=...
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/customer/functions.php';
require_once __DIR__ . '/includes/customer/laravel-auth.php';
require_once __DIR__ . '/includes/customer/invoice-queries.php';

$pageTitle = 'GroceryGo - Payment Successful';
$customerNavActive = 'payment-success.php';
$errorMessage = null;
$summary = null;
$showFreshSuccess = false;
$emailNotice = null;

try {
    $user = customer_require_auth();
    $customerUser = $user;
    $customerId = (string) $user->user_id;

    $orderId = trim((string) ($_GET['order_id'] ?? ''));
    if ($orderId !== '') {
        $orderId = substr($orderId, 0, 32);
    }

    if ($orderId === '') {
        $errorMessage = 'Missing order reference. Open your invoice from your account orders.';
    } else {
        $summary = invoice_fetch_payment_summary($customerId, $orderId);
        if ($summary === null) {
            $errorMessage = 'Order not found or you do not have permission to view it.';
        } else {
            $sessionOrder = (string) customer_session_get('payment_success_order_id', '');
            $sessionToken = (string) customer_session_get('payment_success_token', '');
            $queryToken = trim((string) ($_GET['token'] ?? ''));

            $tokenOk = $queryToken !== ''
                && $sessionToken !== ''
                && hash_equals($sessionToken, $queryToken)
                && hash_equals($sessionOrder, $orderId);

            if ($tokenOk) {
                $showFreshSuccess = true;
                customer_session_pull('payment_success_order_id');
                customer_session_pull('payment_success_token');
                customer_flash_put('invoice_payment_success', 'Payment successful! Thank you for your order.');
            } elseif (customer_flash_pull('invoice_payment_success') !== null) {
                $showFreshSuccess = true;
            } elseif (!empty($summary['is_paid'])) {
                $showFreshSuccess = true;
            }

            $emailNotice = customer_session_pull('payment_email_notice');
        }
    }
} catch (Throwable $e) {
    $errorMessage = 'Could not load your order confirmation.';
    error_log('payment-success.php: ' . $e->getMessage());
}

require __DIR__ . '/includes/customer/header.php';
?>

<section class="section payment-success-page">
    <div class="container">
        <?php if ($errorMessage): ?>
            <article class="card payment-success-card payment-success-card--error">
                <h1>Something went wrong</h1>
                <p><?= customer_h($errorMessage) ?></p>
                <div class="payment-success-actions">
                    <a href="<?= customer_h(customer_url('cart')) ?>" class="btn btn-primary">View basket</a>
                    <a href="<?= customer_h(customer_url()) ?>" class="btn btn-outline">Continue shopping</a>
                </div>
            </article>
        <?php elseif ($summary !== null): ?>
            <article class="card payment-success-card">
                <div class="payment-success-icon" aria-hidden="true">✓</div>
                <h1 class="payment-success-title">Payment Successful</h1>
                <p class="payment-success-lead">
                    Thank you for your purchase. Your order has been confirmed.
                </p>

                <?php if ($showFreshSuccess): ?>
                    <div class="invoice-success-banner" role="status">
                        <strong>Confirmed</strong>
                        <p>Your payment was received and your basket has been cleared.</p>
                    </div>
                <?php endif; ?>

                <?php if (is_string($emailNotice) && $emailNotice !== ''): ?>
                    <p class="payment-success-email-note" role="status"><?= customer_h($emailNotice) ?></p>
                <?php endif; ?>

                <dl class="payment-success-meta">
                    <div>
                        <dt>Order ID</dt>
                        <dd><?= customer_h((string) $summary['order_id']) ?></dd>
                    </div>
                    <div>
                        <dt>Payment status</dt>
                        <dd>
                            <span class="invoice-paid-badge invoice-paid-badge--yes">
                                <?= customer_h(!empty($summary['is_paid']) ? 'Paid' : (string) $summary['payment_status']) ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt>Total paid</dt>
                        <dd><?= customer_h(customer_money((float) ($summary['total'] ?? 0))) ?></dd>
                    </div>
                    <div>
                        <dt>Payment method</dt>
                        <dd><?= customer_h((string) ($summary['payment_method'] ?? '—')) ?></dd>
                    </div>
                    <div>
                        <dt>Pickup</dt>
                        <dd>
                            <?= customer_h((string) ($summary['pickup_date'] ?? '—')) ?>
                            <?php if (!empty($summary['pickup_time'])): ?>
                                · <?= customer_h((string) $summary['pickup_time']) ?>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>

                <div class="payment-success-actions">
                    <a href="<?= customer_h(customer_url('invoice.php?order_id=' . rawurlencode((string) $summary['order_id']) . '&paid=1')) ?>" class="btn btn-primary">View Invoice</a>
                    <a href="<?= customer_h(customer_url('categories')) ?>" class="btn btn-outline">Continue Shopping</a>
                </div>
            </article>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/customer/footer.php'; ?>
