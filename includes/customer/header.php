<?php
/**
 * Customer site header — matches Laravel navbar (Home, Shops, Categories, Baskets, Profile).
 */
declare(strict_types=1);

$customerNavActive = $customerNavActive ?? basename($_SERVER['PHP_SELF'] ?? '');
$customerUser = $customerUser ?? customer_auth_user();
$onInvoice = $customerNavActive === 'invoice.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= customer_h($pageTitle ?? 'GroceryGo') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= customer_h(customer_asset('css/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <input type="checkbox" id="site-nav-toggle" class="site-nav-checkbox">
    <div class="container navbar-wrap">
        <a href="<?= customer_h(customer_url()) ?>" class="brand" aria-label="GroceryGo home">
            <img src="<?= customer_h(customer_asset('assets/logo/GroceryGo-main.png')) ?>" alt="GroceryGo logo" class="brand-logo">
        </a>

        <label for="site-nav-toggle" class="menu-toggle" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </label>

        <nav class="main-nav" aria-label="Primary">
            <div class="nav-primary">
                <a href="<?= customer_h(customer_url()) ?>">Home</a>
                <a href="<?= customer_h(customer_url('shops')) ?>">Shops</a>
                <a href="<?= customer_h(customer_url('categories')) ?>">Categories</a>
            </div>
            <div class="nav-actions">
                <a href="<?= customer_h(customer_url('cart')) ?>" class="nav-baskets">Baskets</a>
                <?php if ($customerUser): ?>
                    <a href="<?= customer_h(customer_url('profile')) ?>" class="nav-baskets">Profile</a>
                    <a href="<?= customer_h(customer_url('orders')) ?>" class="nav-baskets">Orders</a>
                    <a href="<?= customer_h(customer_url('invoice.php')) ?>" class="nav-baskets<?= $onInvoice ? ' active' : '' ?>"<?= $onInvoice ? ' aria-current="page"' : '' ?>>Invoices</a>
                    <a href="<?= customer_h(customer_url('logout')) ?>" class="btn btn-signup nav-login">Logout</a>
                <?php else: ?>
                    <a href="<?= customer_h(customer_url('login')) ?>" class="btn btn-signup nav-login">Login</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
<main>
