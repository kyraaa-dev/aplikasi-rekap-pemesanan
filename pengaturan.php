<?php
require 'config.php';

// Fetch current settings
$q_settings = $conn->query("SELECT * FROM settings LIMIT 1");
$settings = $q_settings->fetch_assoc();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        header("Location: pengaturan.php?error_msg=" . urlencode("Token keamanan CSRF tidak valid. Silakan muat ulang halaman."));
        exit;
    }

    $username = trim($_POST['admin_username'] ?? '');
    $password = $_POST['admin_password'] ?? '';
    $harga_biasa = (int)($_POST['harga_biasa'] ?? 55000);
    $harga_kepala = (int)($_POST['harga_kepala'] ?? 150000);

    if (!empty($password)) {
        // Hash the new password securely with bcrypt
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE settings SET admin_username = ?, admin_password = ?, harga_biasa = ?, harga_kepala = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssiii", $username, $password_hash, $harga_biasa, $harga_kepala, $settings['id']);
            $success = $stmt->execute();
            $stmt->close();
        }
    } else {
        // Keep existing password
        $stmt = $conn->prepare("UPDATE settings SET admin_username = ?, harga_biasa = ?, harga_kepala = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("siii", $username, $harga_biasa, $harga_kepala, $settings['id']);
            $success = $stmt->execute();
            $stmt->close();
        }
    }

    if (!empty($success)) {
        // Update persistent cookie token if username/password changed
        if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
            $_SESSION['admin_user'] = $username;
            $token_pwd = !empty($password) ? $password_hash : $settings['admin_password'];
            $token = generate_auth_token($username, $token_pwd);
            $cookie_val = base64_encode($username . ':' . $token);
            $is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            setcookie('emutz_auth_remember', $cookie_val, [
                'expires' => time() + (86400 * 30),
                'path' => '/',
                'secure' => $is_https,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        header("Location: pengaturan.php?notif=pengaturan_sukses");
        exit;
    } else {
        header("Location: pengaturan.php?error_msg=" . urlencode("Gagal menyimpan: " . $conn->error));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - E-MutZ KORPRI</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
                gap: 1.25rem;
            }
        }
        @media (max-width: 768px) {
            .settings-grid { grid-template-columns: 1fr; }
        }
        .form-section {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid var(--gray-light);
        }
        .form-section h3 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <h1>Pengaturan Aplikasi</h1>
        </div>
        

        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <div class="settings-grid">
                <!-- Authentication Settings -->
                <div class="form-section">
                    <h3>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Kredensial Login
                    </h3>
                    <div class="form-group">
                        <label>Username Admin</label>
                        <input type="text" name="admin_username" value="<?= htmlspecialchars($settings['admin_username'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Password Baru (Kosongkan jika tidak ingin mengubah)</label>
                        <input type="password" name="admin_password" placeholder="Masukkan password baru...">
                    </div>
                    <p style="font-size: 0.85rem; color: var(--gray);">Catatan: Harap simpan kredensial ini dengan baik. Jika Anda lupa, Anda harus mengatur ulang via Database (phpMyAdmin).</p>
                </div>
                
                <!-- Pricing Settings -->
                <div class="form-section">
                    <h3>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        Harga Pesanan Mutz
                    </h3>
                    <div class="form-group">
                        <label>Harga Mutz Biasa (Rp)</label>
                        <input type="number" name="harga_biasa" value="<?= htmlspecialchars($settings['harga_biasa'] ?? '55000') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Harga Mutz Kepala SKPD (Rp)</label>
                        <input type="number" name="harga_kepala" value="<?= htmlspecialchars($settings['harga_kepala'] ?? '150000') ?>" required>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--gray);">Catatan: Perubahan harga ini akan mempengaruhi kueri total tagihan di menu Dashboard dan Cetak Laporan.</p>
                </div>
            </div>
            
            <div style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem; border-radius: 8px; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);">Simpan Pengaturan</button>
            </div>
        </form>
    </main>
    <script>
        if(localStorage.getItem('theme') === 'dark') document.body.setAttribute('data-theme', 'dark');
    </script>
</body>
</html>
