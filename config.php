<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 86400 * 30);
    $is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => 86400 * 30, // 30 days
        'path' => '/',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Global Security Response Headers
if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

// CSRF Protection Helpers
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

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
    $host = get_env_var(['DB_HOST', 'DATABASE_HOST', 'MYSQL_HOST', 'TIDB_HOST'], 'localhost');
    $user = get_env_var(['DB_USER', 'DB_USERNAME', 'DATABASE_USER', 'MYSQL_USER', 'TIDB_USER'], 'root');
    $pass = get_env_var(['DB_PASS', 'DB_PASSWORD', 'DATABASE_PASSWORD', 'MYSQL_PASSWORD'], '');
    $db   = get_env_var(['DB_NAME', 'DB_DATABASE', 'DATABASE_NAME', 'MYSQL_DATABASE', 'TIDB_DATABASE_NAME'], 'rekap_mutz_asn');
    $port = (int)get_env_var(['DB_PORT', 'DATABASE_PORT', 'MYSQL_PORT', 'TIDB_PORT'], '3306');
}

// Cek apakah berjalan di Vercel tapi env DB_HOST belum terdeteksi
if (($host === "localhost" || $host === "127.0.0.1") && (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || getenv('VERCEL'))) {
    $detected_host = get_env_var('DB_HOST', '(tidak terdeteksi)');
    $detected_port = get_env_var('DB_PORT', '(tidak terdeteksi)');
    $detected_user = get_env_var('DB_USER', '(tidak terdeteksi)');
    $detected_db   = get_env_var('DB_NAME', '(tidak terdeteksi)');
    $has_pass      = get_env_var('DB_PASS') !== '' ? '✅ Terisi' : '❌ Kosong';

    $env_keys = implode(', ', array_keys($_ENV));
    $server_keys = implode(', ', array_keys($_SERVER));

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

        <div style='background:#f1f5f9; padding:12px; font-size:12px; overflow-wrap: break-word;'>
            <strong>Debug Info:</strong><br>
            ENV Keys: $env_keys<br>
            SERVER Keys: $server_keys
        </div>

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

    // Auto-migrate missing columns if any
    $check_catatan = @$conn->query("SHOW COLUMNS FROM pesanan LIKE 'catatan'");
    if ($check_catatan && $check_catatan->num_rows == 0) {
        @$conn->query("ALTER TABLE pesanan ADD COLUMN catatan VARCHAR(255) NULL AFTER status_pengambilan");
    }

    $check_wa = @$conn->query("SHOW COLUMNS FROM skpd LIKE 'no_wa'");
    if ($check_wa && $check_wa->num_rows == 0) {
        @$conn->query("ALTER TABLE skpd ADD COLUMN no_wa VARCHAR(30) NULL AFTER nama_skpd");
    }
}

// Authentication Check & Persistent Session
define('AUTH_SECRET_KEY', get_env_var(['AUTH_SECRET', 'JWT_SECRET'], 'emutz_korpri_secret_auth_token_key_' . date('Y')));

function generate_auth_token($username, $password) {
    return hash_hmac('sha256', $username . '::' . $password, AUTH_SECRET_KEY);
}

function verify_and_upgrade_password($conn, $settings_id, $input_password, $stored_hash) {
    if (password_verify($input_password, $stored_hash)) {
        if (password_needs_rehash($stored_hash, PASSWORD_BCRYPT)) {
            $new_hash = password_hash($input_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE settings SET admin_password = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $new_hash, $settings_id);
                $stmt->execute();
                $stmt->close();
            }
        }
        return true;
    } elseif ($input_password === $stored_hash) {
        // Automatically upgrade legacy plaintext password to secure bcrypt hash
        $new_hash = password_hash($input_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE settings SET admin_password = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $new_hash, $settings_id);
            $stmt->execute();
            $stmt->close();
        }
        return true;
    }
    return false;
}

// Auto-relogin via Persistent Cookie if Session Expired (e.g. Vercel serverless idle spin-down)
if ((!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) && isset($_COOKIE['emutz_auth_remember'])) {
    $decoded = base64_decode($_COOKIE['emutz_auth_remember']);
    if ($decoded && strpos($decoded, ':') !== false) {
        list($c_user, $c_token) = explode(':', $decoded, 2);
        $stmt_auth = $conn->prepare("SELECT id, admin_username, admin_password FROM settings WHERE admin_username = ? LIMIT 1");
        if ($stmt_auth) {
            $stmt_auth->bind_param("s", $c_user);
            $stmt_auth->execute();
            $res_auth = $stmt_auth->get_result();
            if ($res_auth && $res_auth->num_rows > 0) {
                $auth_row = $res_auth->fetch_assoc();
                $expected_token = generate_auth_token($auth_row['admin_username'], $auth_row['admin_password']);
                if (hash_equals($expected_token, $c_token)) {
                    $_SESSION['is_logged_in'] = true;
                    $_SESSION['admin_user'] = $auth_row['admin_username'];
                }
            }
            $stmt_auth->close();
        }
    }
}

$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$allowed_pages = ['login.php', 'setup.php'];

if (php_sapi_name() !== 'cli' && !in_array($current_page, $allowed_pages)) {
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
