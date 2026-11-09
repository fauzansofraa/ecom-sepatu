<?php
/** Endpoint AJAX keranjang: add | update | remove */
require __DIR__ . '/includes/bootstrap.php';

if (!is_post()) {
    json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
}
csrf_check();

$action = (string) ($_POST['action'] ?? '');

if ($action === 'add') {
    $product = find_product((int) ($_POST['product_id'] ?? 0));
    if (!$product) {
        json_response(['ok' => false, 'message' => 'Product not found.'], 404);
    }
    $size = (string) ($_POST['size'] ?? '');
    $color = (string) ($_POST['color'] ?? '');
    $qty = max(1, min(10, (int) ($_POST['qty'] ?? 1)));
    if (!in_array($size, parse_sizes($product['sizes']), true)) {
        json_response(['ok' => false, 'message' => 'Please choose a size.'], 422);
    }
    if (!in_array($color, array_column(parse_colors($product['colors']), 'name'), true)) {
        json_response(['ok' => false, 'message' => 'Please choose a color.'], 422);
    }
    if ((int) $product['stock'] < $qty) {
        json_response(['ok' => false, 'message' => 'Not enough stock.'], 422);
    }
    cart_add((int) $product['id'], $size, $color, $qty);
    json_response(['ok' => true, 'count' => cart_count(), 'message' => $product['name'] . ' added to cart.']);
}

if ($action === 'update' || $action === 'remove') {
    $key = (string) ($_POST['key'] ?? '');
    cart_update($key, $action === 'remove' ? 0 : (int) ($_POST['qty'] ?? 1));
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        json_response(['ok' => true, 'count' => cart_count()]);
    }
    redirect('cart.php');
}

json_response(['ok' => false, 'message' => 'Unknown action'], 400);
