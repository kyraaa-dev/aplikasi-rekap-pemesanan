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
    header("Location: pesanan.php?error_msg=" . urlencode("Pesanan belum valid untuk diretur (Hanya pesanan berstatus 'Sudah Diambil' yang dapat diproses retur)."));
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

        // Kembalikan stok ukuran lama & kurangi stok ukuran baru (catat kartu stok)
        $jm = $pesanan['jenis_mutz'];
        $jk = $pesanan['jenis_kelamin'];
        $jml = (int)$pesanan['jumlah'];
        
        sesuaikan_stok($conn, $jm, $jk, $ukuran_lama, $jml, "Retur Pesanan #$id (Ukuran $ukuran_lama kembali)", 'Masuk');
        sesuaikan_stok($conn, $jm, $jk, $ukuran_baru, -$jml, "Retur Pesanan #$id (Tukar ke ukuran $ukuran_baru)", 'Keluar');

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Retur Pesanan #<?= $pesanan['id'] ?> - E-MutZ KORPRI</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2563EB">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-MutZ KORPRI">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/app.css?v=<?= filemtime("assets/css/app.css") ?>">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <style>
        .retur-card-container {
            max-width: 760px;
            margin: 1.5rem auto 3rem 0;
            background: #fff;
            border: 3px solid #000;
            box-shadow: 6px 6px 0px #000;
            border-radius: 8px;
            overflow: hidden;
        }
        .visual-comparison-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            background: #F8FAFC;
            border: 2px solid #000;
            border-radius: 8px;
            padding: 16px;
            margin: 1.25rem 0;
            box-shadow: 3px 3px 0 #E2E8F0;
        }
        .size-box {
            text-align: center;
            padding: 10px 18px;
            border-radius: 6px;
            border: 2px solid #000;
            min-width: 100px;
        }
        .size-box-old {
            background: #FEE2E2;
            color: #991B1B;
        }
        .size-box-new {
            background: #D1FAE5;
            color: #065F46;
        }
        .transition-arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            background: #000;
            color: #fff;
            border-radius: 50%;
            font-size: 1.25rem;
            box-shadow: 2px 2px 0 #94A3B8;
        }
        .form-control-brutal {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #000;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            background: #fff;
            box-sizing: border-box;
            transition: all 0.15s ease;
        }
        .form-control-brutal:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <!-- Header -->
        <div class="header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 1.5rem;">
            <div>
                <h1 style="display: flex; align-items: center; gap: 10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color: #D97706;"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path><path d="M3 22v-6h6"></path><path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path></svg>
                    Proses Retur Pesanan
                </h1>
                <p style="margin: 4px 0 0 0; color: var(--gray); font-size: 0.875rem;">Formulir penukaran ukuran atau pengembalian barang cacat jahit ke penjahit mitra</p>
            </div>
            <a href="pesanan.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 0.65rem 1.15rem; border-radius: 6px; font-weight: 700; border: 2px solid #000; box-shadow: 3px 3px 0 #000; background: #fff; color: #000;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Kembali ke Pesanan
            </a>
        </div>
        
        <!-- Formulir Container Neobrutalist -->
        <div class="retur-card-container">
            <!-- Card Header -->
            <div style="background: #FEF08A; border-bottom: 3px solid #000; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: #000; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Formulir Retur Barang
                </h2>
                <span style="font-size: 0.825rem; background: #000; color: #fff; padding: 4px 12px; border-radius: 4px; font-weight: 800; letter-spacing: 0.5px;">
                    ID Pesanan: #<?= $pesanan['id'] ?>
                </span>
            </div>
            
            <div style="padding: 1.5rem;">
                <!-- Ringkasan Pesanan Saat Ini -->
                <div style="background: #F8FAFC; border: 2px solid #000; border-radius: 6px; padding: 16px; margin-bottom: 1.5rem; box-shadow: 3px 3px 0 #CBD5E1;">
                    <h3 style="margin: 0 0 12px 0; color: var(--dark); font-size: 0.95rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px dashed #CBD5E1; padding-bottom: 6px;">
                        📌 Informasi Pesanan Saat Ini
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 0.875rem;">
                        <div>
                            <span style="color: var(--gray); display: block; font-size: 0.8rem; font-weight: 600;">Instansi / SKPD:</span>
                            <strong style="color: var(--dark); font-size: 0.95rem;"><?= htmlspecialchars($pesanan['nama_skpd']) ?></strong>
                        </div>
                        <div>
                            <span style="color: var(--gray); display: block; font-size: 0.8rem; font-weight: 600;">Nama Pemesan:</span>
                            <strong style="color: var(--dark);"><?= htmlspecialchars($pesanan['nama_pemesan']) ?: '-' ?></strong>
                            <span style="font-size: 0.8rem; color: var(--gray);">(<?= $pesanan['jenis_kelamin'] ?>)</span>
                        </div>
                        <div>
                            <span style="color: var(--gray); display: block; font-size: 0.8rem; font-weight: 600;">Jenis Mutz & Jumlah:</span>
                            <strong style="color: var(--dark);"><?= $pesanan['jenis_mutz'] ?></strong> (<?= $pesanan['jumlah'] ?> pcs)
                        </div>
                        <div>
                            <span style="color: var(--gray); display: block; font-size: 0.8rem; font-weight: 600;">Ukuran Lama Saat Ini:</span>
                            <span style="background: #000; color: #fff; padding: 2px 10px; border-radius: 4px; font-weight: 800; font-size: 0.95rem;">
                                Ukuran <?= $pesanan['ukuran'] ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Live Before vs After Visual Comparison Widget -->
                <div class="visual-comparison-box">
                    <div class="size-box size-box-old">
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Ukuran Lama</div>
                        <div style="font-size: 1.5rem; font-weight: 900; line-height: 1.2;" id="displayOldSize"><?= $pesanan['ukuran'] ?></div>
                    </div>
                    
                    <div class="transition-arrow" title="Ditukar Menjadi">
                        &rarr;
                    </div>

                    <div class="size-box size-box-new">
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Ukuran Pengganti</div>
                        <div style="font-size: 1.5rem; font-weight: 900; line-height: 1.2;" id="displayNewSize"><?= $pesanan['ukuran'] ?></div>
                    </div>
                </div>
                <div style="text-align: center; font-size: 0.85rem; font-weight: 700; margin-bottom: 1.5rem; color: #047857;" id="diffSizeNote">
                    Ukuran masih sama (Cocok jika retur karena barang cacat)
                </div>
                
                <!-- Form Input Retur -->
                <form method="POST" id="formProsesRetur" onsubmit="return window.handleSubmitRetur()">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label style="font-weight: 700; font-size: 0.9rem; color: var(--dark); margin-bottom: 6px; display: block;">
                            Alasan Retur <span style="color: #DC2626;">*</span>
                        </label>
                        <select name="alasan" id="alasan" required onchange="toggleAlasanLainnya()" class="form-control-brutal">
                            <option value="">-- Pilih Alasan Retur --</option>
                            <option value="Kekecilan">Kekecilan (Perlu ukuran lebih besar)</option>
                            <option value="Kebesaran">Kebesaran (Perlu ukuran lebih kecil)</option>
                            <option value="Barang Cacat / Rusak">Barang Cacat / Rusak (Jahitan rusak/kain cacat)</option>
                            <option value="Lainnya">Lainnya (Tulis alasan khusus)...</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="alasan_lainnya_group" style="display: none; margin-bottom: 1.25rem;">
                        <label style="font-weight: 700; font-size: 0.9rem; color: var(--dark); margin-bottom: 6px; display: block;">
                            Tuliskan Alasan Khusus <span style="color: #DC2626;">*</span>
                        </label>
                        <input type="text" name="alasan_lainnya" id="alasan_lainnya" placeholder="Contoh: Salah kirim jenis topi / logo tidak presisi" class="form-control-brutal">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1.75rem;">
                        <label style="font-weight: 700; font-size: 0.9rem; color: var(--dark); margin-bottom: 6px; display: block;">
                            Ukuran Pengganti Baru <span style="color: #DC2626;">*</span>
                        </label>
                        <select name="ukuran_baru" id="ukuranBaruSelect" required onchange="updateComparisonDisplay()" class="form-control-brutal">
                            <option value="">-- Pilih Ukuran Pengganti --</option>
                            <?php foreach($options as $opt): ?>
                                <option value="<?= $opt ?>" <?= $pesanan['ukuran'] == $opt ? 'selected' : '' ?>>Ukuran <?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--gray); display: block; margin-top: 6px; font-size: 0.8rem;">
                            *Jika retur karena cacat jahit (bukan tukar ukuran), biarkan ukuran tetap sama.
                        </small>
                    </div>
                    
                    <!-- Action Footer -->
                    <div style="display: flex; gap: 12px; justify-content: flex-end; align-items: center; border-top: 2px solid #E5E7EB; padding-top: 1.5rem; flex-wrap: wrap;">
                        <a href="pesanan.php" class="btn btn-secondary" style="padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; font-weight: 700; border: 2px solid #000; box-shadow: 3px 3px 0 #000; background: #fff; color: #000;">
                            Batal
                        </a>
                        <button type="submit" id="btnSubmitRetur" class="btn-card-primary" style="padding: 0.75rem 1.75rem; border-radius: 6px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; border: 2px solid #000; box-shadow: 4px 4px 0 #000; background: #F59E0B; color: #000; cursor: pointer; font-size: 0.95rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path><path d="M3 22v-6h6"></path><path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path></svg>
                            <span id="btnTextSubmit">Simpan & Kembalikan ke Penjahit</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="assets/js/script.js?v=<?= filemtime("assets/css/app.css") ?>"></script>
    <script>
        const oldSize = <?= (int)$pesanan['ukuran'] ?>;

        function toggleAlasanLainnya() {
            var val = document.getElementById('alasan').value;
            var group = document.getElementById('alasan_lainnya_group');
            var input = document.getElementById('alasan_lainnya');
            if(val === 'Lainnya') {
                group.style.display = 'block';
                input.required = true;
                input.focus();
            } else {
                group.style.display = 'none';
                input.required = false;
                input.value = '';
            }
        }

        function updateComparisonDisplay() {
            const select = document.getElementById('ukuranBaruSelect');
            const newSize = parseInt(select.value) || oldSize;
            
            document.getElementById('displayNewSize').textContent = newSize;
            const note = document.getElementById('diffSizeNote');

            if (newSize > oldSize) {
                note.textContent = 'Tukar Ukuran: +' + (newSize - oldSize) + ' nomor lebih besar';
                note.style.color = '#047857';
            } else if (newSize < oldSize) {
                note.textContent = 'Tukar Ukuran: ' + (newSize - oldSize) + ' nomor lebih kecil';
                note.style.color = '#B45309';
            } else {
                note.textContent = 'Ukuran tetap sama (Cocok jika retur karena barang cacat jahit)';
                note.style.color = '#4B5563';
            }
        }

        function handleSubmitRetur() {
            const btn = document.getElementById('btnSubmitRetur');
            const text = document.getElementById('btnTextSubmit');
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
            text.textContent = 'Memproses Retur...';
            return true;
        }

        document.addEventListener('DOMContentLoaded', updateComparisonDisplay);
    </script>
</body>
</html>
