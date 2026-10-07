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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - E-MutZ KORPRI</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2563EB">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-MutZ KORPRI">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/app.css?v=<?= filemtime("assets/css/app.css") ?>">
    <link rel="icon" type="image/png" href="assets/images/logo.png">

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
                        <label class="form-label-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            Username Admin
                        </label>
                        <input type="text" name="admin_username" value="<?= htmlspecialchars($settings['admin_username'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            Password Baru <span style="color: var(--gray); font-weight: 400;">(Kosongkan jika tidak ingin mengubah)</span>
                        </label>
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
                        <label class="form-label-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            Harga Mutz Biasa (Rp)
                        </label>
                        <input type="number" name="harga_biasa" value="<?= htmlspecialchars($settings['harga_biasa'] ?? '55000') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            Harga Mutz Kepala SKPD (Rp)
                        </label>
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
                        <a href="backup.php?format=sql" class="btn" style="background: #4ADE80; color: #000; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 6px; font-weight: 800; border: var(--brutal-border); box-shadow: var(--shadow);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                            Unduh Cadangan SQL (.sql)
                        </a>
                        <a href="backup.php?format=json" class="btn" style="background: #00E5FF; color: #000; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 6px; font-weight: 800; border: var(--brutal-border); box-shadow: var(--shadow);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            Unduh Cadangan JSON (.json)
                        </a>
                    </div>
                </div>

                <!-- File Upload Section with Basketball Animation -->
                <div class="form-section" style="grid-column: 1 / -1;">
                    <h3>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        Unggah Dokumen
                    </h3>
                    <p style="color: var(--gray); font-size: 0.9rem; margin-bottom: 1.25rem;">
                        Unggah file dokumen penting (Word, Excel, atau PDF). Seret file ke dalam ring basket atau klik area unggah untuk memilih file. Maksimal 10MB per file.
                    </p>

                    <!-- Basketball Hoop Drop Zone -->
                    <div class="basketball-upload-zone" id="basketballUploadZone">
                        <!-- Basketball Court Floor Markings -->
                        <div class="court-flooring">
                            <div class="court-three-pt-arc"></div>
                            <div class="court-paint-key"></div>
                            <div class="court-free-throw-circle"></div>
                        </div>

                        <!-- Backboard, Rim & Net System -->
                        <div class="bb-stage">
                            <div class="bb-mount"></div>
                            <div class="bb-backboard">
                                <div class="bb-target-box"></div>
                            </div>
                            <div class="bb-hoop-wrap" id="hoopWrap">
                                <!-- Layer 1: Back Rim & Back Net Mesh -->
                                <svg class="hoop-svg-back" viewBox="0 0 120 95">
                                    <!-- Back rim arc -->
                                    <ellipse cx="60" cy="14" rx="46" ry="11" fill="none" stroke="#B73200" stroke-width="6" stroke-dasharray="144" stroke-dashoffset="72" />
                                    <!-- Back net strands -->
                                    <g stroke="rgba(255, 255, 255, 0.45)" stroke-width="1.8" fill="none" stroke-linecap="round">
                                        <path d="M22,18 L34,36 L48,54 L52,70 L48,84" />
                                        <path d="M36,18 L48,36 L58,54 L60,70 L58,84" />
                                        <path d="M50,18 L58,36 L66,54 L68,70 L66,84" />
                                        <path d="M70,18 L68,36 L66,54 L64,70 L64,84" />
                                        <path d="M84,18 L76,36 L68,54 L66,70 L68,84" />
                                        <path d="M98,18 L86,36 L74,54 L70,70 L72,84" />
                                    </g>
                                </svg>

                                <!-- Layer 2: 3D Basketball with seam lines & doc icon -->
                                <div class="file-basketball" id="fileBasketball">
                                    <div class="ball-rib-h"></div>
                                    <div class="ball-rib-v"></div>
                                    <div class="ball-rib-c1"></div>
                                    <div class="ball-rib-c2"></div>
                                    <div class="ball-doc-badge" id="ballDocBadge">
                                        <span id="ballBadgeIcon">📄</span>
                                        <span id="ballBadgeText">DOC</span>
                                    </div>
                                </div>

                                <!-- Layer 3: Front Woven Diamond Net & Solid Front Rim -->
                                <svg class="hoop-svg-front" id="hoopFrontSvg" viewBox="0 0 120 95">
                                    <!-- Front woven diamond lattice net -->
                                    <g class="net-woven-front" stroke="#FFFFFF" stroke-width="2" fill="none" stroke-linecap="round">
                                        <!-- Top loop attachments to rim -->
                                        <path d="M16,14 Q22,21 28,14 Q34,21 40,14 Q46,21 52,14 Q58,21 64,14 Q70,21 76,14 Q82,21 88,14 Q94,21 100,14 Q104,18 106,14" stroke="rgba(255,255,255,0.95)" stroke-width="2.5" />
                                        <!-- Diagonal cords left-to-right -->
                                        <path d="M18,17 L32,32 L46,47 L53,63 L51,80" />
                                        <path d="M28,17 L42,32 L56,47 L61,63 L59,80" />
                                        <path d="M40,17 L54,32 L64,47 L67,63 L65,80" />
                                        <path d="M54,17 L64,32 L72,47 L72,63 L70,80" />
                                        <path d="M70,17 L76,32 L78,47 L76,63 L74,80" />
                                        <!-- Diagonal cords right-to-left -->
                                        <path d="M102,17 L88,32 L74,47 L67,63 L69,80" />
                                        <path d="M92,17 L78,32 L64,47 L59,63 L61,80" />
                                        <path d="M80,17 L66,32 L56,47 L53,63 L55,80" />
                                        <path d="M66,17 L56,32 L48,47 L48,63 L50,80" />
                                        <path d="M50,17 L44,32 L42,47 L44,63 L46,80" />
                                        <!-- Knotted horizontal rings -->
                                        <ellipse cx="60" cy="32" rx="36" ry="6" stroke="rgba(255,255,255,0.7)" stroke-width="1.6" stroke-dasharray="4,4" />
                                        <ellipse cx="60" cy="47" rx="26" ry="5" stroke="rgba(255,255,255,0.7)" stroke-width="1.6" stroke-dasharray="4,4" />
                                        <ellipse cx="60" cy="63" rx="18" ry="4" stroke="rgba(255,255,255,0.7)" stroke-width="1.6" stroke-dasharray="3,3" />
                                        <!-- Bottom rim frills -->
                                        <ellipse cx="60" cy="80" rx="14" ry="3.5" stroke="#FFFFFF" stroke-width="2" />
                                    </g>
                                    <!-- Solid Front Rim (Orange regulation ring with highlight) -->
                                    <ellipse cx="60" cy="14" rx="46" ry="11" fill="none" stroke="#FF5722" stroke-width="6.5" stroke-dasharray="144" stroke-dashoffset="0" />
                                    <ellipse cx="60" cy="13" rx="44" ry="9.5" fill="none" stroke="#FFA726" stroke-width="2" stroke-dasharray="144" stroke-dashoffset="0" />
                                    <!-- Rim mounting bracket joint -->
                                    <rect x="52" y="3" width="16" height="11" rx="2" fill="#D84315" stroke="#222" stroke-width="1.5" />
                                </svg>

                                <!-- Slam Dunk Celebration Comic Popup -->
                                <div class="dunk-fx-layer" id="dunkFxLayer">
                                    <div class="dunk-badge-popup" id="dunkBadgeText">🔥 SLAM DUNK! 🎯</div>
                                    <div class="dunk-sparks" id="dunkSparks"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Drag Target Aiming Guide (Visible during dragover) -->
                        <div class="drag-aim-guide" id="dragAimGuide">
                            <div class="aim-crosshair"></div>
                            <div class="aim-callout-text">🎯 LEPASKAN UNTUK SLAM DUNK!</div>
                        </div>

                        <!-- Drop Zone Center Content -->
                        <div class="drop-content" id="dropContent">
                            <div class="drop-status-pill">🏀 AREA SLAM DUNK DOKUMEN</div>
                            <h4 class="drop-title">Seret & Masukkan File ke Ring Basket!</h4>
                            <p class="drop-subtitle">Tarik berkas Anda ke atas ring atau pilih file dari komputer</p>
                            <div class="browse-btn-wrap">
                                <button type="button" class="browse-action-btn" id="browseBtn" onclick="document.getElementById('fileInput').click()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                                    Pilih Berkas Dokumen
                                </button>
                            </div>
                            <div class="file-format-pills">
                                <span class="pill-format pill-word">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="4" fill="#2B579A"/><text x="12" y="17" fill="white" font-size="12" font-weight="900" text-anchor="middle">W</text></svg>
                                    Word (.doc, .docx)
                                </span>
                                <span class="pill-format pill-excel">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="4" fill="#217346"/><text x="12" y="17" fill="white" font-size="12" font-weight="900" text-anchor="middle">X</text></svg>
                                    Excel (.xls, .xlsx)
                                </span>
                                <span class="pill-format pill-pdf">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="4" fill="#D32F2F"/><text x="12" y="17" fill="white" font-size="12" font-weight="900" text-anchor="middle">P</text></svg>
                                    PDF (.pdf)
                                </span>
                            </div>
                        </div>

                        <input type="file" id="fileInput" accept=".doc,.docx,.xls,.xlsx,.pdf" style="display: none;">
                    </div>

                    <!-- Upload Progress Bar -->
                    <div class="upload-progress-container" id="uploadProgressContainer" style="display: none;">
                        <div class="upload-progress-info">
                            <span id="uploadFileName" style="font-weight: 800;"></span>
                            <span id="uploadPercent" style="font-weight: 900; color: #FF6B00;">0%</span>
                        </div>
                        <div class="upload-progress-bar">
                            <div class="upload-progress-fill" id="uploadProgressFill"></div>
                        </div>
                    </div>

                    <!-- Uploaded Files List Section -->
                    <div class="uploaded-files-section" id="uploadedFilesSection">
                        <div class="uploaded-files-header">
                            <h4 style="margin: 0; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                                Dokumen Terunggah
                            </h4>
                            <span class="uploaded-files-count" id="uploadedFilesCount">0 File</span>
                        </div>
                        <div id="uploadedFilesList" class="uploaded-files-list">
                            <!-- Files loaded dynamically via JS -->
                        </div>
                        <div id="noFilesMessage" class="no-files-message" style="display: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="var(--gray)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.6;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                            <p style="margin: 0; font-weight: 700;">Belum ada dokumen yang diunggah</p>
                            <span style="font-size: 0.8rem; color: var(--gray);">Tarik berkas ke ring basket di atas untuk memulai</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn-card-primary" style="padding: 0.85rem 1.75rem; border-radius: 6px; font-size: 1.05rem; ;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    if(localStorage.getItem('theme') === 'dark') document.body.setAttribute('data-theme', 'dark');

    (function() {
        const zone = document.getElementById('basketballUploadZone');
        const fileInput = document.getElementById('fileInput');
        const hoopWrap = document.getElementById('hoopWrap');
        const hoopFrontSvg = document.getElementById('hoopFrontSvg');
        const fileBasketball = document.getElementById('fileBasketball');
        const ballBadgeIcon = document.getElementById('ballBadgeIcon');
        const ballBadgeText = document.getElementById('ballBadgeText');
        const dunkFxLayer = document.getElementById('dunkFxLayer');
        const dunkBadgeText = document.getElementById('dunkBadgeText');
        const dunkSparks = document.getElementById('dunkSparks');
        const dropContent = document.getElementById('dropContent');
        const progressContainer = document.getElementById('uploadProgressContainer');
        const progressFill = document.getElementById('uploadProgressFill');
        const uploadFileName = document.getElementById('uploadFileName');
        const uploadPercent = document.getElementById('uploadPercent');
        const uploadedFilesList = document.getElementById('uploadedFilesList');
        const uploadedFilesCount = document.getElementById('uploadedFilesCount');
        const noFilesMessage = document.getElementById('noFilesMessage');
        const csrfToken = '<?= generate_csrf_token() ?>';

        let dragCounter = 0;
        let isAnimating = false;

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Drag enter
        zone.addEventListener('dragenter', function(e) {
            dragCounter++;
            zone.classList.add('drag-over');
        });

        // Drag leave
        zone.addEventListener('dragleave', function(e) {
            dragCounter--;
            if (dragCounter <= 0) {
                dragCounter = 0;
                zone.classList.remove('drag-over');
            }
        });

        // Drop handler
        zone.addEventListener('drop', function(e) {
            dragCounter = 0;
            zone.classList.remove('drag-over');

            const files = e.dataTransfer.files;
            if (files && files.length > 0) {
                handleFileSelection(files[0]);
            }
        });

        // Browse File Input
        fileInput.addEventListener('change', function() {
            if (fileInput.files && fileInput.files.length > 0) {
                handleFileSelection(fileInput.files[0]);
                fileInput.value = ''; // Reset input agar bisa pilih file yang sama
            }
        });

        function handleFileSelection(file) {
            if (isAnimating) return;
            playBasketballAnimation(file, () => {
                uploadFile(file);
            });
        }

        // Basketball Slam Dunk Animation
        function playBasketballAnimation(file, onComplete) {
            isAnimating = true;

            const ext = (file.name.split('.').pop() || '').toLowerCase();
            let icon = '📄';
            let label = 'DOC';
            if (['xls', 'xlsx'].includes(ext)) { icon = '📊'; label = 'XLS'; }
            if (ext === 'pdf') { icon = '📕'; label = 'PDF'; }

            ballBadgeIcon.textContent = icon;
            ballBadgeText.textContent = label;

            // Fade center content slightly so court focus is on dunk
            dropContent.style.opacity = '0.2';
            dropContent.style.transform = 'scale(0.96)';

            // Clean previous animation state
            fileBasketball.classList.remove('animating-dunk');
            hoopWrap.classList.remove('hoop-dunk-hit');
            hoopFrontSvg.classList.remove('net-swish-active');
            dunkFxLayer.classList.remove('show-celebration');
            dunkSparks.innerHTML = '';

            // Generate particles
            const celebrations = ['🔥 SLAM DUNK! 🎯', '⚡ SWISH! +3 PTS', '🏀 RIM ROCKER! 🔥', '💥 KABOOM! 🎯'];
            dunkBadgeText.textContent = celebrations[Math.floor(Math.random() * celebrations.length)];

            // Spawn spark dots around the hoop
            for (let i = 0; i < 8; i++) {
                const spark = document.createElement('div');
                spark.className = 'spark-particle';
                const angle = (i / 8) * Math.PI * 2;
                const distance = 35 + Math.random() * 30;
                const tx = Math.cos(angle) * distance;
                const ty = Math.sin(angle) * distance - 10;
                spark.style.left = '60px';
                spark.style.top = '30px';
                spark.style.transition = 'all 0.6s cubic-bezier(0.2, 0.8, 0.2, 1)';
                spark.style.transform = `translate(0, 0) scale(1)`;
                spark.style.opacity = '1';
                dunkSparks.appendChild(spark);

                setTimeout(() => {
                    spark.style.transform = `translate(${tx}px, ${ty}px) scale(0)`;
                    spark.style.opacity = '0';
                }, 520);
            }

            // Start Ball Flight
            requestAnimationFrame(() => {
                fileBasketball.classList.add('animating-dunk');
            });

            // Impact Moment: Rim flex + Net swish + Comic popup
            setTimeout(() => {
                hoopWrap.classList.add('hoop-dunk-hit');
                hoopFrontSvg.classList.add('net-swish-active');
                dunkFxLayer.classList.add('show-celebration');
            }, 500);

            // Cleanup & callback to upload
            setTimeout(() => {
                fileBasketball.classList.remove('animating-dunk');
                hoopWrap.classList.remove('hoop-dunk-hit');
                hoopFrontSvg.classList.remove('net-swish-active');
                dunkFxLayer.classList.remove('show-celebration');
                
                dropContent.style.opacity = '1';
                dropContent.style.transform = 'scale(1)';
                isAnimating = false;

                if (typeof onComplete === 'function') {
                    onComplete();
                }
            }, 1250);
        }

        // Upload File Function
        function uploadFile(file) {
            const allowedExts = ['doc', 'docx', 'xls', 'xlsx', 'pdf'];
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            
            if (!allowedExts.includes(ext)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Format Tidak Didukung!',
                    text: 'Hanya dokumen Word (.doc, .docx), Excel (.xls, .xlsx), dan PDF (.pdf) yang diperbolehkan.',
                    confirmButtonColor: '#000',
                });
                return;
            }

            // Batas 10MB
            if (file.size > 10 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'Ukuran Terlalu Besar!',
                    text: 'Ukuran dokumen maksimal adalah 10MB.',
                    confirmButtonColor: '#000',
                });
                return;
            }

            const formData = new FormData();
            formData.append('upload_file', file);
            formData.append('csrf_token', csrfToken);

            uploadFileName.textContent = file.name;
            uploadPercent.textContent = '0%';
            progressFill.style.width = '0%';
            progressContainer.style.display = 'block';

            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const pct = Math.round((e.loaded / e.total) * 100);
                    progressFill.style.width = pct + '%';
                    uploadPercent.textContent = pct + '%';
                }
            });

            xhr.addEventListener('load', function() {
                progressFill.style.width = '100%';
                uploadPercent.textContent = '100%';

                setTimeout(() => {
                    progressContainer.style.display = 'none';
                }, 600);

                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.session_expired) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Sesi Telah Berakhir',
                            text: 'Silakan login kembali untuk melanjutkan.',
                            confirmButtonColor: '#000',
                        }).then(() => { window.location.href = 'login.php'; });
                        return;
                    }

                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '🏀 Slam Dunk Berhasil!',
                            html: `<strong>${escapeHtml(res.file.name)}</strong> telah aman tersimpan.<br><small style="color:#666;">${escapeHtml(res.file.type)} — ${escapeHtml(res.file.size)}</small>`,
                            confirmButtonColor: '#000',
                            timer: 3000,
                            timerProgressBar: true,
                        });
                        loadUploadedFiles();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Mengunggah',
                            text: res.message || 'Terjadi kesalahan.',
                            confirmButtonColor: '#000',
                        });
                    }
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Respon Tidak Valid',
                        text: 'Terjadi kesalahan pada respon server.',
                        confirmButtonColor: '#000',
                    });
                }
            });

            xhr.addEventListener('error', function() {
                progressContainer.style.display = 'none';
                Swal.fire({
                    icon: 'error',
                    title: 'Koneksi Terputus',
                    text: 'Tidak dapat terhubung ke server.',
                    confirmButtonColor: '#000',
                });
            });

            xhr.open('POST', 'proses_upload.php');
            xhr.send(formData);
        }

        // Helper escape HTML
        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Load Uploaded Files List
        function loadUploadedFiles() {
            fetch('api_files.php?action=list')
                .then(r => r.json())
                .then(data => {
                    if (data.session_expired) {
                        return;
                    }

                    if (!data.success || !data.files || data.files.length === 0) {
                        uploadedFilesList.innerHTML = '';
                        uploadedFilesCount.textContent = '0 File';
                        noFilesMessage.style.display = 'flex';
                        return;
                    }

                    noFilesMessage.style.display = 'none';
                    uploadedFilesCount.textContent = `${data.files.length} File`;

                    uploadedFilesList.innerHTML = data.files.map(f => {
                        const extClass = (f.extension || '').toLowerCase();
                        let badgeBg = '#2B579A';
                        if (['xls', 'xlsx'].includes(extClass)) badgeBg = '#217346';
                        if (extClass === 'pdf') badgeBg = '#D32F2F';

                        const displayName = f.display_name || f.name;

                        return `
                        <div class="uploaded-file-card" data-filename="${escapeHtml(f.name)}">
                            <div class="file-card-icon" style="background: ${badgeBg};">
                                <span style="font-size: 1.35rem;">${f.icon || '📄'}</span>
                            </div>
                            <div class="file-card-info">
                                <p class="file-card-name" title="${escapeHtml(f.name)}">${escapeHtml(displayName)}</p>
                                <div class="file-card-meta">
                                    <span class="file-ext-badge" style="background: ${badgeBg};">${escapeHtml(f.extension)}</span>
                                    <span>${escapeHtml(f.size)}</span>
                                    <span>•</span>
                                    <span>${escapeHtml(f.uploaded_at)}</span>
                                </div>
                            </div>
                            <div class="file-card-actions">
                                <a href="${escapeHtml(f.url)}" download class="file-action-btn file-action-download" title="Unduh File">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                </a>
                                <button type="button" class="file-action-btn file-action-delete" title="Hapus File" data-filename="${escapeHtml(f.name)}" data-display-name="${escapeHtml(displayName)}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </div>
                        </div>`;
                    }).join('');
                })
                .catch(() => {
                    noFilesMessage.style.display = 'flex';
                });
        }

        // Event Delegation for Delete Button (Bulletproof across all browsers & characters)
        uploadedFilesList.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.file-action-delete');
            if (!deleteBtn) return;
            e.preventDefault();

            const card = deleteBtn.closest('.uploaded-file-card');
            const filename = deleteBtn.getAttribute('data-filename');
            const displayName = deleteBtn.getAttribute('data-display-name') || filename;

            if (!filename) return;

            confirmDeleteFile(filename, displayName, card, deleteBtn);
        });

        // Delete File with custom confirmation & smooth animation
        function confirmDeleteFile(filename, displayName, card, deleteBtn) {
            Swal.fire({
                title: 'Hapus Dokumen?',
                html: `Apakah Anda yakin ingin menghapus <strong>${escapeHtml(displayName)}</strong>?<br><span style="font-size:0.85rem; color:#888;">File akan dihapus permanen dari server.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FF0055',
                cancelButtonColor: '#000',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteBtn.disabled = true;
                    deleteBtn.classList.add('is-loading');

                    const formData = new FormData();
                    formData.append('csrf_token', csrfToken);
                    formData.append('file', filename);

                    fetch('api_files.php?action=delete', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.session_expired) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Sesi Berakhir',
                                text: 'Silakan login kembali.',
                                confirmButtonColor: '#000',
                            }).then(() => { window.location.href = 'login.php'; });
                            return;
                        }

                        if (data.success) {
                            // Smooth exit transition
                            if (card) {
                                card.classList.add('card-deleting');
                                setTimeout(() => {
                                    card.remove();
                                    const remainingCards = uploadedFilesList.querySelectorAll('.uploaded-file-card');
                                    uploadedFilesCount.textContent = `${remainingCards.length} File`;
                                    if (remainingCards.length === 0) {
                                        noFilesMessage.style.display = 'flex';
                                    }
                                }, 450);
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Terhapus!',
                                text: 'Dokumen berhasil dihapus dari server.',
                                timer: 1800,
                                showConfirmButton: false,
                            });
                        } else {
                            deleteBtn.disabled = false;
                            deleteBtn.classList.remove('is-loading');
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Menghapus',
                                text: data.message || 'Terjadi kesalahan saat menghapus file.',
                                confirmButtonColor: '#000',
                            });
                        }
                    })
                    .catch(() => {
                        deleteBtn.disabled = false;
                        deleteBtn.classList.remove('is-loading');
                        Swal.fire({
                            icon: 'error',
                            title: 'Koneksi Gagal',
                            text: 'Gagal menghubungi server.',
                            confirmButtonColor: '#000',
                        });
                    });
                }
            });
        }

        // Inisialisasi daftar file saat halaman dimuat
        loadUploadedFiles();
    })();
    </script>
</body>
</html>
