<?php
require 'config.php';

// Pastikan tabel riwayat_stok ada
if (function_exists('catat_riwayat_stok')) {
    $conn->query("CREATE TABLE IF NOT EXISTS riwayat_stok (
        id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        jenis_mutz ENUM('Biasa', 'Kepala SKPD') NOT NULL,
        jenis_kelamin ENUM('Laki-laki', 'Perempuan') NOT NULL,
        ukuran INT(3) NOT NULL,
        tipe_mutasi ENUM('Masuk', 'Keluar', 'Penyesuaian') NOT NULL,
        jumlah INT(6) NOT NULL,
        stok_sebelum INT(6) NOT NULL DEFAULT 0,
        stok_sesudah INT(6) NOT NULL DEFAULT 0,
        keterangan VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_waktu (created_at),
        INDEX idx_barang (jenis_mutz, jenis_kelamin, ukuran)
    )");
}

// 1. Handle Tambah Stok Masuk (Restock Cepat)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_restock'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        header("Location: stok.php?error_msg=" . urlencode("Token keamanan CSRF tidak valid. Silakan muat ulang halaman."));
        exit;
    }

    $jenis_mutz = trim($_POST['jenis_mutz'] ?? 'Biasa');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? 'Laki-laki');
    $ukuran = (int)($_POST['ukuran'] ?? 0);
    $jumlah_masuk = (int)($_POST['jumlah_masuk'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (!in_array($jenis_mutz, ['Biasa', 'Kepala SKPD']) || !in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'])) {
        header("Location: stok.php?error_msg=" . urlencode("Kategori jenis atau jenis kelamin tidak valid."));
        exit;
    }

    if ($ukuran <= 0 || $jumlah_masuk <= 0) {
        header("Location: stok.php?error_msg=" . urlencode("Ukuran dan jumlah masuk harus lebih besar dari 0."));
        exit;
    }

    $ket_final = !empty($keterangan) ? $keterangan : "Restock pengadaan masuk (+{$jumlah_masuk} pcs)";
    sesuaikan_stok($conn, $jenis_mutz, $jenis_kelamin, $ukuran, $jumlah_masuk, $ket_final, 'Masuk');

    header("Location: stok.php?notif=restock_sukses");
    exit;
}

// 2. Handle Bulk Update Manual Stok Fisik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stok'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        header("Location: stok.php?error_msg=" . urlencode("Token keamanan CSRF tidak valid. Silakan muat ulang halaman."));
        exit;
    }

    if (isset($_POST['stok']) && is_array($_POST['stok'])) {
        // Ambil data stok yang ada sekarang untuk perbandingan mutasi
        $current_rows = [];
        $res_curr = $conn->query("SELECT id, jenis_mutz, jenis_kelamin, ukuran, jumlah_stok FROM stok_mutz");
        if ($res_curr) {
            while ($cr = $res_curr->fetch_assoc()) {
                $current_rows[(int)$cr['id']] = $cr;
            }
        }

        $stmt_up = $conn->prepare("UPDATE stok_mutz SET jumlah_stok = ? WHERE id = ?");
        foreach ($_POST['stok'] as $id => $jumlah) {
            $id_int = (int)$id;
            $jumlah_int = max(0, (int)$jumlah);

            if (isset($current_rows[$id_int])) {
                $old_row = $current_rows[$id_int];
                $old_qty = (int)$old_row['jumlah_stok'];
                $diff = $jumlah_int - $old_qty;

                if ($diff !== 0) {
                    // Update stok fisik
                    if ($stmt_up) {
                        $stmt_up->bind_param("ii", $jumlah_int, $id_int);
                        $stmt_up->execute();
                    }

                    // Catat log mutasi penyesuaian manual
                    $diff_str = ($diff > 0) ? "+{$diff}" : "{$diff}";
                    $ket_adj = "Penyesuaian manual stok: {$diff_str} pcs (Fisik gudang)";
                    catat_riwayat_stok($conn, $old_row['jenis_mutz'], $old_row['jenis_kelamin'], (int)$old_row['ukuran'], 'Penyesuaian', abs($diff), $old_qty, $jumlah_int, $ket_adj);
                }
            }
        }
        if ($stmt_up) $stmt_up->close();
    }

    header("Location: stok.php?notif=stok_sukses");
    exit;
}

// Tab aktif dari URL parameter (default: stok)
$active_tab = $_GET['tab'] ?? 'stok';
if (!in_array($active_tab, ['stok', 'riwayat'])) {
    $active_tab = 'stok';
}

// Fetch all stock data
$stok_data = $conn->query("SELECT * FROM stok_mutz ORDER BY jenis_mutz ASC, jenis_kelamin ASC, ukuran ASC");
$stok_list = [];
$low_stock_items = [];
$stock_json_map = [];

if ($stok_data) {
    while ($row = $stok_data->fetch_assoc()) {
        $stok_list[] = $row;
        $key = $row['jenis_mutz'] . '|' . $row['jenis_kelamin'] . '|' . $row['ukuran'];
        $stock_json_map[$key] = (int)$row['jumlah_stok'];

        if ((int)$row['jumlah_stok'] <= 5) {
            $low_stock_items[] = $row;
        }
    }
}

// Fetch totals
$q_tot_l = $conn->query("SELECT SUM(jumlah_stok) as tot FROM stok_mutz WHERE jenis_kelamin = 'Laki-laki'");
$tot_l = (int)($q_tot_l->fetch_assoc()['tot'] ?? 0);

$q_tot_p = $conn->query("SELECT SUM(jumlah_stok) as tot FROM stok_mutz WHERE jenis_kelamin = 'Perempuan'");
$tot_p = (int)($q_tot_p->fetch_assoc()['tot'] ?? 0);
$tot_all = $tot_l + $tot_p;

// Mutasi 30 hari terakhir
$q_mut_in = $conn->query("SELECT SUM(jumlah) as tot FROM riwayat_stok WHERE tipe_mutasi = 'Masuk' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$tot_mut_in = (int)($q_mut_in->fetch_assoc()['tot'] ?? 0);

$q_mut_out = $conn->query("SELECT SUM(jumlah) as tot FROM riwayat_stok WHERE tipe_mutasi = 'Keluar' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$tot_mut_out = (int)($q_mut_out->fetch_assoc()['tot'] ?? 0);

// Fetch Riwayat Mutasi dengan Filter
$filter_tipe = trim($_GET['tipe'] ?? '');
$filter_jenis = trim($_GET['jenis'] ?? '');
$filter_gender = trim($_GET['gender'] ?? '');

$sql_riwayat = "SELECT * FROM riwayat_stok WHERE 1=1";
$params = [];
$types = "";

if (!empty($filter_tipe) && in_array($filter_tipe, ['Masuk', 'Keluar', 'Penyesuaian'])) {
    $sql_riwayat .= " AND tipe_mutasi = ?";
    $params[] = $filter_tipe;
    $types .= "s";
}
if (!empty($filter_jenis) && in_array($filter_jenis, ['Biasa', 'Kepala SKPD'])) {
    $sql_riwayat .= " AND jenis_mutz = ?";
    $params[] = $filter_jenis;
    $types .= "s";
}
if (!empty($filter_gender) && in_array($filter_gender, ['Laki-laki', 'Perempuan'])) {
    $sql_riwayat .= " AND jenis_kelamin = ?";
    $params[] = $filter_gender;
    $types .= "s";
}

$sql_riwayat .= " ORDER BY created_at DESC, id DESC LIMIT 100";

if (!empty($params)) {
    $stmt_rw = $conn->prepare($sql_riwayat);
    $stmt_rw->bind_param($types, ...$params);
    $stmt_rw->execute();
    $riwayat_data = $stmt_rw->get_result();
} else {
    $riwayat_data = $conn->query($sql_riwayat);
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
    <title>Manajemen Stok & Riwayat Mutasi - E-MutZ KORPRI</title>
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
        .stok-input {
            width: 85px;
            padding: 6px 8px;
            text-align: center;
            border: 2px solid #000;
            border-radius: 4px;
            font-size: 0.95rem;
            font-weight: 700;
            background: #fff;
            transition: all 0.15s ease;
        }
        .stok-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
            outline: none;
        }
        .stok-habis {
            border-color: #DC2626 !important;
            background-color: #FEF2F2 !important;
            color: #DC2626 !important;
        }
        .stok-menipis {
            border-color: #F59E0B !important;
            background-color: #FFFBEB !important;
            color: #B45309 !important;
        }
        
        /* Tab Nav Styles */
        .tab-nav-container {
            display: flex;
            gap: 8px;
            border-bottom: 3px solid #000;
            margin-bottom: 1.5rem;
            padding-bottom: 0;
            flex-wrap: wrap;
        }
        .tab-btn {
            background: #E5E7EB;
            color: #1F2937;
            border: 2px solid #000;
            border-bottom: none;
            padding: 10px 18px;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 6px 6px 0 0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            position: relative;
            bottom: -3px;
            transition: all 0.15s ease;
        }
        .tab-btn:hover {
            background: #D1D5DB;
        }
        .tab-btn.active {
            background: #fff;
            color: #000;
            border-bottom: 3px solid #fff;
            box-shadow: 0 -2px 0 #000;
        }

        /* Filter Pills */
        .filter-pill {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
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

        /* Modal Restock */
        .restock-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            z-index: 100000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .restock-modal-overlay.active {
            display: flex !important;
        }
        .restock-modal-box {
            background: #fff;
            width: 100%;
            max-width: 540px;
            border: 4px solid #000;
            box-shadow: 8px 8px 0px #000;
            border-radius: 8px;
            overflow: hidden;
            animation: modalPopCenter 0.18s ease forwards;
        }
        @keyframes modalPopCenter {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Print Media Styles */
        @media print {
            .hide-on-print, .tab-nav-container, .stock-summary-grid, .btn-card-primary, .btn-restock-trigger {
                display: none !important;
            }
            .panel {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            .stok-input {
                border: none !important;
                font-weight: bold !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <!-- Header -->
        <div class="header hide-on-print" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 1.5rem;">
            <div>
                <h1 style="display: flex; align-items: center; gap: 10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    Manajemen Stok Gudang
                </h1>
                <p style="margin: 4px 0 0 0; color: var(--gray); font-size: 0.875rem;">Pantau stok fisik per ukuran topi, mutasi keluar-masuk (kartu stok), dan restock pengadaan</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="button" onclick="window.openRestockModal()" class="btn-card-primary btn-restock-trigger" style="display: inline-flex; align-items: center; gap: 8px; padding: 0.65rem 1.25rem; font-weight: 700; border-radius: 6px; background: #10B981; color: #fff; border: 2px solid #000; box-shadow: 3px 3px 0 #000; cursor: pointer;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    + Tambah Stok Masuk (Restock)
                </button>
                <button type="button" onclick="window.print()" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; padding: 0.65rem 1rem; font-weight: 600; border: 2px solid #000; box-shadow: 3px 3px 0 #000; border-radius: 6px; background: #fff;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Cetak Lembar Stok
                </button>
            </div>
        </div>
        
        <!-- Summary Metric Cards -->
        <div class="panel" style="margin-bottom: 1.5rem;">
            <div class="stock-summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                <div style="background: #fff; border: 2px solid #000; padding: 16px; border-radius: 6px; border-left: 8px solid #2563EB; box-shadow: 4px 4px 0 #000;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--gray); text-transform: uppercase;">Total Stok Laki-laki</div>
                    <div style="font-size: 1.85rem; font-weight: 900; color: var(--dark); margin-top: 4px;"><?= number_format($tot_l) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--gray);">Pcs</span></div>
                </div>
                <div style="background: #fff; border: 2px solid #000; padding: 16px; border-radius: 6px; border-left: 8px solid #EC4899; box-shadow: 4px 4px 0 #000;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--gray); text-transform: uppercase;">Total Stok Perempuan</div>
                    <div style="font-size: 1.85rem; font-weight: 900; color: var(--dark); margin-top: 4px;"><?= number_format($tot_p) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--gray);">Pcs</span></div>
                </div>
                <div style="background: #fff; border: 2px solid #000; padding: 16px; border-radius: 6px; border-left: 8px solid #10B981; box-shadow: 4px 4px 0 #000;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--gray); text-transform: uppercase;">Total Keseluruhan Fisik</div>
                    <div style="font-size: 1.85rem; font-weight: 900; color: var(--dark); margin-top: 4px;"><?= number_format($tot_all) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--gray);">Pcs</span></div>
                </div>
                <div style="background: #fff; border: 2px solid #000; padding: 16px; border-radius: 6px; border-left: 8px solid #F59E0B; box-shadow: 4px 4px 0 #000;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--gray); text-transform: uppercase;">Mutasi 30 Hari Terakhir</div>
                    <div style="font-size: 1.15rem; font-weight: 800; color: var(--dark); margin-top: 6px; display: flex; gap: 12px; align-items: center;">
                        <span style="color: #10B981; display: inline-flex; align-items: center; gap: 2px;" title="Total Masuk (30 hari)">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                            +<?= number_format($tot_mut_in) ?>
                        </span>
                        <span style="color: #EF4444; display: inline-flex; align-items: center; gap: 2px;" title="Total Keluar (30 hari)">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                            -<?= number_format($tot_mut_out) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Banner Peringatan Stok Menipis -->
            <?php if (!empty($low_stock_items)): ?>
            <div class="hide-on-print" style="margin-top: 1.25rem; background: #FEF2F2; border: 2px solid #EF4444; border-radius: 6px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="background: #EF4444; color: #fff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900;">!</div>
                    <div>
                        <strong style="color: #991B1B; font-size: 0.95rem;">Perhatian: Terdapat <?= count($low_stock_items) ?> ukuran mutz dengan stok menipis (&le; 5 pcs) atau habis!</strong>
                        <div style="color: #B91C1C; font-size: 0.825rem; margin-top: 2px;">
                            <?php 
                            $low_names = array_map(function($i) {
                                return $i['jenis_mutz'] . ' ' . ($i['jenis_kelamin'] == 'Laki-laki' ? 'Pria' : 'Wanita') . ' Uk.' . $i['ukuran'] . ' (' . $i['jumlah_stok'] . ' pcs)';
                            }, array_slice($low_stock_items, 0, 4));
                            echo htmlspecialchars(implode(', ', $low_names));
                            if (count($low_stock_items) > 4) echo ' dan ' . (count($low_stock_items) - 4) . ' lainnya.';
                            ?>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="window.filterTableStatus('menipis')" class="btn" style="background: #EF4444; color: #fff; font-weight: 700; padding: 6px 14px; font-size: 0.85rem; border-radius: 4px; border: 1px solid #7F1D1D; cursor: pointer;">
                    Lihat Ukuran Menipis &darr;
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tab Navigasi: Stok Fisik vs Riwayat Mutasi -->
        <div class="panel" style="padding-top: 1.25rem;">
            <div class="tab-nav-container hide-on-print">
                <a href="stok.php?tab=stok" class="tab-btn <?= $active_tab === 'stok' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                    Posisi Stok Fisik Gudang
                </a>
                <a href="stok.php?tab=riwayat" class="tab-btn <?= $active_tab === 'riwayat' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Kartu Riwayat Mutasi (Log Stok)
                </a>
            </div>

            <!-- ============================================== -->
            <!-- TAB 1: POSISI STOK FISIK                       -->
            <!-- ============================================== -->
            <?php if ($active_tab === 'stok'): ?>
            <div id="tabContentStok">
                <div class="flex justify-between items-center mb-4 flex-wrap gap-2 hide-on-print" style="margin-bottom: 1.25rem;">
                    <div>
                        <h2 style="margin: 0; font-size: 1.25rem;">Daftar Stok Fisik Mutz</h2>
                        <p style="color: var(--gray); font-size: 0.85rem; margin: 3px 0 0 0;">
                            *Stok berkurang otomatis saat pesanan dibuat, bertambah saat retur/restock, atau dapat disesuaikan manual di bawah ini.
                        </p>
                    </div>
                    <!-- Filter Pills Cepat -->
                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                        <button type="button" class="filter-pill active" onclick="window.filterTableCategory('all', this)">Semua</button>
                        <button type="button" class="filter-pill" onclick="window.filterTableCategory('biasa-pria', this)">Biasa (Pria)</button>
                        <button type="button" class="filter-pill" onclick="window.filterTableCategory('biasa-wanita', this)">Biasa (Wanita)</button>
                        <button type="button" class="filter-pill" onclick="window.filterTableCategory('kepala', this)">Kepala SKPD</button>
                        <button type="button" class="filter-pill" onclick="window.filterTableCategory('menipis', this)" style="border-color: #DC2626; color: #DC2626;">Stok &le; 5</button>
                    </div>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="update_stok" value="1">
                    <div class="table-responsive" style="border: 2px solid #000; border-radius: 6px; overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #E5E7EB; border-bottom: 2px solid #000;">
                                    <th style="padding: 12px 14px; text-align: center; width: 50px;">No</th>
                                    <th style="padding: 12px 14px;">Jenis Mutz</th>
                                    <th style="padding: 12px 14px; text-align: center;">Jenis Kelamin</th>
                                    <th style="padding: 12px 14px; text-align: center; width: 90px;">Ukuran</th>
                                    <th style="padding: 12px 14px; text-align: center; width: 140px;">Status Stok</th>
                                    <th style="padding: 12px 14px; text-align: center; width: 150px;">Sisa Stok Fisik</th>
                                    <th style="padding: 12px 14px; text-align: center; width: 130px;" class="hide-on-print">Aksi Cepat</th>
                                </tr>
                            </thead>
                            <tbody id="stokTableBody">
                                <?php 
                                $no = 1;
                                foreach($stok_list as $row): 
                                    $stok_val = (int)$row['jumlah_stok'];
                                    $is_habis = $stok_val <= 0;
                                    $is_menipis = $stok_val > 0 && $stok_val <= 5;
                                    
                                    // Generate data category tag for filtering
                                    $cat_tag = 'all';
                                    if ($row['jenis_mutz'] === 'Biasa' && $row['jenis_kelamin'] === 'Laki-laki') $cat_tag .= ' biasa-pria';
                                    if ($row['jenis_mutz'] === 'Biasa' && $row['jenis_kelamin'] === 'Perempuan') $cat_tag .= ' biasa-wanita';
                                    if ($row['jenis_mutz'] === 'Kepala SKPD') $cat_tag .= ' kepala';
                                    if ($stok_val <= 5) $cat_tag .= ' menipis';
                                ?>
                                <tr data-category="<?= $cat_tag ?>" style="border-bottom: 1px solid #E5E7EB; <?= $is_habis ? 'background: #FFF1F2;' : ($is_menipis ? 'background: #FFFBEB;' : '') ?>">
                                    <td style="text-align: center; font-weight: 600; color: var(--gray);"><?= $no++ ?></td>
                                    <td>
                                        <span style="font-weight: 700; color: var(--dark);"><?= htmlspecialchars($row['jenis_mutz']) ?></span>
                                        <?php if ($row['jenis_mutz'] === 'Kepala SKPD'): ?>
                                            <span style="background: #FEF3C7; color: #92400E; font-size: 0.75rem; padding: 2px 6px; border-radius: 4px; font-weight: 700; margin-left: 6px; border: 1px solid #F59E0B;">VIP</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($row['jenis_kelamin'] === 'Laki-laki'): ?>
                                            <span style="background: #DBEAFE; color: #1E40AF; padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">Laki-laki</span>
                                        <?php else: ?>
                                            <span style="background: #FCE7F3; color: #9D174D; padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">Perempuan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span style="display: inline-block; min-width: 38px; padding: 4px 8px; background: #000; color: #fff; border-radius: 4px; font-weight: 800; font-size: 0.95rem;">
                                            <?= $row['ukuran'] ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($is_habis): ?>
                                            <span style="background: #FEE2E2; color: #991B1B; padding: 4px 8px; border-radius: 4px; font-weight: 800; font-size: 0.8rem; border: 1px solid #EF4444; display: inline-block;">
                                                HABIS (0)
                                            </span>
                                        <?php elseif ($is_menipis): ?>
                                            <span style="background: #FEF3C7; color: #92400E; padding: 4px 8px; border-radius: 4px; font-weight: 800; font-size: 0.8rem; border: 1px solid #F59E0B; display: inline-block;">
                                                MENIPIS (&le;5)
                                            </span>
                                        <?php else: ?>
                                            <span style="background: #D1FAE5; color: #065F46; padding: 4px 8px; border-radius: 4px; font-weight: 700; font-size: 0.8rem; border: 1px solid #10B981; display: inline-block;">
                                                AMAN
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: inline-flex; align-items: center; gap: 4px;">
                                            <input type="number" 
                                                   name="stok[<?= $row['id'] ?>]" 
                                                   value="<?= $row['jumlah_stok'] ?>" 
                                                   min="0"
                                                   class="stok-input <?= $is_habis ? 'stok-habis' : ($is_menipis ? 'stok-menipis' : '') ?>">
                                            <span style="font-size: 0.85rem; color: var(--gray); font-weight: 600;">Pcs</span>
                                        </div>
                                    </td>
                                    <td style="text-align: center;" class="hide-on-print">
                                        <button type="button" 
                                                onclick="window.quickRestockRow('<?= htmlspecialchars($row['jenis_mutz']) ?>', '<?= htmlspecialchars($row['jenis_kelamin']) ?>', <?= $row['ukuran'] ?>, <?= $row['jumlah_stok'] ?>)" 
                                                class="btn" 
                                                style="padding: 5px 10px; font-size: 0.8rem; font-weight: 700; background: #EEF2FF; color: #2563EB; border: 1px solid #C7D2FE; border-radius: 4px; cursor: pointer;">
                                            + Restock
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="hide-on-print" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                        <div style="font-size: 0.85rem; color: var(--gray);">
                            *Jika Anda mengubah angka di tabel, sistem akan otomatis mencatat riwayat penyesuaian (*Adjustment*) saat disimpan.
                        </div>
                        <button type="submit" class="btn-card-primary" style="padding: 0.75rem 1.6rem; border-radius: 6px; font-size: 1rem; border: 2px solid #000; box-shadow: 4px 4px 0 #000; background: #2563EB; color: #fff; font-weight: 700; cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                            Simpan Perubahan Stok Manual
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- ============================================== -->
            <!-- TAB 2: KARTU RIWAYAT MUTASI                    -->
            <!-- ============================================== -->
            <?php if ($active_tab === 'riwayat'): ?>
            <div id="tabContentRiwayat">
                <!-- Filter Bar Riwayat Mutasi -->
                <div class="hide-on-print" style="background: #F9FAFB; border: 2px solid #000; border-radius: 6px; padding: 14px 18px; margin-bottom: 1.25rem;">
                    <form method="GET" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                        <input type="hidden" name="tab" value="riwayat">
                        
                        <div style="flex: 1; min-width: 140px;">
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--dark); margin-bottom: 4px;">Tipe Mutasi</label>
                            <select name="tipe" class="form-control" style="width: 100%; padding: 7px 10px; border: 2px solid #000; border-radius: 4px; font-size: 0.9rem;">
                                <option value="">Semua Tipe</option>
                                <option value="Masuk" <?= $filter_tipe === 'Masuk' ? 'selected' : '' ?>>Barang Masuk (Restock / Retur)</option>
                                <option value="Keluar" <?= $filter_tipe === 'Keluar' ? 'selected' : '' ?>>Barang Keluar (Pesanan)</option>
                                <option value="Penyesuaian" <?= $filter_tipe === 'Penyesuaian' ? 'selected' : '' ?>>Penyesuaian Manual</option>
                            </select>
                        </div>

                        <div style="flex: 1; min-width: 140px;">
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--dark); margin-bottom: 4px;">Jenis Mutz</label>
                            <select name="jenis" class="form-control" style="width: 100%; padding: 7px 10px; border: 2px solid #000; border-radius: 4px; font-size: 0.9rem;">
                                <option value="">Semua Jenis</option>
                                <option value="Biasa" <?= $filter_jenis === 'Biasa' ? 'selected' : '' ?>>Biasa</option>
                                <option value="Kepala SKPD" <?= $filter_jenis === 'Kepala SKPD' ? 'selected' : '' ?>>Kepala SKPD</option>
                            </select>
                        </div>

                        <div style="flex: 1; min-width: 140px;">
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--dark); margin-bottom: 4px;">Jenis Kelamin</label>
                            <select name="gender" class="form-control" style="width: 100%; padding: 7px 10px; border: 2px solid #000; border-radius: 4px; font-size: 0.9rem;">
                                <option value="">Semua Gender</option>
                                <option value="Laki-laki" <?= $filter_gender === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= $filter_gender === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>

                        <div style="display: flex; gap: 8px;">
                            <button type="submit" class="btn" style="background: #2563EB; color: #fff; font-weight: 700; border: 2px solid #000; border-radius: 4px; padding: 7px 16px; cursor: pointer;">
                                Filter
                            </button>
                            <a href="stok.php?tab=riwayat" class="btn btn-secondary" style="border: 2px solid #000; border-radius: 4px; padding: 7px 14px; text-decoration: none; color: #000; font-weight: 600;">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Tabel Riwayat Mutasi -->
                <div class="table-responsive" style="border: 2px solid #000; border-radius: 6px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #E5E7EB; border-bottom: 2px solid #000;">
                                <th style="padding: 12px 14px; text-align: center; width: 50px;">No</th>
                                <th style="padding: 12px 14px; width: 160px;">Waktu Mutasi</th>
                                <th style="padding: 12px 14px;">Barang & Ukuran</th>
                                <th style="padding: 12px 14px; text-align: center; width: 130px;">Tipe Mutasi</th>
                                <th style="padding: 12px 14px; text-align: center; width: 110px;">Perubahan</th>
                                <th style="padding: 12px 14px; text-align: center; width: 130px;">Stok Sebelum &rarr; Sesudah</th>
                                <th style="padding: 12px 14px;">Keterangan / Sumber</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($riwayat_data && $riwayat_data->num_rows > 0):
                                $no_rw = 1;
                                while($rw = $riwayat_data->fetch_assoc()):
                                    $time_str = date('d M Y, H:i', strtotime($rw['created_at']));
                                    $tipe = $rw['tipe_mutasi'];
                            ?>
                            <tr style="border-bottom: 1px solid #E5E7EB;">
                                <td style="text-align: center; font-weight: 600; color: var(--gray);"><?= $no_rw++ ?></td>
                                <td style="font-size: 0.85rem; color: var(--dark); font-weight: 500;">
                                    <?= $time_str ?> WIB
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($rw['jenis_mutz']) ?></strong>
                                    <span style="font-size: 0.825rem; color: var(--gray);">(<?= $rw['jenis_kelamin'] ?>)</span>
                                    <span style="background: #000; color: #fff; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 0.8rem; margin-left: 4px;">
                                        Uk. <?= $rw['ukuran'] ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($tipe === 'Masuk'): ?>
                                        <span style="background: #D1FAE5; color: #065F46; padding: 4px 10px; border-radius: 4px; font-weight: 800; font-size: 0.8rem; border: 1px solid #10B981; display: inline-flex; align-items: center; gap: 4px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                                            MASUK
                                        </span>
                                    <?php elseif ($tipe === 'Keluar'): ?>
                                        <span style="background: #FEE2E2; color: #991B1B; padding: 4px 10px; border-radius: 4px; font-weight: 800; font-size: 0.8rem; border: 1px solid #EF4444; display: inline-flex; align-items: center; gap: 4px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                                            KELUAR
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #DBEAFE; color: #1E40AF; padding: 4px 10px; border-radius: 4px; font-weight: 800; font-size: 0.8rem; border: 1px solid #3B82F6; display: inline-flex; align-items: center; gap: 4px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                                            PENYESUAIAN
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; font-weight: 800; font-size: 0.95rem;">
                                    <?php if ($tipe === 'Masuk'): ?>
                                        <span style="color: #059669;">+<?= $rw['jumlah'] ?> pcs</span>
                                    <?php elseif ($tipe === 'Keluar'): ?>
                                        <span style="color: #DC2626;">-<?= $rw['jumlah'] ?> pcs</span>
                                    <?php else: ?>
                                        <span style="color: #2563EB;">&plusmn;<?= $rw['jumlah'] ?> pcs</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; font-size: 0.875rem;">
                                    <span style="color: var(--gray);"><?= $rw['stok_sebelum'] ?></span>
                                    <span style="margin: 0 4px; color: var(--dark); font-weight: 700;">&rarr;</span>
                                    <strong style="color: var(--dark);"><?= $rw['stok_sesudah'] ?></strong>
                                </td>
                                <td style="font-size: 0.85rem; color: var(--dark);">
                                    <?= htmlspecialchars($rw['keterangan'] ?: '-') ?>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2.5rem 1rem; color: var(--gray);">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 8px; opacity: 0.5;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    <div style="font-weight: 600;">Belum ada riwayat mutasi stok yang tercatat sesuai filter.</div>
                                    <div style="font-size: 0.8rem; margin-top: 4px;">Mutasi akan tercatat otomatis saat ada transaksi restock, pesanan masuk, atau penyesuaian manual.</div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- ============================================== -->
    <!-- MODAL RESTOCK / STOK MASUK                     -->
    <!-- ============================================== -->
    <div id="restockModal" class="restock-modal-overlay" onclick="if(event.target===this) window.closeRestockModal();">
        <div class="restock-modal-box">
            <!-- Modal Header -->
            <div style="padding: 1.15rem 1.4rem; border-bottom: 3px solid #000; background: #D1FAE5; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="background: #10B981; color: #fff; width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; border: 2px solid #000;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    </div>
                    <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: #065F46;">Tambah Stok Masuk (Restock)</h3>
                </div>
                <button type="button" onclick="window.closeRestockModal()" style="background: #F87171; border: 2px solid #000; font-size: 1.25rem; font-weight: 900; line-height: 1; padding: 4px 10px; cursor: pointer; border-radius: 4px; box-shadow: 2px 2px 0 #000;">&times;</button>
            </div>

            <!-- Modal Form Body -->
            <form method="POST" style="padding: 1.4rem;" onsubmit="return window.validateRestockForm()">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="tambah_restock" value="1">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--dark); margin-bottom: 6px;">Jenis Mutz</label>
                        <select name="jenis_mutz" id="restockJenisMutz" onchange="window.updateRestockOptions()" style="width: 100%; padding: 8px 10px; border: 2px solid #000; border-radius: 4px; font-weight: 600;">
                            <option value="Biasa">Biasa</option>
                            <option value="Kepala SKPD">Kepala SKPD</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--dark); margin-bottom: 6px;">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="restockGender" onchange="window.updateRestockOptions()" style="width: 100%; padding: 8px 10px; border: 2px solid #000; border-radius: 4px; font-weight: 600;">
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--dark); margin-bottom: 6px;">Ukuran Topi</label>
                        <select name="ukuran" id="restockUkuran" onchange="window.updateRestockCurrentStockInfo()" style="width: 100%; padding: 8px 10px; border: 2px solid #000; border-radius: 4px; font-weight: 700;">
                            <!-- Dinamis via JS -->
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--dark); margin-bottom: 6px;">Jumlah Masuk (Pcs)</label>
                        <input type="number" name="jumlah_masuk" id="restockJumlah" min="1" placeholder="Contoh: 50" required style="width: 100%; padding: 8px 10px; border: 2px solid #000; border-radius: 4px; font-weight: 700; box-sizing: border-box;">
                    </div>
                </div>

                <!-- Info Box Sisa Stok Saat Ini -->
                <div style="background: #F3F4F6; border: 1px solid #D1D5DB; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem;">
                    <span style="color: var(--gray);">Stok Fisik Saat Ini:</span>
                    <strong id="restockCurrentStockText" style="color: var(--dark); font-size: 1rem;">- Pcs</strong>
                </div>

                <div style="margin-bottom: 1.4rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--dark); margin-bottom: 6px;">Keterangan / Sumber Barang (Opsional)</label>
                    <input type="text" name="keterangan" id="restockKeterangan" placeholder="Misal: Kiriman Konveksi Batch 2, Pengadaan Baru, dll." style="width: 100%; padding: 8px 10px; border: 2px solid #000; border-radius: 4px; box-sizing: border-box;">
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 2px solid #E5E7EB; padding-top: 1rem;">
                    <button type="button" onclick="window.closeRestockModal()" class="btn btn-secondary" style="border: 2px solid #000; border-radius: 4px; padding: 8px 16px; font-weight: 600; cursor: pointer;">
                        Batal
                    </button>
                    <button type="submit" class="btn-card-primary" style="background: #10B981; color: #fff; border: 2px solid #000; box-shadow: 3px 3px 0 #000; border-radius: 4px; padding: 8px 18px; font-weight: 800; cursor: pointer;">
                        Simpan Stok Masuk
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/script.js?v=<?= filemtime("assets/css/app.css") ?>"></script>
    <script>
    // JSON Map Stok Saat Ini
    const stockMap = <?= json_encode($stock_json_map) ?>;

    // Helper Modal Restock
    window.openRestockModal = function() {
        const modal = document.getElementById('restockModal');
        if (modal) {
            modal.classList.add('active');
            window.updateRestockOptions();
        }
    };

    window.closeRestockModal = function() {
        const modal = document.getElementById('restockModal');
        if (modal) modal.classList.remove('active');
    };

    // Update opsi ukuran berdasarkan gender
    window.updateRestockOptions = function(selectedUkuran = null) {
        const gender = document.getElementById('restockGender').value;
        const ukuranSelect = document.getElementById('restockUkuran');
        if (!ukuranSelect) return;

        ukuranSelect.innerHTML = '';
        const sizes = (gender === 'Laki-laki') ? [55, 56, 57, 58, 59, 60] : [58, 59, 60];
        sizes.forEach(size => {
            const opt = document.createElement('option');
            opt.value = size;
            opt.textContent = 'Ukuran ' + size;
            if (selectedUkuran && parseInt(selectedUkuran) === size) {
                opt.selected = true;
            }
            ukuranSelect.appendChild(opt);
        });

        window.updateRestockCurrentStockInfo();
    };

    // Update teks info stok saat ini di modal
    window.updateRestockCurrentStockInfo = function() {
        const jm = document.getElementById('restockJenisMutz').value;
        const jk = document.getElementById('restockGender').value;
        const uk = document.getElementById('restockUkuran').value;
        const key = jm + '|' + jk + '|' + uk;
        const currentQty = (key in stockMap) ? stockMap[key] : 0;
        
        const label = document.getElementById('restockCurrentStockText');
        if (label) {
            label.textContent = currentQty + ' Pcs';
        }
    };

    // Tombol + Restock per baris di tabel
    window.quickRestockRow = function(jenisMutz, gender, ukuran, currentStock) {
        document.getElementById('restockJenisMutz').value = jenisMutz;
        document.getElementById('restockGender').value = gender;
        window.updateRestockOptions(ukuran);
        document.getElementById('restockJumlah').value = '';
        document.getElementById('restockKeterangan').value = 'Restock barang masuk ukuran ' + ukuran;
        window.openRestockModal();
    };

    // Filter Kategori di Tab Stok Fisik
    window.filterTableCategory = function(category, btnElement) {
        document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
        if (btnElement) btnElement.classList.add('active');

        const rows = document.querySelectorAll('#stokTableBody tr');
        rows.forEach(row => {
            const tags = row.getAttribute('data-category') || '';
            if (category === 'all' || tags.includes(category)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    };

    window.filterTableStatus = function(status) {
        const pills = document.querySelectorAll('.filter-pill');
        pills.forEach(p => {
            if (p.textContent.includes('Stok ≤ 5') || p.textContent.includes('menipis')) {
                window.filterTableCategory('menipis', p);
            }
        });
    };

    // Shortcut ESC untuk tutup modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.closeRestockModal();
        }
    });
    </script>
</body>
</html>
