<?php
/**
 * Integrasi Midtrans (Snap API + Status API) memakai cURL (atau stream PHP jika cURL mati),
 * tanpa Composer. Dokumentasi: https://docs.midtrans.com
 */

/**
 * Bersihkan kunci hasil copy-paste: buang spasi, enter, tab, spasi tak terlihat
 * (zero-width / non-breaking space) dan tanda kutip yang ikut tertempel.
 */
function midtrans_clean_key(string $key): string
{
    $key = preg_replace('/[\s\x{00A0}\x{200B}-\x{200D}\x{2060}\x{FEFF}]+/u', '', $key) ?? $key;
    return trim($key, "'\"`");
}

function midtrans_server_key(): string
{
    return midtrans_clean_key((string) config('midtrans.server_key'));
}

function midtrans_client_key(): string
{
    return midtrans_clean_key((string) config('midtrans.client_key'));
}

function midtrans_is_configured(): bool
{
    $sk = midtrans_server_key();
    $ck = midtrans_client_key();
    return $sk !== '' && $ck !== '' && !str_contains($sk, 'ISI_') && !str_contains($ck, 'ISI_');
}

function midtrans_snap_base(): string
{
    return config('midtrans.is_production') ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
}

function midtrans_api_base(): string
{
    return config('midtrans.is_production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
}

function midtrans_snap_js(): string
{
    return midtrans_snap_base() . '/snap/snap.js';
}

/** Kirim request ke Midtrans. Mengembalikan [httpCode, arrayBody]. */
function midtrans_request(string $method, string $url, ?array $payload = null, ?string $serverKey = null): array
{
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(($serverKey ?? midtrans_server_key()) . ':'),
    ];
    [$code, $raw, $err] = http_send($method, $url, $headers, $payload !== null ? json_encode($payload) : null, 30);

    if ($raw === false) {
        return [0, ['error_messages' => ['Could not reach Midtrans: ' . $err]]];
    }
    return [$code, json_decode((string) $raw, true) ?: []];
}

/**
 * Buat transaksi Snap. Hanya channel yang dipilih pembeli yang diaktifkan,
 * jadi popup Midtrans langsung membuka bank / e-wallet tersebut.
 */
function midtrans_create_snap(array $order, array $items): array
{
    $itemDetails = [];
    foreach ($items as $it) {
        $itemDetails[] = [
            'id'       => 'P' . $it['product_id'],
            'price'    => (int) $it['price'],
            'quantity' => (int) $it['qty'],
            'name'     => mb_substr($it['product_name'] . ' (' . $it['size'] . ')', 0, 50),
            'brand'    => 'LZFJ',
            'category' => 'Shoes',
        ];
    }
    if ((int) $order['shipping_cost'] > 0) {
        $itemDetails[] = ['id' => 'SHIPPING', 'price' => (int) $order['shipping_cost'], 'quantity' => 1, 'name' => 'Shipping ' . ucfirst($order['delivery_method'])];
    }
    if ((int) $order['discount'] > 0) {
        $itemDetails[] = ['id' => 'DISCOUNT', 'price' => -1 * (int) $order['discount'], 'quantity' => 1, 'name' => 'Discount ' . ($order['promo_code'] ?? '')];
    }

    $nameParts = preg_split('/\s+/', trim($order['full_name']), 2);
    $address = [
        'first_name'   => $nameParts[0] ?? '',
        'last_name'    => $nameParts[1] ?? '',
        'email'        => $order['email'],
        'phone'        => $order['phone'],
        'address'      => $order['address'],
        'city'         => $order['city'],
        'postal_code'  => $order['postal_code'],
        'country_code' => 'IDN',
    ];

    $payload = [
        'transaction_details' => [
            'order_id'     => $order['order_code'],
            'gross_amount' => (int) $order['total'],
        ],
        'item_details'     => $itemDetails,
        'customer_details' => [
            'first_name'       => $address['first_name'],
            'last_name'        => $address['last_name'],
            'email'            => $order['email'],
            'phone'            => $order['phone'],
            'billing_address'  => $address,
            'shipping_address' => $address,
        ],
        'enabled_payments' => [$order['payment_channel']],
        'callbacks' => [
            'finish' => url('order-confirmation.php?order=' . urlencode($order['order_code'])),
        ],
        'expiry' => [
            'unit'     => 'hours',
            'duration' => 24,
        ],
    ];
    if ($order['payment_channel'] === 'credit_card') {
        $payload['credit_card'] = ['secure' => true];
    }

    [$code, $body] = midtrans_request('POST', midtrans_snap_base() . '/snap/v1/transactions', $payload);
    if ($code !== 201 || empty($body['token'])) {
        $msg = implode(' ', (array) ($body['error_messages'] ?? $body['error'] ?? ['Unknown error from Midtrans (HTTP ' . $code . ').']));
        if ($code === 401) {
            $msg = midtrans_explain_401();
        }
        throw new RuntimeException($msg);
    }
    return $body; // ['token' => ..., 'redirect_url' => ...]
}

/** Ambil status transaksi langsung dari Midtrans. */
function midtrans_get_status(string $orderCode): ?array
{
    if (!midtrans_is_configured()) {
        return null;
    }
    [$code, $body] = midtrans_request('GET', midtrans_api_base() . '/v2/' . rawurlencode($orderCode) . '/status');
    if ($code !== 200 || empty($body['transaction_status'])) {
        return null;
    }
    return $body;
}

/** Verifikasi signature_key dari notifikasi Midtrans. */
function midtrans_verify_signature(array $n): bool
{
    if (empty($n['signature_key'])) {
        return false;
    }
    $expected = hash('sha512', ($n['order_id'] ?? '') . ($n['status_code'] ?? '') . ($n['gross_amount'] ?? '') . midtrans_server_key());
    return hash_equals($expected, (string) $n['signature_key']);
}

/** Ubah transaction_status Midtrans menjadi status order toko. */
function midtrans_map_status(array $n): string
{
    $ts = $n['transaction_status'] ?? '';
    $fraud = $n['fraud_status'] ?? 'accept';
    return match ($ts) {
        'capture'    => $fraud === 'accept' ? 'paid' : 'pending',
        'settlement' => 'paid',
        'pending'    => 'pending',
        'deny', 'failure' => 'failed',
        'expire'     => 'expired',
        'cancel'     => 'cancelled',
        'refund', 'partial_refund' => 'refunded',
        default      => 'pending',
    };
}

/** Ambil nomor VA / kode bayar dari respons Midtrans (jika ada). */
function midtrans_extract_va(array $n): ?string
{
    if (!empty($n['va_numbers'][0]['va_number'])) {
        return strtoupper($n['va_numbers'][0]['bank']) . ' ' . $n['va_numbers'][0]['va_number'];
    }
    if (!empty($n['permata_va_number'])) {
        return 'PERMATA ' . $n['permata_va_number'];
    }
    if (!empty($n['bill_key'])) {
        return 'Mandiri Biller ' . ($n['biller_code'] ?? '') . ' / Bill Key ' . $n['bill_key'];
    }
    return null;
}

/** Simpan status Midtrans ke tabel orders. */
function midtrans_apply_status(array $order, array $n): array
{
    $status = midtrans_map_status($n);
    // Jangan turunkan status yang sudah final (mis. paid -> pending)
    if ($order['status'] === 'paid' && !in_array($status, ['refunded'], true)) {
        $status = 'paid';
    }
    $paidAt = $status === 'paid' ? ($order['paid_at'] ?: date('Y-m-d H:i:s')) : $order['paid_at'];
    $va = midtrans_extract_va($n) ?? $order['va_number'];

    $stmt = db()->prepare('UPDATE orders SET status = ?, paid_at = ?, va_number = ?, midtrans_transaction_id = ?, midtrans_payment_type = ? WHERE id = ?');
    $stmt->execute([
        $status,
        $paidAt,
        $va,
        $n['transaction_id'] ?? $order['midtrans_transaction_id'],
        $n['payment_type'] ?? $order['midtrans_payment_type'],
        $order['id'],
    ]);
    $order['status'] = $status;
    $order['paid_at'] = $paidAt;
    $order['va_number'] = $va;

    // Kembalikan stok sekali saja jika pembayaran gagal / kedaluwarsa / dibatalkan
    if (in_array($status, ['failed', 'expired', 'cancelled'], true) && empty($order['stock_restored'])) {
        $flag = db()->prepare('UPDATE orders SET stock_restored = 1 WHERE id = ? AND stock_restored = 0');
        $flag->execute([$order['id']]);
        if ($flag->rowCount() === 1) {
            $items = db()->prepare('SELECT product_id, qty FROM order_items WHERE order_id = ? AND product_id IS NOT NULL');
            $items->execute([$order['id']]);
            $restock = db()->prepare('UPDATE products SET stock = stock + ?, sold = GREATEST(CAST(sold AS SIGNED) - ?, 0) WHERE id = ?');
            foreach ($items->fetchAll() as $it) {
                $restock->execute([$it['qty'], $it['qty'], $it['product_id']]);
            }
        }
        $order['stock_restored'] = 1;
    }
    return $order;
}


/**
 * Cek apakah Server Key diterima di Sandbox / Production.
 * Memakai Status API dengan order_id palsu: kunci benar => "404 transaksi tidak ada",
 * kunci salah => 401. Tidak membuat transaksi apa pun.
 * @return string 'ok' | 'rejected' | 'error: ...'
 */
function midtrans_probe_key(bool $production, ?string $serverKey = null): string
{
    $base = $production ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    [$code, $body] = midtrans_request('GET', $base . '/v2/lzfj-key-check-' . bin2hex(random_bytes(4)) . '/status', null, $serverKey);
    if ($code === 0) {
        return 'error: ' . implode(' ', (array) ($body['error_messages'] ?? ['tidak bisa terhubung']));
    }
    $status = (string) ($body['status_code'] ?? $code);
    if ($code === 401 || $status === '401') {
        return 'rejected';
    }
    return 'ok';
}

/** Pesan error 401 yang jelas (Bahasa Indonesia) + saran perbaikan. */
function midtrans_explain_401(): string
{
    $sk = midtrans_server_key();
    $ck = midtrans_client_key();
    $isProd = (bool) config('midtrans.is_production');
    $mode = $isProd ? 'Production' : 'Sandbox';

    if (stripos($sk, 'client') !== false) {
        return 'Server Key ditolak: yang terisi di server_key adalah Client Key. Tukar isi server_key dan client_key di config/config.php.';
    }
    if ($sk === $ck) {
        return 'Server Key dan Client Key di config/config.php isinya sama. Ambil keduanya lagi dari Midtrans > Settings > Access Keys.';
    }
    $other = midtrans_probe_key(!$isProd);
    if ($other === 'ok') {
        return $isProd
            ? 'Server Key ini adalah kunci SANDBOX. Ubah is_production menjadi false di config/config.php.'
            : 'Server Key ini adalah kunci PRODUCTION (uang asli). Untuk belajar, ganti dashboard Midtrans ke mode Sandbox lalu ambil kunci Sandbox-nya. Atau ubah is_production menjadi true.';
    }
    return 'Server Key ditolak Midtrans (mode ' . $mode . '). Kunci ini tidak dikenali di Sandbox maupun Production: '
        . 'kemungkinan salah ketik, sudah di-revoke, atau bukan kunci terbaru. Buka halaman cek-midtrans.php untuk detail.';
}
