<?php
/**
 * Invoice data via Oracle OCI8 (prepared statements).
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once dirname(__DIR__, 2) . '/trader-portal/includes/db.php';

/**
 * @return list<array<string, mixed>>
 */
function invoice_search_orders(string $customerId, ?string $search, ?string $from, ?string $to, int $limit = 50): array
{
    $sql = 'SELECT o.order_id, o.order_date, o.amount, o.customer_id,
                   p.payment_status, p.paid_amount
            FROM orders o
            LEFT JOIN payment p ON p.order_id = o.order_id
            WHERE o.customer_id = :customer_id';
    $binds = ['customer_id' => $customerId];

    if ($search !== null && $search !== '') {
        $sql .= ' AND UPPER(o.order_id) LIKE UPPER(:search)';
        $binds['search'] = '%' . $search . '%';
    }
    if ($from !== null && $from !== '') {
        $sql .= " AND TRUNC(o.order_date) >= TO_DATE(:from_date, 'YYYY-MM-DD')";
        $binds['from_date'] = $from;
    }
    if ($to !== null && $to !== '') {
        $sql .= " AND TRUNC(o.order_date) <= TO_DATE(:to_date, 'YYYY-MM-DD')";
        $binds['to_date'] = $to;
    }

    $sql .= ' ORDER BY o.order_date DESC FETCH FIRST ' . max(1, min(100, $limit)) . ' ROWS ONLY';

    return db_fetch_all($sql, $binds);
}

/**
 * @return array<string, mixed>|null
 */
function invoice_fetch_order_header(string $customerId, string $orderId): ?array
{
    return db_fetch_one(
        'SELECT o.order_id, o.order_date, o.status, o.amount, o.customer_id,
                u.first_name, u.last_name, u.email,
                p.payment_id, p.paid_amount, p.payment_method, p.payment_status,
                cs.date_ AS pickup_date, cs.time_ AS pickup_time, cs.pickup_location
         FROM orders o
         INNER JOIN users u ON u.user_id = o.customer_id
         LEFT JOIN payment p ON p.order_id = o.order_id
         LEFT JOIN collection_slot cs ON cs.order_id = o.order_id
         WHERE o.order_id = :order_id AND o.customer_id = :customer_id',
        ['order_id' => $orderId, 'customer_id' => $customerId]
    );
}

/**
 * @return list<array<string, mixed>>
 */
function invoice_fetch_order_items(string $orderId): array
{
    return db_fetch_all(
        'SELECT oi.order_item_id, oi.quantity, oi.price, oi.product_id, oi.order_id,
                pr.product_name
         FROM order_item oi
         INNER JOIN product pr ON pr.product_id = oi.product_id
         WHERE oi.order_id = :order_id
         ORDER BY oi.order_item_id',
        ['order_id' => $orderId]
    );
}

/**
 * Latest order for customer.
 *
 * @return array<string, mixed>|null
 */
function invoice_fetch_latest_order_id(string $customerId): ?string
{
    $row = db_fetch_one(
        'SELECT order_id FROM orders
         WHERE customer_id = :customer_id
         ORDER BY order_date DESC
         FETCH FIRST 1 ROW ONLY',
        ['customer_id' => $customerId]
    );

    return $row ? (string) ($row['order_id'] ?? '') : null;
}

/**
 * Build invoice view-model for template.
 *
 * @return array<string, mixed>|null
 */
function invoice_build(string $customerId, string $orderId): ?array
{
    $header = invoice_fetch_order_header($customerId, $orderId);
    if ($header === null) {
        return null;
    }

    $items = invoice_fetch_order_items($orderId);
    $lines = [];
    $subtotal = 0.0;

    foreach ($items as $item) {
        $qty = (int) ($item['quantity'] ?? 0);
        $unit = (float) ($item['price'] ?? 0);
        $lineTotal = $unit * $qty;
        $subtotal += $lineTotal;
        $lines[] = [
            'product_name' => (string) ($item['product_name'] ?? 'Product'),
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'quantity' => $qty,
            'unit_price' => $unit,
            'line_total' => $lineTotal,
        ];
    }

    $total = (float) ($header['amount'] ?? 0);
    $discount = max(0, round($subtotal - $total, 2));
    $status = strtolower((string) ($header['payment_status'] ?? ''));
    $first = trim((string) ($header['first_name'] ?? ''));
    $last = trim((string) ($header['last_name'] ?? ''));

    return [
        'invoice_id' => $orderId,
        'order_id' => $orderId,
        'customer_id' => $customerId,
        'customer_name' => trim($first . ' ' . $last) ?: 'Customer',
        'customer_email' => (string) ($header['email'] ?? ''),
        'order_date' => customer_format_date($header['order_date'] ?? null),
        'pickup_date' => customer_format_date($header['pickup_date'] ?? null),
        'pickup_time' => trim((string) ($header['pickup_time'] ?? '')),
        'pickup_location' => trim((string) ($header['pickup_location'] ?? '')),
        'subtotal' => $subtotal,
        'discount' => $discount,
        'total' => $total,
        'payment_status' => $status !== '' ? ucfirst($status) : 'Pending',
        'is_paid' => in_array($status, ['paid', 'completed'], true),
        'lines' => $lines,
    ];
}
