# Binance Spot API Client (PHP)

Ini adalah klien PHP dasar untuk berinteraksi dengan Binance Spot REST API. Class ini menyediakan fungsionalitas untuk mengambil data pasar, mengajukan dan mengelola order, serta memeriksa status akun dan saldo Anda.

## Daftar Isi
- [Fitur Utama](#fitur-utama)
- [Instalasi](#instalasi)
- [Konfigurasi API Key](#konfigurasi-api-key)
- [Cara Penggunaan](#cara-penggunaan)
  - [Inisialisasi Class](#inisialisasi-class)
  - [Menguji Konektivitas (Ping)](#menguji-konektivitas-ping)
  - [Mendapatkan Waktu Server](#mendapatkan-waktu-server)
  - [Mengambil Informasi Exchange (Aturan Trading)](#mengambil-informasi-exchange-aturan-trading)
  - [Mengambil Harga Terkini (Ticker Price)](#mengambil-harga-terkini-ticker-price)
  - [Mengambil Data Klines/Candlestick](#mengambil-data-klinescandlestick)
  - [Mengambil Kedalaman Pasar (Order Book)](#mengambil-kedalaman-pasar-order-book)
  - [Mengajukan Order Baru (Limit, Market, Stop Loss, Take Profit)](#mengajukan-order-baru-limit-market-stop-loss-take-profit)
  - [Membatalkan Order](#membatalkan-order)
  - [Mengambil Status Order Tertentu](#mengambil-status-order-tertentu)
  - [Mengambil Open Orders](#mengambil-open-orders)
  - [Mengambil Semua Riwayat Order](#mengambil-semua-riwayat-order)
  - [Mengambil Riwayat Perdagangan Pribadi](#mengambil-riwayat-perdagangan-pribadi)
  - [Mengambil Informasi Akun dan Saldo Spot](#mengambil-informasi-akun-dan-saldo-spot)
  - [Mengelola User Data Stream (WebSocket)](#mengelola-user-data-stream-websocket)
- [Catatan Penting](#catatan-penting)

## Fitur Utama
Class `BinanceSpotAPI` ini mencakup fungsionalitas untuk endpoint Binance Spot REST API berikut:

1.  `GET /api/v3/ping` - Menguji konektivitas API.
2.  `GET /api/v3/time` - Mendapatkan waktu server Binance.
3.  `GET /api/v3/exchangeInfo` - Mendapatkan informasi dan aturan exchange.
4.  `GET /api/v3/ticker/price` - Mendapatkan harga terkini dari simbol.
5.  `GET /api/v3/klines` - Mengambil data Klines/Candlestick.
6.  `GET /api/v3/depth` - Mengambil kedalaman buku pesanan (Order Book).
7.  `POST /api/v3/order` - Mengajukan berbagai tipe order.
8.  `DELETE /api/v3/order` - Membatalkan order.
9.  `GET /api/v3/order` - Mengambil status order tertentu.
10. `GET /api/v3/openOrders` - Mengambil daftar open order yang aktif.
11. `GET /api/v3/allOrders` - Mengambil semua riwayat order.
12. `GET /api/v3/myTrades` - Mengambil riwayat perdagangan pribadi.
13. `GET /api/v3/account` - Mengambil informasi akun dan saldo spot.
14. `POST /api/v3/userDataStream` - Membuat listenKey untuk User Data Stream (WebSocket).
15. `PUT /api/v3/userDataStream` - Memperpanjang listenKey User Data Stream.
16. `DELETE /api/v3/userDataStream` - Menutup listenKey User Data Stream.

## Instalasi
1.  Pastikan Anda memiliki PHP (versi 7.0 atau lebih tinggi direkomendasikan) dengan ekstensi `cURL` diaktifkan.
2.  Unduh file `BinanceSpotAPI.php` dan simpan di direktori proyek Anda.

## Konfigurasi API Key
Untuk menggunakan fungsi yang memerlukan otentikasi (Signed Endpoint), Anda perlu mendapatkan API Key dan Secret Key dari akun Binance Anda:

1.  Masuk ke akun Binance Anda.
2.  Navigasi ke `API Management`.
3.  Buat API Key baru.
4.  Pastikan untuk hanya memberikan izin yang diperlukan (misalnya, `Enable Reading`, `Enable Spot & Margin Trading`). **JANGAN PERNAH** mengaktifkan `Enable Withdrawals` kecuali Anda sepenuhnya memahami risikonya dan memiliki langkah keamanan yang sangat kuat.
5.  **Simpan API Key dan Secret Key Anda dengan sangat aman.** Secret Key hanya akan ditampilkan satu kali.

**Penting:** Untuk produksi, **jangan pernah hardcode** API Key dan Secret Key langsung di dalam kode sumber Anda. Gunakan *environment variables* atau file konfigurasi yang aman (`.env` file yang tidak di-commit ke Git).

## Cara Penggunaan

### Inisialisasi Class
Sertakan file `BinanceSpotAPI.php` dan buat instance class dengan API Key dan Secret Key Anda.

```php
<?php
require_once 'BinanceSpotAPI.php';

// GANTI DENGAN API KEY DAN SECRET KEY ASLI ANDA
$api_key = 'YOUR_BINANCE_API_KEY';
$secret_key = 'YOUR_BINANCE_SECRET_KEY';

$binanceApi = new BinanceSpotAPI($api_key, $secret_key);
?>

<?php
// ... inisialisasi class ...

// Menguji konektivitas
$ping = $binanceApi->ping();
if ($ping !== false) { // Ping akan mengembalikan array kosong [] jika sukses
    echo "Koneksi ke Binance API berhasil!\n";
} else {
    echo "Gagal koneksi ke Binance API.\n";
}
?>
