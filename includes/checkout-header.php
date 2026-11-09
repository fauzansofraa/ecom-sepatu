<?php
/** Header sederhana untuk Cart / Checkout / Confirmation (sesuai desain). */
require APP_ROOT . '/includes/head.php';
?>
<header class="checkout-header">
    <div class="container checkout-header-inner">
        <a class="logo" href="<?= e(url('home.php')) ?>"><b>LZFJ</b> <span>Shoes Store</span></a>
        <p class="muted">Secure checkout. Need help? <?= e(config('store.support')) ?></p>
    </div>
</header>
