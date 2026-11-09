<?php
/**
 * Bootstrap: dimuat di awal setiap halaman.
 * - Membaca konfigurasi
 * - Memulai session
 * - Koneksi database (PDO MySQL)
 * - Fungsi bantuan (helper)
 */
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
date_default_timezone_set('Asia/Jakarta');

$GLOBALS['config'] = require APP_ROOT . '/config/config.php';

// Konfigurasi lokal/rahasia (tidak ikut GitHub): menimpa nilai bawaan jika file ada.
$localConfigFile = APP_ROOT . '/config/config.local.php';
if (is_file($localConfigFile)) {
    $localConfig = require $localConfigFile;
    if (is_array($localConfig)) {
        $GLOBALS['config'] = array_replace_recursive($GLOBALS['config'], $localConfig);
    }
}

function config(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

// ---------------------------------------------------------------- Session
$sessionLifetime = 60 * 60 * 24 * 30; // 30 hari (dipakai saat "Remember Me")
ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
session_name('LZFJSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ---------------------------------------------------------------- Database
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = config('db');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int) $c['port'], $c['name']);
    try {
        $pdo = new PDO($dsn, $c['user'], $c['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '+07:00'"); // WIB, agar waktu pesanan sesuai jam Indonesia
    } catch (PDOException $e) {
        http_response_code(500);
        echo '<div style="font-family:Poppins,Arial,sans-serif;max-width:640px;margin:80px auto;padding:24px;border:1px solid #e5e5e5;border-radius:12px">'
            . '<h2 style="margin:0 0 8px">Tidak bisa terhubung ke database</h2>'
            . '<p style="color:#666">Pastikan MySQL di XAMPP sudah <b>Start</b>, database <b>' . htmlspecialchars($c['name'])
            . '</b> sudah dibuat &amp; file <code>database/lzfj_shoes_store.sql</code> sudah di-import, lalu cek <code>config/config.php</code>.</p>'
            . '<pre style="background:#f4f4f4;padding:12px;border-radius:8px;white-space:pre-wrap">' . htmlspecialchars($e->getMessage()) . '</pre></div>';
        exit;
    }
    return $pdo;
}

// ---------------------------------------------------------------- URL helpers
function base_url(): string
{
    $configured = rtrim((string) config('base_url', ''), '/');
    if ($configured !== '') {
        return $configured;
    }
    $https  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $scheme . '://' . $host . $dir;
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function product_image(string $file): string
{
    return asset('img/products/' . $file);
}

function redirect(string $path): never
{
    $target = preg_match('#^https?://#', $path) ? $path : url($path);
    header('Location: ' . $target);
    exit;
}

/** Hanya izinkan redirect internal (mencegah open-redirect). */
function safe_redirect_path(?string $path, string $fallback = 'home.php'): string
{
    $path = (string) $path;
    if ($path === '' || preg_match('#^(https?:)?//#i', $path) || str_contains($path, "\n")) {
        return $fallback;
    }
    return ltrim($path, '/');
}

// ---------------------------------------------------------------- Output helpers
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rupiah(int|float $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

// ---------------------------------------------------------------- Flash message
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---------------------------------------------------------------- CSRF
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            json_response(['ok' => false, 'message' => 'Session expired, please refresh the page.'], 419);
        }
        http_response_code(419);
        exit('Session expired, please go back and refresh the page.');
    }
}

// ---------------------------------------------------------------- Auth
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) {
        return $user = null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch() ?: null;
    if (!$user) {
        unset($_SESSION['user_id']);
    }
    return $user;
}

function login_user(int $userId, bool $remember = false): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    if ($remember) {
        $p = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires'  => time() + 60 * 60 * 24 * 30,
            'path'     => $p['path'],
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $p['secure'],
        ]);
    }
}

function require_login(?string $returnTo = null): array
{
    $user = current_user();
    if (!$user) {
        $returnTo = $returnTo ?? ltrim(basename($_SERVER['SCRIPT_NAME'] ?? 'home.php') . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']), '/');
        flash('info', 'Please log in first to continue.');
        redirect('login.php?redirect=' . urlencode($returnTo));
    }
    return $user;
}

function unique_username(string $base): string
{
    $base = strtolower(preg_replace('/[^a-z0-9_]/i', '', $base)) ?: 'user';
    $base = substr($base, 0, 40);
    $candidate = $base;
    $i = 1;
    $stmt = db()->prepare('SELECT 1 FROM users WHERE username = ?');
    while (true) {
        $stmt->execute([$candidate]);
        if (!$stmt->fetchColumn()) {
            return $candidate;
        }
        $candidate = $base . (++$i);
    }
}

// ---------------------------------------------------------------- Products
function parse_colors(string $raw): array
{
    $out = [];
    foreach (array_filter(array_map('trim', explode(',', $raw))) as $pair) {
        [$name, $hex] = array_pad(explode(':', $pair, 2), 2, '#111111');
        $out[] = ['name' => trim($name), 'hex' => trim($hex)];
    }
    return $out;
}

function parse_sizes(string $raw): array
{
    return array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
}

function product_public(array $p): array
{
    return [
        'id'          => (int) $p['id'],
        'slug'        => $p['slug'],
        'name'        => $p['name'],
        'brand'       => $p['brand'],
        'category'    => strtoupper($p['category']),
        'price'       => (int) $p['price'],
        'price_label' => rupiah((int) $p['price']),
        'old_price'   => $p['old_price'] ? rupiah((int) $p['old_price']) : null,
        'badge'       => $p['badge'],
        'rating'      => number_format((float) $p['rating'], 1),
        'reviews'     => (int) $p['reviews'],
        'sold'        => (int) $p['sold'],
        'description' => $p['description'],
        'image'       => product_image($p['image']),
        'sizes'       => parse_sizes($p['sizes']),
        'colors'      => parse_colors($p['colors']),
        'stock'       => (int) $p['stock'],
    ];
}

function find_product(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// ---------------------------------------------------------------- Cart (disimpan di session)
function cart_items(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(array_column(cart_items(), 'qty'));
}

function cart_add(int $productId, string $size, string $color, int $qty): void
{
    $key = $productId . '|' . $size . '|' . $color;
    $cart = cart_items();
    if (isset($cart[$key])) {
        $cart[$key]['qty'] = min(10, $cart[$key]['qty'] + $qty);
    } else {
        $cart[$key] = ['product_id' => $productId, 'size' => $size, 'color' => $color, 'qty' => min(10, $qty)];
    }
    $_SESSION['cart'] = $cart;
}

function cart_update(string $key, int $qty): void
{
    if (!isset($_SESSION['cart'][$key])) {
        return;
    }
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
    } else {
        $_SESSION['cart'][$key]['qty'] = min(10, $qty);
    }
}

function cart_clear(): void
{
    unset($_SESSION['cart'], $_SESSION['promo']);
}

/** Gabungkan isi cart dengan data produk terbaru dari database. */
function cart_detailed(): array
{
    $items = [];
    $subtotal = 0;
    foreach (cart_items() as $key => $row) {
        $p = find_product((int) $row['product_id']);
        if (!$p) {
            continue;
        }
        $line = (int) $p['price'] * (int) $row['qty'];
        $subtotal += $line;
        $items[] = [
            'key'     => $key,
            'product' => $p,
            'size'    => $row['size'],
            'color'   => $row['color'],
            'qty'     => (int) $row['qty'],
            'line'    => $line,
        ];
    }
    return ['items' => $items, 'subtotal' => $subtotal];
}

function shipping_cost(string $method, int $subtotal): int
{
    $min = (int) config('free_shipping_min', 0);
    if ($min > 0 && $subtotal >= $min) {
        return 0;
    }
    return (int) config('shipping.' . ($method === 'express' ? 'express' : 'regular'), 25000);
}

function promo_discount(?array $promo, int $subtotal): int
{
    if (!$promo) {
        return 0;
    }
    $d = $promo['type'] === 'percent'
        ? (int) floor($subtotal * ((int) $promo['value']) / 100)
        : (int) $promo['value'];
    if (!empty($promo['max_discount'])) {
        $d = min($d, (int) $promo['max_discount']);
    }
    return max(0, min($d, $subtotal));
}

function find_promo(string $code): ?array
{
    $stmt = db()->prepare('SELECT * FROM promo_codes WHERE code = ? AND is_active = 1');
    $stmt->execute([strtoupper(trim($code))]);
    return $stmt->fetch() ?: null;
}

// ---------------------------------------------------------------- Payment channels
function payment_channels(): array
{
    return [
        'bank_transfer' => [
            'label' => 'Bank Transfer',
            'channels' => [
                'bca_va'     => ['name' => 'BCA',         'full' => 'BCA Virtual Account',         'color' => '#0060AF', 'logo' => 'bca'],
                'bni_va'     => ['name' => 'BNI',         'full' => 'BNI Virtual Account',         'color' => '#F15A23', 'logo' => 'bni'],
                'bri_va'     => ['name' => 'BRI',         'full' => 'BRI Virtual Account',         'color' => '#00529C', 'logo' => 'bri'],
                'echannel'   => ['name' => 'Mandiri',     'full' => 'Mandiri Bill Payment',        'color' => '#003D79', 'logo' => 'mandiri'],
                'bsi_va'     => ['name' => 'BSI',         'full' => 'BSI Virtual Account',         'color' => '#00A39D', 'logo' => 'bsi'],
                'permata_va' => ['name' => 'Permata',     'full' => 'Permata Virtual Account',     'color' => '#E2001A', 'logo' => 'permata'],
                'cimb_va'    => ['name' => 'CIMB Niaga',  'full' => 'CIMB Niaga Virtual Account',  'color' => '#7A0019', 'logo' => 'cimb'],
            ],
        ],
        'ewallet' => [
            'label' => 'E-Wallet',
            'channels' => [
                'gopay'      => ['name' => 'GoPay',     'full' => 'GoPay',     'color' => '#00AED6', 'logo' => 'gopay'],
                'shopeepay'  => ['name' => 'ShopeePay', 'full' => 'ShopeePay', 'color' => '#EE4D2D', 'logo' => 'shopeepay'],
                'other_qris' => ['name' => 'QRIS',      'full' => 'QRIS (DANA, OVO, LinkAja, dll)', 'color' => '#111111', 'logo' => 'qris'],
            ],
        ],
        'card' => [
            'label' => 'Credit / Debit Card',
            'channels' => [
                'credit_card' => ['name' => 'Card', 'full' => 'Visa / Mastercard / JCB', 'color' => '#111111', 'logo' => 'card'],
            ],
        ],
    ];
}

/**
 * URL logo pembayaran. Mencari file di assets/img/payment/<nama>.(svg|png|webp|jpg|jpeg|gif)
 * (taruh logo resmi di sana). Jika tidak ada, memakai logo sementara di assets/img/payment/placeholder/.
 */
function payment_logo_url(string $name): string
{
    $root = APP_ROOT . '/assets/img/payment/';
    foreach (['svg', 'png', 'webp', 'jpg', 'jpeg', 'gif'] as $ext) {
        if (is_file($root . $name . '.' . $ext)) {
            return asset('img/payment/' . $name . '.' . $ext);
        }
    }
    return asset('img/payment/placeholder/' . $name . '.svg');
}

function payment_label(string $method, string $channel): string
{
    $all = payment_channels();
    $m = $all[$method] ?? null;
    if (!$m) {
        return ucfirst($method);
    }
    if ($method === 'card') {
        return $m['label'];
    }
    $c = $m['channels'][$channel]['name'] ?? strtoupper($channel);
    return $m['label'] . ' - ' . $c;
}

function status_label(string $status): string
{
    return [
        'pending'   => 'Waiting for payment',
        'paid'      => 'Paid',
        'failed'    => 'Failed',
        'expired'   => 'Expired',
        'cancelled' => 'Cancelled',
        'refunded'  => 'Refunded',
    ][$status] ?? ucfirst($status);
}

require_once APP_ROOT . '/includes/http.php';
require_once APP_ROOT . '/includes/icons.php';
