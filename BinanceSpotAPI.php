<?php
/**
 * Class BinanceSpotAPI
 *
 * Kelas fungsionalitas untuk berinteraksi dengan Binance Spot REST API.
 * Fitur-fitur yang dicakup meliputi:
 * 1. Mengambil data Klines/Candlestick untuk analisis teknis.
 * 2. Mengajukan berbagai tipe order (LIMIT, MARKET, STOP_LOSS, TAKE_PROFIT).
 * 3. Mengambil status order tertentu.
 * 4. Mengambil daftar open order yang aktif.
 * 5. Mengambil informasi akun dan saldo spot.
 * 6. Mendapatkan waktu server Binance.
 * 7. Mendapatkan informasi exchange (aturan trading, presisi).
 * 8. Membatalkan order.
 * 9. Mendapatkan harga terkini (ticker price).
 * 10. Mendapatkan riwayat perdagangan pribadi.
 * 11. Mendapatkan order book (kedalaman pasar).
 * 12. Menguji konektivitas API (ping).
 * 13. Mengambil semua riwayat order (termasuk yang tidak aktif/dibatalkan).
 * 14. Mengelola User Data Stream (membuat, memperpanjang, menutup listenKey untuk WebSocket).
 */

class BinanceSpotAPI {
    private $api_key;
    private $secret_key;
    private $base_url = 'https://api.binance.com'; // Untuk Binance Global

    /**
     * Konstruktor kelas BinanceSpotAPI.
     * Menginisialisasi API Key dan Secret Key yang akan digunakan untuk otentikasi.
     *
     * @param string $api_key Kunci API dari akun Binance Anda. Digunakan untuk identifikasi.
     * @param string $secret_key Kunci rahasia API dari akun Binance Anda. Digunakan untuk menandatangani permintaan sensitif.
     */
    public function __construct($api_key, $secret_key) {
        $this->api_key = $api_key;
        $this->secret_key = $secret_key;
    }

    /**
     * Metode internal untuk mengirim permintaan HTTP ke Binance API.
     * Ini adalah metode inti yang menangani komunikasi dengan server Binance,
     * termasuk penambahan timestamp dan signature untuk permintaan yang memerlukan otentikasi.
     *
     * @param string $method Metode HTTP (GET/POST/DELETE) yang akan digunakan untuk permintaan.
     * @param string $endpoint Endpoint API Binance (misal: '/api/v3/account', '/api/v3/order').
     * @param array $params Parameter yang akan disertakan dalam permintaan (query string atau body).
     * @param bool $signed Menunjukkan apakah permintaan ini memerlukan penandatanganan dengan Secret Key.
     * @return array|false Respons dari API dalam bentuk array asosiatif jika sukses, atau false jika terjadi kegagalan (cURL error, HTTP error, atau API error).
     */
    private function callApi($method, $endpoint, $params = [], $signed = false) {
        $url = $this->base_url . $endpoint;
        $headers = [
            'X-MBX-APIKEY: ' . $this->api_key,
            'Content-Type: application/x-www-form-urlencoded'
        ];

        // Tambahkan timestamp dan tanda tangan jika diperlukan
        if ($signed) {
            // Timestamp harus dalam milidetik
            // Menggunakan waktu server yang diambil dari getServerTime()
            $params['timestamp'] = $this->getServerTime(); // Memanggil getServerTime() untuk mendapatkan timestamp terbaru
            $query_string = http_build_query($params);
            $signature = hash_hmac('sha256', $query_string, $this->secret_key);
            $url .= '?' . $query_string . '&signature=' . $signature;
        } else if (!empty($params)) {
            // Untuk permintaan tidak bertanda tangan, parameter ditambahkan langsung ke URL
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Mengembalikan transfer sebagai string
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Mengikuti redirect

        // Konfigurasi untuk metode POST atau DELETE
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            // Untuk POST, parameters dikirim dalam body
            // Jika signed, $params sudah punya timestamp dan signature.
            // Jika tidak signed, $params hanya parameter data.
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            // Untuk DELETE, parameters sudah ada di URL jika signed
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            error_log("cURL Error for $endpoint: " . curl_error($ch));
            return false;
        }

        $data = json_decode($response, true);

        // Penanganan error dari API Binance (status HTTP 4xx/5xx atau kode error Binance negatif)
        if ($http_code >= 400 || (isset($data['code']) && $data['code'] < 0)) {
            $error_msg = $data['msg'] ?? 'Unknown API error';
            error_log("Binance API Error ($http_code) for $endpoint: $error_msg. Response: " . $response);
            return false;
        }

        return $data;
    }

    /**
     * Menguji konektivitas ke REST API. Ini adalah endpoint yang sangat ringan.
     * Endpoint: GET /api/v3/ping
     * Tipe: Public (Tidak memerlukan API Key atau Secret Key).
     *
     * @return array|false Array kosong jika koneksi berhasil, atau false jika gagal.
     * Contoh return: []
     */
    public function ping() {
        return $this->callApi('GET', '/api/v3/ping', [], false);
    }

    /**
     * Mengambil waktu server Binance.
     * Endpoint: GET /api/v3/time
     * Tipe: Public (Tidak memerlukan API Key atau Secret Key).
     * Waktu yang dikembalikan adalah Unix timestamp dalam milidetik (UTC).
     * Ini sangat penting untuk sinkronisasi waktu agar permintaan signed tidak ditolak.
     *
     * @return int|false Unix timestamp dalam milidetik jika sukses, atau false jika gagal.
     * Contoh return: 1678886400000
     */
    public function getServerTime() {
        $response = $this->callApi('GET', '/api/v3/time', [], false);
        return $response['serverTime'] ?? false;
    }

    /**
     * Mengambil informasi dan aturan exchange, termasuk detail setiap simbol perdagangan
     * (misalnya, presisi harga dan kuantitas, batasan order).
     * Endpoint: GET /api/v3/exchangeInfo
     * Tipe: Public (Tidak memerlukan API Key atau Secret Key).
     * Sangat penting untuk validasi order sebelum diajukan ke Binance.
     *
     * @return array|false Array asosiatif yang berisi informasi exchange jika sukses, atau false jika gagal.
     * Contoh return (struktur disederhanakan):
     * [
     * "timezone" => "UTC",
     * "serverTime" => 1678886400000,
     * "rateLimits" => [ /* ... batasan rate limit ... * / ],
     * "exchangeFilters" => [],
     * "symbols" => [ /* ... daftar simbol perdagangan dengan detail filter (PRICE_FILTER, LOT_SIZE, MIN_NOTIONAL, dll.) ... * / ]
     * ]
     */
    public function getExchangeInfo() {
        return $this->callApi('GET', '/api/v3/exchangeInfo', [], false);
    }

    /**
     * Mengambil harga terkini untuk satu atau semua pasangan perdagangan.
     * Endpoint: GET /api/v3/ticker/price
     * Tipe: Public (Tidak memerlukan API Key atau Secret Key).
     *
     * @param string|null $symbol Pasangan trading (misal: 'BTCUSDT'). Opsional. Jika null, akan mengembalikan harga semua symbol.
     * @return array|false Jika $symbol diberikan, mengembalikan array asosiatif dengan 'symbol' dan 'price'.
     * Jika $symbol null, mengembalikan array dari array objek ticker.
     * Mengembalikan false jika gagal.
     * Contoh return untuk satu symbol ('BTCUSDT'):
     * [ "symbol" => "BTCUSDT", "price" => "30000.00000000" ]
     * Contoh return untuk semua symbol:
     * [ [ "symbol" => "BNBBTC", "price" => "0.00213000" ], [ "symbol" => "BTCUSDT", "price" => "30000.00000000" ], /* ... * / ]
     */
    public function getSymbolPrice($symbol = null) {
        $params = [];
        if ($symbol) {
            $params['symbol'] = strtoupper($symbol);
        }
        return $this->callApi('GET', '/api/v3/ticker/price', $params, false);
    }

    /**
     * Mengambil data candlestick (Klines) untuk pasangan trading tertentu.
     * Endpoint: GET /api/v3/klines
     * Tipe: Public (Tidak memerlukan API Key atau Secret Key).
     *
     * @param string $symbol Pasangan trading (misal: 'BTCUSDT'). Wajib.
     * @param string $interval Interval candlestick (misal: '1m', '5m', '1h', '1d'). Wajib.
     * @param int $limit Jumlah candlestick yang ingin diambil (default: 500, max: 1000). Opsional.
     * @param int|null $startTime Waktu mulai data (Unix timestamp dalam milidetik). Opsional.
     * @param int|null $endTime Waktu akhir data (Unix timestamp dalam milidetik). Opsional.
     * @return array|false Array dari array candlestick jika sukses, atau false jika gagal.
     * Setiap candlestick adalah array dengan format:
     * [ openTime, openPrice, highPrice, lowPrice, closePrice, volume, closeTime, quoteAssetVolume, numberOfTrades, takerBuyBaseAssetVolume, takerBuyQuoteAssetVolume, ignore ]
     */
    public function getKlines($symbol, $interval, $limit = 500, $startTime = null, $endTime = null) {
        $params = [
            'symbol'   => strtoupper($symbol),
            'interval' => $interval,
            'limit'    => $limit,
        ];
        if ($startTime) {
            $params['startTime'] = $startTime;
        }
        if ($endTime) {
            $params['endTime'] = $endTime;
        }
        return $this->callApi('GET', '/api/v3/klines', $params, false);
    }

    /**
     * Mengambil kedalaman buku pesanan (Order Book) untuk pasangan trading tertentu.
     * Endpoint: GET /api/v3/depth
     * Tipe: Public (Tidak memerlukan API Key atau Secret Key).
     *
     * @param string $symbol Pasangan trading (misal: 'BTCUSDT'). Wajib.
     * @param int $limit Jumlah entri kedalaman yang ingin diambil (misal: 5, 10, 20, 50, 100, 500, 1000, 5000). Default 100.
     * @return array|false Array asosiatif yang berisi bid dan ask jika sukses, atau false jika gagal.
     * Contoh return:
     * [
     * "lastUpdateId" => 1027024,
     * "bids" => [ // Pesanan beli: [harga, kuantitas]
     * ["40000.00000000", "0.00200000"],
     * ["39999.00000000", "0.00500000"]
     * ],
     * "asks" => [ // Pesanan jual: [harga, kuantitas]
     * ["40001.00000000", "0.00300000"],
     * ["40002.00000000", "0.00100000"]
     * ]
     * ]
     */
    public function getDepth($symbol, $limit = 100) {
        $params = [
            'symbol' => strtoupper($symbol),
            'limit'  => $limit,
        ];
        return $this->callApi('GET', '/api/v3/depth', $params, false);
    }

    /**
     * Mengajukan order perdagangan baru (Limit, Market, Stop Loss, Take Profit).
     * Endpoint: POST /api/v3/order
     * Tipe: Signed (Memerlukan API Key dan Secret Key).
     *
     * @param string $symbol Pasangan trading (misal: 'BTCUSDT'). Wajib.
     * @param string $side Arah order ('BUY' atau 'SELL'). Wajib.
     * @param string $type Tipe order ('LIMIT', 'MARKET', 'STOP_LOSS', 'TAKE_PROFIT'). Wajib.
     * @param float $quantity Jumlah aset dasar yang ingin diperdagangkan. Wajib.
     * @param float|null $price Harga eksekusi untuk order LIMIT, atau harga limit untuk STOP_LOSS/TAKE_PROFIT. Opsional, wajib untuk LIMIT, STOP_LOSS, TAKE_PROFIT.
     * @param float|null $stopPrice Harga pemicu (trigger price) untuk order STOP_LOSS/TAKE_PROFIT. Opsional, wajib untuk STOP_LOSS, TAKE_PROFIT.
     * @param string|null $timeInForce Durasi order di pasar (misal: 'GTC', 'IOC', 'FOK'). Opsional, hanya untuk order LIMIT. Default 'GTC'.
     * @param string|null $newClientOrderId ID order kustom yang Anda buat. Maksimal 36 karakter, harus unik dalam 24 jam. Opsional.
     * @return array|false Detail order yang berhasil ditempatkan jika sukses, atau false jika gagal.
     * Contoh return untuk order berhasil:
     * [ "symbol" => "BTCUSDT", "orderId" => 123456789, "clientOrderId" => "myCustomOrderId", "transactTime" => 1678886400000, "price" => "30000.00000000", "origQty" => "0.00100000", "executedQty" => "0.00000000", "status" => "NEW", "timeInForce" => "GTC", "type" => "LIMIT", "side" => "BUY", /* ... * / ]
     */
    public function placeOrder($symbol, $side, $type, $quantity, $price = null, $stopPrice = null, $timeInForce = null, $newClientOrderId = null) {
        $params = [
            'symbol'   => strtoupper($symbol),
            'side'     => strtoupper($side),
            'type'     => strtoupper($type),
            'quantity' => (string)$quantity,
        ];

        if ($type === 'LIMIT') {
            if ($price === null) { error_log("Error: Limit order requires 'price'."); return false; }
            $params['price'] = (string)$price;
            $params['timeInForce'] = $timeInForce ?: 'GTC';
        } elseif (in_array($type, ['STOP_LOSS', 'TAKE_PROFIT'])) {
            if ($price === null || $stopPrice === null) { error_log("Error: STOP_LOSS/TAKE_PROFIT order requires 'price' and 'stopPrice'."); return false; }
            $params['price'] = (string)$price;
            $params['stopPrice'] = (string)$stopPrice;
        }

        if ($newClientOrderId) {
            $params['newClientOrderId'] = $newClientOrderId;
        }

        return $this->callApi('POST', '/api/v3/order', $params, true);
    }

    /**
     * Membatalkan order yang belum terisi.
     * Endpoint: DELETE /api/v3/order
     * Tipe: Signed (Memerlukan API Key dan Secret Key).
     *
     * @param string $symbol Pasangan trading dari order yang akan dibatalkan (misal: 'BNBUSDT'). Wajib.
     * @param int|null $orderId ID order Binance. Anda harus menyediakan salah satu ($orderId atau $origClientOrderId).
     * @param string|null $origClientOrderId ID order kustom yang Anda tentukan saat membuat order. Anda harus menyediakan salah satu ($orderId atau $origClientOrderId).
     * @return array|false Detail order yang berhasil dibatalkan jika sukses, atau false jika order tidak ditemukan atau gagal dibatalkan.
     * Contoh return:
     * [ "symbol" => "LTCBTC", "origClientOrderId" => "myOrder1", "orderId" => 1, "orderListId" => -1, "clientOrderId" => "cancelMyOrder1", "price" => "0.10000000", "origQty" => "1.00000000", "executedQty" => "0.00000000", "cummulativeQuoteQty" => "0.00000000", "status" => "CANCELED", "timeInForce" => "GTC", "type" => "LIMIT", "side" => "BUY" ]
     */
    public function cancelOrder($symbol, $orderId = null, $origClientOrderId = null) {
        $params = ['symbol' => strtoupper($symbol)];
        if ($orderId) {
            $params['orderId'] = $orderId;
        } elseif ($origClientOrderId) {
            $params['origClientOrderId'] = $origClientOrderId;
        } else {
            error_log("Error: cancelOrder requires orderId or origClientOrderId.");
            return false;
        }
        return $this->callApi('DELETE', '/api/v3/order', $params, true);
    }

    /**
     * Mengambil status detail dari order tertentu.
     * Endpoint: GET /api/v3/order
     * Tipe: Signed (Memerlukan API Key dan Secret Key).
     *
     * @param string $symbol Pasangan trading dari order yang ingin diperiksa (misal: 'BNBUSDT'). Wajib.
     * @param int|null $orderId ID order yang diberikan oleh Binance. Anda harus menyediakan salah satu ($orderId atau $origClientOrderId).
     * @param string|null $origClientOrderId ID order kustom yang Anda tentukan saat membuat order. Anda harus menyediakan salah satu ($orderId atau $origClientOrderId).
     * @return array|false Detail status order jika sukses, atau false jika order tidak ditemukan atau terjadi kesalahan.
     * Contoh return:
     * [ "symbol" => "LTCBTC", "orderId" => 1, "orderListId" => -1, "clientOrderId" => "myOrder1", "price" => "0.10000000", "origQty" => "1.00000000", "executedQty" => "0.00000000", "cummulativeQuoteQty" => "0.00000000", "status" => "NEW", "timeInForce" => "GTC", "type" => "LIMIT", "side" => "BUY", "stopPrice" => "0.00000000", "icebergQty" => "0.00000000", "time" => 1499827319559, "updateTime" => 1499827319559, "isWorking" => true, "origQuoteOrderQty" => "0.00000000" ]
     */
    public function getOrderStatus($symbol, $orderId = null, $origClientOrderId = null) {
        $params = ['symbol' => strtoupper($symbol)];
        if ($orderId) {
            $params['orderId'] = $orderId;
        } elseif ($origClientOrderId) {
            $params['origClientOrderId'] = $origClientOrderId;
        } else {
            error_log("Error: getOrderStatus requires orderId or origClientOrderId.");
            return false;
        }
        return $this->callApi('GET', '/api/v3/order', $params, true);
    }

    /**
     * Mengambil daftar semua open order (order yang aktif dan belum sepenuhnya terisi/dibatalkan).
     * Endpoint: GET /api/v3/openOrders
     * Tipe: Signed (Memerlukan API Key dan Secret Key).
     *
     * @param string|null $symbol Pasangan trading (misal: 'BNBUSDT'). Opsional. Jika diberikan, hanya akan mengembalikan open order untuk symbol tersebut. Jika null, akan mengembalikan semua open order dari semua symbol.
     * @return array|false Array dari array open order jika sukses (array kosong [] jika tidak ada open order), atau false jika terjadi kesalahan.
     * Contoh return (array berisi objek order yang serupa dengan getOrderStatus):
     * [ [ "symbol" => "LTCBTC", "orderId" => 1, "clientOrderId" => "myOrder1", "price" => "0.10000000", "origQty" => "1.00000000", "executedQty" => "0.00000000", "status" => "NEW", "timeInForce" => "GTC", "type" => "LIMIT", "side" => "BUY", "time" => 1499827319559, "updateTime" => 1499827319559, "isWorking" => true ], /* ... * / ]
     */
    public function getOpenOrders($symbol = null) {
        $params = [];
        if ($symbol) {
            $params['symbol'] = strtoupper($symbol);
        }
        return $this->callApi('GET', '/api/v3/openOrders', $params, true);
    }

    /**
     * Mengambil semua riwayat order (termasuk yang NEW, PARTIALLY_FILLED, FILLED, CANCELED, EXPIRED)
     * untuk pasangan simbol tertentu.
     * Endpoint: GET /api/v3/allOrders
     * Tipe: Signed (Memerlukan API Key dan Secret Key).
     *
     * @param string $symbol Pasangan trading (misal: 'BNBUSDT'). Wajib.
     * @param int|null $orderId Order ID untuk memulai pengambilan (akan mengembalikan order dengan ID lebih besar dari ini). Opsional.
     * @param int|null $startTime Waktu mulai data (Unix timestamp dalam milidetik). Opsional.
     * @param int|null $endTime Waktu akhir data (Unix timestamp dalam milidetik). Opsional.
     * @param int $limit Jumlah order yang ingin diambil (default: 500, max: 1000). Opsional.
     * @return array|false Array dari array order jika sukses, atau false jika gagal.
     * Contoh return (array berisi objek order yang serupa dengan getOrderStatus):
     * [ [ "symbol" => "LTCBTC", "orderId" => 1, "clientOrderId" => "myOrder1", "price" => "0.10000000", "origQty" => "1.00000000", "executedQty" => "0.00000000", "status" => "FILLED", "timeInForce" => "GTC", "type" => "LIMIT", "side" => "BUY", "time" => 1499827319559, "updateTime" => 1499827319559, "isWorking" => false ], /* ... * / ]
     */
    public function getAllOrders($symbol, $orderId = null, $startTime = null, $endTime = null, $limit = 500) {
        $params = ['symbol' => strtoupper($symbol)];
        if ($orderId) { $params['orderId'] = $orderId; }
        if ($startTime) { $params['startTime'] = $startTime; }
        if ($endTime) { $params['endTime'] = $endTime; }
        $params['limit'] = $limit;
        return $this->callApi('GET', '/api/v3/allOrders', $params, true);
    }

    /**
     * Mengambil riwayat perdagangan (trades) Anda sendiri untuk pasangan simbol tertentu.
     * Ini berbeda dengan riwayat order karena hanya menampilkan eksekusi perdagangan yang berhasil (filled),
     * termasuk komisi dan aset komisi.
     * Endpoint: GET /api/v3/myTrades
     * Tipe: Signed (Memerlukan API Key dan Secret Key).
     *
     * @param string $symbol Pasangan trading (misal: 'BTCUSDT'). Wajib.
     * @param int|null $orderId Order ID untuk filter trades. Opsional.
     * @param int|null $startTime Waktu mulai data (Unix timestamp dalam milidetik). Opsional.
     * @param int|null $endTime Waktu akhir data (Unix timestamp dalam milidetik). Opsional.
     * @param int $limit Jumlah trade yang ingin diambil (default: 500, max: 1000). Opsional.
     * @param int|null $fromId Trade ID untuk memulai pengambilan (akan mengembalikan trade dengan ID lebih besar dari ini). Opsional.
     * @return array|false Array dari array trade jika sukses, atau false jika gagal.
     * Contoh return:
     * [
     * [
     * "symbol" => "BNBBTC",
     * "id" => 28, // Trade ID
     * "orderId" => 12345,
     * "orderListId" => -1,
     * "price" => "0.00010000",
     * "qty" => "100.00000000",
     * "quoteQty" => "0.01000000",
     * "commission" => "0.00000781",
     * "commissionAsset" => "BNB",
     * "time" => 1499865549590, // Waktu trade (ms)
     * "isBuyer" => true,
     * "isMaker" => false,
     * "isBestMatch" => true
     * ],
     * // ... lebih banyak trade
     * ]
     */
    public function getMyTrades($symbol, $orderId = null, $startTime = null, $endTime = null, $limit = 500, $fromId = null) {
        $params = ['symbol' => strtoupper($symbol)];
        if ($orderId) { $params['orderId'] = $orderId; }
        if ($startTime) { $params['startTime'] = $startTime; }
        if ($endTime) { $params['endTime'] = $endTime; }
        if ($fromId) { $params['fromId'] = $fromId; }
        $params['limit'] = $limit;
        return $this->callApi('GET', '/api/v3/myTrades', $params, true);
    }

    /**
     * Mengambil informasi akun saat ini, termasuk status trading permission dan saldo semua aset.
     * Endpoint: GET /api/v3/account
     * Tipe: Signed (Memerlukan API Key dan Secret Key).
     *
     * @return array|false Informasi akun jika sukses, atau false jika terjadi kesalahan.
     * Contoh return:
     * [ "makerCommission" => 15, "takerCommission" => 15, "buyerCommission" => 0, "sellerCommission" => 0, "canTrade" => true, "canWithdraw" => true, "canDeposit" => true, "updateTime" => 123456789, "accountType" => "SPOT", "balances" => [ /* ... saldo aset ... * / ], "permissions" => ["SPOT"] ]
     */
    public function getAccountInfo() {
        return $this->callApi('GET', '/api/v3/account', [], true);
    }

    /**
     * Metode pembantu untuk mendapatkan saldo spot saja dari fungsi getAccountInfo().
     * Ini hanyalah convenience method yang memanggil getAccountInfo() dan mengekstrak bagian 'balances'.
     *
     * @return array|false Array dari saldo aset (mirip dengan bagian 'balances' dari getAccountInfo()) jika sukses, atau false jika gagal.
     */
    public function getSpotBalance() {
        $accountInfo = $this->getAccountInfo();
        return $accountInfo['balances'] ?? false;
    }

    /**
     * Membuat listenKey baru untuk User Data Stream.
     * Endpoint: POST /api/v3/userDataStream
     * Tipe: Signed (Memerlukan API Key dan Secret Key).
     * listenKey digunakan untuk koneksi WebSocket yang menerima update akun real-time.
     *
     * @return string|false listenKey jika sukses, atau false jika gagal.
     * Contoh return: "pqia91ma1FxD6ByALQ"
     */
    public function startUserDataStream() {
        $response = $this->callApi('POST', '/api/v3/userDataStream', [], true);
        return $response['listenKey'] ?? false;
    }

    /**
     * Memperpanjang masa berlaku listenKey untuk User Data Stream.
     * listenKey memiliki masa berlaku 30 menit dan harus diperpanjang setiap 30 menit.
     * Endpoint: PUT /api/v3/userDataStream
     * Tipe: Signed (Memerlukan API Key dan Secret Key).
     *
     * @param string $listenKey listenKey yang akan diperpanjang. Wajib.
     * @return array|false Array kosong jika sukses, atau false jika gagal.
     * Contoh return: []
     */
    public function keepAliveUserDataStream($listenKey) {
        $params = ['listenKey' => $listenKey];
        return $this->callApi('PUT', '/api/v3/userDataStream', $params, true);
    }

    /**
     * Menutup User Data Stream yang aktif.
     * Endpoint: DELETE /api/v3/userDataStream
     * Tipe: Signed (Memerlukan API Key dan Secret Key).
     *
     * @param string $listenKey listenKey yang akan ditutup. Wajib.
     * @return array|false Array kosong jika sukses, atau false jika gagal.
     * Contoh return: []
     */
    public function closeUserDataStream($listenKey) {
        $params = ['listenKey' => $listenKey];
        return $this->callApi('DELETE', '/api/v3/userDataStream', $params, true);
    }
}

?>
