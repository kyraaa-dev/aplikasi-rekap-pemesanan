<?php
session_start();

$host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? getenv('DB_HOST') ?: "localhost";
$user = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? getenv('DB_USER') ?: "root";
$pass = $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? (getenv('DB_PASS') !== false ? getenv('DB_PASS') : "root");
$db   = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? getenv('DB_NAME') ?: "rekap_mutz_asn";
$port = (int)($_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? getenv('DB_PORT') ?: 3306);

// Cek apakah berjalan di Vercel tapi env DB_HOST belum diatur
if (($host === "localhost" || $host === "127.0.0.1") && (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']))) {
    die("<div style='font-family:sans-serif; padding:20px; background:#fff3cd; color:#856404; border:1px solid #ffeeba; border-radius:8px; max-width:600px; margin:40px auto; line-height:1.6;'>
        <h3 style='margin-top:0;'>⚠️ Environment Variables Database Belum Aktif di Vercel</h3>
        <p>Aplikasi belum menerima variabel database dari TiDB Cloud.</p>
        <ol style='padding-left:20px;'>
            <li>Buka dashboard <b>Vercel</b> > pilih projek ini > masuk menu <b>Settings > Environment Variables</b>.</li>
            <li>Pastikan variabel <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_USER</code>, <code>DB_PASS</code>, dan <code>DB_NAME</code> sudah ditambahkan.</li>
            <li>Klik tab <b>Deployments</b> > klik titik tiga (<code>...</code>) pada deployment terakhir > pilih <b>Redeploy</b> agar variabel aktif.</li>
        </ol>
    </div>");
}

// Inisialisasi koneksi dengan dukungan SSL untuk Cloud MySQL (TiDB Cloud)
$conn = mysqli_init();
if (!$conn) {
    die("mysqli_init gagal");
}

// Enable SSL jika menggunakan Cloud Host (bukan localhost)
if ($host !== "localhost" && $host !== "127.0.0.1") {
    $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
    $connected = @$conn->real_connect($host, $user, $pass, null, $port, NULL, MYSQLI_CLIENT_SSL);
} else {
    $connected = @$conn->real_connect($host, $user, $pass, null, $port);
}

if (!$connected || $conn->connect_error) {
    die("<div style='font-family:sans-serif; padding:20px; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; border-radius:8px; max-width:600px; margin:40px auto;'>
        <h3 style='margin-top:0;'>❌ Gagal Terhubung ke Database</h3>
        <p>Error: " . ($conn->connect_error ?: "Gagal terhubung ke host $host:$port") . "</p>
        <p>Pastikan kredensial di <b>Environment Variables</b> Vercel sudah sesuai dengan data dari TiDB Cloud.</p>
    </div>");
}

if (!isset($skip_db_select)) {
    if (!$conn->select_db($db)) {
        die("<div style='font-family:sans-serif; padding:20px; background:#fff3cd; color:#856404; border:1px solid #ffeeba; border-radius:8px; max-width:600px; margin:40px auto;'>
            <h3 style='margin-top:0;'>⚠️ Database '$db' belum siap</h3>
            <p>Silakan jalankan <a href='setup.php' style='color:#0056b3; font-weight:bold;'>setup.php</a> terlebih dahulu untuk membuat tabel database.</p>
        </div>");
    }
}

// Authentication Check
$current_page = basename($_SERVER['PHP_SELF']);
$allowed_pages = ['login.php', 'setup.php'];

if (!in_array($current_page, $allowed_pages)) {
    if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
        header("Location: login.php");
        exit;
    }
}
// Global Settings
$app_settings = [];
if (!isset($skip_db_select)) {
    $q_settings = $conn->query("SELECT * FROM settings LIMIT 1");
    if ($q_settings && $q_settings->num_rows > 0) {
        $app_settings = $q_settings->fetch_assoc();
    }
}
define('HARGA_BIASA', isset($app_settings['harga_biasa']) ? (int)$app_settings['harga_biasa'] : 55000);
define('HARGA_KEPALA', isset($app_settings['harga_kepala']) ? (int)$app_settings['harga_kepala'] : 150000);
?>
