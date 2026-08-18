<?php
require 'config.php';

$msg = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    header("Location: skpd.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nama_skpd'])) {
    $nama = $conn->real_escape_string($_POST['nama_skpd']);

    $sql = "UPDATE skpd SET nama_skpd = '$nama' WHERE id = $id";
    
    if ($conn->query($sql)) {
        header("Location: edit_skpd.php?id=$id&notif=edit_sukses");
        exit;
    } else {
        header("Location: edit_skpd.php?id=$id&error_msg=" . urlencode("Gagal mengupdate SKPD: " . $conn->error));
        exit;
    }
}

$skpd = $conn->query("SELECT * FROM skpd WHERE id = $id")->fetch_assoc();
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
                <div class="form-group">
                    <label>Nama SKPD</label>
                    <input type="text" name="nama_skpd" value="<?= htmlspecialchars($skpd['nama_skpd']) ?>" placeholder="Masukkan Nama SKPD" required>
                </div>
                <button type="submit">Update SKPD</button>
            </form>
        </div>
    </main>
</body>
</html>
