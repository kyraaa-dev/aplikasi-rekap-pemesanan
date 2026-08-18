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
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <h1>Edit SKPD</h1>
        </div>

        
        <div class="panel">
            <div class="flex justify-between items-center mb-4">
                <h2>Form Edit SKPD</h2>
                <a href="skpd.php" class="btn btn-sm btn-secondary">Kembali</a>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <div class="form-group">
                    <label>Nama SKPD</label>
                    <input type="text" name="nama_skpd" value="<?= htmlspecialchars($skpd['nama_skpd']) ?>" placeholder="Masukkan Nama SKPD" required>
                </div>
                <div class="form-group">
                    <label>No. WhatsApp / Kontak Narahubung (Opsional)</label>
                    <input type="text" name="no_wa" value="<?= htmlspecialchars($skpd['no_wa'] ?? '') ?>" placeholder="Contoh: 08123456789">
                </div>
                <button type="submit">Update SKPD</button>
            </form>
        </div>
    </main>
</body>
</html>
