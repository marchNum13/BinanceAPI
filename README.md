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
```

### Menguji Konektivitas Ping
`ping()` - Menguji konektivitas ke REST API Binance.

```php
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
```

### Mendapatkan Waktu Server
`getServerTime()` - Mengambil waktu server Binance dalam format Unix timestamp milidetik (UTC).

```php
<?php
  // ... inisialisasi class ...

  // Mengambil waktu server Binance
  $serverTime = $binanceApi->getServerTime();
  if ($serverTime) {
      echo "Waktu Server Binance (milidetik): " . $serverTime . "\n";
      // Konversi ke format tanggal yang lebih mudah dibaca (ingat ini UTC)
      echo "Waktu Server Binance (UTC): " . date('Y-m-d H:i:s', $serverTime / 1000) . " UTC\n";
      
      // Contoh konversi ke zona waktu lokal (WITA) menggunakan DateTime
      $witaDateTime = new DateTime("@" . ($serverTime / 1000), new DateTimeZone('UTC'));
      $witaDateTime->setTimezone(new DateTimeZone('Asia/Makassar')); // Sesuaikan dengan zona waktu lokal Anda
      echo "Waktu Server Binance (Lokal WITA): " . $witaDateTime->format('Y-m-d H:i:s') . " WITA\n";
  } else {
      echo "Gagal mengambil waktu server Binance.\n";
  }
?>
```

### Mengambil Informasi Exchange (Aturan Trading)
`getExchangeInfo()` - Mengambil informasi dan aturan exchange, termasuk detail setiap simbol perdagangan (presisi harga/kuantitas, batasan order). Sangat direkomendasikan untuk digunakan sebelum mengajukan order!

```php
<?php
// ... inisialisasi class ...

// Mengambil informasi exchange
$exchangeInfo = $binanceApi->getExchangeInfo();
if ($exchangeInfo) {
    echo "Informasi Exchange:\n";
    echo "  Timezone: " . $exchangeInfo['timezone'] . "\n";
    echo "  Server Time: " . date('Y-m-d H:i:s', $exchangeInfo['serverTime'] / 1000) . " UTC\n";

    echo "  Contoh Info Symbol BTCUSDT:\n";
    $btcUsdtInfo = null;
    foreach ($exchangeInfo['symbols'] as $symbolInfo) {
        if ($symbolInfo['symbol'] === 'BTCUSDT') {
            $btcUsdtInfo = $symbolInfo;
            break;
        }
    }

    if ($btcUsdtInfo) {
        echo "    Symbol: " . $btcUsdtInfo['symbol'] . "\n";
        echo "    Status: " . $btcUsdtInfo['status'] . "\n";
        echo "    Base Asset: " . $btcUsdtInfo['baseAsset'] . ", Precision: " . $btcUsdtInfo['baseAssetPrecision'] . "\n";
        echo "    Quote Asset: " . $btcUsdtInfo['quoteAsset'] . ", Precision: " . $btcUsdtInfo['quoteAssetPrecision'] . "\n";
        echo "    Filters:\n";
        foreach ($btcUsdtInfo['filters'] as $filter) {
            echo "      - Type: " . $filter['filterType'];
            if ($filter['filterType'] === 'PRICE_FILTER') {
                echo ", minPrice: " . $filter['minPrice'] . ", maxPrice: " . $filter['maxPrice'] . ", tickSize: " . $filter['tickSize'];
            } elseif ($filter['filterType'] === 'LOT_SIZE') {
                echo ", minQty: " . $filter['minQty'] . ", maxQty: " . $filter['maxQty'] . ", stepSize: " . $filter['stepSize'];
            } elseif ($filter['filterType'] === 'MIN_NOTIONAL') {
                echo ", minNotional: " . $filter['minNotional'];
            }
            echo "\n";
        }
    } else {
        echo "    Info BTCUSDT tidak ditemukan.\n";
    }
} else {
    echo "Gagal mengambil informasi exchange.\n";
}
?>
```

### Mengambil Harga Terkini (Ticker Price)
`getSymbolPrice(string $symbol = null)` - Mengambil harga terkini untuk satu atau semua pasangan perdagangan.
- `$symbol` (opsional): Filter berdasarkan pasangan trading (misal: `'BTCUSDT'`). Jika `null`, akan mengembalikan harga semua symbol.

``` php
<?php
  // ... inisialisasi class ...

  // Mengambil harga BTCUSDT
  $btcPrice = $binanceApi->getSymbolPrice('BTCUSDT');
  if ($btcPrice) {
      echo "Harga BTCUSDT saat ini: " . $btcPrice['price'] . "\n";
  } else {
      echo "Gagal mengambil harga BTCUSDT.\n";
  }

  // Mengambil harga semua symbol (bisa sangat banyak!)
  /*
  $allPrices = $binanceApi->getSymbolPrice();
  if ($allPrices) {
      echo "Beberapa Harga:\n";
      foreach (array_slice($allPrices, 0, 5) as $ticker) { // Hanya menampilkan 5 contoh
          echo "  Symbol: " . $ticker['symbol'] . ", Price: " . $ticker['price'] . "\n";
      }
  }
  */
?>
```

### Mengambil Data Klines/Candlestick
`getKlines(string $symbol, string $interval, int $limit = 500, int $startTime = null, int $endTime = null)` - Mengambil data candlestick.
- `$symbol`: Pasangan trading (misal: `'BTCUSDT'`).
- `$interval`: Interval candlestick (misal: `'1m'`, `'1h'`, `'1d'`).
- `$limit`: Jumlah candlestick yang diinginkan (default `500`, max `1000`).
- `$startTime`, `$endTime`: Filter berdasarkan rentang waktu (Unix timestamp milidetik).

```php
<?php
  // ... inisialisasi class ...

  // Mengambil 10 candlestick BTCUSDT dengan interval 1 jam
  $klines = $binanceApi->getKlines('BTCUSDT', '1h', 10);
  if ($klines) {
      echo "Data Klines BTCUSDT (1h):\n";
      foreach ($klines as $kline) {
          echo "  Time: " . date('Y-m-d H:i:s', $kline[0] / 1000) . " | Open: " . $kline[1] . " | Close: " . $kline[4] . " | Volume: " . $kline[5] . "\n";
      }
  } else {
      echo "Gagal mengambil data klines.\n";
  }
?>
```

### Mengambil Kedalaman Pasar (Order Book)
`getDepth(string $symbol, int $limit = 100)` - Mengambil order book (buku pesanan) untuk pasangan trading tertentu.
- `$symbol`: Pasangan trading (misal: `'BTCUSDT'`).
- `$limit`: Jumlah entri kedalaman yang ingin diambil (misal: `5`, `10`, `20`, `50`, `100`, `500`, `1000`, `5000`). Default `100`.

```php
<?php
  // ... inisialisasi class ...

  // Mengambil kedalaman pasar (order book) untuk BTCUSDT dengan limit 10
  $depth = $binanceApi->getDepth('BTCUSDT', 10);
  if ($depth) {
      echo "Order Book BTCUSDT (Top 10):\n";
      echo "  Bids (Buy Orders):\n";
      foreach ($depth['bids'] as $bid) {
          echo "    Price: " . $bid[0] . ", Quantity: " . $bid[1] . "\n";
      }
      echo "  Asks (Sell Orders):\n";
      foreach ($depth['asks'] as $ask) {
          echo "    Price: " . $ask[0] . ", Quantity: " . $ask[1] . "\n";
      }
  } else {
      echo "Gagal mengambil kedalaman pasar.\n";
  }
?>
```

### Mengajukan Order Baru (Limit, Market, Stop Loss, Take Profit)
`placeOrder(string $symbol, string $side, string $type, float $quantity, float $price = null, float $stopPrice = null, string $timeInForce = null, string $newClientOrderId = null)` - Mengajukan order baru.
- `$symbol`: Pasangan trading (misal: `'BTCUSDT'`).
- `$side`: Arah order (`'BUY'` atau `'SELL'`).
- `$type`: Tipe order (`'LIMIT'`, `'MARKET'`, `'STOP_LOSS'`, `'TAKE_PROFIT'`).
- `$quantity`: Jumlah aset dasar.
- `$price` (opsional): Harga untuk order LIMIT, atau harga limit untuk order STOP_LOSS/TAKE_PROFIT.
- `$stopPrice` (opsional): Harga pemicu untuk order STOP_LOSS/TAKE_PROFIT.
- `$timeInForce` (opsional): Untuk order LIMIT (`'GTC'`, `'IOC'`, `'FOK'`).
- `$newClientOrderId` (opsional): ID order kustom Anda.

```php
<?php
  // ... inisialisasi class ...

  // CONTOH ORDER DAN PEMBATALAN (KOMENTARI ATAU GUNAKAN DENGAN SANGAT HATI-HATI PADA AKUN NYATA)
  // Pastikan Anda memahami presisi dan aturan trading dari getExchangeInfo() sebelum mencoba ini.
  // Order di bawah ini akan gagal jika kondisi tidak terpenuhi atau saldo tidak cukup.


  echo "--- Mengajukan Order Limit (Contoh: Beli BNBUSDT 0.01 di harga 250) ---\n";
  $symbol_limit = 'BNBUSDT';
  $side_limit = 'BUY';
  $quantity_limit = 0.01; // Pastikan sesuai stepSize BNBUSDT
  $price_limit = 250.00;   // Pastikan sesuai tickSize BNBUSDT

  $newOrder = $binanceApi->placeOrder($symbol_limit, $side_limit, 'LIMIT', $quantity_limit, $price_limit, null, 'GTC');
  if ($newOrder) {
      echo "Order Limit Berhasil Dibuat: Order ID " . $newOrder['orderId'] . ", Status: " . $newOrder['status'] . "\n";
      $placed_order_id = $newOrder['orderId'];
      $placed_client_order_id = $newOrder['clientOrderId'];
  } else {
      echo "Gagal membuat order LIMIT.\n";
  }

?>
```

### Membatalkan Order
`cancelOrder(string $symbol, int $orderId = null, string $origClientOrderId = null)` - Membatalkan order yang belum terisi.
- `$symbol`: Pasangan trading order.
- Anda harus menyediakan salah satu:
  - `$orderId`: ID order Binance.
  - `$origClientOrderId`: ID order kustom yang Anda tentukan.

```php
<?php
  // ... inisialisasi class ...

  // Contoh membatalkan order (LANJUTAN DARI CONTOH PLACE ORDER)
  if (isset($placed_order_id) && $placed_order_id) { // Hanya jika order sebelumnya berhasil dibuat
      echo "\n--- Membatalkan Order LIMIT yang Baru Dibuat ---\n";
      $cancelResult = $binanceApi->cancelOrder($symbol_limit, $placed_order_id);
      if ($cancelResult) {
          echo "Order LIMIT ID " . $placed_order_id . " berhasil dibatalkan. Status: " . $cancelResult['status'] . "\n";
      } else {
          echo "Gagal membatalkan order.\n";
      }
  }
?>
```

### Mengambil Status Order Tertentu
`getOrderStatus(string $symbol, int $orderId = null, string $origClientOrderId = null)` - Mengambil status detail dari order.
- `$symbol`: Pasangan trading order.
- Anda harus menyediakan salah satu:
  - `$orderId`: ID order Binance.
  - `$origClientOrderId`: ID order kustom yang Anda tentukan.

```php
<?php
  // ... inisialisasi class ...

  // Mengambil status order dengan Order ID Binance
  $orderId = 123456789; // Ganti dengan Order ID Anda yang valid
  $symbol = 'BNBUSDT';
  $orderStatus = $binanceApi->getOrderStatus($symbol, $orderId);
  if ($orderStatus) {
      echo "Status Order ID " . $orderId . ": " . $orderStatus['status'] . ", Qty: " . $orderStatus['executedQty'] . "/" . $orderStatus['origQty'] . "\n";
  } else {
      echo "Gagal mengambil status order.\n";
  }
?>
```

### Mengambil Open Orders
`getOpenOrders(string $symbol = null)` - Mengambil semua open order atau open order untuk symbol tertentu.
- `$symbol` (opsional): Filter berdasarkan pasangan trading (misal: `'BNBUSDT'`). Jika null, akan mengembalikan semua open order.

```php
<?php
  // ... inisialisasi class ...

  // Mengambil semua open orders
  $openOrders = $binanceApi->getOpenOrders();
  if ($openOrders !== false) {
      if (empty($openOrders)) {
          echo "Tidak ada open order saat ini.\n";
      } else {
          echo "Open Orders Anda:\n";
          foreach ($openOrders as $order) {
              echo "  Symbol: " . $order['symbol'] . ", ID: " . $order['orderId'] . ", Type: " . $order['type'] . ", Side: " . $order['side'] . ", Status: " . $order['status'] . "\n";
          }
      }
  } else {
      echo "Gagal mengambil open orders.\n";
  }
?>
```

### Mengambil Semua Riwayat Order
`getAllOrders(string $symbol, int $orderId = null, int $startTime = null, int $endTime = null, int $limit = 500)` - Mengambil semua riwayat order (termasuk yang tidak aktif/dibatalkan) untuk symbol tertentu.
- `$symbol`: Pasangan trading (misal: `'BNBUSDT'`).
- `$orderId` (opsional): Order ID untuk memulai pengambilan.
- `$startTime`, `$endTime` (opsional): Filter berdasarkan rentang waktu (Unix timestamp milidetik).
- `$limit` (opsional): Jumlah order yang diinginkan (default `500`, max `1000`).

```php
<?php
  // ... inisialisasi class ...

  // Mengambil semua riwayat order untuk BTCUSDT
  $allOrders = $binanceApi->getAllOrders('BTCUSDT', null, null, null, 5); // Ambil 5 order terbaru
  if ($allOrders) {
      echo "Riwayat Semua Order BTCUSDT (5 Terbaru):\n";
      foreach ($allOrders as $order) {
          echo "  Order ID: " . $order['orderId'] . ", Type: " . $order['type'] . ", Side: " . $order['side'] . ", Status: " . $order['status'] . ", Qty: " . $order['origQty'] . "\n";
      }
  } else {
      echo "Gagal mengambil semua riwayat order.\n";
  }
?>
```

### Mengambil Riwayat Perdagangan Pribadi
`getMyTrades(string $symbol, int $orderId = null, int $startTime = null, int $endTime = null, int $limit = 500, int $fromId = null)` - Mengambil riwayat perdagangan (trades) Anda sendiri untuk pasangan simbol tertentu.
- `$symbol`: Pasangan trading (misal: `'BTCUSDT'`).
- `$orderId` (opsional): Filter berdasarkan Order ID.
- `$startTime`, `$endTime` (opsional): Filter berdasarkan rentang waktu trade (Unix timestamp milidetik).
- `$limit` (opsional): Jumlah trade yang diinginkan (default `500`, max `1000`).
- `$fromId` (opsional): Trade ID untuk memulai pengambilan (mengambil trade dengan ID lebih besar dari ini).

```php
<?php
  // ... inisialisasi class ...

  // Mengambil 10 riwayat trade terbaru untuk BTCUSDT
  $myTrades = $binanceApi->getMyTrades('BTCUSDT', null, null, null, 10);
  if ($myTrades) {
      echo "Riwayat Perdagangan BTCUSDT (10 Terbaru):\n";
      foreach ($myTrades as $trade) {
          echo "  Trade ID: " . $trade['id'] . ", Price: " . $trade['price'] . ", Qty: " . $trade['qty'] . ", Commission: " . $trade['commission'] . " " . $trade['commissionAsset'] . ", Buyer: " . ($trade['isBuyer'] ? 'Yes' : 'No') . "\n";
      }
  } else {
      echo "Gagal mengambil riwayat perdagangan.\n";
  }
?>
```

### Mengambil Informasi Akun dan Saldo Spot
`getAccountInfo()` - Mengambil detail akun termasuk status trading dan semua saldo aset.
`getSpotBalance()` - Metode pembantu untuk hanya mengambil array saldo dari `getAccountInfo()`.

```php
<?php
  // ... inisialisasi class ...

  // Mengambil info akun
  $accountInfo = $binanceApi->getAccountInfo();
  if ($accountInfo) {
      echo "Informasi Akun:\n";
      echo "  Dapat Berdagang: " . ($accountInfo['canTrade'] ? 'Ya' : 'Tidak') . "\n";
      echo "  Saldo Aset:\n";
      foreach ($accountInfo['balances'] as $asset) {
          if (floatval($asset['free']) > 0 || floatval($asset['locked']) > 0) {
              echo "    Asset: " . $asset['asset'] . ", Free: " . $asset['free'] . ", Locked: " . $asset['locked'] . "\n";
          }
      }
  } else {
      echo "Gagal mengambil informasi akun.\n";
  }
?>
```

### Mengelola User Data Stream (WebSocket)
Ini adalah endpoint REST untuk mengelola `listenKey` yang diperlukan untuk terhubung ke WebSocket User Data Stream, yang memberikan update real-time tentang akun Anda.
- `startUserDataStream()`: Membuat listenKey baru.
- `keepAliveUserDataStream(string $listenKey)`: Memperpanjang masa berlaku listenKey (harus dipanggil setiap 30 menit).
- `closeUserDataStream(string $listenKey)`: Menutup `listenKey`.

```php
<?php
  // ... inisialisasi class ...

  // Contoh penggunaan User Data Stream (hanya bagian REST API)
  echo "\n--- Mengelola User Data Stream ---\n";
  $listenKey = $binanceApi->startUserDataStream();
  if ($listenKey) {
      echo "ListenKey baru dibuat: " . $listenKey . "\n";
      echo "ListenKey ini akan berakhir dalam 30 menit. Anda harus memanggil keepAliveUserDataStream() secara berkala.\n";

      // Contoh: Simulasikan memperpanjang setelah 1 menit (dalam produksi ini harus di cron job atau loop terpisah)
      sleep(60); // Tunggu 60 detik
      echo "Memperpanjang ListenKey setelah 1 menit...\n";
      $keepAliveResult = $binanceApi->keepAliveUserDataStream($listenKey);
      if ($keepAliveResult !== false) {
          echo "ListenKey berhasil diperpanjang.\n";
      } else {
          echo "Gagal memperpanjang ListenKey.\n";
      }

      // Contoh: Menutup ListenKey (lakukan ini saat aplikasi Anda berhenti atau tidak lagi membutuhkan stream)
      echo "Menutup ListenKey...\n";
      $closeResult = $binanceApi->closeUserDataStream($listenKey);
      if ($closeResult !== false) {
          echo "ListenKey berhasil ditutup.\n";
      } else {
          echo "Gagal menutup ListenKey.\n";
      }

  } else {
      echo "Gagal membuat ListenKey untuk User Data Stream.\n";
  }
?>
```

## Catatan Penting
- **Keamanan API Key**: Selalu jaga kerahasiaan Secret Key Anda. Pertimbangkan untuk menggunakan IP Whitelisting di pengaturan API key Binance Anda untuk keamanan tambahan.
- **Time Synchronization**: API Binance sangat sensitif terhadap perbedaan waktu. Pastikan waktu server Anda disinkronkan dengan waktu server Binance. Fungsi `getServerTime()` dapat membantu Anda dalam hal ini. Perbedaan waktu yang terlalu besar (biasanya lebih dari 1000-2000 ms) akan menyebabkan `signed requests` ditolak.
- **Presisi (Precision) & Aturan Trading**: Sangat penting untuk selalu merujuk pada data dari `getExchangeInfo()` sebelum mengajukan order. Setiap pasangan trading memiliki aturan presisi yang berbeda untuk harga (`price`) dan kuantitas (`quantity`), serta batasan minimum/maksimum (`minQty`, `maxQty`, `minNotional`). Kegagalan untuk mematuhi aturan ini akan menyebabkan order Anda ditolak. Gunakan `tickSize` untuk harga dan `stepSize` untuk kuantitas dari `LOT_SIZE` dan `PRICE_FILTER` untuk memastikan Anda menggunakan kelipatan yang benar.
- **Error Handling**: Klien ini memiliki error handling dasar. Untuk aplikasi produksi, implementasikan try-catch blocks dan penanganan kode error Binance secara spesifik.
- **Rate Limits**: Binance memberlakukan rate limits pada permintaan API. Jika Anda melebihi batas, permintaan Anda akan ditolak. Terapkan delay atau strategi rate limit dalam kode Anda jika Anda membuat banyak permintaan. Anda bisa mendapatkan informasi `rateLimits` dari `getExchangeInfo()`.
- **PHP cURL Extension**: Pastikan ekstensi `php_curl` diaktifkan di instalasi PHP Anda.
- **WebSocket (User Data Stream)**: Perlu diingat bahwa fungsi `userDataStream` di kelas ini hanya untuk manajemen `listenKey` melalui REST API. Untuk benar-benar menerima real-time updates dari aktivitas akun (misalnya, order terisi, saldo berubah), Anda perlu mengimplementasikan klien WebSocket terpisah yang terhubung ke URL WebSocket Binance menggunakan `listenKey` yang Anda dapatkan.
