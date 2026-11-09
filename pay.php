<?php
/** Ambil kembali Snap Token untuk pesanan yang belum dibayar (tombol "Pay Now"). */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/midtrans.php';

if (!is_post()) {
    json_response(['ok' => false], 405);
}
csrf_check();
$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'message' => 'Please log in first.'], 401);
}

$stmt = db()->prepare('SELECT * FROM orders WHERE order_code = ? AND user_id = ?');
$stmt->execute([(string) ($_POST['order'] ?? ''), $user['id']]);
$order = $stmt->fetch();
if (!$order) {
    json_response(['ok' => false, 'message' => 'Order not found.'], 404);
}
if ($order['status'] !== 'pending' || !$order['snap_token']) {
    json_response(['ok' => false, 'message' => 'This order can no longer be paid.'], 422);
}
json_response(['ok' => true, 'token' => $order['snap_token'], 'redirect_url' => $order['snap_redirect_url']]);
