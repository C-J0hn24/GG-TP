<?php
/**
 * Invoice body partial — expects $invoice, $company arrays.
 */
declare(strict_types=1);

if (!isset($invoice) || !is_array($invoice)) {
    return;
}
$company = $company ?? $GLOBALS['CUSTOMER_COMPANY'] ?? [];

?>
<div class="invoice-header-grid">
    <article class="card invoice-party-card">
        <h2><?= customer_h((string) ($company['name'] ?? 'GroceryGo')) ?></h2>
        <p><?= customer_h((string) ($company['address'] ?? '')) ?></p>
        <p>Mobile: <?= customer_h((string) ($company['phone'] ?? '')) ?></p>
        <p>Email: <?= customer_h((string) ($company['email'] ?? '')) ?></p>
    </article>

    <article class="card invoice-party-card invoice-party-card--customer">
        <h2>Order by <?= customer_h((string) ($invoice['customer_name'] ?? 'Customer')) ?></h2>
        <p>Customer ID: <?= customer_h((string) ($invoice['customer_id'] ?? '')) ?></p>
        <?php if (!empty($invoice['customer_email'])): ?>
            <p>Email: <?= customer_h((string) $invoice['customer_email']) ?></p>
        <?php endif; ?>
    </article>

    <article class="card invoice-party-card invoice-party-card--logo" aria-hidden="true">
        <img src="<?= customer_h(customer_asset('assets/logo/GroceryGo-main.png')) ?>" alt="" class="invoice-brand-logo">
    </article>
</div>

<section class="card invoice-document" id="invoice-print-area" aria-label="Invoice details">
    <header class="invoice-document-head">
        <h2>Invoice List</h2>
        <div class="invoice-document-meta">
            <p><strong>Date:</strong> <?= customer_h((string) ($invoice['order_date'] ?? '—')) ?></p>
            <p><strong>Invoice ID:</strong> <?= customer_h((string) ($invoice['invoice_id'] ?? '')) ?></p>
        </div>
    </header>

    <div class="invoice-section-divider" role="presentation"></div>

    <div class="table-scroll invoice-table-wrap">
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Order ID</th>
                    <th>Customer ID</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice['lines'] as $line): ?>
                    <tr>
                        <td><?= customer_h((string) ($line['product_name'] ?? '')) ?></td>
                        <td><?= customer_h((string) ($line['order_id'] ?? '')) ?></td>
                        <td><?= customer_h((string) ($line['customer_id'] ?? '')) ?></td>
                        <td><?= (int) ($line['quantity'] ?? 0) ?></td>
                        <td><?= customer_h(customer_money((float) ($line['unit_price'] ?? 0))) ?></td>
                        <td><?= customer_h(customer_money((float) ($line['line_total'] ?? 0))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <footer class="invoice-document-foot">
        <div class="invoice-dates">
            <p><strong>Pick Up Date:</strong> <?= customer_h((string) ($invoice['pickup_date'] ?? '—')) ?></p>
            <p><strong>Order Date:</strong> <?= customer_h((string) ($invoice['order_date'] ?? '—')) ?></p>
        </div>
        <div class="invoice-totals">
            <p><span>Discount:</span> <strong><?= customer_h(customer_money((float) ($invoice['discount'] ?? 0))) ?></strong></p>
            <p class="invoice-total-row"><span>Total:</span> <strong><?= customer_h(customer_money((float) ($invoice['total'] ?? 0))) ?></strong></p>
            <span class="invoice-paid-badge<?= !empty($invoice['is_paid']) ? ' invoice-paid-badge--yes' : '' ?>">
                <?= customer_h(!empty($invoice['is_paid']) ? 'Paid' : (string) ($invoice['payment_status'] ?? 'Pending')) ?>
            </span>
        </div>
    </footer>
</section>
