<?php
/**
 * Halaman pengecekan koneksi Midtrans.
 * Buka: http://localhost/lzfj-shoes-store/cek-midtrans.php
 * Demi keamanan hanya bisa dibuka dari komputer sendiri (localhost).
 */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/midtrans.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Halaman ini hanya bisa dibuka dari localhost.');
}

$rawSk = (string) config('midtrans.server_key');
$rawCk = (string) config('midtrans.client_key');
$sk = midtrans_server_key();
$ck = midtrans_client_key();
$isProd = (bool) config('midtrans.is_production');

function mask_key(string $k): string
{
    if ($k === '') {
        return '(kosong)';
    }
    $len = strlen($k);
    $show = min(14, max(0, $len - 4));
    return substr($k, 0, $show) . str_repeat('•', max(0, $len - $show - 4)) . substr($k, -4) . "  ({$len} karakter)";
}

$checks = [];
$checks[] = ['Ekstensi cURL', function_exists('curl_init') ? 'ok' : 'warn', function_exists('curl_init') ? 'Aktif' : 'Mati (toko memakai cara cadangan, tidak masalah selama openssl aktif)'];
$checks[] = ['Ekstensi openssl', extension_loaded('openssl') ? 'ok' : 'bad', extension_loaded('openssl') ? 'Aktif' : 'Mati - aktifkan extension=openssl di php.ini lalu restart Apache'];
$checks[] = ['Mode di config.php', 'ok', $isProd ? 'Production (is_production = true)' : 'Sandbox (is_production = false)'];
$checks[] = ['Server Key', $sk === '' || str_contains($sk, 'ISI_') ? 'bad' : 'ok', mask_key($sk)];
$checks[] = ['Client Key', $ck === '' || str_contains($ck, 'ISI_') ? 'bad' : 'ok', mask_key($ck)];
if ($rawSk !== $sk || $rawCk !== $ck) {
    $checks[] = ['Spasi / karakter tersembunyi', 'warn', 'Ditemukan dan sudah dibersihkan otomatis. Sebaiknya rapikan juga di config.php.'];
}
if (stripos($sk, 'client') !== false) {
    $checks[] = ['Posisi kunci', 'bad', 'server_key berisi Client Key. Tukar isi server_key dan client_key.'];
} elseif ($sk !== '' && $sk === $ck) {
    $checks[] = ['Posisi kunci', 'bad', 'server_key dan client_key isinya sama.'];
}

$sandbox = $production = null;
$snapResult = null;
if ($sk !== '' && !str_contains($sk, 'ISI_')) {
    $sandbox = midtrans_probe_key(false);
    $production = midtrans_probe_key(true);
    $label = fn($r) => $r === 'ok' ? 'DITERIMA' : ($r === 'rejected' ? 'DITOLAK (401 Unauthorized)' : $r);
    $checks[] = ['Server Key di Sandbox', $sandbox === 'ok' ? 'ok' : 'bad', $label($sandbox)];
    $checks[] = ['Server Key di Production', $production === 'ok' ? 'ok' : 'warn', $label($production)];

    // Uji buat token Snap (jendela pembayaran) di mode yang dipakai config. Tidak menagih apa pun.
    [$code, $body] = midtrans_request('POST', midtrans_snap_base() . '/snap/v1/transactions', [
        'transaction_details' => ['order_id' => 'CEK-' . date('YmdHis') . '-' . bin2hex(random_bytes(2)), 'gross_amount' => 10000],
        'expiry' => ['unit' => 'minutes', 'duration' => 1],
    ]);
    $ok = $code === 201 && !empty($body['token']);
    $snapResult = $ok ? 'Berhasil - jendela pembayaran Midtrans bisa dibuat.' : 'Gagal (HTTP ' . $code . '): ' . implode(' ', (array) ($body['error_messages'] ?? $body['error'] ?? ['tidak ada pesan']));
    $checks[] = ['Uji jendela pembayaran (Snap)', $ok ? 'ok' : 'bad', $snapResult];
}

// Kesimpulan
if ($sk === '' || str_contains($sk, 'ISI_')) {
    $verdict = 'Server Key belum diisi di config/config.php.';
} elseif ($sandbox === 'ok' && !$isProd) {
    $verdict = 'Server Key Sandbox kamu BENAR. Kalau uji Snap juga berhasil, Place Order seharusnya sudah jalan.';
} elseif ($production === 'ok' && $isProd) {
    $verdict = 'Server Key Production kamu BENAR.';
} elseif ($production === 'ok' && !$isProd) {
    $verdict = 'Kunci ini adalah kunci PRODUCTION, padahal config memakai Sandbox. Untuk belajar: di dashboard Midtrans ganti ke Sandbox (pojok kiri atas), buka Settings > Access Keys, lalu copy Server Key & Client Key dari sana.';
} elseif ($sandbox === 'ok' && $isProd) {
    $verdict = 'Kunci ini adalah kunci SANDBOX. Ubah is_production menjadi false di config/config.php.';
} elseif ($sandbox === 'rejected' && $production === 'rejected') {
    $verdict = 'Server Key tidak dikenali Midtrans sama sekali. Penyebab umum: (1) file config.php yang diedit bukan yang ada di C:\\xampp\\htdocs\\lzfj-shoes-store\\config, atau belum disimpan (Ctrl+S); (2) kunci lama yang sudah di-revoke; (3) kunci diketik ulang, bukan di-copy. Bandingkan 14 huruf awal & 4 huruf akhir di atas dengan kunci di dashboard Midtrans.';
} else {
    $verdict = 'Tidak bisa menghubungi Midtrans. Cek koneksi internet dan ekstensi openssl.';
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cek Koneksi Midtrans - LZFJ Shoes Store</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  body{font-family:Poppins,Arial,sans-serif;background:#fafafa;color:#111;margin:0;padding:40px 16px}
  .box{max-width:760px;margin:0 auto;background:#fff;border:1px solid #e5e5e5;border-radius:16px;padding:28px}
  h1{font-size:22px;margin:0 0 4px} p.sub{color:#666;margin:0 0 20px;font-size:14px}
  table{width:100%;border-collapse:collapse;font-size:14px}
  td{padding:10px 8px;border-top:1px solid #eee;vertical-align:top}
  td:first-child{width:38%;font-weight:500}
  .tag{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:8px}
  .ok{background:#16a34a}.warn{background:#f59e0b}.bad{background:#dc2626}
  code{font-family:Consolas,monospace;word-break:break-all}
  .verdict{margin-top:20px;padding:16px;border-radius:12px;background:#111;color:#fff;font-size:14px;line-height:1.6}
  a{color:#111}
</style>
</head>
<body>
<div class="box">
  <h1>Cek Koneksi Midtrans</h1>
  <p class="sub">Membaca <code><?= e(APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php') ?></code></p>
  <table>
    <?php foreach ($checks as [$name, $state, $info]): ?>
      <tr><td><span class="tag <?= e($state) ?>"></span><?= e($name) ?></td><td><code><?= e($info) ?></code></td></tr>
    <?php endforeach; ?>
  </table>
  <div class="verdict"><b>Kesimpulan:</b> <?= e($verdict) ?></div>
  <p class="sub" style="margin-top:16px">Setelah mengubah config.php, simpan (Ctrl+S) lalu <a href="cek-midtrans.php">muat ulang halaman ini</a>. Kembali ke <a href="checkout.php">checkout</a>.</p>
</div>
</body>
</html>
