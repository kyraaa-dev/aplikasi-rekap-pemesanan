<?php
require 'config.php';

$msg = '';



if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['skpd_id'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        header("Location: pesanan.php?error_msg=" . urlencode("Token keamanan CSRF tidak valid. Silakan muat ulang halaman."));
        exit;
    }

    $skpd_id = (int)$_POST['skpd_id'];
    $nama_pemesan = trim($_POST['nama_pemesan'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? 'Laki-laki');
    $ukuran = (int)($_POST['ukuran'] ?? 58);
    $jumlah = (int)($_POST['jumlah'] ?? 1);
    $jenis_mutz = trim($_POST['jenis_mutz'] ?? 'Biasa');
    $status_bayar = trim($_POST['status_bayar'] ?? 'Belum Lunas');
    $catatan = trim($_POST['catatan'] ?? '');

    $stmt = $conn->prepare("INSERT INTO pesanan (skpd_id, nama_pemesan, jenis_kelamin, ukuran, jumlah, jenis_mutz, status_bayar, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issiisss", $skpd_id, $nama_pemesan, $jenis_kelamin, $ukuran, $jumlah, $jenis_mutz, $status_bayar, $catatan);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            // Kurangi stok otomatis via prepared statement
            $stmt_stok = $conn->prepare("UPDATE stok_mutz SET jumlah_stok = jumlah_stok - ? WHERE jenis_mutz = ? AND jenis_kelamin = ? AND ukuran = ?");
            if ($stmt_stok) {
                $stmt_stok->bind_param("issi", $jumlah, $jenis_mutz, $jenis_kelamin, $ukuran);
                $stmt_stok->execute();
                $stmt_stok->close();
            }
            
            header("Location: pesanan.php?notif=simpan_sukses");
            exit;
        } else {
            header("Location: pesanan.php?error_msg=" . urlencode("Gagal menyimpan pesanan: " . $conn->error));
            exit;
        }
    }
}

if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    
    // Kembalikan stok sebelum dihapus
    $stmt_old = $conn->prepare("SELECT jenis_mutz, jenis_kelamin, ukuran, jumlah FROM pesanan WHERE id = ?");
    if ($stmt_old) {
        $stmt_old->bind_param("i", $id);
        $stmt_old->execute();
        $res_old = $stmt_old->get_result();
        if ($old = $res_old->fetch_assoc()) {
            $old_jm = $old['jenis_mutz'];
            $old_jk = $old['jenis_kelamin'];
            $old_uk = (int)$old['ukuran'];
            $old_jml = (int)$old['jumlah'];
            
            $stmt_inc = $conn->prepare("UPDATE stok_mutz SET jumlah_stok = jumlah_stok + ? WHERE jenis_mutz = ? AND jenis_kelamin = ? AND ukuran = ?");
            if ($stmt_inc) {
                $stmt_inc->bind_param("issi", $old_jml, $old_jm, $old_jk, $old_uk);
                $stmt_inc->execute();
                $stmt_inc->close();
            }
        }
        $stmt_old->close();
    }
    
    $stmt_del = $conn->prepare("DELETE FROM pesanan WHERE id = ?");
    if ($stmt_del) {
        $stmt_del->bind_param("i", $id);
        $stmt_del->execute();
        $stmt_del->close();
    }

    header("Location: pesanan.php?notif=hapus_sukses");
    exit;
}

if (isset($_GET['lunas'])) {
    $id = (int)$_GET['lunas'];
    $stmt_lunas = $conn->prepare("UPDATE pesanan SET status_bayar = 'Lunas' WHERE id = ?");
    if ($stmt_lunas) {
        $stmt_lunas->bind_param("i", $id);
        $stmt_lunas->execute();
        $stmt_lunas->close();
    }
    header("Location: pesanan.php?notif=bayar_sukses");
    exit;
}

if (isset($_GET['update_status'])) {
    $id = (int)$_GET['update_status'];
    $status = $_GET['status'] ?? '';
    $is_ajax = isset($_GET['ajax']) ? true : false;
    
    $valid_statuses = ['Menunggu Diproses', 'Sedang Dibuat', 'Siap Diambil', 'Sudah Diambil'];
    if (in_array($status, $valid_statuses)) {
        $stmt_status = $conn->prepare("UPDATE pesanan SET status_pengambilan = ? WHERE id = ?");
        if ($stmt_status) {
            $stmt_status->bind_param("si", $status, $id);
            $stmt_status->execute();
            $stmt_status->close();
        }
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        header("Location: pesanan.php?notif=status_sukses");
        exit;
    }
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false]);
        exit;
    }
}

$skpds = $conn->query("SELECT * FROM skpd ORDER BY nama_skpd ASC");

$filter_skpd = isset($_GET['filter_skpd']) ? (int)$_GET['filter_skpd'] : 0;
$filter_status = isset($_GET['filter_status']) ? $conn->real_escape_string($_GET['filter_status']) : '';
$filter_ambil = isset($_GET['filter_ambil']) ? $conn->real_escape_string($_GET['filter_ambil']) : '';

$where = [];
if ($filter_skpd > 0) {
    $where[] = "p.skpd_id = $filter_skpd";
}
if ($filter_status != '') {
    $where[] = "p.status_bayar = '$filter_status'";
}
if ($filter_ambil != '') {
    $where[] = "p.status_pengambilan = '$filter_ambil'";
}

$where_clause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
$limit = count($where) > 0 ? "LIMIT 500" : "LIMIT 50";

$pesanans = $conn->query("
    SELECT p.*, s.nama_skpd 
    FROM pesanan p 
    JOIN skpd s ON p.skpd_id = s.id 
    $where_clause
    ORDER BY p.id DESC $limit
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pesanan Mutz - E-MutZ KORPRI</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2563EB">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-MutZ KORPRI">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/app.css?v=<?= time() ?>">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <div>
                <h1>Input Pesanan Mutz</h1>
                <p style="margin: 4px 0 0 0; color: var(--gray); font-size: 0.875rem;">Kelola pesanan topi mutz, filter data, dan cetak kwitansi</p>
            </div>
        </div>

        
        <div class="panel" style="margin: 1.5rem 0; padding: 0; overflow: hidden;">
            <div style="display: flex; flex-wrap: wrap;">
                <!-- Kiri: Deskripsi & Instruksi -->
                <div style="flex: 1.5; min-width: 350px; padding: 2.5rem; border-right: var(--brutal-border);">
                    <div style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 6px; background: var(--light); color: var(--primary); margin-bottom: 1.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    </div>
                    <h2 style="margin: 0 0 0.5rem 0; font-size: 1.5rem; font-weight: 700; color: var(--dark);">Form Input Pesanan Baru</h2>
                    <p style="color: var(--gray); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">
                        Silakan masukkan data pesanan dengan lengkap. Pastikan ukuran mutz dan jumlah pesanan sudah sesuai sebelum menyimpan data.
                    </p>
                    <ul style="list-style: none; padding: 0; margin: 0; color: var(--gray); font-size: 0.9rem; display: flex; flex-direction: column; gap: 10px;">
                        <li style="display: flex; align-items: center; gap: 8px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Pilih instansi pemesan
                        </li>
                        <li style="display: flex; align-items: center; gap: 8px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Tentukan jenis dan ukuran mutz
                        </li>
                        <li style="display: flex; align-items: center; gap: 8px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Atur status pembayaran
                        </li>
                    </ul>
                </div>
                
                <!-- Kanan: Form Input -->
                <div style="flex: 2; min-width: 400px; padding: 2.5rem;">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        
                        <div class="form-grid">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label-with-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                    SKPD / Instansi <span style="color: #DC2626;">*</span>
                                </label>
                                <select name="skpd_id" required>
                                    <option value="">-- Pilih SKPD --</option>
                                    <?php while($s = $skpds->fetch_assoc()): ?>
                                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_skpd']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label-with-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                    Nama Pemesan <span style="color: var(--gray); font-weight: 400;">(Opsional)</span>
                                </label>
                                <input type="text" name="nama_pemesan" placeholder="Masukkan nama pemesan (jika ada)">
                            </div>
                        </div>

                        <div class="form-grid-3">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label-with-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    Jenis Kelamin <span style="color: #DC2626;">*</span>
                                </label>
                                <select name="jenis_kelamin" id="jenis_kelamin" required>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label-with-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="12" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="4"></line><line x1="8" y1="4" x2="16" y2="4"></line></svg>
                                    Jenis Mutz <span style="color: #DC2626;">*</span>
                                </label>
                                <select name="jenis_mutz" required>
                                    <option value="Biasa">Biasa (Rp 55.000)</option>
                                    <option value="Kepala SKPD">Kepala SKPD (Rp 150.000)</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label-with-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                                    Ukuran <span style="color: #DC2626;">*</span>
                                </label>
                                <select name="ukuran" id="ukuran" required>
                                    <!-- Akan diisi oleh JavaScript berdasarkan pilihan Jenis Kelamin -->
                                </select>
                            </div>
                        </div>

                        <div class="form-grid-3">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label-with-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect><polyline points="17 2 12 7 7 2"></polyline></svg>
                                    Jumlah <span style="color: #DC2626;">*</span>
                                </label>
                                <input type="number" name="jumlah" value="1" min="1" required>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label-with-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                                    Pembayaran <span style="color: #DC2626;">*</span>
                                </label>
                                <select name="status_bayar" required>
                                    <option value="Belum Lunas">Belum Lunas</option>
                                    <option value="Lunas">Lunas</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label-with-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                Catatan Tambahan <span style="color: var(--gray); font-weight: 400;">(Opsional)</span>
                            </label>
                            <input type="text" name="catatan" placeholder="Contoh: Titip ke bagian admin, minta dikemas terpisah, dsb.">
                        </div>

                        <div style="display: flex; justify-content: flex-end; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-light);">
                            <button type="submit" class="btn-card-primary" style="padding: 0.85rem 2rem; border-radius: 6px; font-size: 1.05rem; ;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                Simpan Pesanan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="flex justify-between items-center mb-4" style="flex-wrap: wrap; gap: 10px;">
                <h2>Daftar Pesanan Mutz</h2>
                <form method="GET" action="pesanan.php" id="filterForm" style="display: flex; gap: 10px; align-items: center; background: transparent; padding: 10px 15px; border-radius: 12px; border: var(--brutal-border);">
                    <select name="filter_skpd" onchange="document.getElementById('filterBtn').click()" style="padding: 0.4rem; border-radius: 0px; border: var(--brutal-border); outline: none; background: var(--white); color: var(--dark); box-shadow: 2px 2px 0px #000;">
                        <option value="0">- Semua SKPD -</option>
                        <?php 
                        // Reset pointer SKPD for filter dropdown
                        $skpds->data_seek(0);
                        while($s = $skpds->fetch_assoc()): 
                        ?>
                            <option value="<?= $s['id'] ?>" <?= $filter_skpd == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nama_skpd']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <select name="filter_status" onchange="document.getElementById('filterBtn').click()" style="padding: 0.4rem; border-radius: 0px; border: var(--brutal-border); outline: none; background: var(--white); color: var(--dark); box-shadow: 2px 2px 0px #000;">
                        <option value="">- Semua Pembayaran -</option>
                        <option value="Lunas" <?= $filter_status == 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                        <option value="Belum Lunas" <?= $filter_status == 'Belum Lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                    </select>
                    <select name="filter_ambil" onchange="document.getElementById('filterBtn').click()" style="padding: 0.4rem; border-radius: 0px; border: var(--brutal-border); outline: none; background: var(--white); color: var(--dark); box-shadow: 2px 2px 0px #000;">
                        <option value="">- Semua Pengambilan -</option>
                        <option value="Menunggu Diproses" <?= $filter_ambil == 'Menunggu Diproses' ? 'selected' : '' ?>>Menunggu Diproses</option>
                        <option value="Sedang Dibuat" <?= $filter_ambil == 'Sedang Dibuat' ? 'selected' : '' ?>>Sedang Dibuat</option>
                        <option value="Siap Diambil" <?= $filter_ambil == 'Siap Diambil' ? 'selected' : '' ?>>Siap Diambil</option>
                        <option value="Sudah Diambil" <?= $filter_ambil == 'Sudah Diambil' ? 'selected' : '' ?>>Sudah Diambil</option>
                    </select>
                    <button type="submit" id="filterBtn" class="btn btn-primary" style="display: none;">Filter</button>
                    <?php if($filter_skpd > 0 || $filter_status != '' || $filter_ambil != ''): ?>
                        <a href="pesanan.php" class="btn btn-secondary" style="padding: 0.4rem 1rem; text-decoration: none;">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if($filter_skpd > 0 || $filter_status != '' || $filter_ambil != ''): ?>
            <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); color: var(--primary); padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <strong>Mode Filter Aktif:</strong> Anda sedang melihat rincian pesanan yang difilter (Tampilan Terbatas).
                </div>
                <a href="pesanan.php" style="background: var(--white); color: #EF4444; border: 1px solid #FCA5A5; padding: 6px 14px; border-radius: 6px; font-weight: 600; text-decoration: none; font-size: 0.85rem; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    Hapus Filter
                </a>
            </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table id="pesananTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>SKPD</th>
                            <th>Nama Pemesan</th>
                            <th>Jenis Kelamin</th>
                            <th>Ukuran</th>
                            <th>Jenis Mutz</th>
                            <th>Jml</th>
                            <th style="white-space: nowrap;">Total Harga</th>
                            <th style="min-width: 130px; white-space: nowrap;">Status Bayar</th>
                            <th style="min-width: 160px; white-space: nowrap;">Pengambilan</th>
                            <th style="min-width: 150px;">Catatan</th>
                            <th>Tanggal Input</th>
                            <th style="position: sticky; right: 0; background: var(--light, #F9FAFB); z-index: 2; border-left: 1px solid var(--gray-light, #E5E7EB); box-shadow: -4px 0 10px rgba(0,0,0,0.05);">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; while($row = $pesanans->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_skpd']) ?></td>
                            <td><?= htmlspecialchars($row['nama_pemesan'] ?? '') ?: '-' ?></td>
                            <td>
                                <span style="color: <?= $row['jenis_kelamin'] == 'Laki-laki' ? 'var(--primary)' : '#EC4899' ?>; font-weight: 600;">
                                    <?= $row['jenis_kelamin'] ?>
                                </span>
                            </td>
                            <td><strong><?= $row['ukuran'] ?></strong></td>
                            <td><?= $row['jenis_mutz'] ?></td>
                            <td><?= $row['jumlah'] ?></td>
                            <td>
                                <?php 
                                    $harga = $row['jenis_mutz'] == 'Kepala SKPD' ? HARGA_KEPALA : HARGA_BIASA;
                                    $total = $harga * $row['jumlah'];
                                ?>
                                <div style="font-weight: bold; color: var(--dark);">Rp <?= number_format($total, 0, ',', '.') ?></div>
                            </td>
                            <td>
                                <?php if($row['status_bayar'] == 'Lunas'): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background-color: #4ADE80; color: #000; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; border: 2px solid #000; letter-spacing: 0.5px; box-shadow: 2px 2px 0px #000;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        LUNAS
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background-color: #F87171; color: #000; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; border: 2px solid #000; letter-spacing: 0.5px; box-shadow: 2px 2px 0px #000;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        BELUM LUNAS
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="margin: 0; display: inline-block; width: 100%;">
                                    <?php 
                                        $st = $row['status_pengambilan'];
                                        if($st == 'Menunggu Diproses') {
                                            $bg = '#E5E7EB'; $color = '#000';
                                        } elseif($st == 'Sedang Dibuat') {
                                            $bg = '#93C5FD'; $color = '#000';
                                        } elseif($st == 'Siap Diambil') {
                                            $bg = '#FDE047'; $color = '#000';
                                        } else { // Sudah Diambil
                                            $bg = '#6EE7B7'; $color = '#000';
                                        }
                                    ?>
                                    <select name="status" data-prev="<?= $st ?>" onchange="updateStatus(<?= $row['id'] ?>, this)" style="width: 100%; padding: 0.35rem 0.5rem; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; border-radius: 4px; border: 2px solid #000; background-color: <?= $bg ?>; color: <?= $color ?>; cursor: pointer; outline: none; letter-spacing: 0.2px; box-shadow: 2px 2px 0px #000; transition: transform 0.1s, box-shadow 0.1s;">
                                        <option value="Menunggu Diproses" <?= $st == 'Menunggu Diproses' ? 'selected' : '' ?>>⏳ Menunggu Diproses</option>
                                        <option value="Sedang Dibuat" <?= $st == 'Sedang Dibuat' ? 'selected' : '' ?>>✂️ Sedang Dibuat</option>
                                        <option value="Siap Diambil" <?= $st == 'Siap Diambil' ? 'selected' : '' ?>>📦 Siap Diambil</option>
                                        <option value="Sudah Diambil" <?= $st == 'Sudah Diambil' ? 'selected' : '' ?>>✅ Sudah Diambil</option>
                                    </select>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($row['catatan'] ?? '') ?: '-' ?></td>
                            <td style="font-size: 0.8rem; color: var(--gray); white-space: nowrap;">
                                <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                            </td>
                            <td style="white-space: nowrap; position: sticky; right: 0; background: var(--white); z-index: 1; border-left: 1px solid var(--gray-light, #E5E7EB); box-shadow: -4px 0 10px rgba(0,0,0,0.05);">
                                <div style="display: flex; gap: 6px; align-items: center; justify-content: center;">
                                    <?php if($row['status_bayar'] == 'Belum Lunas'): ?>
                                    <a href="pesanan.php?lunas=<?= $row['id'] ?>" class="btn-confirm btn btn-sm" data-confirm-title="Konfirmasi Pembayaran" data-confirm-text="Tandai pesanan ini sebagai Lunas?" data-confirm-btn="Ya, Lunas" data-confirm-color="#10B981" title="Tandai Lunas" style="background-color: #10B981; color: white; padding: 5px 10px; font-size: 0.8rem; border-radius: 6px; border: 1px solid #000; box-shadow: 2px 2px 0px #000;">
                                        Lunas
                                    </a>
                                    <?php else: ?>
                                    <a href="cetak_kwitansi.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm" title="Cetak Kwitansi" style="background-color: #3B82F6; color: white; padding: 5px 10px; font-size: 0.8rem; border-radius: 6px; border: 1px solid #000; box-shadow: 2px 2px 0px #000;">
                                        Cetak
                                    </a>
                                    <?php endif; ?>
                                    
                                    <a href="edit_pesanan.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary" title="Edit Pesanan" style="padding: 5px 10px; font-size: 0.8rem; border-radius: 6px;">
                                        Edit
                                    </a>
                                    
                                    <?php if($row['status_pengambilan'] == 'Sudah Diambil'): ?>
                                    <a href="proses_retur.php?id=<?= $row['id'] ?>" class="btn btn-sm" title="Proses Retur" style="background-color: #F59E0B; color: white; padding: 5px 10px; font-size: 0.8rem; border-radius: 6px; border: 1px solid #000; box-shadow: 2px 2px 0px #000;">
                                        Retur
                                    </a>
                                    <?php endif; ?>
                                    
                                    <a href="pesanan.php?del=<?= $row['id'] ?>" class="btn btn-sm btn-danger btn-confirm" data-confirm-title="Hapus Pesanan" data-confirm-text="Apakah Anda yakin ingin menghapus pesanan ini secara permanen?" data-confirm-btn="Ya, Hapus" data-confirm-color="#EF4444" title="Hapus Pesanan" style="padding: 5px 10px; font-size: 0.8rem; border-radius: 6px;">
                                        Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($pesanans->num_rows == 0): ?>
                        <tr>
                            <td colspan="13" style="text-align: center; padding: 4rem 1rem;">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0.7;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--gray); margin-bottom: 1rem;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line><path d="M13 8h4"></path><path d="M13 12h4"></path></svg>
                                    <h3 style="font-size: 1.1rem; color: var(--dark); margin-bottom: 0.5rem; font-weight: 600;">Data Kosong</h3>
                                    <p style="color: var(--gray); font-size: 0.9rem; max-width: 300px; margin: 0 auto;">Belum ada pesanan yang sesuai dengan filter Anda, atau data memang belum tersedia.</p>
                                    <?php if($filter_skpd > 0 || $filter_status != '' || $filter_ambil != ''): ?>
                                    <a href="pesanan.php" style="margin-top: 1.5rem; display: inline-block; background: var(--primary); color: white; padding: 0.5rem 1.25rem; border-radius: 6px; text-decoration: none; font-weight: 500; transition: transform 0.2s; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">Reset Filter</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script src="assets/js/script.js?v=<?= time() ?>"></script>
    <script>
    function updateStatus(id, selectElement) {
        const newStatus = selectElement.value;
        const previousStatus = selectElement.getAttribute('data-prev');
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Konfirmasi',
                text: 'Apakah Anda yakin ingin mengubah status menjadi "' + newStatus + '"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2563EB',
                cancelButtonColor: '#EF4444',
                confirmButtonText: 'Ya, Ubah',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    processUpdateStatus(id, selectElement, newStatus, previousStatus);
                } else {
                    selectElement.value = previousStatus;
                }
            });
        } else {
            if (!confirm('Apakah Anda yakin ingin mengubah status menjadi "' + newStatus + '"?')) {
                selectElement.value = previousStatus;
                return;
            }
            processUpdateStatus(id, selectElement, newStatus, previousStatus);
        }
    }

    function processUpdateStatus(id, selectElement, newStatus, previousStatus) {
        selectElement.disabled = true;
        selectElement.style.opacity = '0.7';

        fetch(`pesanan.php?update_status=${id}&status=${encodeURIComponent(newStatus)}&ajax=1`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    selectElement.setAttribute('data-prev', newStatus);
                    let bg;
                    if (newStatus === 'Menunggu Diproses') {
                        bg = '#E5E7EB';
                    } else if (newStatus === 'Sedang Dibuat') {
                        bg = '#93C5FD';
                    } else if (newStatus === 'Siap Diambil') {
                        bg = '#FDE047';
                    } else {
                        bg = '#6EE7B7';
                    }
                    selectElement.style.backgroundColor = bg;
                    selectElement.style.color = '#000';
                    selectElement.style.border = '2px solid #000';
                    selectElement.style.boxShadow = '2px 2px 0px #000';
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Status pesanan berhasil diperbarui',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Gagal', 'Gagal mengupdate status', 'error');
                    } else {
                        alert('Gagal mengupdate status');
                    }
                    selectElement.value = previousStatus;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Terjadi kesalahan koneksi', 'error');
                } else {
                    alert('Terjadi kesalahan koneksi');
                }
                selectElement.value = previousStatus;
            })
            .finally(() => {
                selectElement.disabled = false;
                selectElement.style.opacity = '1';
            });
    }
    </script>
</body>
</html>
