<?php
/**
 * ============================================================
 *  LZFJ Shoes Store - KONFIGURASI
 *  Ubah nilai di bawah ini sesuai komputer / hosting Anda.
 *  Lihat README.md untuk panduan lengkap.
 *
 *  PENTING (GitHub): jangan tulis kunci rahasia di file ini.
 *  Salin config/config.example.php -> config/config.local.php lalu isi kunci
 *  Google & Midtrans di sana. File config.local.php diabaikan oleh Git dan
 *  nilainya otomatis menimpa nilai di file ini.
 * ============================================================
 */
return [
    // Alamat website TANPA garis miring di akhir.
    // Biarkan kosong ('') agar dideteksi otomatis, contoh hasilnya di XAMPP:
    //   http://localhost/lzfj-shoes-store
    // Isi manual jika sudah online, contoh: 'https://tokoanda.com'
    'base_url' => '',

    // ---------- Database MySQL (XAMPP default: user root, password kosong) ----------
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'lzfj_shoes_store',
        'user'     => 'root',
        'password' => '',
    ],

    // ---------- Login dengan Google (Google Cloud Console > Credentials) ----------
    'google' => [
        'client_id'     => '35694891877-d6ncbhh463fl7or3vj9j5og3frr72unu.apps.googleusercontent.com',
        'client_secret' => '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T',
        // Harus SAMA PERSIS dengan "Authorized redirect URI" di Google Console.
        // Kosongkan agar otomatis: {base_url}/google-callback.php
        'redirect_uri'  => '',
    ],

    // ---------- Midtrans (Dashboard Midtrans > Settings > Access Keys) ----------
    'midtrans' => [
        'is_production' => false,                 // false = Sandbox (uji coba), true = Production (uang asli)
        'server_key'    => 'SB-Mid-server-bVwQX~Nd6GoGeVPfmjTxJjpJ',
        'client_key'    => 'SB-Mid-client-M6O-oik-1L.te8UK9URQYTKE',
    ],

    // ---------- Ongkos kirim (Rupiah) ----------
    'shipping' => [
        'regular' => 25000,   // Regular (3-5 days)
        'express' => 50000,   // Express (1-2 days)
    ],
    // Gratis ongkir jika subtotal >= nilai ini. 0 = nonaktif.
    'free_shipping_min' => 0,

    'store' => [
        'name'    => 'LZFJ Shoes Store',
        'address' => '[STORE ADDRESS]',
        'email'   => '[STORE EMAIL]',
        'support' => '[SUPPORT CONTACT]',
    ],
];