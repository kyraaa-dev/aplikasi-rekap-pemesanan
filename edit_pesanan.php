<?php
require 'config.php';

$msg = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    header("Location: pesanan.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['skpd_id'])) {
    $skpd_id = (int)$_POST['skpd_id'];
    $nama_pemesan = $conn->real_escape_string($_POST['nama_pemesan']);
    $jenis_kelamin = $conn->real_escape_string($_POST['jenis_kelamin']);
    $ukuran = (int)$_POST['ukuran'];
    $jumlah = (int)$_POST['jumlah'];
    $jenis_mutz = $conn->real_escape_string($_POST['jenis_mutz']);
    $status_bayar = $conn->real_escape_string($_POST['status_bayar']);
    $status_pengambilan = $conn->real_escape_string($_POST['status_pengambilan']);
    $catatan = isset($_POST['catatan']) ? $conn->real_escape_string($_POST['catatan']) : '';

    // Ambil data pesanan lama untuk mengembalikan stok
    $q_old = $conn->query("SELECT jenis_mutz, jenis_kelamin, ukuran, jumlah FROM pesanan WHERE id = $id");
    $old = $q_old->fetch_assoc();

    $sql = "UPDATE pesanan SET 
            skpd_id = $skpd_id, 
            nama_pemesan = '$nama_pemesan', 
            jenis_kelamin = '$jenis_kelamin', 
            ukuran = $ukuran, 
            jumlah = $jumlah,
            jenis_mutz = '$jenis_mutz',
            status_bayar = '$status_bayar',
            status_pengambilan = '$status_pengambilan',
            catatan = '$catatan'
            WHERE id = $id";
    
    if ($conn->query($sql)) {
        if ($old) {
            // Kembalikan stok lama
            $old_jm = $old['jenis_mutz'];
            $old_jk = $old['jenis_kelamin'];
            $old_uk = $old['ukuran'];
            $old_jml = (int)$old['jumlah'];
            $conn->query("UPDATE stok_mutz SET jumlah_stok = jumlah_stok + $old_jml WHERE jenis_mutz = '$old_jm' AND jenis_kelamin = '$old_jk' AND ukuran = $old_uk");
            
            // Kurangi dengan stok baru
            $conn->query("UPDATE stok_mutz SET jumlah_stok = jumlah_stok - $jumlah WHERE jenis_mutz = '$jenis_mutz' AND jenis_kelamin = '$jenis_kelamin' AND ukuran = $ukuran");
        }

        header("Location: edit_pesanan.php?id=$id&notif=edit_sukses");
        exit;
    } else {
        header("Location: edit_pesanan.php?id=$id&error_msg=" . urlencode("Gagal mengupdate pesanan: " . $conn->error));
        exit;
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
