<?php
declare(strict_types=1);
?>
</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand-block">
            <a href="<?= customer_h(customer_url()) ?>" class="footer-brand" aria-label="GroceryGo home">
                <img src="<?= customer_h(customer_asset('assets/logo/GroceryGo-main.png')) ?>" alt="GroceryGo" class="footer-logo">
            </a>
            <p class="footer-text">
                Your neighborhood marketplace for fresh groceries, local traders, and easy community pickup.
            </p>
        </div>

        <div>
            <h3>Traders</h3>
            <ul class="footer-links">
                <li><a href="<?= customer_h(customer_url('shops')) ?>">Browse local shops</a></li>
                <li><a href="<?= customer_h(customer_url('trader-portal/register.php')) ?>">Become a trader</a></li>
                <li><a href="<?= customer_h(customer_url('categories')) ?>">Shop categories</a></li>
            </ul>
        </div>

        <div>
            <h3>Legal</h3>
            <ul class="footer-links">
                <li><a href="<?= customer_h(customer_url('terms')) ?>">Terms and Conditions</a></li>
                <li><a href="<?= customer_h(customer_url('privacy')) ?>">Privacy Policy</a></li>
                <li><a href="<?= customer_h(customer_url('cookies')) ?>">Cookie Notice</a></li>
            </ul>
        </div>

        <div>
            <h3>Know More</h3>
            <ul class="footer-links">
                <li><a href="<?= customer_h(customer_url('about')) ?>">About Us</a></li>
                <li><a href="<?= customer_h(customer_url()) ?>#how-it-works">How it works</a></li>
                <li><a href="<?= customer_h(customer_url('contact')) ?>">Help &amp; support</a></li>
            </ul>
        </div>

        <div>
            <h3>Contact Us</h3>
            <ul class="footer-links">
                <li><a href="mailto:support.aim@tbc.edu.np">support.aim@tbc.edu.np</a></li>
                <li><a href="tel:9840000000">9840******</a></li>
            </ul>
        </div>
    </div>
    <div class="copyright">
        <p>&copy; <?= date('Y') ?> Grocery Go. All rights reserved. Made by AIM.</p>
    </div>
</footer>
<script src="<?= customer_h(customer_asset('js/invoice.js')) ?>" defer></script>
</body>
</html>
