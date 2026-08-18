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
                header("Location: edit_skpd.php?id=$id&notif=edit_sukses");
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
            <h1>Edit SKPD</h1>
        </div>

        
        <div class="panel" style="background: var(--white); border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid var(--gray-light); max-width: 600px;">
            <div class="flex justify-between items-center mb-4">
                <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                    Form Edit SKPD
                </h2>
                <a href="skpd.php" class="btn btn-sm btn-secondary" style="padding: 6px 12px; border-radius: 8px; font-size: 0.825rem; text-decoration: none;">← Kembali</a>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--dark);">Nama SKPD / Instansi <span style="color: #DC2626;">*</span></label>
                    <input type="text" name="nama_skpd" value="<?= htmlspecialchars($skpd['nama_skpd']) ?>" placeholder="Masukkan Nama SKPD" required style="width: 100%; border-radius: 8px; border: 1px solid var(--gray-light); padding: 0.65rem 1rem; box-sizing: border-box;">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--dark);">
                        <span style="display: inline-flex; align-items: center; gap: 5px; color: #059669;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            No. WhatsApp / Kontak Narahubung
                        </span>
                        <span style="font-size:0.75rem; color:var(--gray); font-weight:400;">(Opsional)</span>
                    </label>
                    <input type="text" name="no_wa" value="<?= htmlspecialchars($skpd['no_wa'] ?? '') ?>" placeholder="Contoh: 08123456789" style="width: 100%; border-radius: 8px; border: 1px solid var(--gray-light); padding: 0.65rem 1rem; box-sizing: border-box;">
                </div>
                <button type="submit" class="btn-card-primary" style="padding: 0.75rem 1.5rem; width: 100%;">
                    💾 Update SKPD
                </button>
            </form>
        </div>
    </main>
</body>
</html>
