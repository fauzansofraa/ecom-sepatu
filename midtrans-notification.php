<?php
/**
 * Payment Notification URL (Webhook) Midtrans.
 * Isi di Dashboard Midtrans > Settings > Payment > Notification URL:
 *   https://DOMAIN-ANDA/midtrans-notification.php
 */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/midtrans.php';

if (!is_post()) {
    http_response_code(405);
    exit('Method not allowed');
}

$raw = file_get_contents('php://input');
$n = json_decode((string) $raw, true);
if (!is_array($n) || empty($n['order_id'])) {
    http_response_code(400);
    exit('Invalid payload');
}

if (!midtrans_verify_signature($n)) {
    http_response_code(403);
    exit('Invalid signature');
}

$stmt = db()->prepare('SELECT * FROM orders WHERE order_code = ?');
$stmt->execute([$n['order_id']]);
$order = $stmt->fetch();
if (!$order) {
    // Tetap balas 200 agar Midtrans tidak mengulang notifikasi untuk order yang tidak dikenal (mis. tes dari dashboard)
    http_response_code(200);
    exit('Order not found');
}

if ((int) round((float) $n['gross_amount']) !== (int) $order['total']) {
    http_response_code(400);
    exit('Amount mismatch');
}

$order = midtrans_apply_status($order, $n);

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['ok' => true, 'status' => $order['status']]);
