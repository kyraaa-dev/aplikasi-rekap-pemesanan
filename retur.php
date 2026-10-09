<?php
require 'config.php';

// Handle Hapus Riwayat Retur
if (isset($_GET['del_retur'])) {
    $del_id = (int)$_GET['del_retur'];
    $conn->query("DELETE FROM retur_pesanan WHERE id = $del_id");
    header("Location: retur.php?notif=hapus_sukses");
    exit;
}

// Fetch Summary Statistics
$q_stats = $conn->query("
    SELECT 
        COUNT(r.id) as total_retur,
        SUM(p.jumlah) as total_pcs,
        SUM(CASE WHEN r.alasan LIKE '%Kekecilan%' THEN 1 ELSE 0 END) as kekecilan,
        SUM(CASE WHEN r.alasan LIKE '%Kebesaran%' THEN 1 ELSE 0 END) as kebesaran,
        SUM(CASE WHEN r.alasan LIKE '%Cacat%' OR r.alasan LIKE '%Rusak%' THEN 1 ELSE 0 END) as cacat,
        SUM(CASE WHEN p.status_pengambilan = 'Sedang Dibuat' THEN 1 ELSE 0 END) as sedang_dibuat,
        SUM(CASE WHEN p.status_pengambilan = 'Siap Diambil' THEN 1 ELSE 0 END) as siap_diambil
    FROM retur_pesanan r
    JOIN pesanan p ON r.pesanan_id = p.id
");
$stats = $q_stats ? $q_stats->fetch_assoc() : [];
$total_retur = (int)($stats['total_retur'] ?? 0);
$total_pcs = (int)($stats['total_pcs'] ?? 0);
$kekecilan = (int)($stats['kekecilan'] ?? 0);
$kebesaran = (int)($stats['kebesaran'] ?? 0);
$cacat = (int)($stats['cacat'] ?? 0);
$sedang_dibuat = (int)($stats['sedang_dibuat'] ?? 0);
$siap_diambil = (int)($stats['siap_diambil'] ?? 0);

// Fetch all retur rows
$returs = $conn->query("
    SELECT r.*, p.nama_pemesan, p.jenis_kelamin, p.jenis_mutz, p.jumlah, p.status_pengambilan, s.nama_skpd 
    FROM retur_pesanan r
    JOIN pesanan p ON r.pesanan_id = p.id
    JOIN skpd s ON p.skpd_id = s.id
    ORDER BY r.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Retur & Tukar Ukuran - E-MutZ KORPRI</title>
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
        .filter-pill {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.825rem;
            font-weight: 700;
            border: 2px solid #000;
            background: #F3F4F6;
            color: #374151;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .filter-pill:hover, .filter-pill.active {
            background: #000;
            color: #fff;
        }
        .retur-table-row:hover {
            background-color: #F8FAFC !important;
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
                    Riwayat Retur & Tukar Ukuran
                </h1>
                <p style="margin: 4px 0 0 0; color: var(--gray); font-size: 0.875rem;">Pantau proses penukaran topi, alasan retur, sinkronisasi stok, dan status penggantian penjahit</p>
            </div>
            <a href="pesanan.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 0.65rem 1.15rem; border-radius: 6px; font-weight: 700; border: 2px solid #000; box-shadow: 3px 3px 0 #000; background: #fff; color: #000;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Kelola Pesanan
            </a>
        </div>
        
        <!-- Summary Cards Statistik Retur Neobrutalist -->
        <div class="panel" style="margin-bottom: 1.5rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                <!-- Total Retur -->
                <div style="background: #fff; border: 2px solid #000; padding: 16px; border-radius: 6px; border-left: 8px solid #F59E0B; box-shadow: 4px 4px 0 #000;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--gray); text-transform: uppercase;">Total Retur Tercatat</div>
                    <div style="font-size: 1.85rem; font-weight: 900; color: var(--dark); margin-top: 4px;">
                        <?= number_format($total_retur) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--gray);">Kasus (<?= number_format($total_pcs) ?> pcs)</span>
                    </div>
                </div>

                <!-- Tukar Ukuran -->
                <div style="background: #fff; border: 2px solid #000; padding: 16px; border-radius: 6px; border-left: 8px solid #2563EB; box-shadow: 4px 4px 0 #000;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--gray); text-transform: uppercase;">Tukar Ukuran</div>
                    <div style="font-size: 1.15rem; font-weight: 800; color: var(--dark); margin-top: 8px; display: flex; gap: 12px; align-items: center;">
                        <span style="background: #EFF6FF; color: #1D4ED8; padding: 2px 8px; border-radius: 4px; border: 1px solid #BFDBFE;">Kekecilan: <?= $kekecilan ?></span>
                        <span style="background: #F3F4F6; color: #374151; padding: 2px 8px; border-radius: 4px; border: 1px solid #D1D5DB;">Kebesaran: <?= $kebesaran ?></span>
                    </div>
                </div>

                <!-- Cacat / Rusak -->
                <div style="background: #fff; border: 2px solid #000; padding: 16px; border-radius: 6px; border-left: 8px solid #EF4444; box-shadow: 4px 4px 0 #000;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--gray); text-transform: uppercase;">Barang Cacat / Rusak</div>
                    <div style="font-size: 1.85rem; font-weight: 900; color: #DC2626; margin-top: 4px;">
                        <?= number_format($cacat) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--gray);">Kasus</span>
                    </div>
                </div>

                <!-- Sedang Dikerjakan Penjahit -->
                <div style="background: #fff; border: 2px solid #000; padding: 16px; border-radius: 6px; border-left: 8px solid #10B981; box-shadow: 4px 4px 0 #000;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--gray); text-transform: uppercase;">Sedang Dibuat Penjahit</div>
                    <div style="font-size: 1.85rem; font-weight: 900; color: #047857; margin-top: 4px;">
                        <?= number_format($sedang_dibuat) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--gray);">Pesanan</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Daftar Retur & Filter -->
        <div class="panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h2 style="font-size: 1.25rem; font-weight: 800; margin: 0; color: var(--dark);">Daftar Barang Diretur</h2>
                    <p style="margin: 3px 0 0 0; color: var(--gray); font-size: 0.85rem;">Status pesanan di bawah ini tersinkronisasi langsung dengan progres penjahit</p>
                </div>

                <!-- Kotak Pencarian Live -->
                <div style="min-width: 260px; max-width: 340px; flex: 1;">
                    <input type="text" id="searchRetur" onkeyup="window.handleFilterRetur()" placeholder="🔍 Cari nama SKPD / Pemesan..." style="width: 100%; padding: 0.65rem 1rem; border: 2px solid #000; border-radius: 6px; font-size: 0.9rem; font-weight: 600; box-sizing: border-box;">
                </div>
            </div>

            <!-- Filter Pills -->
            <div style="display: flex; gap: 8px; margin-bottom: 1.25rem; flex-wrap: wrap; align-items: center;">
                <span style="font-size: 0.85rem; font-weight: 700; color: var(--gray); margin-right: 4px;">Filter Alasan:</span>
                <button type="button" class="filter-pill active" onclick="window.setReturFilter('all', this)">Semua (<?= $total_retur ?>)</button>
                <button type="button" class="filter-pill" onclick="window.setReturFilter('kekecilan', this)">Kekecilan (<?= $kekecilan ?>)</button>
                <button type="button" class="filter-pill" onclick="window.setReturFilter('kebesaran', this)">Kebesaran (<?= $kebesaran ?>)</button>
                <button type="button" class="filter-pill" onclick="window.setReturFilter('cacat', this)">Cacat / Rusak (<?= $cacat ?>)</button>
                <button type="button" class="filter-pill" onclick="window.setReturFilter('sedang_dibuat', this)" style="border-color: #059669; color: #059669;">Sedang Dibuat (<?= $sedang_dibuat ?>)</button>
            </div>
            
            <?php if($returs && $returs->num_rows > 0): ?>
            <div class="table-responsive" style="border: 2px solid #000; border-radius: 6px; overflow: hidden; box-shadow: 4px 4px 0 #000;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #E5E7EB; border-bottom: 2px solid #000;">
                            <th style="padding: 12px 14px; text-align: center; width: 45px;">No</th>
                            <th style="padding: 12px 14px; width: 140px;">Waktu Retur</th>
                            <th style="padding: 12px 14px;">Instansi SKPD</th>
                            <th style="padding: 12px 14px;">Nama Pemesan</th>
                            <th style="padding: 12px 14px; text-align: center;">Jenis Mutz</th>
                            <th style="padding: 12px 14px;">Alasan Retur</th>
                            <th style="padding: 12px 14px; text-align: center; width: 150px;">Perubahan Ukuran</th>
                            <th style="padding: 12px 14px; text-align: center; width: 150px;">Status Produksi</th>
                            <th style="padding: 12px 14px; text-align: center; width: 130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="returTableBody">
                        <?php 
                        $no = 1; 
                        while($row = $returs->fetch_assoc()): 
                            $alasan_text = strtolower($row['alasan']);
                            $alasan_tag = 'lainnya';
                            if (strpos($alasan_text, 'kekecilan') !== false) $alasan_tag = 'kekecilan';
                            elseif (strpos($alasan_text, 'kebesaran') !== false) $alasan_tag = 'kebesaran';
                            elseif (strpos($alasan_text, 'cacat') !== false || strpos($alasan_text, 'rusak') !== false) $alasan_tag = 'cacat';

                            $cur_status = $row['status_pengambilan'] ?: 'Dihapus';
                            $status_tag = ($cur_status === 'Sedang Dibuat') ? 'sedang_dibuat' : 'lain';
                            
                            $search_data = strtolower($row['nama_skpd'] . ' ' . ($row['nama_pemesan'] ?? '') . ' ' . $row['alasan']);

                            // Status Badge Colors
                            if($cur_status == 'Menunggu Diproses') {
                                $bg = '#F3F4F6'; $color = '#4B5563'; $border = '#9CA3AF';
                            } elseif($cur_status == 'Sedang Dibuat') {
                                $bg = '#EFF6FF'; $color = '#1D4ED8'; $border = '#3B82F6';
                            } elseif($cur_status == 'Siap Diambil') {
                                $bg = '#FEF3C7'; $color = '#92400E'; $border = '#F59E0B';
                            } elseif($cur_status == 'Sudah Diambil') {
                                $bg = '#D1FAE5'; $color = '#065F46'; $border = '#10B981';
                            } else {
                                $bg = '#FEE2E2'; $color = '#991B1B'; $border = '#EF4444';
                            }
                        ?>
                        <tr class="retur-table-row" data-alasan="<?= $alasan_tag ?>" data-status="<?= $status_tag ?>" data-search="<?= htmlspecialchars($search_data) ?>" style="border-bottom: 1px solid #E5E7EB;">
                            <td style="text-align: center; font-weight: 600; color: var(--gray);"><?= $no++ ?></td>
                            <td style="font-size: 0.85rem; color: var(--dark); font-weight: 500;">
                                <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                            </td>
                            <td>
                                <strong style="color: var(--dark);"><?= htmlspecialchars($row['nama_skpd']) ?></strong>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: var(--dark);"><?= htmlspecialchars($row['nama_pemesan'] ?? '') ?: '-' ?></span>
                                <span style="font-size: 0.8rem; color: var(--gray);">(<?= $row['jenis_kelamin'] == 'Laki-laki' ? 'Pria' : 'Wanita' ?>)</span>
                            </td>
                            <td style="text-align: center;">
                                <span style="font-weight: 700; color: var(--dark);"><?= htmlspecialchars($row['jenis_mutz']) ?></span>
                            </td>
                            <td>
                                <span style="background: #FEF3C7; color: #92400E; padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: 700; border: 1px solid #F59E0B; display: inline-block;">
                                    <?= htmlspecialchars($row['alasan']) ?>
                                </span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <span style="color: #9CA3AF; text-decoration: line-through; font-weight: 600; font-size: 0.9rem;">
                                    Uk. <?= $row['ukuran_lama'] ?>
                                </span>
                                <span style="font-weight: 900; color: #000; margin: 0 6px;">&rarr;</span>
                                <span style="background: #000; color: #fff; padding: 3px 8px; border-radius: 4px; font-weight: 800; font-size: 0.9rem;">
                                    Uk. <?= $row['ukuran_baru'] ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <span style="display: inline-flex; align-items: center; gap: 4px; background-color: <?= $bg ?>; color: <?= $color ?>; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; border: 1.5px solid <?= $border ?>; letter-spacing: 0.5px; white-space: nowrap;">
                                    <?= $cur_status ?>
                                </span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="edit_pesanan.php?id=<?= $row['pesanan_id'] ?>" class="btn btn-sm" style="display: inline-flex; align-items: center; gap: 4px; padding: 0.4rem 0.75rem; font-weight: 700; border-radius: 4px; border: 1.5px solid #000; background: #EEF2FF; color: #2563EB; margin-right: 4px; text-decoration: none;" title="Buka Detail Pesanan">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    Pesanan
                                </a>
                                <a href="retur.php?del_retur=<?= $row['id'] ?>" class="btn btn-sm btn-danger btn-hapus btn-confirm" data-confirm-title="Hapus Riwayat Retur" data-confirm-text="Apakah Anda yakin ingin menghapus catatan retur ini? (Hanya menghapus riwayat, tidak membatalkan retur pesanan)" data-confirm-btn="Ya, Hapus" data-confirm-color="#EF4444" style="display: inline-flex; align-items: center; gap: 4px; padding: 0.4rem 0.75rem; font-weight: 700; border-radius: 4px; border: 1.5px solid #000; background: #EF4444; color: #fff; text-decoration: none;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <!-- Empty State Neobrutalist yang Ramah & Menarik -->
            <div style="background: #F8FAFC; border: 3px solid #000; box-shadow: 6px 6px 0 #000; border-radius: 8px; padding: 3rem 1.5rem; text-align: center; margin: 1rem 0;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: #D1FAE5; border: 3px solid #000; color: #065F46; margin-bottom: 1.25rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <h3 style="font-size: 1.35rem; font-weight: 900; color: #000; margin: 0 0 0.5rem 0;">
                    Semua Ukuran Pas &amp; Belum Ada Retur!
                </h3>
                <p style="color: var(--gray); font-size: 0.95rem; max-width: 520px; margin: 0 auto 1.5rem auto; line-height: 1.6;">
                    Alhamdulillah, belum ada pengembalian atau penukaran topi yang dilaporkan sejauh ini. Jika ada pemesan yang ingin tukar ukuran setelah barang diambil, proses dapat dimulai dari menu Pesanan.
                </p>
                <a href="pesanan.php" class="btn-card-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 0.75rem 1.75rem; border-radius: 6px; font-weight: 800; font-size: 0.95rem; text-decoration: none; border: 2px solid #000; box-shadow: 4px 4px 0 #000; background: #2563EB; color: #fff;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Buka Data Pesanan
                </a>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="assets/js/script.js?v=<?= filemtime("assets/css/app.css") ?>"></script>
    <script>
        let currentAlasanFilter = 'all';

        window.setReturFilter = function(filter, btn) {
            currentAlasanFilter = filter;
            document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            window.handleFilterRetur();
        };

        window.handleFilterRetur = function() {
            const searchKeyword = (document.getElementById('searchRetur')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.retur-table-row');

            rows.forEach(row => {
                const rowAlasan = row.getAttribute('data-alasan') || '';
                const rowStatus = row.getAttribute('data-status') || '';
                const rowSearch = row.getAttribute('data-search') || '';

                let matchFilter = false;
                if (currentAlasanFilter === 'all') {
                    matchFilter = true;
                } else if (currentAlasanFilter === 'sedang_dibuat') {
                    matchFilter = (rowStatus === 'sedang_dibuat');
                } else {
                    matchFilter = (rowAlasan === currentAlasanFilter);
                }

                const matchSearch = (!searchKeyword || rowSearch.includes(searchKeyword));

                if (matchFilter && matchSearch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        };
    </script>
</body>
</html>
