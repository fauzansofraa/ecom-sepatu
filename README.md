# LZFJ Shoes Store — Website E-Commerce (PHP + MySQL + Midtrans + Login Google)

Website toko sepatu yang tampilannya dibuat mengikuti desain Figma **LZFJ Shoes Store**:
Login, Create Account, Home (Dashboard), Shop, Men, Women, Kids (dengan panel detail produk di kanan),
Cart, Checkout, dan Order Confirmation.

Fitur:

- Login & daftar dengan email/password **dan Login With Google**
- Produk, user, pesanan & kode promo disimpan di **database MySQL**
- Filter kategori / ukuran / warna / harga, pencarian, urutan, dan halaman (pagination)
- Keranjang belanja, kode promo (`LZFJ10` = diskon 10%), pilihan ongkir Regular / Express
- Pembayaran **Midtrans**:
  - **Bank Transfer** → muncul pilihan bank: **BCA, BNI, BRI, Mandiri, BSI, Permata, CIMB Niaga** (Virtual Account)
  - **E-Wallet** → GoPay, ShopeePay, QRIS (DANA, OVO, LinkAja, dll)
  - **Credit / Debit Card** → Visa, Mastercard, JCB
- Status pembayaran otomatis berubah (Waiting for payment → Paid) lewat notifikasi Midtrans
- Halaman **My Account** untuk melacak pesanan (Track Order)

Tidak perlu Composer / npm. Cukup PHP 8.1+ dan MySQL (XAMPP / Laragon sudah cukup).

---

## Daftar isi

1. [Menjalankan di komputer (XAMPP)](#1-menjalankan-di-komputer-xampp)
2. [Membuat & menyambungkan database](#2-membuat--menyambungkan-database)
3. [Tutorial menyambungkan Midtrans](#3-tutorial-menyambungkan-midtrans)
4. [Tutorial Login dengan Google](#4-tutorial-login-dengan-google)
5. [Mencoba pembayaran (mode Sandbox)](#5-mencoba-pembayaran-mode-sandbox)
6. [Upload ke hosting & mulai terima uang asli](#6-upload-ke-hosting--mulai-terima-uang-asli)
7. [Struktur folder](#7-struktur-folder)
8. [Masalah yang sering terjadi](#8-masalah-yang-sering-terjadi)

---

## 1. Menjalankan di komputer (XAMPP)

1. Install **XAMPP** (PHP 8.1 atau lebih baru): <https://www.apachefriends.org>
2. Ekstrak file ZIP ini, lalu salin folder **`lzfj-shoes-store`** ke:
   - Windows: `C:\xampp\htdocs\lzfj-shoes-store`
   - macOS: `/Applications/XAMPP/htdocs/lzfj-shoes-store`
3. Buka **XAMPP Control Panel** → klik **Start** pada **Apache** dan **MySQL**.
4. Pastikan ekstensi `curl`, `pdo_mysql`, `mbstring`, `openssl` aktif (di XAMPP sudah aktif secara bawaan).
   Jika belum: XAMPP → Apache **Config** → `php.ini` → hapus tanda `;` di depan `extension=curl`, lalu restart Apache.

## 2. Membuat & menyambungkan database

1. Buka <http://localhost/phpmyadmin>
2. Klik tab **Import** → **Choose File** → pilih `lzfj-shoes-store/database/lzfj_shoes_store.sql` → klik **Go / Import**.
   File ini otomatis membuat database **`lzfj_shoes_store`** beserta tabel:

   | Tabel | Isi |
   |---|---|
   | `users` | akun pembeli (email/password atau Google) |
   | `products` | 13 produk sepatu sesuai desain (Men, Women, Kids) |
   | `orders` | pesanan + status pembayaran Midtrans + nomor VA |
   | `order_items` | detail sepatu di setiap pesanan |
   | `promo_codes` | kode promo (`LZFJ10`, `HEMAT50K`) |
   | `subscribers` | email dari form "Get 10% off your first order" |

3. Salin `config/config.example.php` menjadi `config/config.local.php` (file ini rahasia & tidak ikut GitHub). Pengaturan bawaan di `config/config.php` sudah cocok untuk XAMPP; jika perlu ubah database, tulis bagian ini di `config.local.php`:

   ```php
   'db' => [
       'host'     => '127.0.0.1',
       'port'     => 3306,
       'name'     => 'lzfj_shoes_store',
       'user'     => 'root',
       'password' => '',
   ],
   ```

4. Buka <http://localhost/lzfj-shoes-store> — website sudah tampil. 🎉

> Menambah / mengubah produk: buka phpMyAdmin → tabel `products` → **Insert** / **Edit**.
> Simpan foto produk (PNG/WEBP transparan) di `assets/img/products/` lalu isi nama filenya di kolom `image`.
> Kolom `sizes` diisi seperti `39,40,41,42,43`, kolom `colors` seperti `Black:#111111,White:#FFFFFF,Grey:#9AA0AC`,
> kolom `gender` = `men` / `women` / `kids` / `unisex`.

## 3. Tutorial menyambungkan Midtrans

### Langkah 1 — Daftar akun Midtrans
1. Daftar di <https://dashboard.midtrans.com/register> lalu verifikasi email.
2. Setelah login, pastikan pojok kiri atas memakai **Environment: Sandbox** (untuk uji coba, tanpa uang asli).

### Langkah 2 — Ambil Server Key & Client Key
1. Menu **Settings → Access Keys**.
2. Salin **Server Key** (diawali `SB-Mid-server-`) dan **Client Key** (diawali `SB-Mid-client-`).
3. Tempel di `config/config.local.php`:

   ```php
   'midtrans' => [
       'is_production' => false,   // false = Sandbox
       'server_key'    => 'SB-Mid-server-xxxxxxxxxxxxxxxx',
       'client_key'    => 'SB-Mid-client-xxxxxxxxxxxxxxxx',
   ],
   ```

   ⚠️ **Server Key itu rahasia.** Jangan dibagikan dan jangan di-upload ke GitHub publik.

### Langkah 3 — Aktifkan metode pembayaran (termasuk BSI)
1. Menu **Settings → Payment** (atau **Settings → Snap Preferences → Payment Channels**, tergantung versi dashboard).
2. Pastikan aktif: **BCA VA, BNI VA, BRI VA, Mandiri Bill, BSI VA, Permata VA, CIMB VA, GoPay, ShopeePay, QRIS, Credit Card**.
   Di Sandbox biasanya semua sudah aktif. Di Production, beberapa channel (misalnya BSI, CIMB) perlu diajukan dulu ke Midtrans.
   Jika sebuah bank belum aktif, popup Midtrans akan menampilkan pesan "No payment channels available".

### Langkah 4 — Atur URL notifikasi & redirect
Menu **Settings → Payment → Notification URL** (atau **Settings → Configuration**):

| Kolom | Isi |
|---|---|
| **Payment Notification URL** | `https://DOMAIN-ANDA/midtrans-notification.php` |
| **Finish Redirect URL** | `https://DOMAIN-ANDA/order-confirmation.php` |
| **Unfinish / Error Redirect URL** | `https://DOMAIN-ANDA/account.php` |

> **Di localhost (XAMPP)**, Midtrans tidak bisa mengirim notifikasi ke komputer Anda.
> Tidak masalah: halaman **Order Confirmation** otomatis mengecek status langsung ke Midtrans setiap kali dibuka
> (tombol **"Already paid? Refresh status"**).
> Jika ingin mencoba notifikasi otomatis dari localhost, pakai **ngrok**:
> 1. Install ngrok (<https://ngrok.com>) lalu jalankan `ngrok http 80`
> 2. Salin alamat `https://xxxx.ngrok-free.app`, lalu isi Notification URL:
>    `https://xxxx.ngrok-free.app/lzfj-shoes-store/midtrans-notification.php`

### Langkah 5 — Cek koneksi
Buka `http://localhost/lzfj-shoes-store/cek-midtrans.php`. Kalau semua titik hijau, pembayaran siap dicoba. Kalau merah, baca bagian **Kesimpulan** di halaman itu. Halaman ini hanya bisa dibuka dari komputer sendiri.

### Cara kerja pembayaran di website ini
1. Pembeli mengisi Shipping Information → memilih Delivery Method → memilih **Bank Transfer** → muncul daftar
   bank (**BCA, BNI, BRI, Mandiri, BSI, Permata, CIMB Niaga**) → pilih salah satu → klik **Place Order**.
2. `place-order.php` menyimpan pesanan ke database, lalu meminta **Snap Token** ke Midtrans hanya untuk bank yang dipilih
   (`enabled_payments`), sehingga popup Midtrans langsung menampilkan **nomor Virtual Account** bank tersebut.
3. Setelah membayar, Midtrans mengirim notifikasi ke `midtrans-notification.php`. File ini memeriksa
   **signature_key** (keamanan) lalu mengubah status pesanan menjadi **Paid**.
4. Pembeli diarahkan ke **Order Confirmation** dan bisa melacak pesanan di **My Account → My Orders**.

## 4. Tutorial Login dengan Google

1. Buka <https://console.cloud.google.com> → buat **project** baru (misal "LZFJ Shoes Store").
2. Menu **APIs & Services → OAuth consent screen** (atau **Google Auth Platform → Branding**):
   - User type: **External** → isi App name `LZFJ Shoes Store`, email support, email developer → **Save**.
   - Bagian **Audience / Test users**: tambahkan email Gmail Anda selama aplikasi masih mode "Testing".
3. Menu **APIs & Services → Credentials → Create Credentials → OAuth client ID**:
   - Application type: **Web application**
   - **Authorized JavaScript origins**: `http://localhost`
   - **Authorized redirect URIs**: `http://localhost/lzfj-shoes-store/google-callback.php`
     (nanti saat online tambahkan juga `https://DOMAIN-ANDA/google-callback.php`)
   - Klik **Create**, lalu salin **Client ID** dan **Client Secret**.
4. Tempel di `config/config.local.php`:

   ```php
   'google' => [
       'client_id'     => '1234567890-xxxx.apps.googleusercontent.com',
       'client_secret' => 'GOCSPX-xxxxxxxx',
       'redirect_uri'  => '', // kosong = otomatis {alamat website}/google-callback.php
   ],
   ```

5. Buka halaman Login → klik **Login With Google**. Akun Google otomatis tersimpan di tabel `users`.
   Jika email Google sama dengan akun yang sudah daftar manual, akunnya otomatis digabung.

> Error **redirect_uri_mismatch** artinya alamat di Google Console tidak sama persis dengan alamat website
> (perhatikan `http` vs `https`, `localhost` vs `127.0.0.1`, dan nama folder).

## 5. Mencoba pembayaran (mode Sandbox)

1. Daftar akun di website → pilih sepatu → **Add to Cart** → ikon tas → **Proceed to Checkout**.
2. Isi data, pilih **Bank Transfer → BSI** (atau bank lain) → **Place Order**. Popup Midtrans menampilkan nomor VA.
3. Buka **Midtrans Payment Simulator**: <https://simulator.sandbox.midtrans.com>
   - Pilih bank yang sama (misal **BSI Virtual Account** / **BNI** / **BRI** / **Mandiri Bill Payment**)
   - Masukkan nomor VA dari popup → **Inquire** → **Pay**.
4. Kembali ke website → buka pesanan (My Account → Details) → status menjadi **Paid** ✅
5. Kartu kredit uji coba: `4811 1111 1111 1114`, CVV `123`, exp `12/30`, OTP `112233`.
   Daftar lengkap: <https://docs.midtrans.com/docs/testing-payment-on-sandbox>

## 6. Upload ke hosting & mulai terima uang asli

1. Upload semua isi folder `lzfj-shoes-store` ke `public_html` hosting (lewat File Manager / FTP).
2. Buat database MySQL di cPanel → import `database/lzfj_shoes_store.sql`
   (jika hosting tidak mengizinkan `CREATE DATABASE`, hapus 3 baris `CREATE DATABASE ...` dan `USE ...` di atas file SQL, lalu import ke database yang sudah dibuat).
3. Ubah `config/config.php`: data database hosting, dan `base_url` (misal `https://tokoanda.com`).
4. Wajib **HTTPS** (SSL gratis dari hosting / Let's Encrypt).
5. Midtrans Production:
   - Lengkapi data bisnis di dashboard Midtrans dan tunggu akun disetujui.
   - Ganti Environment ke **Production**, ambil **Server Key & Client Key Production** (diawali `Mid-server-` / `Mid-client-`).
   - Di `config.php` ubah `'is_production' => true` dan isi key production.
   - Isi ulang Notification URL & Redirect URL di dashboard Production.
6. Google: tambahkan redirect URI `https://tokoanda.com/google-callback.php` dan **Publish App** di OAuth consent screen.

## 7. Struktur folder

```
lzfj-shoes-store/
├── index.php                 Home / Dashboard
├── shop.php                  Shop (semua produk) + panel detail
├── men.php / women.php / kids.php
├── login.php / register.php / logout.php / forgot-password.php
├── google-login.php          Mulai login Google
├── google-callback.php       Google mengembalikan user ke sini
├── cart.php / cart-action.php
├── checkout.php              Form checkout + pilihan bank / e-wallet
├── apply-promo.php           Cek kode promo (AJAX)
├── place-order.php           Simpan pesanan + minta Snap Token Midtrans
├── pay.php                   Tombol "Pay Now" untuk pesanan yang belum dibayar
├── order-confirmation.php    Halaman "Thank you for your order!"
├── midtrans-notification.php Webhook notifikasi pembayaran Midtrans
├── account.php               My Account + Track Order
├── subscribe.php             Form newsletter
├── config/config.php         ← PENGATURAN bawaan (tanpa kunci rahasia)
├── config/config.example.php ← Contoh; salin jadi config.local.php untuk kunci rahasia
├── includes/                 Fungsi bantuan, header, footer, Midtrans, Google
├── assets/css/style.css      Semua tampilan (mengikuti desain Figma)
├── assets/js/                app.js, shop.js, checkout.js
├── assets/img/               Foto produk, gambar login, logo
└── database/lzfj_shoes_store.sql
```

## 8. Masalah yang sering terjadi

| Masalah | Solusi |
|---|---|
| "Tidak bisa terhubung ke database" | MySQL di XAMPP belum Start, atau database belum di-import, atau user/password di `config.php` salah. |
| "Call to undefined function curl_init()" | Sudah diperbaiki di versi 2 (otomatis pakai cara lain). Tetap disarankan aktifkan cURL: XAMPP → Apache **Config** → `php.ini` → cari `;extension=curl` → hapus `;` → simpan → Stop lalu Start Apache. |
| "Unauthorized" / "Server Key ditolak" saat Place Order | Buka `http://localhost/lzfj-shoes-store/cek-midtrans.php`. Halaman itu mengetes Server Key ke Sandbox & Production dan memberi tahu penyebabnya (kunci Production, kunci tertukar, kunci lama/revoke, atau file config yang salah). |
| "Midtrans is not configured yet" | Server Key / Client Key di `config.php` belum diisi. |
| "Payment could not be started: Access denied / unauthorized" | Server Key salah, atau memakai key Sandbox dengan `is_production => true` (atau sebaliknya). |
| Popup Midtrans tidak muncul | Cek koneksi internet & Client Key. Pastikan `snap.js` tidak diblokir ad-blocker. |
| Bank tertentu (mis. BSI) tidak muncul di popup | Channel belum aktif di dashboard Midtrans (Settings → Payment). |
| Status tetap "Waiting for payment" setelah bayar | Di localhost klik "Already paid? Refresh status". Di hosting cek Notification URL di dashboard Midtrans. |
| Google: `redirect_uri_mismatch` | Samakan Authorized redirect URI di Google Console dengan alamat `google-callback.php` website Anda. |
| Google: "Access blocked / app not verified" | Tambahkan email Anda sebagai **Test user**, atau Publish App. |
| Ongkir tidak gratis padahal di atas Rp 1.000.000 | Atur `'free_shipping_min' => 1000000` di `config.php` (bawaan 0 agar sama dengan desain checkout). |

Selamat berjualan! 👟

---

## Catatan Revisi

- **Halaman awal = Login.** Membuka `http://localhost/lzfj-shoes-store/` (atau `http://lzfj-shoes-store.test` di Laragon) selalu menampilkan `login.php`, walau sesi lama masih tersimpan. Beranda toko sekarang ada di `home.php` (`index.php` hanya pintu masuk yang mengarahkan ke login).
- **Logo pembayaran** ada di `assets/img/payment/`. Lihat `assets/img/payment/PETUNJUK-LOGO.txt` untuk mengganti dengan logo resmi.
