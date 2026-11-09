<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/google.php';

$redirectTo = safe_redirect_path($_GET['redirect'] ?? $_POST['redirect'] ?? '', 'home.php');
$startPage = isset($_GET['start']);
$me = current_user();
if ($me && !$startPage) {
    redirect($redirectTo);
}

$error = '';
$identity = '';
if (is_post()) {
    csrf_check();
    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
    $stmt->execute([strtolower($identity), strtolower($identity)]);
    $user = $stmt->fetch();
    if (!$user || !$user['password_hash'] || !password_verify($password, $user['password_hash'])) {
        $error = ($user && !$user['password_hash'])
            ? 'This account was created with Google. Please use "Login With Google".'
            : 'Incorrect email/username or password.';
    } else {
        login_user((int) $user['id'], !empty($_POST['remember']));
        flash('success', 'Welcome back, ' . $user['name'] . '!');
        redirect($redirectTo);
    }
}

$pageTitle = 'Login — LZFJ Shoes Store';
$pageDescription = 'Sign in to track orders, save favorites and check out faster.';
$bodyClass = 'auth-body';
require __DIR__ . '/includes/head.php';
?>
<main class="auth-split">
    <section class="brand-panel" style="background-image:url('<?= e(asset('img/login-bg.jpg')) ?>')">
        <a class="brand-logo" href="<?= e(url('home.php')) ?>">LZFJ</a>
        <div class="brand-copy">
            <h1>Step Into Greatness</h1>
            <p>Sign in to track orders, save favorites and check out faster.</p>
        </div>
        <small class="brand-foot">© LZFJ Shoes Store</small>
    </section>
    <section class="form-side">
        <div class="auth-card">
            <a class="logo logo-center" href="<?= e(url('home.php')) ?>"><b>LZFJ</b> <span>Shoes Store</span></a>
            <h2>Welcome Back</h2>
            <p class="muted center">Login to continue shopping</p>
            <?php if ($me): ?><div class="alert">You are already signed in as <b><?= e($me['name']) ?></b>. <a class="link-strong" href="<?= e(url('home.php')) ?>">Continue to the store</a></div><?php endif; ?>
            <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
            <form method="post" class="auth-form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="redirect" value="<?= e($redirectTo) ?>">
                <label class="field">
                    <span>Email or username</span>
                    <input class="input" type="text" name="identity" value="<?= e($identity) ?>" placeholder="name@email.com" autocomplete="username" required>
                </label>
                <label class="field">
                    <span>Password</span>
                    <span class="password-wrap">
                        <input class="input" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="show-btn" data-toggle-password>Show</button>
                    </span>
                </label>
                <div class="auth-row">
                    <label class="check check-dark"><input type="checkbox" name="remember" value="1" checked><span>Remember Me</span></label>
                    <a class="link-strong" href="<?= e(url('forgot-password.php')) ?>">Forgot Password?</a>
                </div>
                <button class="btn btn-dark btn-block btn-lg" type="submit">Login</button>
                <a class="btn btn-google btn-block" href="<?= e(url('google-login.php?redirect=' . urlencode($redirectTo))) ?>">
                    <?= google_logo(18) ?><span>Login With Google</span>
                </a>
                <p class="auth-switch">New here? <a href="<?= e(url('register.php' . ($redirectTo !== 'home.php' ? '?redirect=' . urlencode($redirectTo) : ''))) ?>">Create an Account</a></p>
            </form>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/scripts.php'; ?>
