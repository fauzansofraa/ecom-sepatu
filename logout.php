<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_post()) {
    csrf_check();
    $cart = $_SESSION['cart'] ?? [];
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['cart'] = $cart;
    flash('success', 'You have been logged out.');
}
redirect('login.php');
