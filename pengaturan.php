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
                            <div class="bb-backboard" id="bbBackboard">
                                <div class="bb-target-box"></div>
                                <div class="bb-pad"></div>
                            </div>
                            <div class="bb-hoop-wrap" id="hoopWrap">
                                <!-- Layer 1: Back Rim & Back Net Mesh -->
                                <svg class="hoop-svg-back" viewBox="0 0 130 105">
                                    <!-- Back rim arc (Seamless solid arc curving through top y=4) -->
                                    <path d="M 13,16 A 52,12 0 0,0 117,16" fill="none" stroke="#B73200" stroke-width="7" stroke-linecap="round" />
                                    <path d="M 13,16 A 52,12 0 0,0 117,16" fill="none" stroke="#D84315" stroke-width="5" stroke-linecap="round" />
                                    <!-- Back net strands -->
                                    <g stroke="rgba(255, 255, 255, 0.45)" stroke-width="2" fill="none" stroke-linecap="round">
                                        <path d="M22,20 L35,42 L52,62 L56,80 L52,94" />
                                        <path d="M38,20 L52,42 L62,62 L64,80 L62,94" />
                                        <path d="M54,20 L64,42 L72,62 L74,80 L72,94" />
                                        <path d="M76,20 L74,42 L72,62 L70,80 L70,94" />
                                        <path d="M92,20 L82,42 L74,62 L72,80 L74,94" />
                                        <path d="M108,20 L95,42 L82,62 L78,80 L80,94" />
                                    </g>
                                </svg>

                                <!-- Layer 2: 3D Flying Document File (The Dunker) -->
                                <div class="flying-document-file" id="flyingDocFile">
                                    <div class="doc-file-fold"></div>
                                    <div class="doc-file-header" id="docFileHeader">DOC</div>
                                    <div class="doc-file-body">
                                        <div class="doc-file-emblem" id="docFileEmblem">📄</div>
                                        <div class="doc-file-lines">
                                            <span class="doc-file-line full"></span>
                                            <span class="doc-file-line full"></span>
                                            <span class="doc-file-line short"></span>
                                        </div>
                                    </div>
                                    <div class="doc-motion-aura"></div>
                                </div>

                                <!-- Layer 3: Front Woven Diamond Net & Solid Front Regulation Rim -->
                                <svg class="hoop-svg-front" id="hoopFrontSvg" viewBox="0 0 130 105">
                                    <!-- Front woven diamond lattice net -->
                                    <g class="net-woven-front" stroke="#FFFFFF" stroke-width="2.2" fill="none" stroke-linecap="round">
                                        <!-- Top loop attachments to rim -->
                                        <path d="M16,16 Q23,24 30,16 Q37,24 44,16 Q51,24 58,16 Q65,24 72,16 Q79,24 86,16 Q93,24 100,16 Q107,24 114,16" stroke="rgba(255,255,255,0.95)" stroke-width="2.8" />
                                        <!-- Diagonal cords left-to-right -->
                                        <path d="M18,19 L34,36 L50,53 L58,70 L56,90" />
                                        <path d="M30,19 L46,36 L62,53 L68,70 L66,90" />
                                        <path d="M44,19 L60,36 L72,53 L75,70 L73,90" />
                                        <path d="M60,19 L72,36 L81,53 L81,70 L79,90" />
                                        <path d="M78,19 L85,36 L88,53 L86,70 L84,90" />
                                        <!-- Diagonal cords right-to-left -->
                                        <path d="M112,19 L96,36 L80,53 L72,70 L74,90" />
                                        <path d="M100,19 L84,36 L68,53 L62,70 L64,90" />
                                        <path d="M86,19 L70,36 L58,53 L55,70 L57,90" />
                                        <path d="M70,19 L58,36 L49,53 L49,70 L51,90" />
                                        <path d="M52,19 L45,36 L42,53 L44,70 L46,90" />
                                        <!-- Solid horizontal woven net rings (No broken dashed cuts) -->
                                        <ellipse cx="65" cy="36" rx="40" ry="7" stroke="rgba(255,255,255,0.65)" stroke-width="1.8" fill="none" />
                                        <ellipse cx="65" cy="53" rx="29" ry="5.5" stroke="rgba(255,255,255,0.65)" stroke-width="1.8" fill="none" />
                                        <ellipse cx="65" cy="70" rx="20" ry="4.5" stroke="rgba(255,255,255,0.65)" stroke-width="1.8" fill="none" />
                                        <!-- Bottom rim fringe -->
                                        <ellipse cx="65" cy="90" rx="16" ry="4" stroke="#FFFFFF" stroke-width="2.4" fill="none" />
                                    </g>
                                    <!-- Solid Front Regulation Rim (Seamless solid front arc, vibrant orange, curves through front y=28) -->
                                    <path d="M 13,16 A 52,12 0 0,1 117,16" fill="none" stroke="#D84315" stroke-width="7" stroke-linecap="round" />
                                    <path d="M 13,16 A 52,12 0 0,1 117,16" fill="none" stroke="#FF5722" stroke-width="5.5" stroke-linecap="round" />
                                    <path d="M 16,16.5 A 49,10.5 0 0,1 114,16.5" fill="none" stroke="#FFA726" stroke-width="2" stroke-linecap="round" />
                                    <!-- Rim mounting bracket joint -->
                                    <rect x="56" y="4" width="18" height="12" rx="2" fill="#D84315" stroke="#222" stroke-width="1.8" />
                                    <circle cx="60.5" cy="10" r="1.5" fill="#222" />
                                    <circle cx="69.5" cy="10" r="1.5" fill="#222" />
                                </svg>

                                <!-- Slam Dunk Celebration Comic Popup & Shockwave -->
                                <div class="dunk-fx-layer" id="dunkFxLayer">
                                    <div class="dunk-shockwave" id="dunkShockwave"></div>
                                    <div class="dunk-badge-popup" id="dunkBadgeText">✨ DOKUMEN MASUK! 🎯</div>
                                    <div class="dunk-sparks" id="dunkSparks"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Drag Target Aiming Guide (Visible during dragover) -->
                        <div class="drag-aim-guide" id="dragAimGuide">
                            <div class="aim-crosshair"></div>
                            <div class="aim-callout-text">🎯 LEPASKAN UNTUK MEMASUKKAN DOKUMEN!</div>
                        </div>

                        <!-- Drop Zone Center Content -->
                        <div class="drop-content" id="dropContent">
                            <h4 class="drop-title">Seret & Masukkan Dokumen ke Ring Basket!</h4>
                            <p class="drop-subtitle">Tarik berkas Word, Excel, atau PDF ke ring basket untuk mengunggah</p>
                            <div class="browse-btn-wrap">
                                <label for="fileInput" class="browse-action-btn" id="browseBtn" role="button" tabindex="0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                                    Pilih Berkas Dokumen
                                </label>
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

                        <input type="file" id="fileInput" accept=".doc,.docx,.xls,.xlsx,.pdf,.DOC,.DOCX,.XLS,.XLSX,.PDF,application/pdf" style="display: none;">
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
        const bbBackboard = document.getElementById('bbBackboard');
        const hoopWrap = document.getElementById('hoopWrap');
        const hoopFrontSvg = document.getElementById('hoopFrontSvg');
        const flyingDocFile = document.getElementById('flyingDocFile');
        const docFileHeader = document.getElementById('docFileHeader');
        const docFileEmblem = document.getElementById('docFileEmblem');
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

        // Helper to extract dropped files from DataTransfer
        function extractDroppedFiles(e) {
            const dt = e.dataTransfer;
            if (!dt) return null;
            if (dt.files && dt.files.length > 0) {
                return dt.files;
            }
            if (dt.items && dt.items.length > 0) {
                const arr = [];
                for (let i = 0; i < dt.items.length; i++) {
                    if (dt.items[i].kind === 'file') {
                        const f = dt.items[i].getAsFile();
                        if (f) arr.push(f);
                    }
                }
                if (arr.length > 0) return arr;
            }
            return null;
        }

        // 1. Universal dragover & dragenter with capture phase - signals to browser that ANY drop is accepted
        ['dragenter', 'dragover'].forEach(evtName => {
            window.addEventListener(evtName, function(e) {
                e.preventDefault();
                if (e.dataTransfer) {
                    try { e.dataTransfer.dropEffect = 'copy'; } catch(err) {}
                }
                if (zone && !zone.classList.contains('drag-over')) {
                    zone.classList.add('drag-over');
                }
            }, true);

            document.addEventListener(evtName, function(e) {
                e.preventDefault();
                if (e.dataTransfer) {
                    try { e.dataTransfer.dropEffect = 'copy'; } catch(err) {}
                }
            }, true);

            if (zone) {
                zone.addEventListener(evtName, function(e) {
                    e.preventDefault();
                    if (e.dataTransfer) {
                        try { e.dataTransfer.dropEffect = 'copy'; } catch(err) {}
                    }
                    zone.classList.add('drag-over');
                }, true);
            }
        });

        // 2. Drag leave - cleanly clear visual styling when mouse leaves window
        window.addEventListener('dragleave', function(e) {
            e.preventDefault();
            if (e.clientX <= 0 || e.clientY <= 0 || e.clientX >= window.innerWidth || e.clientY >= window.innerHeight) {
                dragCounter = 0;
                if (zone) zone.classList.remove('drag-over');
            }
        }, true);

        if (zone) {
            zone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                const rect = zone.getBoundingClientRect();
                if (e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom) {
                    zone.classList.remove('drag-over');
                }
            }, false);
        }

        // 3. UNIVERSAL DROP HANDLER on Window (Capture Phase) - Absolutely prevents new tab preview and processes file
        window.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dragCounter = 0;
            if (zone) zone.classList.remove('drag-over');

            const files = extractDroppedFiles(e);
            if (files && files.length > 0) {
                handleFileSelection(files[0]);
            }
        }, true);

        // Also direct drop on zone
        if (zone) {
            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dragCounter = 0;
                zone.classList.remove('drag-over');

                const files = extractDroppedFiles(e);
                if (files && files.length > 0) {
                    handleFileSelection(files[0]);
                }
            }, true);
        }

        // Click zone to browse (ignores click on browse label/buttons)
        if (zone) {
            zone.addEventListener('click', function(e) {
                if (e.target.closest('#browseBtn') || e.target.closest('button') || e.target.closest('a') || isAnimating) return;
                if (fileInput) fileInput.click();
            });
        }

        // Browse File Input
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (fileInput.files && fileInput.files.length > 0) {
                    const selected = fileInput.files[0];
                    handleFileSelection(selected);
                }
            });
        }

        function handleFileSelection(file) {
            if (!file) return;
            isAnimating = false; // Reset lock to guarantee animation runs
            playBasketballAnimation(file, () => {
                uploadFile(file);
            });
        }

        // Basketball & 3D Document Slam Dunk Animation
        function playBasketballAnimation(file, onComplete) {
            isAnimating = true;

            // Safety timeout to prevent locking
            const safetyTimer = setTimeout(() => {
                isAnimating = false;
            }, 3000);

            const ext = (file && file.name ? file.name.split('.').pop() : '').toLowerCase();
            let label = 'DOC';
            let icon = '📄';
            let headerGradient = 'linear-gradient(135deg, #185ABD 0%, #103F91 100%)';

            if (['xls', 'xlsx'].includes(ext)) {
                label = 'EXCEL';
                icon = '📊';
                headerGradient = 'linear-gradient(135deg, #107C41 0%, #0A532B 100%)';
            } else if (ext === 'pdf') {
                label = 'PDF';
                icon = '📕';
                headerGradient = 'linear-gradient(135deg, #E81123 0%, #A80000 100%)';
            } else if (['doc', 'docx'].includes(ext)) {
                label = 'WORD';
                icon = '📄';
                headerGradient = 'linear-gradient(135deg, #185ABD 0%, #103F91 100%)';
            } else {
                label = ext.toUpperCase().slice(0, 5) || 'FILE';
                icon = '📁';
                headerGradient = 'linear-gradient(135deg, #6C5CE7 0%, #4834D4 100%)';
            }

            if (docFileHeader) {
                docFileHeader.textContent = label;
                docFileHeader.style.background = headerGradient;
            }
            if (docFileEmblem) {
                docFileEmblem.textContent = icon;
            }

            // Fade center content slightly so focus is on document slam dunk
            dropContent.style.opacity = '0.12';
            dropContent.style.transform = 'scale(0.96)';

            // Clean previous animation state
            if (flyingDocFile) {
                flyingDocFile.classList.remove('animating-dunk');
                void flyingDocFile.offsetWidth;
            }
            if (bbBackboard) {
                bbBackboard.classList.remove('board-shaking');
                void bbBackboard.offsetWidth;
            }
            if (hoopWrap) {
                hoopWrap.classList.remove('hoop-dunk-hit');
                void hoopWrap.offsetWidth;
            }
            if (hoopFrontSvg) {
                hoopFrontSvg.classList.remove('net-swish-active');
                void hoopFrontSvg.offsetWidth;
            }
            if (dunkFxLayer) {
                dunkFxLayer.classList.remove('show-celebration');
                void dunkFxLayer.offsetWidth;
            }
            if (dunkSparks) dunkSparks.innerHTML = '';

            // Generate celebration shoutout
            if (dunkBadgeText) {
                const celebrations = [
                    '✨ DOKUMEN MASUK! 🎯', 
                    '⚡ BERKAS TERSIMPAN! 📄', 
                    '🎯 SUKSES MASUK! ⚡',
                    '🏆 DOKUMEN TERUNGGAH! 📁'
                ];
                dunkBadgeText.textContent = celebrations[Math.floor(Math.random() * celebrations.length)];
            }

            // Spawn 14 spark & star particles around the rim
            if (dunkSparks) {
                const colors = ['#FF5722', '#FFC107', '#FF9800', '#FFFFFF', '#00FFCC'];
                for (let i = 0; i < 14; i++) {
                    const spark = document.createElement('div');
                    spark.className = 'spark-particle';
                    const angle = (i / 14) * Math.PI * 2;
                    const distance = 42 + Math.random() * 38;
                    const tx = Math.cos(angle) * distance;
                    const ty = Math.sin(angle) * distance - 8;
                    spark.style.left = '65px';
                    spark.style.top = '35px';
                    spark.style.background = colors[i % colors.length];
                    spark.style.transition = 'all 0.65s cubic-bezier(0.2, 0.8, 0.2, 1)';
                    spark.style.transform = 'translate(0, 0) scale(1)';
                    spark.style.opacity = '1';
                    dunkSparks.appendChild(spark);

                    setTimeout(() => {
                        spark.style.transform = `translate(${tx}px, ${ty}px) scale(0)`;
                        spark.style.opacity = '0';
                    }, 480);
                }
            }

            // Start Document Flight into Ring
            if (flyingDocFile) flyingDocFile.classList.add('animating-dunk');

            // Subtle Web Audio sound synthesis for whoosh & dunk impact
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (AudioCtx) {
                    const ctx = new AudioCtx();
                    setTimeout(() => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(400, ctx.currentTime);
                        osc.frequency.exponentialRampToValueAtTime(120, ctx.currentTime + 0.22);
                        gain.gain.setValueAtTime(0.18, ctx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.22);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start();
                        osc.stop(ctx.currentTime + 0.22);
                    }, 480);
                }
            } catch (e) {}

            // Impact Moment @ 480ms: Backboard rattle + Rim flex + Net swish + Comic popup!
            setTimeout(() => {
                if (bbBackboard) bbBackboard.classList.add('board-shaking');
                if (hoopWrap) hoopWrap.classList.add('hoop-dunk-hit');
                if (hoopFrontSvg) hoopFrontSvg.classList.add('net-swish-active');
                if (dunkFxLayer) dunkFxLayer.classList.add('show-celebration');
            }, 480);

            // Cleanup & callback to upload
            setTimeout(() => {
                clearTimeout(safetyTimer);
                if (flyingDocFile) flyingDocFile.classList.remove('animating-dunk');
                if (bbBackboard) bbBackboard.classList.remove('board-shaking');
                if (hoopWrap) hoopWrap.classList.remove('hoop-dunk-hit');
                if (hoopFrontSvg) hoopFrontSvg.classList.remove('net-swish-active');
                if (dunkFxLayer) dunkFxLayer.classList.remove('show-celebration');
                
                if (dropContent) {
                    dropContent.style.opacity = '1';
                    dropContent.style.transform = 'scale(1)';
                }
                isAnimating = false;

                if (typeof onComplete === 'function') {
                    onComplete();
                }
            }, 1250);
        }

        // Upload File Function
        function uploadFile(file) {
            const allowedExts = ['doc', 'docx', 'xls', 'xlsx', 'pdf'];
            const ext = (file && file.name ? file.name.split('.').pop() : '').toLowerCase();
            
            if (!allowedExts.includes(ext)) {
                fileInput.value = '';
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
                fileInput.value = '';
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
            fileInput.value = ''; // Safely reset here after file is captured into FormData

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
                            title: 'Dokumen Berhasil Diunggah',
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
                                <button type="button" class="file-action-btn file-action-delete" title="Hapus Dokumen" aria-label="Hapus Dokumen" data-filename="${escapeHtml(f.name)}" data-display-name="${escapeHtml(displayName)}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18"></path>
                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                    </svg>
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
                html: `<div style="margin:10px 0 16px;"><div style="width:54px; height:54px; margin:0 auto; background:#FEE2E2; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #EF4444;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg></div></div>Apakah Anda yakin ingin menghapus <strong>${escapeHtml(displayName)}</strong>?<br><span style="font-size:0.85rem; color:#888;">File akan dihapus permanen dari server.</span>`,
                showCancelButton: true,
                confirmButtonColor: '#DC2626',
                cancelButtonColor: '#000',
                confirmButtonText: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; margin-right:4px;"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Ya, Hapus!',
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
