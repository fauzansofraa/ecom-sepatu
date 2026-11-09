<?php
/**
 * CONTOH konfigurasi lokal. Salin file ini menjadi config/config.local.php
 * lalu isi nilai yang sesuai. config.local.php TIDAK ikut ke GitHub (.gitignore).
 * Hanya tulis bagian yang ingin Anda timpa dari config/config.php.
 */
return [
    // 'base_url' => 'https://tokoanda.com',

    // 'db' => [
    //     'host'     => '127.0.0.1',
    //     'port'     => 3306,
    //     'name'     => 'lzfj_shoes_store',
    //     'user'     => 'root',
    //     'password' => '',
    // ],

    'google' => [
        'client_id'     => 'ISI_GOOGLE_CLIENT_ID',
        'client_secret' => 'ISI_GOOGLE_CLIENT_SECRET',
    ],

    'midtrans' => [
        'is_production' => false,   // false = Sandbox, true = Production
        'server_key'    => 'ISI_MIDTRANS_SERVER_KEY',
        'client_key'    => 'ISI_MIDTRANS_CLIENT_KEY',
    ],
];
