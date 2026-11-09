<?php
require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Forgot Password — LZFJ Shoes Store';
$pageDescription = 'Recover access to your LZFJ Shoes Store account.';
$bodyClass = 'auth-body auth-plain';
require __DIR__ . '/includes/head.php';
?>
<main class="auth-center">
    <div class="auth-card">
        <a class="logo logo-center" href="<?= e(url('home.php')) ?>"><b>LZFJ</b> <span>Shoes Store</span></a>
        <h2>Forgot Password?</h2>
        <p class="muted center">If you signed up with Google, just use <b>Login With Google</b>.<br>
            Otherwise contact us at <?= e(config('store.email')) ?> and we will reset your password.</p>
        <a class="btn btn-dark btn-block btn-lg" href="<?= e(url('login.php')) ?>">Back to Login</a>
    </div>
</main>
<?php require __DIR__ . '/includes/scripts.php'; ?>
