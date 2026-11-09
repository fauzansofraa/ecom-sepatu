<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/google.php';

$redirectTo = $_SESSION['google_redirect'] ?? 'home.php';
$expected = $_SESSION['google_state'] ?? '';
unset($_SESSION['google_state'], $_SESSION['google_redirect']);

if (!empty($_GET['error'])) {
    flash('error', 'Google login was cancelled.');
    redirect('login.php');
}
if (!$expected || !hash_equals($expected, (string) ($_GET['state'] ?? '')) || empty($_GET['code'])) {
    flash('error', 'Google login failed (invalid state). Please try again.');
    redirect('login.php');
}

try {
    $profile = google_fetch_profile((string) $_GET['code']);
} catch (Throwable $e) {
    flash('error', $e->getMessage());
    redirect('login.php');
}

$googleId = (string) $profile['sub'];
$email = strtolower((string) $profile['email']);
$name = (string) ($profile['name'] ?? strstr($email, '@', true));
$avatar = (string) ($profile['picture'] ?? '');

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM users WHERE google_id = ? OR email = ? ORDER BY google_id = ? DESC LIMIT 1');
$stmt->execute([$googleId, $email, $googleId]);
$user = $stmt->fetch();

if ($user) {
    // Hubungkan akun lama (email sama) dengan Google
    $upd = $pdo->prepare("UPDATE users SET google_id = ?, avatar_url = ?, name = IF(name = '', ?, name) WHERE id = ?");
    $upd->execute([$googleId, $avatar ?: $user['avatar_url'], $name, $user['id']]);
    $userId = (int) $user['id'];
} else {
    $ins = $pdo->prepare('INSERT INTO users (name, username, email, google_id, avatar_url) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$name, unique_username(strstr($email, '@', true) ?: 'user'), $email, $googleId, $avatar]);
    $userId = (int) $pdo->lastInsertId();
}

login_user($userId, true);
flash('success', 'Logged in with Google. Welcome, ' . $name . '!');
redirect($redirectTo);
