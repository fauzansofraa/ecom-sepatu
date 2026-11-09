<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/midtrans.php';

$code = (string) ($_GET['order'] ?? $_GET['order_id'] ?? '');
$user = require_login('order-confirmation.php?order=' . urlencode($code));

$stmt = db()->prepare('SELECT * FROM orders WHERE order_code = ? AND user_id = ?');
$stmt->execute([$code, $user['id']]);
$order = $stmt->fetch();
if (!$order) {
    http_response_code(404);
    flash('error', 'Order not found.');
    redirect('account.php');
}

// Sinkronkan status langsung dari Midtrans (berguna di localhost, saat notifikasi
// Midtrans belum bisa menjangkau komputer Anda).
if ($order['status'] === 'pending') {
    $status = midtrans_get_status($order['order_code']);
    if ($status) {
        $order = midtrans_apply_status($order, $status);
    }
}

$itemsStmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$order['id']]);
$items = $itemsStmt->fetchAll();

$created = new DateTime($order['created_at']);
[$minDays, $maxDays] = $order['delivery_method'] === 'express' ? [1, 2] : [3, 5];
$etaFrom = (clone $created)->modify("+$minDays day");
$etaTo = (clone $created)->modify("+$maxDays day");
$isPaid = $order['status'] === 'paid';
$isPending = $order['status'] === 'pending';

$pageTitle = 'Order ' . $order['order_code'] . ' — LZFJ Shoes Store';
$pageDescription = 'Thank you for your order at LZFJ Shoes Store.';
require __DIR__ . '/includes/checkout-header.php';
?>
<main class="container confirm-page">
    <?php $stepDone = $isPaid ? 4 : 3; $stepsClass = 'steps-center'; require __DIR__ . '/includes/steps.php'; ?>
    <div class="confirm-check <?= $isPaid ? '' : 'is-pending' ?>"><?= icon($isPaid || $isPending ? 'check' : 'x', 30) ?></div>
    <h1 class="confirm-title"><?= $isPaid || $isPending ? 'Thank you for your order!' : 'Payment ' . e(strtolower(status_label($order['status']))) ?></h1>
    <p class="muted center">
        <?php if ($isPaid): ?>
            Your order has been placed. A confirmation email was sent to <?= e($order['email']) ?>.
        <?php elseif ($isPending): ?>
            Your order has been placed. Please complete your payment within 24 hours.
        <?php else: ?>
            This order was not paid. You can place a new order from the shop.
        <?php endif; ?>
    </p>

    <div class="confirm-card">
        <div class="sum-row"><span>Order number</span><strong><?= e($order['order_code']) ?></strong></div>
        <div class="sum-row"><span>Order date</span><strong><?= e($created->format('d M Y, H:i')) ?></strong></div>
        <div class="sum-row"><span>Payment method</span><strong><?= e(payment_label($order['payment_method'], $order['payment_channel'])) ?></strong></div>
        <div class="sum-row"><span>Payment status</span><strong><span class="status status-<?= e($order['status']) ?>"><?= e(status_label($order['status'])) ?></span></strong></div>
        <?php if ($isPending && $order['va_number']): ?>
            <div class="sum-row"><span>Virtual Account</span><strong class="va"><?= e($order['va_number']) ?></strong></div>
        <?php endif; ?>
        <div class="sum-row"><span>Estimated delivery</span><strong><?= e($etaFrom->format('d M') . ' - ' . $etaTo->format('d M Y')) ?></strong></div>
        <hr>
        <?php foreach ($items as $it): ?>
            <div class="sum-row"><span><?= e($it['product_name']) ?> (Size <?= e($it['size']) ?> x <?= (int) $it['qty'] ?>)</span><strong><?= e(rupiah($it['price'] * $it['qty'])) ?></strong></div>
        <?php endforeach; ?>
        <div class="sum-row"><span>Shipping</span><strong><?= e(rupiah((int) $order['shipping_cost'])) ?></strong></div>
        <?php if ((int) $order['discount'] > 0): ?>
            <div class="sum-row"><span>Discount (<?= e($order['promo_code']) ?>)</span><strong>- <?= e(rupiah((int) $order['discount'])) ?></strong></div>
        <?php endif; ?>
        <div class="sum-total"><span>Total</span><span><?= e(rupiah((int) $order['total'])) ?></span></div>
    </div>

    <div class="confirm-actions">
        <?php if ($isPending && $order['snap_token']): ?>
            <button class="btn btn-dark btn-lg" type="button" data-pay-now="<?= e($order['order_code']) ?>">Pay Now</button>
        <?php else: ?>
            <a class="btn btn-dark btn-lg" href="<?= e(url('account.php')) ?>">Track Order</a>
        <?php endif; ?>
        <a class="btn btn-outline btn-lg" href="<?= e(url('shop.php')) ?>">Continue Shopping</a>
    </div>
    <?php if ($isPending): ?>
        <p class="center small muted"><a class="link" href="<?= e(url('order-confirmation.php?order=' . urlencode($order['order_code']))) ?>">Already paid? Refresh status</a> · <a class="link" href="<?= e(url('account.php')) ?>">Track Order</a></p>
    <?php endif; ?>
</main>
<?php if ($isPending): ?>
<script src="<?= e(midtrans_snap_js()) ?>" data-client-key="<?= e(midtrans_client_key()) ?>"></script>
<?php endif; ?>
<?php $extraScripts = [asset('js/checkout.js') . '?v=1']; require __DIR__ . '/includes/scripts.php'; ?>
