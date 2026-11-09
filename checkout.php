<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/midtrans.php';

$user = require_login('checkout.php');
$cart = cart_detailed();
if (!$cart['items']) {
    flash('info', 'Your cart is empty.');
    redirect('cart.php');
}

$promo = !empty($_SESSION['promo']) ? find_promo($_SESSION['promo']) : null;
$discount = promo_discount($promo, $cart['subtotal']);
$shipRegular = shipping_cost('regular', $cart['subtotal']);
$shipExpress = shipping_cost('express', $cart['subtotal']);
$channels = payment_channels();

// Isi otomatis dari pesanan terakhir
$last = db()->prepare('SELECT full_name, phone, city, address, postal_code FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$last->execute([$user['id']]);
$prefill = $last->fetch() ?: [];

$pageTitle = 'Checkout — LZFJ Shoes Store';
$pageDescription = 'Secure checkout with bank transfer, e-wallet or card via Midtrans.';
require __DIR__ . '/includes/checkout-header.php';
?>
<main class="container checkout-page">
    <h1 class="page-title">Checkout</h1>
    <?php $stepDone = 2; require __DIR__ . '/includes/steps.php'; ?>

    <?php if (!midtrans_is_configured()): ?>
        <div class="alert">Midtrans is not configured yet. Fill in <code>midtrans.server_key</code> and <code>midtrans.client_key</code> in <code>config/config.php</code> (see README.md).</div>
    <?php endif; ?>

    <form class="checkout-layout" id="checkout-form" novalidate
          data-subtotal="<?= (int) $cart['subtotal'] ?>"
          data-discount="<?= (int) $discount ?>"
          data-ship-regular="<?= (int) $shipRegular ?>"
          data-ship-express="<?= (int) $shipExpress ?>">
        <?= csrf_field() ?>
        <div class="checkout-main">
            <section class="panel">
                <h2 class="panel-title">Shipping Information</h2>
                <div class="form-grid">
                    <label class="field"><span>Full name</span>
                        <input class="input" name="full_name" required maxlength="120" placeholder="[FULL NAME]" value="<?= e($prefill['full_name'] ?? $user['name']) ?>" autocomplete="name"></label>
                    <label class="field"><span>Email</span>
                        <input class="input" type="email" name="email" required maxlength="190" placeholder="name@email.com" value="<?= e($user['email']) ?>" autocomplete="email"></label>
                    <label class="field"><span>Phone number</span>
                        <input class="input" type="tel" name="phone" required maxlength="20" placeholder="[PHONE]" value="<?= e($prefill['phone'] ?? $user['phone'] ?? '') ?>" autocomplete="tel"></label>
                    <label class="field"><span>City</span>
                        <input class="input" name="city" required maxlength="80" placeholder="[CITY]" value="<?= e($prefill['city'] ?? '') ?>" autocomplete="address-level2"></label>
                    <label class="field span-2"><span>Address</span>
                        <input class="input" name="address" required maxlength="255" placeholder="[FULL ADDRESS]" value="<?= e($prefill['address'] ?? '') ?>" autocomplete="street-address"></label>
                    <label class="field span-2"><span>Postal code</span>
                        <input class="input" name="postal_code" required maxlength="10" inputmode="numeric" placeholder="[POSTAL CODE]" value="<?= e($prefill['postal_code'] ?? '') ?>" autocomplete="postal-code"></label>
                </div>
            </section>

            <section class="panel">
                <h2 class="panel-title">Delivery Method</h2>
                <div class="options">
                    <label class="option"><input type="radio" name="delivery" value="regular" required><span class="radio"></span><span class="option-label">Regular (3-5 days)</span><span class="option-aside"><?= e(rupiah($shipRegular)) ?></span></label>
                    <label class="option"><input type="radio" name="delivery" value="express"><span class="radio"></span><span class="option-label">Express (1-2 days)</span><span class="option-aside"><?= e(rupiah($shipExpress)) ?></span></label>
                </div>
            </section>

            <section class="panel">
                <h2 class="panel-title">Payment Method</h2>
                <div class="options">
                    <?php foreach ($channels as $method => $group): ?>
                        <div class="option-group" data-method-group="<?= e($method) ?>">
                            <label class="option">
                                <input type="radio" name="payment_method" value="<?= e($method) ?>" required>
                                <span class="radio"></span>
                                <span class="option-label"><?= e($group['label']) ?></span>
                                <span class="option-aside"><?= icon($method === 'bank_transfer' ? 'bank' : ($method === 'ewallet' ? 'wallet' : 'card'), 18) ?></span>
                            </label>
                            <div class="channel-list" data-channels="<?= e($method) ?>" hidden>
                                <p class="channel-hint"><?= $method === 'bank_transfer' ? 'Choose your bank (Virtual Account):' : ($method === 'ewallet' ? 'Choose your e-wallet:' : 'Pay with Visa, Mastercard or JCB. Card details are entered securely on Midtrans.') ?></p>
                                <div class="channel-grid <?= $method === 'card' ? 'single' : '' ?>">
                                    <?php foreach ($group['channels'] as $code => $ch): ?>
                                        <label class="channel">
                                            <input type="radio" name="payment_channel" value="<?= e($code) ?>" <?= $method === 'card' ? 'data-auto-select' : '' ?>>
                                            <?php if (!empty($ch['logo'])): ?>
                                                <img class="channel-img" src="<?= e(payment_logo_url($ch['logo'])) ?>" alt="<?= e($ch['name']) ?>" loading="lazy">
                                            <?php else: ?>
                                                <span class="channel-logo" style="--brand: <?= e($ch['color']) ?>"><?= e($ch['name']) ?></span>
                                            <?php endif; ?>
                                            <span class="channel-name"><?= e($ch['full']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <aside class="summary summary-sticky">
            <h2 class="panel-title">Order Summary</h2>
            <div class="summary-items">
                <?php foreach ($cart['items'] as $it): $p = $it['product']; ?>
                    <div class="summary-item">
                        <div class="thumb thumb-sm"><img src="<?= e(product_image($p['image'])) ?>" alt=""></div>
                        <div><strong><?= e($p['name']) ?></strong><span class="muted">Size <?= e($it['size']) ?> / Qty <?= (int) $it['qty'] ?></span></div>
                        <strong><?= e(rupiah($it['line'])) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="promo">
                <input class="input" name="promo_code" placeholder="Promo code" value="<?= e($promo['code'] ?? '') ?>" aria-label="Promo code" data-promo-input>
                <button class="btn btn-outline" type="button" data-apply-promo>Apply</button>
            </div>
            <p class="promo-msg small" data-promo-msg><?= $promo ? 'Promo ' . e($promo['code']) . ' applied.' : '' ?></p>
            <div class="sum-row"><span>Subtotal</span><span data-sum-subtotal><?= e(rupiah($cart['subtotal'])) ?></span></div>
            <div class="sum-row"><span>Shipping</span><span data-sum-shipping><?= e(rupiah($shipRegular)) ?></span></div>
            <div class="sum-row"><span>Discount</span><span data-sum-discount>- <?= e(rupiah($discount)) ?></span></div>
            <hr>
            <div class="sum-total"><span>Total</span><span data-sum-total><?= e(rupiah($cart['subtotal'] + $shipRegular - $discount)) ?></span></div>
            <p class="form-error" data-form-error hidden></p>
            <button class="btn btn-dark btn-block btn-lg" type="submit" data-place-order>Place Order</button>
            <p class="muted center small">Payments are encrypted and secure.</p>
        </aside>
    </form>
</main>
<script src="<?= e(midtrans_snap_js()) ?>" data-client-key="<?= e(midtrans_client_key()) ?>"></script>
<?php $extraScripts = [asset('js/checkout.js') . '?v=1']; require __DIR__ . '/includes/scripts.php'; ?>
