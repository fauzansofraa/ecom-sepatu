<?php
require __DIR__ . '/includes/bootstrap.php';

$cart = cart_detailed();
$pageTitle = 'Your Cart — LZFJ Shoes Store';
$pageDescription = 'Review the shoes in your cart before checkout.';
require __DIR__ . '/includes/checkout-header.php';
?>
<main class="container checkout-page">
    <h1 class="page-title">Your Cart</h1>
    <?php $stepDone = 1; require __DIR__ . '/includes/steps.php'; ?>

    <?php if (!$cart['items']): ?>
        <div class="panel empty-cart">
            <?= icon('bag', 40) ?>
            <h2>Your cart is empty</h2>
            <p class="muted">Find your next favorite pair in our collection.</p>
            <a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="checkout-layout">
            <section class="panel">
                <h2 class="panel-title">Cart Items</h2>
                <div class="cart-list">
                    <?php foreach ($cart['items'] as $it): $p = $it['product']; ?>
                        <div class="cart-row">
                            <div class="thumb"><img src="<?= e(product_image($p['image'])) ?>" alt="<?= e($p['name']) ?>"></div>
                            <div class="cart-meta">
                                <strong><?= e($p['name']) ?></strong>
                                <span class="muted">Size <?= e($it['size']) ?> / <?= e($it['color']) ?></span>
                                <span><?= e(rupiah((int) $p['price'])) ?></span>
                            </div>
                            <form class="qty-form" method="post" action="<?= e(url('cart-action.php')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="key" value="<?= e($it['key']) ?>">
                                <div class="qty">
                                    <button class="qty-btn" type="submit" name="qty" value="<?= $it['qty'] - 1 ?>" aria-label="Decrease">-</button>
                                    <span><?= (int) $it['qty'] ?></span>
                                    <button class="qty-btn" type="submit" name="qty" value="<?= min(10, $it['qty'] + 1) ?>" aria-label="Increase">+</button>
                                </div>
                            </form>
                            <strong class="cart-line"><?= e(rupiah($it['line'])) ?></strong>
                            <form method="post" action="<?= e(url('cart-action.php')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="key" value="<?= e($it['key']) ?>">
                                <button class="icon-btn remove-btn" type="submit" aria-label="Remove item"><?= icon('trash', 18) ?></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <aside class="summary">
                <h2 class="panel-title">Order Summary</h2>
                <div class="sum-row"><span>Subtotal</span><span><?= e(rupiah($cart['subtotal'])) ?></span></div>
                <div class="sum-row"><span>Shipping</span><span>Calculated at next step</span></div>
                <hr>
                <div class="sum-total"><span>Total</span><span><?= e(rupiah($cart['subtotal'])) ?></span></div>
                <a class="btn btn-dark btn-block btn-lg" href="<?= e(url('checkout.php')) ?>">Proceed to Checkout</a>
                <a class="btn btn-outline btn-block" href="<?= e(url('shop.php')) ?>">Continue Shopping</a>
                <p class="muted center small">Payments are encrypted and secure.</p>
            </aside>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/scripts.php'; ?>
