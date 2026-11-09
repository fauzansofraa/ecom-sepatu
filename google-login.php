<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/google.php';

if (!google_is_configured()) {
    flash('error', 'Google login is not configured yet. Fill in google.client_id & client_secret in config/config.php (see README.md).');
    redirect('login.php');
}

$state = bin2hex(random_bytes(16));
$_SESSION['google_state'] = $state;
$_SESSION['google_redirect'] = safe_redirect_path($_GET['redirect'] ?? '', 'home.php');

header('Location: ' . google_auth_url($state));
exit;
