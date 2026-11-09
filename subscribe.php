<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_post()) {
    csrf_check();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        db()->prepare('INSERT IGNORE INTO subscribers (email) VALUES (?)')->execute([$email]);
        flash('success', 'Thanks for subscribing! Use code LZFJ10 for 10% off your first order.');
    } else {
        flash('error', 'Please enter a valid email address.');
    }
}
redirect('home.php');
