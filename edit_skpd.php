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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit SKPD - E-MutZ KORPRI</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2563EB">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-MutZ KORPRI">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/app.css">
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
            <a href="skpd.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Kembali ke Daftar SKPD
            </a>
        </div>

        <div class="panel responsive-panel" style="max-width: 620px; margin: 1.5rem 0; border-radius: 6px; background: var(--white); box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid var(--gray-light);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--gray-light); padding-bottom: 1.25rem; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                    Formulir Edit SKPD
                </h2>
                <span style="font-size: 0.8rem; background: #EEF2FF; color: #1D4ED8; padding: 4px 10px; border-radius: 6px; font-weight: 600;">
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
                    <input type="text" name="nama_skpd" value="<?= htmlspecialchars($skpd['nama_skpd']) ?>" placeholder="Masukkan Nama SKPD" required style="width: 100%; border-radius: 6px; border: 1px solid var(--gray-light); padding: 0.7rem 1rem; box-sizing: border-box; font-size: 0.9rem;">
                </div>

                <div class="form-group" style="margin-bottom: 1.75rem;">
                    <label class="form-label-with-icon">
                        <span style="color: #059669; display: flex; align-items: center; justify-content: center; width: 16px; height: 16px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="color: #16A34A; flex-shrink: 0;" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </span>
                        No. WhatsApp Narahubung <span style="color: var(--gray); font-weight: 400;">(Opsional)</span>
                    </label>
                    <input type="text" name="no_wa" value="<?= htmlspecialchars($skpd['no_wa'] ?? '') ?>" placeholder="Contoh: 08123456789 atau 628123456789" style="width: 100%; border-radius: 6px; border: 1px solid var(--gray-light); padding: 0.7rem 1rem; box-sizing: border-box; font-size: 0.9rem;">
                </div>

                <div class="form-action-footer">
                    <a href="skpd.php" class="btn btn-secondary" style="padding: 0.7rem 1.5rem; text-decoration: none; border-radius: 6px; font-weight: 600;">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary" style="padding: 0.7rem 2rem; border-radius: 6px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
