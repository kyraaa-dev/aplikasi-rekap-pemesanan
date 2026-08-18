<?php
session_start();

function get_env_var($keys, $default = '') {
    if (!is_array($keys)) $keys = [$keys];
    
    // Check all combinations
    foreach ($keys as $k) {
        $variants = [$k, strtoupper($k), strtolower($k)];
        foreach ($variants as $var) {
            if (getenv($var) !== false && getenv($var) !== '') return trim(getenv($var));
            if (isset($_ENV[$var]) && $_ENV[$var] !== '') return trim($_ENV[$var]);
            if (isset($_SERVER[$var]) && $_SERVER[$var] !== '') return trim($_SERVER[$var]);
        }
    }
    return $default;
}

// Check for DATABASE_URL / MYSQL_URL connection string if present
$db_url = get_env_var(['DATABASE_URL', 'MYSQL_URL', 'JAWSDB_URL', 'CLEARDB_DATABASE_URL']);
if (!empty($db_url) && $parsed = parse_url($db_url)) {
    $host = $parsed['host'] ?? 'localhost';
    $port = (int)($parsed['port'] ?? 3306);
    $user = $parsed['user'] ?? 'root';
    $pass = $parsed['pass'] ?? '';
    $db   = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'rekap_mutz_asn';
} else {
    $host = get_env_var(['DB_HOST', 'DATABASE_HOST', 'MYSQL_HOST', 'TIDB_HOST', 'HOST'], 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com');
    $user = get_env_var(['DB_USER', 'DB_USERNAME', 'DATABASE_USER', 'MYSQL_USER', 'TIDB_USER', 'USER'], '4J75fkYqnQjcSSW.root');
    $pass = get_env_var(['DB_PASS', 'DB_PASSWORD', 'DATABASE_PASSWORD', 'MYSQL_PASSWORD', 'PASSWORD'], '7Y3R2Vazfvg7LKFg');
    $db   = get_env_var(['DB_NAME', 'DB_DATABASE', 'DATABASE_NAME', 'MYSQL_DATABASE', 'TIDB_DATABASE'], 'test');
    $port = (int)get_env_var(['DB_PORT', 'DATABASE_PORT', 'MYSQL_PORT', 'TIDB_PORT', 'PORT'], '4000');
}

// Cek apakah berjalan di Vercel tapi env DB_HOST belum terdeteksi
if (($host === "localhost" || $host === "127.0.0.1") && (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || getenv('VERCEL'))) {
    $detected_host = get_env_var('DB_HOST', '(tidak terdeteksi)');
    $detected_port = get_env_var('DB_PORT', '(tidak terdeteksi)');
    $detected_user = get_env_var('DB_USER', '(tidak terdeteksi)');
    $detected_db   = get_env_var('DB_NAME', '(tidak terdeteksi)');
    $has_pass      = get_env_var('DB_PASS') !== '' ? '✅ Terisi' : '❌ Kosong';

    die("<div style='font-family:sans-serif; padding:24px; background:#fff; color:#333; border:1px solid #ddd; border-radius:12px; max-width:650px; margin:40px auto; box-shadow:0 4px 20px rgba(0,0,0,0.08); line-height:1.6;'>
        <h2 style='color:#d97706; margin-top:0;'>⚠️ Environment Variables Belum Terhubung</h2>
        <p>Aplikasi berjalan di Vercel, tetapi variabel database dari TiDB Cloud belum terdeteksi oleh sistem.</p>
        
        <table style='width:100%; border-collapse:collapse; margin:16px 0; font-size:14px;'>
            <tr style='background:#f3f4f6; text-align:left;'>
                <th style='padding:8px; border:1px solid #e5e7eb;'>Key</th>
                <th style='padding:8px; border:1px solid #e5e7eb;'>Status Saat Ini</th>
            </tr>
            <tr><td style='padding:8px; border:1px solid #e5e7eb;'><code>DB_HOST</code></td><td style='padding:8px; border:1px solid #e5e7eb;'>$detected_host</td></tr>
            <tr><td style='padding:8px; border:1px solid #e5e7eb;'><code>DB_PORT</code></td><td style='padding:8px; border:1px solid #e5e7eb;'>$detected_port</td></tr>
            <tr><td style='padding:8px; border:1px solid #e5e7eb;'><code>DB_USER</code></td><td style='padding:8px; border:1px solid #e5e7eb;'>$detected_user</td></tr>
            <tr><td style='padding:8px; border:1px solid #e5e7eb;'><code>DB_NAME</code></td><td style='padding:8px; border:1px solid #e5e7eb;'>$detected_db</td></tr>
            <tr><td style='padding:8px; border:1px solid #e5e7eb;'><code>DB_PASS</code></td><td style='padding:8px; border:1px solid #e5e7eb;'>$has_pass</td></tr>
        </table>

        <h3 style='margin-bottom:8px;'>Langkah Solusi di Vercel:</h3>
        <ol style='padding-left:20px; color:#4b5563;'>
            <li>Buka <b>Settings > Environment Variables</b> di dashboard Vercel.</li>
            <li>Pastikan saat menambahkan variabel, centang <b>Production</b>, <b>Preview</b>, dan <b>Development</b>.</li>
            <li>Buka tab <b>Deployments</b> > klik titik tiga (<code>...</code>) pada baris deployment paling atas > pilih <b>Redeploy</b> (tanpa centang Use existing build cache).</li>
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
