<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <div class="footer-brand"><?= e(config('store.name')) ?></div>
            <p><?= e(config('store.address')) ?></p>
            <p><?= e(config('store.email')) ?></p>
        </div>
        <div>
            <h4>Shop</h4>
            <a href="<?= e(url('men.php')) ?>">Men</a>
            <a href="<?= e(url('women.php')) ?>">Women</a>
            <a href="<?= e(url('kids.php')) ?>">Kids</a>
        </div>
        <div>
            <h4>Help</h4>
            <a href="mailto:<?= e(config('store.email')) ?>">Contact</a>
            <a href="<?= e(url('account.php')) ?>">Shipping</a>
            <a href="<?= e(url('account.php')) ?>">Returns</a>
        </div>
        <div>
            <h4>Follow</h4>
            <a href="https://instagram.com" target="_blank" rel="noopener">Instagram</a>
            <a href="https://facebook.com" target="_blank" rel="noopener">Facebook</a>
            <a href="https://tiktok.com" target="_blank" rel="noopener">TikTok</a>
        </div>
    </div>
</footer>
<?php require APP_ROOT . '/includes/scripts.php'; ?>
