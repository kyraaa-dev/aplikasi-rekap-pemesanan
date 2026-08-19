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
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4F46E5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-MutZ KORPRI">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
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
            <div>
                <h1>Pengaturan Aplikasi</h1>
                <p style="margin: 4px 0 0 0; color: var(--gray); font-size: 0.875rem;">Kelola harga satuan topi mutz, kredensial admin, dan cadangan database</p>
            </div>
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

                <!-- Database Backup Section -->
                <div class="form-section" style="grid-column: 1 / -1;">
                    <h3>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Pencadangan Database (Backup 1-Klik)
                    </h3>
                    <p style="color: var(--gray); font-size: 0.9rem; margin-bottom: 1.25rem;">
                        Unduh salinan lengkap seluruh data sistem (Data SKPD, Semua Pesanan, Stok Gudang, Log Retur, dan Pengaturan) ke komputer Anda secara berkala sebagai arsip yang aman.
                    </p>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <a href="backup.php?format=sql" class="btn" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 12px rgba(16,185,129,0.3); transition: transform 0.15s ease;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                            Unduh Cadangan SQL (.sql)
                        </a>
                        <a href="backup.php?format=json" class="btn" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%); color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 12px rgba(79,70,229,0.3); transition: transform 0.15s ease;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            Unduh Cadangan JSON (.json)
                        </a>
                    </div>
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
