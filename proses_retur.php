<?php
require 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    header("Location: pesanan.php");
    exit;
}

$stmt_pesanan_init = $conn->prepare("SELECT p.*, s.nama_skpd FROM pesanan p JOIN skpd s ON p.skpd_id = s.id WHERE p.id = ?");
$pesanan = null;
if ($stmt_pesanan_init) {
    $stmt_pesanan_init->bind_param("i", $id);
    $stmt_pesanan_init->execute();
    $res_init = $stmt_pesanan_init->get_result();
    $pesanan = $res_init->fetch_assoc();
    $stmt_pesanan_init->close();
}

if (!$pesanan || $pesanan['status_pengambilan'] != 'Sudah Diambil') {
    echo "Pesanan tidak valid untuk diretur.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['alasan'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        header("Location: proses_retur.php?id=$id&error_msg=" . urlencode("Token keamanan CSRF tidak valid. Silakan muat ulang halaman."));
        exit;
    }

    $alasan = trim($_POST['alasan'] ?? '');
    if ($alasan == 'Lainnya') {
        $alasan = trim($_POST['alasan_lainnya'] ?? 'Lainnya');
    }
    $ukuran_baru = (int)($_POST['ukuran_baru'] ?? 0);
    $ukuran_lama = (int)$pesanan['ukuran'];

    $conn->begin_transaction();
    try {
        // Insert log retur via prepared statement
        $stmt_retur = $conn->prepare("INSERT INTO retur_pesanan (pesanan_id, alasan, ukuran_lama, ukuran_baru) VALUES (?, ?, ?, ?)");
        if (!$stmt_retur) throw new Exception($conn->error);
        $stmt_retur->bind_param("isii", $id, $alasan, $ukuran_lama, $ukuran_baru);
        $stmt_retur->execute();
        $stmt_retur->close();

        // Update pesanan: ganti ukuran dan ubah status menjadi Sedang Dibuat
        $stmt_pesanan = $conn->prepare("UPDATE pesanan SET ukuran = ?, status_pengambilan = 'Sedang Dibuat' WHERE id = ?");
        if (!$stmt_pesanan) throw new Exception($conn->error);
        $stmt_pesanan->bind_param("ii", $ukuran_baru, $id);
        $stmt_pesanan->execute();
        $stmt_pesanan->close();

        // Kembalikan stok ukuran lama via prepared statement
        $jm = $pesanan['jenis_mutz'];
        $jk = $pesanan['jenis_kelamin'];
        $jml = (int)$pesanan['jumlah'];
        
        $stmt_inc = $conn->prepare("UPDATE stok_mutz SET jumlah_stok = jumlah_stok + ? WHERE jenis_mutz = ? AND jenis_kelamin = ? AND ukuran = ?");
        if ($stmt_inc) {
            $stmt_inc->bind_param("issi", $jml, $jm, $jk, $ukuran_lama);
            $stmt_inc->execute();
            $stmt_inc->close();
        }
        
        // Kurangi stok ukuran baru via prepared statement
        $stmt_dec = $conn->prepare("UPDATE stok_mutz SET jumlah_stok = jumlah_stok - ? WHERE jenis_mutz = ? AND jenis_kelamin = ? AND ukuran = ?");
        if ($stmt_dec) {
            $stmt_dec->bind_param("issi", $jml, $jm, $jk, $ukuran_baru);
            $stmt_dec->execute();
            $stmt_dec->close();
        }

        $conn->commit();
        header("Location: retur.php?notif=retur_sukses");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: pesanan.php?error_msg=" . urlencode("Gagal memproses retur: " . $e->getMessage()));
        exit;
    }
}

// Generate options for ukuran based on gender
$options = [];
if ($pesanan['jenis_kelamin'] == 'Laki-laki') {
    $options = [55, 56, 57, 58, 59, 60];
} else {
    $options = [58, 59, 60];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Retur - E-MutZ KORPRI</title>
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
                <h1>Proses Retur Pesanan</h1>
                <p style="margin: 4px 0 0 0; color: var(--gray); font-size: 0.875rem;">Proses penukaran ukuran atau pengembalian barang cacat ke penjahit</p>
            </div>
            <a href="pesanan.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Kembali ke Pesanan
            </a>
        </div>
        
        <div class="panel" style="max-width: 720px; margin: 1.5rem 0; padding: 2rem; border-radius: 6px; background: var(--white); box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid var(--gray-light);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--gray-light); padding-bottom: 1.25rem; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: #D97706; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path><path d="M3 22v-6h6"></path><path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path></svg>
                    Formulir Retur Barang #<?= $pesanan['id'] ?>
                </h2>
                <span style="font-size: 0.8rem; background: #FEF3C7; color: #B45309; padding: 4px 10px; border-radius: 6px; font-weight: 600;">
                    ID Pesanan: <?= $pesanan['id'] ?>
                </span>
            </div>
            
            <div style="background: #F9FAFB; padding: 16px; border-radius: 6px; border: 1px solid #E5E7EB; margin-bottom: 1.5rem;">
                <h3 style="margin: 0 0 10px 0; color: var(--dark); font-size: 0.95rem; font-weight: 700;">Informasi Pesanan Saat Ini</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 0.875rem;">
                    <div><span style="color: var(--gray);">SKPD:</span> <strong style="color: var(--dark);"><?= htmlspecialchars($pesanan['nama_skpd']) ?></strong></div>
                    <div><span style="color: var(--gray);">Pemesan:</span> <strong style="color: var(--dark);"><?= htmlspecialchars($pesanan['nama_pemesan']) ?: '-' ?></strong> (<?= $pesanan['jenis_kelamin'] ?>)</div>
                    <div><span style="color: var(--gray);">Ukuran Lama:</span> <span style="background: #FEE2E2; color: #B91C1C; padding: 2px 8px; border-radius: 6px; font-weight: bold; font-size: 0.825rem;"><?= $pesanan['ukuran'] ?></span></div>
                    <div><span style="color: var(--gray);">Jenis & Qty:</span> <strong style="color: var(--dark);"><?= $pesanan['jenis_mutz'] ?> (<?= $pesanan['jumlah'] ?> pcs)</strong></div>
                </div>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="font-weight: 600; font-size: 0.875rem; color: var(--dark); margin-bottom: 6px; display: block;">Alasan Retur <span style="color: #DC2626;">*</span></label>
                    <select name="alasan" id="alasan" required onchange="toggleAlasanLainnya()" style="width: 100%; border-radius: 6px; border: 1px solid var(--gray-light); padding: 0.7rem 1rem; font-size: 0.9rem;">
                        <option value="">-- Pilih Alasan Retur --</option>
                        <option value="Kekecilan">Kekecilan</option>
                        <option value="Kebesaran">Kebesaran</option>
                        <option value="Barang Cacat / Rusak">Barang Cacat / Rusak</option>
                        <option value="Lainnya">Lainnya...</option>
                    </select>
                </div>
                
                <div class="form-group" id="alasan_lainnya_group" style="display: none; margin-bottom: 1.25rem;">
                    <label style="font-weight: 600; font-size: 0.875rem; color: var(--dark); margin-bottom: 6px; display: block;">Tuliskan Alasan Lainnya <span style="color: #DC2626;">*</span></label>
                    <input type="text" name="alasan_lainnya" id="alasan_lainnya" placeholder="Contoh: Salah kirim jenis mutz" style="width: 100%; border-radius: 6px; border: 1px solid var(--gray-light); padding: 0.7rem 1rem; font-size: 0.9rem; box-sizing: border-box;">
                </div>
                
                <div class="form-group" style="margin-bottom: 1.75rem;">
                    <label style="font-weight: 600; font-size: 0.875rem; color: var(--dark); margin-bottom: 6px; display: block;">Ukuran Pengganti Baru <span style="color: #DC2626;">*</span></label>
                    <select name="ukuran_baru" required style="width: 100%; border-radius: 6px; border: 1px solid var(--gray-light); padding: 0.7rem 1rem; font-size: 0.9rem;">
                        <option value="">-- Pilih Ukuran Baru --</option>
                        <?php foreach($options as $opt): ?>
                            <option value="<?= $opt ?>" <?= $pesanan['ukuran'] == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: var(--gray); display: block; margin-top: 6px; font-size: 0.8rem;">Jika retur karena cacat (bukan tukar ukuran), biarkan ukuran tetap sama.</small>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; align-items: center; border-top: 1px solid var(--gray-light); padding-top: 1.5rem;">
                    <a href="pesanan.php" class="btn btn-secondary" style="padding: 0.7rem 1.5rem; text-decoration: none; border-radius: 6px; font-weight: 600;">
                        Batal
                    </a>
                    <button type="submit" class="btn-card-amber" style="padding: 0.7rem 2rem; border-radius: 6px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path><path d="M3 22v-6h6"></path><path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path></svg>
                        Simpan & Kembalikan ke Penjahit
                    </button>
                </div>
            </form>
        </div>
    </main>
    <script>
        function toggleAlasanLainnya() {
            var val = document.getElementById('alasan').value;
            var group = document.getElementById('alasan_lainnya_group');
            var input = document.getElementById('alasan_lainnya');
            if(val === 'Lainnya') {
                group.style.display = 'block';
                input.required = true;
            } else {
                group.style.display = 'none';
                input.required = false;
                input.value = '';
            }
        }
    </script>
</body>
</html>
