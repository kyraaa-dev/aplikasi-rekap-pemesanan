<?php
require 'config.php';

$returs = $conn->query("
    SELECT r.*, p.nama_pemesan, p.jenis_kelamin, p.jenis_mutz, s.nama_skpd 
    FROM retur_pesanan r
    JOIN pesanan p ON r.pesanan_id = p.id
    JOIN skpd s ON p.skpd_id = s.id
    ORDER BY r.id DESC
");

if (isset($_GET['del_retur'])) {
    $del_id = (int)$_GET['del_retur'];
    $conn->query("DELETE FROM retur_pesanan WHERE id = $del_id");
    header("Location: retur.php?notif=hapus_sukses");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Retur - E-MutZ KORPRI</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <h1>Riwayat Retur & Tukar Ukuran</h1>
        </div>
        
        <div class="panel">
            <div class="flex justify-between items-center mb-4">
                <h2>Daftar Barang Diretur</h2>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal Retur</th>
                            <th>SKPD</th>
                            <th>Pemesan</th>
                            <th>Jenis Mutz</th>
                            <th>Alasan</th>
                            <th>Ukuran Lama</th>
                            <th>Ukuran Baru</th>
                            <th>Status Pesanan Saat Ini</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; while($row = $returs->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td><?= htmlspecialchars($row['nama_skpd']) ?></td>
                            <td><?= htmlspecialchars($row['nama_pemesan'] ?? '') ?: '-' ?> (<?= $row['jenis_kelamin'] == 'Laki-laki' ? 'L' : 'P' ?>)</td>
                            <td><?= $row['jenis_mutz'] ?></td>
                            <td>
                                <span style="background: #FEE2E2; color: #B91C1C; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 500;">
                                    <?= htmlspecialchars($row['alasan']) ?>
                                </span>
                            </td>
                            <td style="text-align: center; color: #6B7280; text-decoration: line-through;"><?= $row['ukuran_lama'] ?></td>
                            <td style="text-align: center; font-weight: bold; color: #10B981;"><?= $row['ukuran_baru'] ?></td>
                            <td>
                                <?php 
                                    // Fetch current status from pesanan table to show realtime status of the returned item
                                    $pid = $row['pesanan_id'];
                                    $cur_pesanan = $conn->query("SELECT status_pengambilan FROM pesanan WHERE id = $pid")->fetch_assoc();
                                    $cur_status = $cur_pesanan ? $cur_pesanan['status_pengambilan'] : 'Dihapus';
                                    
                                    if($cur_status == 'Menunggu Diproses') {
                                        $bg = '#F3F4F6'; $color = '#4B5563'; $border = '#D1D5DB';
                                    } elseif($cur_status == 'Sedang Dibuat') {
                                        $bg = '#EFF6FF'; $color = '#1D4ED8'; $border = '#BFDBFE';
                                    } elseif($cur_status == 'Siap Diambil') {
                                        $bg = '#FEF3C7'; $color = '#B45309'; $border = '#FDE68A';
                                    } elseif($cur_status == 'Sudah Diambil') {
                                        $bg = '#F0FDF4'; $color = '#15803D'; $border = '#BBF7D0';
                                    } else {
                                        $bg = '#FEE2E2'; $color = '#B91C1C'; $border = '#FECACA';
                                    }
                                ?>
                                <span style="display: inline-flex; align-items: center; gap: 4px; background-color: <?= $bg ?>; color: <?= $color ?>; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; border: 1px solid <?= $border ?>; letter-spacing: 0.5px; white-space: nowrap;">
                                    <?= $cur_status ?>
                                </span>
                            </td>
                            <td style="white-space: nowrap;">
                                <a href="edit_pesanan.php?id=<?= $row['pesanan_id'] ?>" class="btn btn-sm btn-secondary" style="display: inline-flex; align-items: center; gap: 4px; margin-right: 5px; padding: 0.35rem 0.7rem; font-weight: 500; border-radius: 6px;" title="Lihat/Edit Pesanan Asli">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    Pesanan
                                </a>
                                <a href="retur.php?del_retur=<?= $row['id'] ?>" class="btn btn-sm btn-danger btn-hapus btn-confirm" data-confirm-title="Hapus Riwayat" data-confirm-text="Apakah Anda yakin ingin menghapus catatan retur ini? (Hanya menghapus riwayat, tidak membatalkan retur pesanan)" data-confirm-btn="Ya, Hapus" data-confirm-color="#EF4444" style="display: inline-flex; align-items: center; gap: 4px; padding: 0.35rem 0.7rem; font-weight: 500; border-radius: 6px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($returs->num_rows == 0): ?>
                        <tr><td colspan="10" style="text-align:center;">Belum ada riwayat retur.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script src="assets/js/script.js?v=<?= time() ?>"></script>
</body>
</html>
