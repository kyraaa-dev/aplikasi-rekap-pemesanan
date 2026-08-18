<?php
require 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    header("Location: pesanan.php");
    exit;
}

$pesanan = $conn->query("
    SELECT p.*, s.nama_skpd 
    FROM pesanan p 
    JOIN skpd s ON p.skpd_id = s.id 
    WHERE p.id = $id
")->fetch_assoc();

if (!$pesanan || $pesanan['status_pengambilan'] != 'Sudah Diambil') {
    echo "Pesanan tidak valid untuk diretur.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['alasan'])) {
    $alasan = $conn->real_escape_string($_POST['alasan']);
    if($alasan == 'Lainnya') {
        $alasan = $conn->real_escape_string($_POST['alasan_lainnya']);
    }
    $ukuran_baru = (int)$_POST['ukuran_baru'];
    $ukuran_lama = (int)$pesanan['ukuran'];

    $conn->begin_transaction();
    try {
        // Insert log retur
        $stmt_retur = $conn->prepare("INSERT INTO retur_pesanan (pesanan_id, alasan, ukuran_lama, ukuran_baru) VALUES (?, ?, ?, ?)");
        $stmt_retur->bind_param("isii", $id, $alasan, $ukuran_lama, $ukuran_baru);
        $stmt_retur->execute();

        // Update pesanan: ganti ukuran dan ubah status menjadi Sedang Dibuat
        $stmt_pesanan = $conn->prepare("UPDATE pesanan SET ukuran = ?, status_pengambilan = 'Sedang Dibuat' WHERE id = ?");
        $stmt_pesanan->bind_param("ii", $ukuran_baru, $id);
        $stmt_pesanan->execute();

        // Kembalikan stok ukuran lama
        $jm = $pesanan['jenis_mutz'];
        $jk = $pesanan['jenis_kelamin'];
        $jml = (int)$pesanan['jumlah'];
        $conn->query("UPDATE stok_mutz SET jumlah_stok = jumlah_stok + $jml WHERE jenis_mutz = '$jm' AND jenis_kelamin = '$jk' AND ukuran = $ukuran_lama");
        
        // Kurangi stok ukuran baru
        $conn->query("UPDATE stok_mutz SET jumlah_stok = jumlah_stok - $jml WHERE jenis_mutz = '$jm' AND jenis_kelamin = '$jk' AND ukuran = $ukuran_baru");

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
    <title>Proses Retur - E-MutZ KORPRI</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <h1>Proses Retur Pesanan</h1>
        </div>
        
        <div class="panel">
            <div class="flex justify-between items-center mb-4">
                <h2>Form Retur Barang</h2>
                <a href="pesanan.php" class="btn btn-sm btn-secondary">Kembali</a>
            </div>
            
            <div style="background: #F9FAFB; padding: 15px; border-radius: 8px; border: 1px solid #E5E7EB; margin-bottom: 20px;">
                <h3 style="margin-top:0; color: #374151; font-size: 1rem;">Detail Pesanan Awal</h3>
                <p style="margin: 5px 0;"><strong>SKPD:</strong> <?= htmlspecialchars($pesanan['nama_skpd']) ?></p>
                <p style="margin: 5px 0;"><strong>Pemesan:</strong> <?= htmlspecialchars($pesanan['nama_pemesan']) ?: '-' ?> (<?= $pesanan['jenis_kelamin'] ?>)</p>
                <p style="margin: 5px 0;"><strong>Ukuran Saat Ini:</strong> <span style="background: #FEE2E2; color: #B91C1C; padding: 2px 6px; border-radius: 4px; font-weight: bold;"><?= $pesanan['ukuran'] ?></span></p>
                <p style="margin: 5px 0;"><strong>Jenis:</strong> <?= $pesanan['jenis_mutz'] ?> (<?= $pesanan['jumlah'] ?> pcs)</p>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Alasan Retur</label>
                    <select name="alasan" id="alasan" required onchange="toggleAlasanLainnya()">
                        <option value="">-- Pilih Alasan --</option>
                        <option value="Kekecilan">Kekecilan</option>
                        <option value="Kebesaran">Kebesaran</option>
                        <option value="Barang Cacat / Rusak">Barang Cacat / Rusak</option>
                        <option value="Lainnya">Lainnya...</option>
                    </select>
                </div>
                
                <div class="form-group" id="alasan_lainnya_group" style="display: none;">
                    <label>Tuliskan Alasan Lainnya</label>
                    <input type="text" name="alasan_lainnya" id="alasan_lainnya" placeholder="Contoh: Salah kirim jenis mutz">
                </div>
                
                <div class="form-group">
                    <label>Ukuran Pengganti Baru</label>
                    <select name="ukuran_baru" required>
                        <option value="">-- Pilih Ukuran Baru --</option>
                        <?php foreach($options as $opt): ?>
                            <option value="<?= $opt ?>" <?= $pesanan['ukuran'] == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #6B7280; display: block; margin-top: 5px;">Jika retur karena cacat (bukan tukar ukuran), biarkan ukurannya tetap sama.</small>
                </div>
                
                <button type="submit" class="btn btn-primary" style="background-color: #F59E0B;">Simpan & Kembalikan ke Penjahit</button>
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
