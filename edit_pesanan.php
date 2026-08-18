<?php
require 'config.php';

$msg = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    header("Location: pesanan.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['skpd_id'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        header("Location: edit_pesanan.php?id=$id&error_msg=" . urlencode("Token keamanan CSRF tidak valid. Silakan muat ulang halaman."));
        exit;
    }

    $skpd_id = (int)$_POST['skpd_id'];
    $nama_pemesan = trim($_POST['nama_pemesan'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? 'Laki-laki');
    $ukuran = (int)($_POST['ukuran'] ?? 58);
    $jumlah = (int)($_POST['jumlah'] ?? 1);
    $jenis_mutz = trim($_POST['jenis_mutz'] ?? 'Biasa');
    $status_bayar = trim($_POST['status_bayar'] ?? 'Belum Lunas');
    $status_pengambilan = trim($_POST['status_pengambilan'] ?? 'Menunggu Diproses');
    $catatan = trim($_POST['catatan'] ?? '');

    // Ambil data pesanan lama untuk mengembalikan stok via prepared statement
    $stmt_old = $conn->prepare("SELECT jenis_mutz, jenis_kelamin, ukuran, jumlah FROM pesanan WHERE id = ?");
    $old = null;
    if ($stmt_old) {
        $stmt_old->bind_param("i", $id);
        $stmt_old->execute();
        $res_old = $stmt_old->get_result();
        $old = $res_old->fetch_assoc();
        $stmt_old->close();
    }

    $stmt_up = $conn->prepare("UPDATE pesanan SET skpd_id = ?, nama_pemesan = ?, jenis_kelamin = ?, ukuran = ?, jumlah = ?, jenis_mutz = ?, status_bayar = ?, status_pengambilan = ?, catatan = ? WHERE id = ?");
    if ($stmt_up) {
        $stmt_up->bind_param("issiissssi", $skpd_id, $nama_pemesan, $jenis_kelamin, $ukuran, $jumlah, $jenis_mutz, $status_bayar, $status_pengambilan, $catatan, $id);
        $success = $stmt_up->execute();
        $stmt_up->close();

        if ($success) {
            if ($old) {
                // Kembalikan stok lama
                $old_jm = $old['jenis_mutz'];
                $old_jk = $old['jenis_kelamin'];
                $old_uk = (int)$old['ukuran'];
                $old_jml = (int)$old['jumlah'];
                $stmt_rev = $conn->prepare("UPDATE stok_mutz SET jumlah_stok = jumlah_stok + ? WHERE jenis_mutz = ? AND jenis_kelamin = ? AND ukuran = ?");
                if ($stmt_rev) {
                    $stmt_rev->bind_param("issi", $old_jml, $old_jm, $old_jk, $old_uk);
                    $stmt_rev->execute();
                    $stmt_rev->close();
                }
                
                // Kurangi dengan stok baru
                $stmt_ded = $conn->prepare("UPDATE stok_mutz SET jumlah_stok = jumlah_stok - ? WHERE jenis_mutz = ? AND jenis_kelamin = ? AND ukuran = ?");
                if ($stmt_ded) {
                    $stmt_ded->bind_param("issi", $jumlah, $jenis_mutz, $jenis_kelamin, $ukuran);
                    $stmt_ded->execute();
                    $stmt_ded->close();
                }
            }

            header("Location: edit_pesanan.php?id=$id&notif=edit_sukses");
            exit;
        } else {
            header("Location: edit_pesanan.php?id=$id&error_msg=" . urlencode("Gagal mengupdate pesanan: " . $conn->error));
            exit;
        }
    }
}

$pesanan = $conn->query("SELECT * FROM pesanan WHERE id = $id")->fetch_assoc();
if (!$pesanan) {
    echo "Pesanan tidak ditemukan.";
    exit;
}

$skpds = $conn->query("SELECT * FROM skpd ORDER BY nama_skpd ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pesanan - E-MutZ KORPRI</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <h1>Edit Pesanan Mutz</h1>
        </div>

        
        <div class="panel">
            <div class="flex justify-between items-center mb-4">
                <h2>Form Edit Pesanan</h2>
                <a href="pesanan.php" class="btn btn-sm btn-secondary">Kembali</a>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <div class="form-group">
                    <label>SKPD</label>
                    <select name="skpd_id" required>
                        <option value="">-- Pilih SKPD --</option>
                        <?php while($s = $skpds->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>" <?= $pesanan['skpd_id'] == $s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nama_skpd']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nama Pemesan (Opsional)</label>
                    <input type="text" name="nama_pemesan" value="<?= htmlspecialchars($pesanan['nama_pemesan'] ?? '') ?>" placeholder="Masukkan nama pemesan (jika ada)">
                </div>
                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="jenis_kelamin" required>
                        <option value="Laki-laki" <?= $pesanan['jenis_kelamin'] == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="Perempuan" <?= $pesanan['jenis_kelamin'] == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ukuran Mutz</label>
                    <select name="ukuran" id="ukuran" data-selected="<?= $pesanan['ukuran'] ?>" required>
                        <!-- Akan diisi oleh JavaScript berdasarkan pilihan Jenis Kelamin -->
                    </select>
                </div>
                <div class="form-group">
                    <label>Jenis Mutz</label>
                    <select name="jenis_mutz" required>
                        <option value="Biasa" <?= (isset($pesanan['jenis_mutz']) && $pesanan['jenis_mutz'] == 'Biasa') ? 'selected' : '' ?>>Biasa (Rp 55.000)</option>
                        <option value="Kepala SKPD" <?= (isset($pesanan['jenis_mutz']) && $pesanan['jenis_mutz'] == 'Kepala SKPD') ? 'selected' : '' ?>>Kepala SKPD (Rp 150.000)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jumlah Pesanan</label>
                    <input type="number" name="jumlah" value="<?= $pesanan['jumlah'] ?>" min="1" required>
                </div>
                <div class="form-group">
                    <label>Status Pembayaran</label>
                    <select name="status_bayar" required>
                        <option value="Belum Lunas" <?= $pesanan['status_bayar'] == 'Belum Lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                        <option value="Lunas" <?= $pesanan['status_bayar'] == 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status Pengambilan</label>
                    <select name="status_pengambilan" required>
                        <option value="Menunggu Diproses" <?= $pesanan['status_pengambilan'] == 'Menunggu Diproses' ? 'selected' : '' ?>>Menunggu Diproses</option>
                        <option value="Sedang Dibuat" <?= $pesanan['status_pengambilan'] == 'Sedang Dibuat' ? 'selected' : '' ?>>Sedang Dibuat</option>
                        <option value="Siap Diambil" <?= $pesanan['status_pengambilan'] == 'Siap Diambil' ? 'selected' : '' ?>>Siap Diambil</option>
                        <option value="Sudah Diambil" <?= $pesanan['status_pengambilan'] == 'Sudah Diambil' ? 'selected' : '' ?>>Sudah Diambil</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Catatan Tambahan (Opsional)</label>
                    <input type="text" name="catatan" value="<?= htmlspecialchars($pesanan['catatan'] ?? '') ?>" placeholder="Contoh: Titip ke bagian admin">
                </div>
                <button type="submit">Update Pesanan</button>
            </form>
        </div>
    </main>
    <script>
        // Modifikasi script.js khusus untuk halaman edit agar bisa auto-select ukuran
        document.addEventListener('DOMContentLoaded', function() {
            const jkSelect = document.getElementById('jenis_kelamin');
            const ukuranSelect = document.getElementById('ukuran');
            const selectedSize = ukuranSelect.getAttribute('data-selected');
            
            if (jkSelect && ukuranSelect) {
                function updateUkuranOptions() {
                    const jk = jkSelect.value;
                    ukuranSelect.innerHTML = '';
                    
                    let options = [];
                    if (jk === 'Laki-laki') {
                        options = [55, 56, 57, 58, 59, 60];
                    } else if (jk === 'Perempuan') {
                        options = [58, 59, 60];
                    } else {
                        ukuranSelect.innerHTML = '<option value="">-- Pilih Jenis Kelamin Dahulu --</option>';
                        return;
                    }
                    
                    options.forEach(size => {
                        const opt = document.createElement('option');
                        opt.value = size;
                        opt.textContent = size;
                        // Select jika size sama dengan selectedSize
                        if (size.toString() === selectedSize.toString()) {
                            opt.selected = true;
                        }
                        ukuranSelect.appendChild(opt);
                    });
                }

                updateUkuranOptions();
                jkSelect.addEventListener('change', updateUkuranOptions);
            }
        });
    </script>
</body>
</html>
