<?php
session_start();

$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "root"; // Default password for MAMP on macOS
$db   = getenv('DB_NAME') ?: "rekap_mutz_asn";
$port = getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306;

// Allow setup.php to skip db selection if it doesn't exist yet
$conn = new mysqli($host, $user, $pass, null, $port);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error . "<br>Periksa konfigurasi database.");
}

if (!isset($skip_db_select)) {
    if (!$conn->select_db($db)) {
        die("Database '$db' belum ada atau belum diatur. Silakan jalankan <a href='setup.php'>setup.php</a> terlebih dahulu.");
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
