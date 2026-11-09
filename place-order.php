<?php
/**
 * Membuat pesanan di database lalu meminta Snap Token ke Midtrans.
 * Dipanggil lewat AJAX dari checkout.php.
 */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/midtrans.php';

if (!is_post()) {
    json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
}
csrf_check();

$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'message' => 'Please log in first.', 'redirect' => url('login.php?redirect=checkout.php')], 401);
}

$cart = cart_detailed();
if (!$cart['items']) {
    json_response(['ok' => false, 'message' => 'Your cart is empty.', 'redirect' => url('cart.php')], 422);
}

// ---------------- Validasi input
$in = fn(string $k, int $max) => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
$data = [
    'full_name'   => $in('full_name', 120),
    'email'       => strtolower($in('email', 190)),
    'phone'       => preg_replace('/[^0-9+]/', '', $in('phone', 20)),
    'city'        => $in('city', 80),
    'address'     => $in('address', 255),
    'postal_code' => preg_replace('/[^0-9]/', '', $in('postal_code', 10)),
];
$errors = [];
if ($data['full_name'] === '') $errors[] = 'Full name is required.';
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is not valid.';
if (strlen($data['phone']) < 8) $errors[] = 'Phone number is not valid.';
if ($data['city'] === '') $errors[] = 'City is required.';
if ($data['address'] === '') $errors[] = 'Address is required.';
if (strlen($data['postal_code']) < 5) $errors[] = 'Postal code is not valid.';

$delivery = $_POST['delivery'] ?? '';
if (!in_array($delivery, ['regular', 'express'], true)) $errors[] = 'Please choose a delivery method.';

$channels = payment_channels();
$method = (string) ($_POST['payment_method'] ?? '');
$channel = (string) ($_POST['payment_channel'] ?? '');
if (!isset($channels[$method])) {
    $errors[] = 'Please choose a payment method.';
} elseif (!isset($channels[$method]['channels'][$channel])) {
    $errors[] = $method === 'bank_transfer' ? 'Please choose your bank.' : ($method === 'ewallet' ? 'Please choose your e-wallet.' : 'Please choose a payment option.');
}
if ($errors) {
    json_response(['ok' => false, 'message' => implode(' ', $errors)], 422);
}
if (!midtrans_is_configured()) {
    json_response(['ok' => false, 'message' => 'Midtrans is not configured yet. Fill in the Midtrans keys in config/config.php.'], 500);
}

// ---------------- Hitung ulang total di server (jangan percaya data dari browser)
foreach ($cart['items'] as $it) {
    if ((int) $it['product']['stock'] < $it['qty']) {
        json_response(['ok' => false, 'message' => $it['product']['name'] . ' is out of stock.'], 422);
    }
}
$promo = !empty($_SESSION['promo']) ? find_promo($_SESSION['promo']) : null;
$subtotal = $cart['subtotal'];
$discount = promo_discount($promo, $subtotal);
$shipping = shipping_cost($delivery, $subtotal);
$total = $subtotal + $shipping - $discount;

$orderCode = 'LZFJ-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
$order = $data + [
    'order_code'      => $orderCode,
    'user_id'         => (int) $user['id'],
    'delivery_method' => $delivery,
    'payment_method'  => $method,
    'payment_channel' => $channel,
    'subtotal'        => $subtotal,
    'shipping_cost'   => $shipping,
    'discount'        => $discount,
    'promo_code'      => $promo['code'] ?? null,
    'total'           => $total,
];
$items = [];
foreach ($cart['items'] as $it) {
    $items[] = [
        'product_id'   => (int) $it['product']['id'],
        'product_name' => $it['product']['name'],
        'image'        => $it['product']['image'],
        'size'         => $it['size'],
        'color'        => $it['color'],
        'qty'          => $it['qty'],
        'price'        => (int) $it['product']['price'],
    ];
}

$pdo = db();
$pdo->beginTransaction();
try {
    $cols = array_keys($order);
    $stmt = $pdo->prepare('INSERT INTO orders (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')');
    $stmt->execute(array_values($order));
    $orderId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, image, size, color, qty, price) VALUES (?,?,?,?,?,?,?,?)');
    $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ?, sold = sold + ? WHERE id = ? AND stock >= ?');
    foreach ($items as $it) {
        $itemStmt->execute([$orderId, $it['product_id'], $it['product_name'], $it['image'], $it['size'], $it['color'], $it['qty'], $it['price']]);
        $stockStmt->execute([$it['qty'], $it['qty'], $it['product_id'], $it['qty']]);
    }

    // Minta Snap Token ke Midtrans
    $snap = midtrans_create_snap($order, $items);
    $pdo->prepare('UPDATE orders SET snap_token = ?, snap_redirect_url = ? WHERE id = ?')
        ->execute([$snap['token'], $snap['redirect_url'] ?? null, $orderId]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_response(['ok' => false, 'message' => 'Payment could not be started: ' . $e->getMessage()], 502);
}

// Simpan data kontak untuk checkout berikutnya
if (empty($user['phone'])) {
    $pdo->prepare('UPDATE users SET phone = ? WHERE id = ?')->execute([$data['phone'], $user['id']]);
}
cart_clear();

json_response([
    'ok'           => true,
    'order_code'   => $orderCode,
    'token'        => $snap['token'],
    'redirect_url' => $snap['redirect_url'] ?? null,
    'confirm_url'  => url('order-confirmation.php?order=' . urlencode($orderCode)),
]);
