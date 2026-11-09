<?php
require __DIR__ . '/includes/bootstrap.php';

$redirectTo = safe_redirect_path($_GET['redirect'] ?? $_POST['redirect'] ?? '', 'home.php');
if (current_user()) {
    redirect($redirectTo);
}

$error = '';
$email = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'This email is already registered. Please log in.';
        } else {
            $local = strstr($email, '@', true) ?: 'user';
            $name = ucwords(str_replace(['.', '_', '-'], ' ', $local));
            $ins = db()->prepare('INSERT INTO users (name, username, email, password_hash) VALUES (?, ?, ?, ?)');
            $ins->execute([$name, unique_username($local), $email, password_hash($password, PASSWORD_DEFAULT)]);
            login_user((int) db()->lastInsertId(), !empty($_POST['remember']));
            flash('success', 'Your account has been created. Happy shopping!');
            redirect($redirectTo);
        }
    }
}

$pageTitle = 'Create Account — LZFJ Shoes Store';
$pageDescription = 'Create an account to continue shopping at LZFJ Shoes Store.';
$bodyClass = 'auth-body auth-plain';
require __DIR__ . '/includes/head.php';
?>
<main class="auth-center">
    <div class="auth-card">
        <a class="logo logo-center" href="<?= e(url('home.php')) ?>"><b>LZFJ</b> <span>Shoes Store</span></a>
        <h2>Create Account</h2>
        <p class="muted center">Create an Account to continue shopping</p>
        <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="auth-form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="redirect" value="<?= e($redirectTo) ?>">
            <label class="field">
                <span>Email or username</span>
                <input class="input" type="email" name="email" value="<?= e($email) ?>" placeholder="name@email.com" autocomplete="email" required>
            </label>
            <label class="field">
                <span>Password</span>
                <span class="password-wrap">
                    <input class="input" type="password" name="password" placeholder="Enter your password" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="show-btn" data-toggle-password>Show</button>
                </span>
            </label>
            <div class="auth-row">
                <label class="check check-dark"><input type="checkbox" name="remember" value="1" checked><span>Remember Me</span></label>
                <a class="link-strong" href="<?= e(url('forgot-password.php')) ?>">Forgot Password?</a>
            </div>
            <button class="btn btn-dark btn-block btn-lg" type="submit">Create Account</button>
            <p class="auth-switch">Have an Account? <a href="<?= e(url('login.php' . ($redirectTo !== 'home.php' ? '?redirect=' . urlencode($redirectTo) : ''))) ?>">Log In</a></p>
        </form>
    </div>
</main>
<?php require __DIR__ . '/includes/scripts.php'; ?>
