<?php
require 'config.php';

$msg = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    header("Location: skpd.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nama_skpd'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        header("Location: edit_skpd.php?id=$id&error_msg=" . urlencode("Token keamanan CSRF tidak valid. Silakan muat ulang halaman."));
        exit;
    }

    $nama = trim($_POST['nama_skpd'] ?? '');
    $no_wa = trim($_POST['no_wa'] ?? '');
    if (!empty($nama)) {
        $stmt = $conn->prepare("UPDATE skpd SET nama_skpd = ?, no_wa = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssi", $nama, $no_wa, $id);
            $success = $stmt->execute();
            $stmt->close();
            if ($success) {
                header("Location: skpd.php?notif=edit_sukses");
                exit;
            }
        }
    }
    header("Location: edit_skpd.php?id=$id&error_msg=" . urlencode("Gagal mengupdate SKPD"));
    exit;
}

$stmt_find = $conn->prepare("SELECT * FROM skpd WHERE id = ? LIMIT 1");
$skpd = null;
if ($stmt_find) {
    $stmt_find->bind_param("i", $id);
    $stmt_find->execute();
    $res_find = $stmt_find->get_result();
    $skpd = $res_find->fetch_assoc();
    $stmt_find->close();
}
if (!$skpd) {
    echo "SKPD tidak ditemukan.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit SKPD - E-MutZ KORPRI</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4F46E5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-MutZ KORPRI">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <div>
                <h1>Edit SKPD</h1>
                <p style="margin: 4px 0 0 0; color: var(--gray); font-size: 0.875rem;">Perbarui data instansi atau kontak narahubung WhatsApp</p>
            </div>
            <a href="skpd.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Kembali ke Daftar SKPD
            </a>
        </div>

        <div class="panel" style="max-width: 620px; margin: 1.5rem 0; padding: 2rem; border-radius: 16px; background: var(--white); box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid var(--gray-light);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--gray-light); padding-bottom: 1.25rem; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                    Formulir Edit SKPD
                </h2>
                <span style="font-size: 0.8rem; background: #EEF2FF; color: #4338CA; padding: 4px 10px; border-radius: 20px; font-weight: 600;">
                    ID: <?= $skpd['id'] ?>
                </span>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label-with-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        Nama SKPD / Instansi <span style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="nama_skpd" value="<?= htmlspecialchars($skpd['nama_skpd']) ?>" placeholder="Masukkan Nama SKPD" required style="width: 100%; border-radius: 8px; border: 1px solid var(--gray-light); padding: 0.7rem 1rem; box-sizing: border-box; font-size: 0.9rem;">
                </div>

                <div class="form-group" style="margin-bottom: 1.75rem;">
                    <label class="form-label-with-icon">
                        <span style="color: #059669; display: flex; align-items: center; justify-content: center; width: 16px; height: 16px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" /><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" /></svg>
                        </span>
                        No. WhatsApp Narahubung <span style="color: var(--gray); font-weight: 400;">(Opsional)</span>
                    </label>
                    <input type="text" name="no_wa" value="<?= htmlspecialchars($skpd['no_wa'] ?? '') ?>" placeholder="Contoh: 08123456789 atau 628123456789" style="width: 100%; border-radius: 8px; border: 1px solid var(--gray-light); padding: 0.7rem 1rem; box-sizing: border-box; font-size: 0.9rem;">
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end; align-items: center; border-top: 1px solid var(--gray-light); padding-top: 1.5rem;">
                    <a href="skpd.php" class="btn btn-secondary" style="padding: 0.7rem 1.5rem; text-decoration: none; border-radius: 8px; font-weight: 600;">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary" style="padding: 0.7rem 2rem; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
