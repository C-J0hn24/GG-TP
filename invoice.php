<?php
/**
 * Customer invoice page (PHP + Oracle OCI8).
 * URL: /GG-TP/invoice.php?order_id=O1&q=&from=&to=
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/customer/functions.php';
require_once __DIR__ . '/includes/customer/laravel-auth.php';
require_once __DIR__ . '/includes/customer/invoice-queries.php';

$pageTitle = 'GroceryGo - Invoice Page';
$customerNavActive = 'invoice.php';
$errorMessage = null;
$successMessage = null;
$invoice = null;
$orderList = [];
$company = $GLOBALS['CUSTOMER_COMPANY'] ?? [];

try {
    $user = customer_require_auth();
    $customerUser = $user;
    $customerId = (string) $user->user_id;

    $search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $from = isset($_GET['from']) ? trim((string) $_GET['from']) : '';
    $to = isset($_GET['to']) ? trim((string) $_GET['to']) : '';
    $orderId = trim((string) ($_GET['order_id'] ?? $_GET['invoice_id'] ?? ''));

    if ($from !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        $from = '';
    }
    if ($to !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $to = '';
    }
    if ($search !== '') {
        $search = substr($search, 0, 40);
    }
    if ($orderId !== '') {
        $orderId = substr($orderId, 0, 32);
    }

    $orderList = invoice_search_orders(
        $customerId,
        $search !== '' ? $search : null,
        $from !== '' ? $from : null,
        $to !== '' ? $to : null
    );

    if ($orderId === '' && $orderList !== []) {
        $orderId = (string) ($orderList[0]['order_id'] ?? '');
    }
    if ($orderId === '') {
        $latest = invoice_fetch_latest_order_id($customerId);
        if ($latest !== null && $latest !== '') {
            $orderId = $latest;
        }
    }

    if ($orderId !== '') {
        $invoice = invoice_build($customerId, $orderId);
        if ($invoice === null) {
            $errorMessage = 'Invoice not found or you do not have permission to view it.';
        }
    }

    $successMessage = customer_flash_pull('invoice_payment_success');
    if ($successMessage === null && (isset($_GET['paid']) || isset($_GET['success']))) {
        $successMessage = 'Payment successful! Your order has been placed and your invoice is ready below.';
    }
    if ($successMessage === null && $invoice !== null && !empty($invoice['is_paid']) && isset($_GET['order_id'])) {
        $successMessage = 'Payment successful! Thank you for your order.';
    }
} catch (Throwable $e) {
    $errorMessage = 'Could not load invoice. Please try again later.';
    if ((getenv('APP_DEBUG') ?: '') === 'true') {
        $errorMessage .= ' ' . $e->getMessage();
    }
}

require __DIR__ . '/includes/customer/header.php';
?>

<section class="section invoice-page">
    <div class="container">
        <h1 class="invoice-page-title">Invoice Page</h1>
        <div class="invoice-title-divider" role="presentation"></div>

        <?php if ($successMessage): ?>
            <div class="invoice-success-banner" role="status" id="invoice-success-banner">
                <strong>Success</strong>
                <p><?= customer_h($successMessage) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <p class="orders-alert" role="alert"><?= customer_h($errorMessage) ?></p>
        <?php endif; ?>

        <form class="invoice-toolbar card" method="get" action="<?= customer_h(customer_url('invoice.php')) ?>" id="invoice-filter-form">
            <label class="invoice-toolbar-field invoice-toolbar-search">
                <span class="sr-only">Search invoice</span>
                <input type="search" name="q" value="<?= customer_h($search ?? '') ?>" placeholder="Search invoice" maxlength="40">
            </label>
            <label class="invoice-toolbar-field">
                <span>From:</span>
                <input type="date" name="from" value="<?= customer_h($from ?? '') ?>">
            </label>
            <label class="invoice-toolbar-field">
                <span>To:</span>
                <input type="date" name="to" value="<?= customer_h($to ?? '') ?>">
            </label>
            <?php if ($orderId !== ''): ?>
                <input type="hidden" name="order_id" value="<?= customer_h($orderId) ?>">
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Search</button>
            <button type="button" class="btn btn-outline invoice-export-btn" id="invoice-export-btn" data-invoice-export>Export</button>
        </form>

        <?php if ($invoice === null && !$errorMessage): ?>
            <article class="card invoice-empty">
                <h2>No invoice found</h2>
                <p class="muted">Place an order to generate your first invoice, or adjust your search filters.</p>
                <a href="<?= customer_h(customer_url('categories')) ?>" class="btn btn-primary">Browse products</a>
            </article>
        <?php elseif ($invoice !== null): ?>
            <?php require __DIR__ . '/includes/customer/invoice-document.php'; ?>
        <?php endif; ?>

        <?php if ($orderList !== [] && count($orderList) > 1): ?>
            <section class="card invoice-history" aria-label="Matching invoices" style="margin-top:24px;">
                <h2 class="invoice-history-title">Other matching invoices</h2>
                <div class="table-scroll invoice-table-wrap">
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th>Invoice ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderList as $row): ?>
                                <?php
                                    $oid = (string) ($row['order_id'] ?? '');
                                    $paid = strtolower((string) ($row['payment_status'] ?? '')) === 'paid';
                                ?>
                                <tr>
                                    <td><?= customer_h($oid) ?></td>
                                    <td><?= customer_h(customer_format_date($row['order_date'] ?? null)) ?></td>
                                    <td><?= customer_h(customer_money((float) ($row['amount'] ?? 0))) ?></td>
                                    <td>
                                        <span class="invoice-paid-badge<?= $paid ? ' invoice-paid-badge--yes' : '' ?>">
                                            <?= $paid ? 'Paid' : 'Pending' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a class="btn btn-outline btn-sm" href="<?= customer_h(customer_url('invoice.php?order_id=' . rawurlencode($oid))) ?>">Open</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/customer/footer.php'; ?>
