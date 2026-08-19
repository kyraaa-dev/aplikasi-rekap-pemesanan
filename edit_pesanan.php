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

            header("Location: pesanan.php?notif=edit_sukses");
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
                <h1>Edit Pesanan Mutz</h1>
                <p style="margin: 4px 0 0 0; color: var(--gray); font-size: 0.875rem;">Perbarui data rincian pemesanan mutz ASN & KORPRI</p>
            </div>
            <a href="pesanan.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Kembali ke Daftar
            </a>
        </div>

        <div class="panel" style="max-width: 820px; margin: 1.5rem 0; padding: 2rem; border-radius: 16px; background: var(--white); box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid var(--gray-light);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--gray-light); padding-bottom: 1.25rem; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    Formulir Edit Pesanan #<?= $pesanan['id'] ?>
                </h2>
                <span style="font-size: 0.8rem; background: #EEF2FF; color: #4338CA; padding: 4px 10px; border-radius: 20px; font-weight: 600;">
                    ID: <?= $pesanan['id'] ?>
                </span>
            </div>
            
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
                                <option value="<?= $s['id'] ?>" <?= $pesanan['skpd_id'] == $s['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nama_skpd']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            Nama Pemesan <span style="color: var(--gray); font-weight: 400;">(Opsional)</span>
                        </label>
                        <input type="text" name="nama_pemesan" value="<?= htmlspecialchars($pesanan['nama_pemesan'] ?? '') ?>" placeholder="Masukkan nama pemesan">
                    </div>
                </div>

                <div class="form-grid-3">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            Jenis Kelamin <span style="color: #DC2626;">*</span>
                        </label>
                        <select name="jenis_kelamin" id="jenis_kelamin" required>
                            <option value="Laki-laki" <?= $pesanan['jenis_kelamin'] == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="Perempuan" <?= $pesanan['jenis_kelamin'] == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                            Ukuran Mutz <span style="color: #DC2626;">*</span>
                        </label>
                        <select name="ukuran" id="ukuran" data-selected="<?= $pesanan['ukuran'] ?>" required>
                            <!-- Akan diisi oleh JavaScript berdasarkan pilihan Jenis Kelamin -->
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="12" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="4"></line><line x1="8" y1="4" x2="16" y2="4"></line></svg>
                            Jenis Mutz <span style="color: #DC2626;">*</span>
                        </label>
                        <select name="jenis_mutz" required>
                            <option value="Biasa" <?= (isset($pesanan['jenis_mutz']) && $pesanan['jenis_mutz'] == 'Biasa') ? 'selected' : '' ?>>Biasa (Rp 55.000)</option>
                            <option value="Kepala SKPD" <?= (isset($pesanan['jenis_mutz']) && $pesanan['jenis_mutz'] == 'Kepala SKPD') ? 'selected' : '' ?>>Kepala SKPD (Rp 150.000)</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid-3">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect><polyline points="17 2 12 7 7 2"></polyline></svg>
                            Jumlah Pesanan <span style="color: #DC2626;">*</span>
                        </label>
                        <input type="number" name="jumlah" value="<?= $pesanan['jumlah'] ?>" min="1" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            Status Pembayaran <span style="color: #DC2626;">*</span>
                        </label>
                        <select name="status_bayar" required>
                            <option value="Belum Lunas" <?= $pesanan['status_bayar'] == 'Belum Lunas' ? 'selected' : '' ?>>🔴 Belum Lunas</option>
                            <option value="Lunas" <?= $pesanan['status_bayar'] == 'Lunas' ? 'selected' : '' ?>>🟢 Lunas</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            Status Pengambilan <span style="color: #DC2626;">*</span>
                        </label>
                        <select name="status_pengambilan" required>
                            <option value="Menunggu Diproses" <?= $pesanan['status_pengambilan'] == 'Menunggu Diproses' ? 'selected' : '' ?>>⏳ Menunggu Diproses</option>
                            <option value="Sedang Dibuat" <?= $pesanan['status_pengambilan'] == 'Sedang Dibuat' ? 'selected' : '' ?>>✂️ Sedang Dibuat</option>
                            <option value="Siap Diambil" <?= $pesanan['status_pengambilan'] == 'Siap Diambil' ? 'selected' : '' ?>>📦 Siap Diambil</option>
                            <option value="Sudah Diambil" <?= $pesanan['status_pengambilan'] == 'Sudah Diambil' ? 'selected' : '' ?>>✅ Sudah Diambil</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.75rem;">
                    <label class="form-label-with-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        Catatan Tambahan <span style="color: var(--gray); font-weight: 400;">(Opsional)</span>
                    </label>
                    <input type="text" name="catatan" value="<?= htmlspecialchars($pesanan['catatan'] ?? '') ?>" placeholder="Contoh: Titip ke bagian admin">
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end; align-items: center; border-top: 1px solid var(--gray-light); padding-top: 1.5rem;">
                    <a href="pesanan.php" class="btn btn-secondary" style="padding: 0.7rem 1.5rem; text-decoration: none; border-radius: 8px; font-weight: 600;">
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
    <script>
        (function initEditPesananForm() {
            const jkSelect = document.getElementById('jenis_kelamin');
            const ukuranSelect = document.getElementById('ukuran');
            if (!jkSelect || !ukuranSelect) return;
            
            const selectedSize = ukuranSelect.getAttribute('data-selected');
            
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
                    if (selectedSize && size.toString() === selectedSize.toString()) {
                        opt.selected = true;
                    }
                    ukuranSelect.appendChild(opt);
                });
            }

            updateUkuranOptions();
            jkSelect.addEventListener('change', updateUkuranOptions);
        })();
    </script>
</body>
</html>
