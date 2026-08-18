<?php
require 'config.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nama_skpd'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        header("Location: skpd.php?error_msg=" . urlencode("Token keamanan CSRF tidak valid. Silakan muat ulang halaman."));
        exit;
    }

    $nama = trim($_POST['nama_skpd'] ?? '');
    if (!empty($nama)) {
        $stmt = $conn->prepare("INSERT INTO skpd (nama_skpd) VALUES (?)");
        if ($stmt) {
            $stmt->bind_param("s", $nama);
            $success = $stmt->execute();
            $stmt->close();
            if ($success) {
                header("Location: skpd.php?notif=simpan_sukses");
                exit;
            }
        }
    }
    header("Location: skpd.php?error_msg=" . urlencode("Gagal menyimpan SKPD"));
    exit;
}

// Delete
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $stmt_del = $conn->prepare("DELETE FROM skpd WHERE id = ?");
    if ($stmt_del) {
        $stmt_del->bind_param("i", $id);
        $stmt_del->execute();
        $stmt_del->close();
    }
    header("Location: skpd.php?notif=hapus_sukses");
    exit;
}

$skpds = $conn->query("
    SELECT s.*, 
           (SELECT COUNT(*) FROM pesanan WHERE skpd_id = s.id AND status_bayar = 'Lunas') as total_lunas,
           (SELECT COUNT(*) FROM pesanan WHERE skpd_id = s.id AND status_bayar = 'Belum Lunas') as total_belum
    FROM skpd s 
    ORDER BY s.nama_skpd ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data SKPD - E-MutZ KORPRI</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <h1>Data SKPD</h1>
        </div>

        <div class="panel">
            <h2>Tambah SKPD Baru</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <div class="form-group flex gap-4 items-center">
                    <input type="text" name="nama_skpd" placeholder="Masukkan Nama SKPD (contoh: Dinas Pendidikan)" required style="max-width: 400px;">
                    <button type="submit" style="white-space:nowrap;">Tambah SKPD</button>
                </div>
            </form>
        </div>
        
        <div class="panel">
            <div class="flex justify-between items-center mb-4">
                <h2>Daftar SKPD Terdaftar</h2>
                <input type="text" id="searchSkpd" onkeyup="filterTable('searchSkpd', 'skpdTable')" placeholder="Cari nama SKPD..." style="width: 250px; padding: 0.5rem 1rem; border-radius: 20px; border: 1px solid var(--gray-light); font-size: 0.9rem;">
            </div>
            <div class="table-responsive">
                <table id="skpdTable">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>Nama SKPD</th>
                            <th>Pesanan Lunas</th>
                            <th>Belum Lunas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; while($row = $skpds->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_skpd']) ?></td>
                            <td>
                                <span style="display: inline-flex; align-items: center; gap: 4px; background-color: #F0FDF4; color: #15803D; padding: 3px 8px; border-radius: 20px; font-weight: 700; font-size: 0.75rem; border: 1px solid #BBF7D0;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    <?= $row['total_lunas'] ?> Lunas
                                </span>
                            </td>
                            <td>
                                <span style="display: inline-flex; align-items: center; gap: 4px; background-color: <?= $row['total_belum'] > 0 ? '#FEF2F2' : '#F3F4F6' ?>; color: <?= $row['total_belum'] > 0 ? '#B91C1C' : '#6B7280' ?>; padding: 3px 8px; border-radius: 20px; font-weight: 700; font-size: 0.75rem; border: 1px solid <?= $row['total_belum'] > 0 ? '#FECACA' : '#E5E7EB' ?>; <?= $row['total_belum'] > 0 ? 'box-shadow: 0 2px 4px rgba(185, 28, 28, 0.1);' : '' ?>">
                                    <?php if($row['total_belum'] > 0): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    <?php endif; ?>
                                    <?= $row['total_belum'] ?> Tunggakan
                                </span>
                            </td>
                            <td>
                                <a href="edit_skpd.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary" style="margin-right: 5px;">Edit</a>
                                <a href="skpd.php?del=<?= $row['id'] ?>" class="btn btn-sm btn-danger btn-confirm" data-confirm-title="Hapus SKPD" data-confirm-text="Hapus SKPD ini? Semua pesanan dari SKPD ini akan ikut terhapus secara permanen!" data-confirm-btn="Ya, Hapus SKPD" data-confirm-color="#EF4444">Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($skpds->num_rows == 0): ?>
                        <tr><td colspan="3" style="text-align:center;">Belum ada data SKPD. Silakan tambahkan terlebih dahulu.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script src="assets/js/script.js?v=<?= time() ?>"></script>
</body>
</html>
