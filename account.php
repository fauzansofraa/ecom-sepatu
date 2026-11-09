<?php
require __DIR__ . '/includes/bootstrap.php';

$user = require_login('account.php');
$stmt = db()->prepare('SELECT o.*, (SELECT SUM(qty) FROM order_items WHERE order_id = o.id) AS item_count FROM orders o WHERE user_id = ? ORDER BY id DESC');
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Account — LZFJ Shoes Store';
$pageDescription = 'Track your orders and manage your LZFJ Shoes Store account.';
require __DIR__ . '/includes/header.php';
?>
<main class="container account-page">
    <div class="account-head">
        <div class="account-user">
            <?php if ($user['avatar_url']): ?>
                <img class="avatar-lg" src="<?= e($user['avatar_url']) ?>" alt="" referrerpolicy="no-referrer">
            <?php else: ?>
                <span class="avatar-lg avatar-initial"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
            <?php endif; ?>
            <div>
                <h1 class="page-title page-title-sm"><?= e($user['name']) ?></h1>
                <p class="muted"><?= e($user['email']) ?><?= $user['google_id'] ? ' · Google account' : '' ?></p>
            </div>
        </div>
        <form method="post" action="<?= e(url('logout.php')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-outline" type="submit"><?= icon('logout', 18) ?> Logout</button>
        </form>
    </div>

    <section class="panel">
        <h2 class="panel-title">My Orders</h2>
        <?php if (!$orders): ?>
            <p class="muted">You have no orders yet.</p>
            <a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Start Shopping</a>
        <?php else: ?>
            <div class="table-wrap">
                <table class="orders-table">
                    <thead><tr><th>Order number</th><th>Date</th><th>Items</th><th>Payment</th><th>Total</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong><?= e($o['order_code']) ?></strong></td>
                            <td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
                            <td><?= (int) $o['item_count'] ?></td>
                            <td><?= e(payment_label($o['payment_method'], $o['payment_channel'])) ?></td>
                            <td><strong><?= e(rupiah((int) $o['total'])) ?></strong></td>
                            <td><span class="status status-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
                            <td><a class="btn btn-outline btn-sm" href="<?= e(url('order-confirmation.php?order=' . urlencode($o['order_code']))) ?>">Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
