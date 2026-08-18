<?php
require 'config.php';

// Bulk Action: Tandai Semua Pesanan SKPD Sudah Diambil
if (isset($_GET['ambil_semua_skpd'])) {
    $skpd_id = (int)$_GET['ambil_semua_skpd'];
    if ($skpd_id > 0) {
        $conn->query("UPDATE pesanan SET status_pengambilan = 'Sudah Diambil' WHERE skpd_id = $skpd_id");
        header("Location: index.php?notif=ambil_semua_sukses");
        exit;
    }
}

// Bulk Action: Tandai Semua Pesanan SKPD Lunas
if (isset($_GET['lunas_semua_skpd'])) {
    $skpd_id = (int)$_GET['lunas_semua_skpd'];
    if ($skpd_id > 0) {
        $conn->query("UPDATE pesanan SET status_bayar = 'Lunas' WHERE skpd_id = $skpd_id");
        header("Location: index.php?notif=bayar_semua_sukses");
        exit;
    }
}

// Get total SKPD
$q_skpd = $conn->query("SELECT COUNT(*) as total FROM skpd");
$t_skpd = $q_skpd->fetch_assoc()['total'];

// Get total orders by gender
$q_l = $conn->query("SELECT SUM(jumlah) as total FROM pesanan WHERE jenis_kelamin = 'Laki-laki'");
$t_l = $q_l->fetch_assoc()['total'] ?? 0;

$q_p = $conn->query("SELECT SUM(jumlah) as total FROM pesanan WHERE jenis_kelamin = 'Perempuan'");
$t_p = $q_p->fetch_assoc()['total'] ?? 0;

$t_all = $t_l + $t_p;

// Get total overall tagihan
$q_tagihan = $conn->query("SELECT SUM(CASE WHEN jenis_mutz = 'Kepala SKPD' THEN jumlah * " . HARGA_KEPALA . " ELSE jumlah * " . HARGA_BIASA . " END) as total FROM pesanan");
$t_tagihan = $q_tagihan->fetch_assoc()['total'] ?? 0;

// Get Top 5 SKPDs
$q_top_skpd = $conn->query("
    SELECT s.nama_skpd, SUM(p.jumlah) as total_pesanan
    FROM pesanan p
    JOIN skpd s ON p.skpd_id = s.id
    GROUP BY s.id
    ORDER BY total_pesanan DESC
    LIMIT 5
");
$top_skpd_labels = [];
$top_skpd_data = [];
if ($q_top_skpd) {
    while ($row = $q_top_skpd->fetch_assoc()) {
        $top_skpd_labels[] = $row['nama_skpd'];
        $top_skpd_data[] = (int)$row['total_pesanan'];
    }
}

// Get Size Distribution
$q_size = $conn->query("
    SELECT ukuran, SUM(jumlah) as total_pesanan
    FROM pesanan
    GROUP BY ukuran
    ORDER BY ukuran ASC
");
$size_labels = [];
$size_data = [];
if ($q_size) {
    while ($row = $q_size->fetch_assoc()) {
        $size_labels[] = "Uk " . $row['ukuran'];
        $size_data[] = (int)$row['total_pesanan'];
    }
}

// Get detailed orders per SKPD
$q_details = $conn->query("
    SELECT s.nama_skpd, p.jenis_kelamin, p.ukuran, p.jenis_mutz, SUM(p.jumlah) as total_jumlah,
           SUM(CASE WHEN p.jenis_mutz = 'Kepala SKPD' THEN p.jumlah * 150000 ELSE p.jumlah * 55000 END) as total_tagihan
    FROM pesanan p
    JOIN skpd s ON p.skpd_id = s.id
    GROUP BY s.nama_skpd, p.jenis_kelamin, p.ukuran, p.jenis_mutz
    ORDER BY s.nama_skpd ASC, p.jenis_kelamin ASC, p.ukuran ASC, p.jenis_mutz ASC
");

$rincian_skpd = [];
if ($q_details) {
    while ($row = $q_details->fetch_assoc()) {
        $rincian_skpd[$row['nama_skpd']][] = $row;
    }
}

// Get payment & pengambilan status per SKPD
$status_skpd = [];
$status_ambil_skpd = [];
$catatan_skpd = [];
$res_status = $conn->query("
    SELECT s.nama_skpd, 
           SUM(CASE WHEN p.status_bayar = 'Belum Lunas' THEN 1 ELSE 0 END) as jml_belum_lunas,
           SUM(CASE WHEN p.status_pengambilan = 'Sudah Diambil' THEN 1 ELSE 0 END) as jml_sudah_diambil,
           COUNT(p.id) as total_pesanan,
           GROUP_CONCAT(DISTINCT NULLIF(TRIM(p.catatan), '') SEPARATOR '; ') as catatan_semua
    FROM pesanan p
    JOIN skpd s ON p.skpd_id = s.id
    GROUP BY s.nama_skpd
");
if ($res_status) {
    while ($row = $res_status->fetch_assoc()) {
        // Status Bayar
        if ($row['jml_belum_lunas'] == 0 && $row['total_pesanan'] > 0) {
            $status_skpd[$row['nama_skpd']] = 'Lunas';
        } elseif ($row['jml_belum_lunas'] < $row['total_pesanan']) {
            $status_skpd[$row['nama_skpd']] = 'Sebagian Lunas';
        } else {
            $status_skpd[$row['nama_skpd']] = 'Belum Lunas';
        }

        // Status Pengambilan
        if ($row['jml_sudah_diambil'] == $row['total_pesanan'] && $row['total_pesanan'] > 0) {
            $status_ambil_skpd[$row['nama_skpd']] = 'Sudah Diambil';
        } elseif ($row['jml_sudah_diambil'] > 0) {
            $status_ambil_skpd[$row['nama_skpd']] = 'Sebagian Diambil';
        } else {
            $status_ambil_skpd[$row['nama_skpd']] = 'Belum Diambil';
        }

        $catatan_skpd[$row['nama_skpd']] = $row['catatan_semua'];
    }
}

// Get all individual orders grouped by SKPD for detail popup modal
$q_all_orders = $conn->query("
    SELECT p.id, p.skpd_id, s.nama_skpd, p.nama_pemesan, p.jenis_kelamin, p.ukuran, p.jumlah, p.jenis_mutz, 
           p.status_bayar, p.status_pengambilan, p.catatan, p.created_at,
           (CASE WHEN p.jenis_mutz = 'Kepala SKPD' THEN p.jumlah * 150000 ELSE p.jumlah * 55000 END) as subtotal
    FROM pesanan p
    JOIN skpd s ON p.skpd_id = s.id
    ORDER BY s.nama_skpd ASC, p.id ASC
");

$skpd_order_items = [];
$skpd_id_map = [];
$skpd_wa_map = [];

// Fetch all SKPD metadata including WA contacts
$q_skpd_meta = $conn->query("SELECT id, nama_skpd, no_wa FROM skpd");
if ($q_skpd_meta) {
    while ($sm = $q_skpd_meta->fetch_assoc()) {
        $skpd_wa_map[$sm['nama_skpd']] = $sm['no_wa'] ?? '';
        $skpd_id_map[$sm['nama_skpd']] = $sm['id'];
    }
}

if ($q_all_orders) {
    while ($row = $q_all_orders->fetch_assoc()) {
        $skpd_order_items[$row['nama_skpd']][] = $row;
        if (!isset($skpd_id_map[$row['nama_skpd']])) {
            $skpd_id_map[$row['nama_skpd']] = $row['skpd_id'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - E-MutZ KORPRI</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4F46E5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-MutZ KORPRI">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <style>
        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }
        .fade-up.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .delay-1 { transition-delay: 0.1s; }
        .delay-2 { transition-delay: 0.2s; }
        .delay-3 { transition-delay: 0.3s; }
        .delay-4 { transition-delay: 0.4s; }
        .delay-5 { transition-delay: 0.5s; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
        </div>

        <div class="card-grid">
            <div class="card fade-up delay-1">
                <h3>Total SKPD Terdaftar</h3>
                <div class="value count-up" data-target="<?= $t_skpd ?>">0</div>
            </div>
            <div class="card fade-up delay-2">
                <h3>Total Pesanan Mutz Laki-laki</h3>
                <div class="value count-up" data-target="<?= $t_l ?>">0</div>
            </div>
            <div class="card perempuan fade-up delay-3">
                <h3>Total Pesanan Mutz Perempuan</h3>
                <div class="value count-up" data-target="<?= $t_p ?>">0</div>
            </div>
            <div class="card fade-up delay-4" style="background: var(--dark); color: var(--light);">
                <h3 style="color: var(--light); opacity: 0.9;">Total Keseluruhan (Pcs)</h3>
                <div class="value count-up" data-target="<?= $t_all ?>" style="color: var(--light);">0</div>
            </div>
            <div class="card fade-up delay-5" style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border: 1px solid #FCD34D;">
                <h3 style="color: #92400E;">Total Tagihan (Rp)</h3>
                <div class="value" style="color: #B45309;">Rp <span class="count-up-money" data-target="<?= $t_tagihan ?>">0</span></div>
            </div>
        </div>
        
        <div class="panel fade-up">
            <h2>Selamat Datang di E-MutZ KORPRI</h2>
            <p>Gunakan menu di samping untuk mengelola Data SKPD, Menginput Pesanan, dan Melihat Rekapitulasi.</p>
        </div>

        <div class="panel fade-up delay-1">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="margin-bottom: 0;">Statistik Pemesanan Mutz</h2>
                <button id="toggleChartBtn" onclick="toggleCharts()" class="btn btn-secondary" style="display: flex; align-items: center; gap: 6px; padding: 0.35rem 0.8rem; font-size: 0.8rem; border-radius: 20px; font-weight: 600;">
                    <svg id="chartIconOff" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    <svg id="chartIconOn" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <span id="chartBtnText">Sembunyikan Grafik</span>
                </button>
            </div>
            <div id="chartContainer" style="display: flex; gap: 2rem; flex-wrap: wrap; align-items: center; justify-content: center; transition: all 0.3s ease;">
                <div style="flex: 1; min-width: 200px; max-width: 250px; margin: 0 auto;">
                    <canvas id="genderChart"></canvas>
                </div>
                <div style="flex: 2; min-width: 300px; max-width: 400px; margin: 0 auto;">
                    <canvas id="topSkpdChart"></canvas>
                </div>
                <div style="flex: 2; min-width: 300px; max-width: 400px; margin: 0 auto;">
                    <canvas id="sizeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="panel fade-up delay-2">
            <h2>Rincian Pesanan per SKPD</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; margin-top: 1.5rem;">
                <?php if(!empty($rincian_skpd)): ?>
                    <?php foreach($rincian_skpd as $nama => $details): ?>
                        <?php 
                            $status = isset($status_skpd[$nama]) ? $status_skpd[$nama] : '-';
                            $status_bg = '#F3F4F6';
                            $status_color = '#374151';
                            if ($status == 'Lunas') {
                                $status_bg = '#DCFCE7'; $status_color = '#15803D';
                            } elseif ($status == 'Sebagian Lunas') {
                                $status_bg = '#FEF3C7'; $status_color = '#D97706';
                            } elseif ($status == 'Belum Lunas') {
                                $status_bg = '#FEE2E2'; $status_color = '#B91C1C';
                            }

                            $status_ambil = isset($status_ambil_skpd[$nama]) ? $status_ambil_skpd[$nama] : 'Belum Diambil';
                            $ambil_bg = '#F3F4F6';
                            $ambil_color = '#374151';
                            if ($status_ambil == 'Sudah Diambil') {
                                $ambil_bg = '#DCFCE7'; $ambil_color = '#15803D';
                            } elseif ($status_ambil == 'Sebagian Diambil') {
                                $ambil_bg = '#DBEAFE'; $ambil_color = '#1D4ED8';
                            } else {
                                $ambil_bg = '#F3F4F6'; $ambil_color = '#6B7280';
                            }
                            $current_skpd_id = $skpd_id_map[$nama] ?? 0;

                            $total_item = 0;
                            $total_rupiah = 0;
                            foreach($details as $d) {
                                $total_item += $d['total_jumlah'];
                                $total_rupiah += $d['total_tagihan'];
                            }
                        ?>
                        <div class="fade-up skpd-card" style="padding: 16px; border-radius: 12px; background: var(--white); box-shadow: 0 2px 5px rgba(0,0,0,0.04);">
                            <div class="skpd-card-header">
                                <h4 style="color: var(--primary); margin: 0; font-size: 0.98rem; font-weight: 700; display: flex; align-items: center; gap: 6px; line-height: 1.3;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M3 21h18"></path><path d="M9 8h1"></path><path d="M9 12h1"></path><path d="M9 16h1"></path><path d="M14 8h1"></path><path d="M14 12h1"></path><path d="M14 16h1"></path><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path></svg>
                                    <span><?= htmlspecialchars($nama) ?></span>
                                </h4>
                                <?php if (!empty($skpd_wa_map[$nama])): ?>
                                    <button type="button" 
                                            class="btn-wa-pill btn-wa-notify" 
                                            data-skpd="<?= htmlspecialchars($nama, ENT_QUOTES) ?>" 
                                            data-wa="<?= htmlspecialchars($skpd_wa_map[$nama], ENT_QUOTES) ?>" 
                                            data-total-qty="<?= $total_item ?>" 
                                            data-total-rp="Rp <?= number_format($total_rupiah, 0, ',', '.') ?>" 
                                            data-status-bayar="<?= $status ?>" 
                                            data-status-ambil="<?= $status_ambil ?>" 
                                            title="Kirim Notifikasi WhatsApp">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                        <?= htmlspecialchars($skpd_wa_map[$nama]) ?>
                                    </button>
                                <?php endif; ?>
                            </div>

                            <ul style="list-style-type: none; margin-left: 0; padding-left: 0; font-size: 0.875rem;">
                                <?php foreach($details as $d): ?>
                                    <li style="display: flex; justify-content: space-between; margin-bottom: 5px; border-bottom: 1px dashed #f0f0f0; padding-bottom: 4px;">
                                        <span>
                                            <span style="display:inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: <?= $d['jenis_kelamin'] == 'Laki-laki' ? 'var(--primary)' : '#EC4899' ?>; margin-right: 5px;"></span>
                                            <?= $d['jenis_kelamin'] ?> (Uk: <?= $d['ukuran'] ?>) - <span style="font-size: 0.78rem; color: var(--gray);"><?= $d['jenis_mutz'] ?></span>
                                        </span>
                                        <strong><?= $d['total_jumlah'] ?> buah</strong>
                                    </li>
                                <?php endforeach; ?>
                                <li style="display: flex; justify-content: space-between; margin-top: 8px; font-weight: 600;">
                                    <span>Total Pesanan:</span>
                                    <span><?= $total_item ?> buah</span>
                                </li>
                                <li style="display: flex; justify-content: space-between; margin-top: 5px; font-weight: 700; color: #B45309; background: #FEF3C7; padding: 5px 8px; border-radius: 6px; font-size: 0.85rem;">
                                    <span>Total Tagihan:</span>
                                    <span>Rp <?= number_format($total_rupiah, 0, ',', '.') ?></span>
                                </li>
                                <li style="display: flex; justify-content: space-between; margin-top: 4px; font-weight: 700; color: <?= $status_color ?>; background: <?= $status_bg ?>; padding: 5px 8px; border-radius: 6px; font-size: 0.825rem;">
                                    <span>Status Bayar:</span>
                                    <span><?= $status ?></span>
                                </li>
                                <li style="display: flex; justify-content: space-between; margin-top: 4px; font-weight: 700; color: <?= $ambil_color ?>; background: <?= $ambil_bg ?>; padding: 5px 8px; border-radius: 6px; font-size: 0.825rem;">
                                    <span>Status Pengambilan:</span>
                                    <span><?= $status_ambil ?></span>
                                </li>
                                <?php if (!empty($catatan_skpd[$nama])): ?>
                                <li style="margin-top: 8px; font-size: 0.825rem; color: var(--gray); background: #F9FAFB; padding: 6px 8px; border-radius: 6px; border: 1px solid var(--gray-light);">
                                    <strong style="display: block; margin-bottom: 2px; color: var(--dark); font-size: 0.75rem;">Catatan:</strong>
                                    <span style="font-style: italic;"><?= htmlspecialchars($catatan_skpd[$nama]) ?></span>
                                </li>
                                <?php endif; ?>
                            </ul>
                            
                            <div class="skpd-card-actions">
                                <button type="button" class="btn-card-primary btn-detail-skpd" 
                                        data-skpd="<?= htmlspecialchars($nama, ENT_QUOTES) ?>" 
                                        data-skpd-id="<?= $current_skpd_id ?>"
                                        data-status="<?= $status ?>"
                                        data-status-color="<?= $status_color ?>"
                                        data-status-bg="<?= $status_bg ?>"
                                        data-status-ambil="<?= $status_ambil ?>"
                                        data-status-ambil-color="<?= $ambil_color ?>"
                                        data-status-ambil-bg="<?= $ambil_bg ?>"
                                        data-total-qty="<?= $total_item ?>"
                                        data-total-rp="Rp <?= number_format($total_rupiah, 0, ',', '.') ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    Lihat Detail Pesanan
                                </button>
                                
                                <div class="skpd-actions-grid">
                                    <button type="button" 
                                            class="btn-wa btn-wa-notify" 
                                            data-skpd="<?= htmlspecialchars($nama, ENT_QUOTES) ?>" 
                                            data-wa="<?= htmlspecialchars($skpd_wa_map[$nama] ?? '', ENT_QUOTES) ?>" 
                                            data-total-qty="<?= $total_item ?>" 
                                            data-total-rp="Rp <?= number_format($total_rupiah, 0, ',', '.') ?>" 
                                            data-status-bayar="<?= $status ?>" 
                                            data-status-ambil="<?= $status_ambil ?>" 
                                            title="Kirim Notifikasi WhatsApp"
                                            style="padding: 7px 8px; font-size: 0.8rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                        Notif WA
                                    </button>

                                    <?php if ($status !== 'Lunas'): ?>
                                        <a href="index.php?lunas_semua_skpd=<?= $current_skpd_id ?>" 
                                           class="btn-card-amber btn-confirm" 
                                           data-confirm-title="Konfirmasi Pembayaran Lunas" 
                                           data-confirm-text="Tandai SEMUA pesanan dari '<?= htmlspecialchars($nama, ENT_QUOTES) ?>' sebagai LUNAS?" 
                                           data-confirm-btn="Ya, Lunas" 
                                           data-confirm-color="#D97706" 
                                           title="Tandai Semua Lunas">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                                            Set Lunas
                                        </a>
                                    <?php elseif ($status_ambil !== 'Sudah Diambil'): ?>
                                        <a href="index.php?ambil_semua_skpd=<?= $current_skpd_id ?>" 
                                           class="btn-card-emerald btn-confirm" 
                                           data-confirm-title="Konfirmasi Pengambilan Barang" 
                                           data-confirm-text="Tandai SEMUA pesanan dari '<?= htmlspecialchars($nama, ENT_QUOTES) ?>' sebagai SUDAH DIAMBIL?" 
                                           data-confirm-btn="Ya, Sudah Diambil" 
                                           data-confirm-color="#10B981" 
                                           title="Tandai Sudah Diambil">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            Set Diambil
                                        </a>
                                    <?php else: ?>
                                        <div style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; border-radius: 8px; font-size: 0.78rem; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 4px; padding: 6px 8px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            Selesai
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--gray); grid-column: 1 / -1;">Belum ada SKPD yang melakukan pemesanan sejauh ini.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal Detail Rincian Pesanan per SKPD -->
        <div id="modalDetailSkpd" class="modal-skpd-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.65); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); z-index: 9999; justify-content: center; align-items: center; padding: 15px;">
            <div class="modal-skpd-content" style="background: var(--white); width: 100%; max-width: 900px; max-height: 90vh; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--gray-light);">
                <!-- Modal Header -->
                <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: flex-start; background: var(--light);">
                    <div>
                        <h3 id="modalSkpdName" style="margin: 0; color: var(--dark); font-size: 1.2rem; font-weight: 700;">-</h3>
                        <div style="display: flex; gap: 8px; margin-top: 8px; font-size: 0.825rem; flex-wrap: wrap;" id="modalSkpdBadges">
                            <!-- Badges -->
                        </div>
                    </div>
                    <button id="closeModalSkpd" type="button" title="Tutup" style="background: transparent; border: none; font-size: 1.5rem; line-height: 1; color: var(--gray); cursor: pointer; padding: 4px 8px; border-radius: 6px; transition: color 0.2s;">&times;</button>
                </div>

                <!-- Modal Body Table -->
                <div style="padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1;">
                    <div class="table-responsive" style="margin-top: 0; border: 1px solid var(--gray-light); border-radius: 8px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem; margin-top: 0;">
                            <thead>
                                <tr style="background: var(--light);">
                                    <th style="padding: 10px 12px; width: 40px; text-align: center;">No</th>
                                    <th style="padding: 10px 12px;">Nama Pemesan</th>
                                    <th style="padding: 10px 12px; text-align: center;">Gender</th>
                                    <th style="padding: 10px 12px; text-align: center;">Jenis Mutz</th>
                                    <th style="padding: 10px 12px; text-align: center;">Ukuran</th>
                                    <th style="padding: 10px 12px; text-align: center;">Jumlah</th>
                                    <th style="padding: 10px 12px; text-align: right;">Tagihan</th>
                                    <th style="padding: 10px 12px; text-align: center;">Status Bayar</th>
                                    <th style="padding: 10px 12px; text-align: center;">Status Ambil</th>
                                    <th style="padding: 10px 12px;">Catatan</th>
                                </tr>
                            </thead>
                            <tbody id="modalSkpdTableBody">
                                <!-- Injected via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center; background: var(--light); flex-wrap: wrap; gap: 10px;">
                    <div id="modalSkpdFooterSummary" style="font-weight: 700; color: var(--dark); font-size: 0.9rem;">
                        <!-- Summary info -->
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <button id="btnModalWa" type="button" class="btn-wa btn-wa-notify" style="padding: 7px 14px; font-size: 0.825rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            Kirim WA
                        </button>
                        <a id="btnModalLunasSemua" href="#" class="btn-card-amber btn-confirm" data-confirm-title="Konfirmasi Pembayaran Lunas" data-confirm-text="Tandai SEMUA pesanan SKPD ini sebagai LUNAS?" data-confirm-btn="Ya, Sudah Lunas" data-confirm-color="#D97706" style="width: auto; padding: 7px 14px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            Tandai Semua Lunas
                        </a>
                        <a id="btnModalAmbilSemua" href="#" class="btn-card-emerald btn-confirm" data-confirm-title="Konfirmasi Pengambilan Barang" data-confirm-text="Tandai SEMUA pesanan SKPD ini sebagai SUDAH DIAMBIL?" data-confirm-btn="Ya, Sudah Diambil" data-confirm-color="#10B981" style="width: auto; padding: 7px 14px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Tandai Semua Sudah Diambil
                        </a>
                        <a id="btnFilterKePesanan" href="#" class="btn-card-primary" style="width: auto; padding: 7px 14px; text-decoration: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            Kelola di Halaman Pesanan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Load Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Data from PHP
        const t_l = <?= (int)$t_l ?>;
        const t_p = <?= (int)$t_p ?>;
        const skpdLabels = <?= json_encode($top_skpd_labels) ?>;
        const skpdData = <?= json_encode($top_skpd_data) ?>;
        const sizeLabels = <?= json_encode($size_labels) ?>;
        const sizeData = <?= json_encode($size_data) ?>;

        // Render Pie Chart
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    data: [t_l, t_p],
                    backgroundColor: ['#4F46E5', '#EC4899'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Proporsi per Jenis Kelamin', font: { size: 16 } }
                }
            }
        });

        // Render Bar Chart
        const topSkpdCtx = document.getElementById('topSkpdChart').getContext('2d');
        new Chart(topSkpdCtx, {
            type: 'bar',
            data: {
                labels: skpdLabels,
                datasets: [{
                    label: 'Total Topi',
                    data: skpdData,
                    backgroundColor: '#10B981',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: '5 SKPD dengan Pesanan Terbanyak', font: { size: 16 } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });

        // Render Size Chart
        const sizeCtx = document.getElementById('sizeChart').getContext('2d');
        new Chart(sizeCtx, {
            type: 'bar',
            data: {
                labels: sizeLabels,
                datasets: [{
                    label: 'Total Topi',
                    data: sizeData,
                    backgroundColor: '#F59E0B',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Distribusi Ukuran Mutz Terlaris', font: { size: 16 } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });

        // Chart Toggle Logic
        const chartContainer = document.getElementById('chartContainer');
        const chartBtnText = document.getElementById('chartBtnText');
        const chartIconOn = document.getElementById('chartIconOn');
        const chartIconOff = document.getElementById('chartIconOff');
        
        function setChartVisibility(show) {
            if (show) {
                chartContainer.style.display = 'flex';
                chartBtnText.innerText = 'Sembunyikan Grafik';
                chartIconOn.style.display = 'block';
                chartIconOff.style.display = 'none';
            } else {
                chartContainer.style.display = 'none';
                chartBtnText.innerText = 'Tampilkan Grafik';
                chartIconOn.style.display = 'none';
                chartIconOff.style.display = 'block';
            }
        }

        function toggleCharts() {
            const isCurrentlyVisible = chartContainer.style.display !== 'none';
            setChartVisibility(!isCurrentlyVisible);
            localStorage.setItem('showCharts', !isCurrentlyVisible);
        }

        // Initialize from localStorage
        const savedPreference = localStorage.getItem('showCharts');
        if (savedPreference === 'false') {
            setChartVisibility(false);
        } else {
            setChartVisibility(true);
        }

        // Animasi Data Muncul Perlahan & Angka Bergerak
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Muncul Perlahan (Intersection Observer)
            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        obs.unobserve(entry.target); // Mencegah glitch saat scroll berulang
                    }
                });
            }, { threshold: 0.05 });

            document.querySelectorAll('.fade-up').forEach((el) => {
                observer.observe(el);
            });

            // 2. Animasi Angka Bergerak (Count Up)
            function animateCountUp(elements) {
                elements.forEach(el => {
                    const target = parseInt(el.getAttribute('data-target')) || 0;
                    const duration = 2000;
                    const step = target / (duration / 16); 
                    let current = 0;
                    
                    if(target === 0) { 
                        el.innerText = "0"; 
                        return; 
                    }
                    
                    const updateCounter = () => {
                        current += step;
                        if (current < target) {
                            el.innerText = Math.ceil(current).toLocaleString('id-ID');
                            requestAnimationFrame(updateCounter);
                        } else {
                            el.innerText = target.toLocaleString('id-ID');
                        }
                    };
                    
                    // Mulai setelah delay sedikit agar animasi slide up selesai setengah
                    setTimeout(() => updateCounter(), 400);
                });
            }
            
            animateCountUp(document.querySelectorAll('.count-up'));
            animateCountUp(document.querySelectorAll('.count-up-money'));
        });

        // Detail Rincian Pesanan per SKPD Modal Logic
        const skpdOrdersData = <?= json_encode($skpd_order_items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
        const skpdWaData = <?= json_encode($skpd_wa_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
        const modalDetailSkpd = document.getElementById('modalDetailSkpd');
        const closeModalSkpd = document.getElementById('closeModalSkpd');
        const modalSkpdName = document.getElementById('modalSkpdName');
        const modalSkpdBadges = document.getElementById('modalSkpdBadges');
        const modalSkpdTableBody = document.getElementById('modalSkpdTableBody');
        const modalSkpdFooterSummary = document.getElementById('modalSkpdFooterSummary');
        const btnFilterKePesanan = document.getElementById('btnFilterKePesanan');
        const btnModalAmbilSemua = document.getElementById('btnModalAmbilSemua');
        const btnModalLunasSemua = document.getElementById('btnModalLunasSemua');
        const btnModalWa = document.getElementById('btnModalWa');

        function openSkpdModal(skpdName, skpdId, status, statusColor, statusBg, statusAmbil, statusAmbilColor, statusAmbilBg, totalQty, totalRp) {
            modalSkpdName.innerText = skpdName;
            
            // Set Badges
            modalSkpdBadges.innerHTML = `
                <span style="background: #e0e7ff; color: #4338ca; padding: 4px 8px; border-radius: 4px; font-weight: 600;">Total: ${totalQty} buah</span>
                <span style="background: #fef3c7; color: #b45309; padding: 4px 8px; border-radius: 4px; font-weight: 600;">${totalRp}</span>
                <span style="background: ${statusBg}; color: ${statusColor}; padding: 4px 8px; border-radius: 4px; font-weight: 600;">Bayar: ${status}</span>
                <span style="background: ${statusAmbilBg}; color: ${statusAmbilColor}; padding: 4px 8px; border-radius: 4px; font-weight: 600;">Ambil: ${statusAmbil}</span>
            `;
            
            // Set Action Link
            btnFilterKePesanan.href = `pesanan.php?filter_skpd=${skpdId}`;

            // Set WhatsApp Modal Link
            if (btnModalWa) {
                btnModalWa.setAttribute('data-skpd', skpdName);
                btnModalWa.setAttribute('data-wa', skpdWaData[skpdName] || '');
                btnModalWa.setAttribute('data-total-qty', totalQty);
                btnModalWa.setAttribute('data-total-rp', totalRp);
                btnModalWa.setAttribute('data-status-bayar', status);
                btnModalWa.setAttribute('data-status-ambil', statusAmbil);
            }

            // Set Bulk Lunas Action Link
            if (btnModalLunasSemua) {
                if (status === 'Lunas') {
                    btnModalLunasSemua.style.display = 'none';
                } else {
                    btnModalLunasSemua.style.display = 'inline-flex';
                    btnModalLunasSemua.href = `index.php?lunas_semua_skpd=${skpdId}`;
                    btnModalLunasSemua.setAttribute('data-confirm-text', `Tandai SEMUA pesanan dari ${skpdName} sebagai LUNAS?`);
                }
            }

            // Set Bulk Ambil Action Link
            if (btnModalAmbilSemua) {
                if (statusAmbil === 'Sudah Diambil') {
                    btnModalAmbilSemua.style.display = 'none';
                } else {
                    btnModalAmbilSemua.style.display = 'inline-flex';
                    btnModalAmbilSemua.href = `index.php?ambil_semua_skpd=${skpdId}`;
                    btnModalAmbilSemua.setAttribute('data-confirm-text', `Tandai SEMUA pesanan dari ${skpdName} sebagai SUDAH DIAMBIL?`);
                }
            }
            
            // Populate Table
            const items = skpdOrdersData[skpdName] || [];
            if (items.length === 0) {
                modalSkpdTableBody.innerHTML = `<tr><td colspan="10" style="text-align: center; padding: 20px; color: var(--gray);">Tidak ada rincian data pemesan untuk SKPD ini.</td></tr>`;
            } else {
                let html = '';
                items.forEach((item, index) => {
                    const genderColor = item.jenis_kelamin === 'Laki-laki' ? 'var(--primary)' : '#EC4899';
                    const bayarBg = item.status_bayar === 'Lunas' ? '#DCFCE7' : '#FEE2E2';
                    const bayarColor = item.status_bayar === 'Lunas' ? '#15803D' : '#B91C1C';
                    
                    let ambilBg = '#F3F4F6', ambilColor = '#374151';
                    if (item.status_pengambilan === 'Sudah Diambil') { ambilBg = '#DCFCE7'; ambilColor = '#15803D'; }
                    else if (item.status_pengambilan === 'Siap Diambil') { ambilBg = '#E0E7FF'; ambilColor = '#4338CA'; }
                    else if (item.status_pengambilan === 'Sedang Dibuat') { ambilBg = '#FEF3C7'; ambilColor = '#D97706'; }

                    const subtotalFormatted = 'Rp ' + parseInt(item.subtotal || 0).toLocaleString('id-ID');
                    const pemesan = item.nama_pemesan && item.nama_pemesan.trim() !== '' ? item.nama_pemesan : '-';
                    const catatan = item.catatan && item.catatan.trim() !== '' ? item.catatan : '-';

                    html += `
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 10px 12px; text-align: center; color: var(--gray);">${index + 1}</td>
                            <td style="padding: 10px 12px; font-weight: 600; color: var(--dark);">${pemesan}</td>
                            <td style="padding: 10px 12px; text-align: center;">
                                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background-color:${genderColor}; margin-right:4px;"></span>
                                ${item.jenis_kelamin}
                            </td>
                            <td style="padding: 10px 12px; text-align: center;"><span style="font-size:0.8rem; background:var(--light); padding:2px 6px; border-radius:4px;">${item.jenis_mutz}</span></td>
                            <td style="padding: 10px 12px; text-align: center; font-weight: 600;">${item.ukuran}</td>
                            <td style="padding: 10px 12px; text-align: center; font-weight: 600;">${item.jumlah}</td>
                            <td style="padding: 10px 12px; text-align: right; font-weight: 600; color:#B45309;">${subtotalFormatted}</td>
                            <td style="padding: 10px 12px; text-align: center;">
                                <span style="background:${bayarBg}; color:${bayarColor}; padding:3px 8px; border-radius:12px; font-size:0.75rem; font-weight:700;">${item.status_bayar}</span>
                            </td>
                            <td style="padding: 10px 12px; text-align: center;">
                                <span style="background:${ambilBg}; color:${ambilColor}; padding:3px 8px; border-radius:12px; font-size:0.75rem; font-weight:600;">${item.status_pengambilan}</span>
                            </td>
                            <td style="padding: 10px 12px; font-size:0.8rem; color:var(--gray);">${catatan}</td>
                        </tr>
                    `;
                });
                modalSkpdTableBody.innerHTML = html;
            }
            
            modalSkpdFooterSummary.innerText = `Menampilkan ${items.length} rincian pemesan (${totalQty} buah mutz)`;
            
            modalDetailSkpd.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeSkpdModal() {
            modalDetailSkpd.style.display = 'none';
            document.body.style.overflow = '';
        }

        document.querySelectorAll('.btn-detail-skpd').forEach(btn => {
            btn.addEventListener('click', function() {
                const skpdName = this.getAttribute('data-skpd');
                const skpdId = this.getAttribute('data-skpd-id');
                const status = this.getAttribute('data-status');
                const statusColor = this.getAttribute('data-status-color');
                const statusBg = this.getAttribute('data-status-bg');
                const statusAmbil = this.getAttribute('data-status-ambil');
                const statusAmbilColor = this.getAttribute('data-status-ambil-color');
                const statusAmbilBg = this.getAttribute('data-status-ambil-bg');
                const totalQty = this.getAttribute('data-total-qty');
                const totalRp = this.getAttribute('data-total-rp');
                openSkpdModal(skpdName, skpdId, status, statusColor, statusBg, statusAmbil, statusAmbilColor, statusAmbilBg, totalQty, totalRp);
            });
        });

        if (closeModalSkpd) closeModalSkpd.addEventListener('click', closeSkpdModal);
        if (modalDetailSkpd) {
            modalDetailSkpd.addEventListener('click', function(e) {
                if (e.target === modalDetailSkpd) {
                    closeSkpdModal();
                }
            });
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modalDetailSkpd && modalDetailSkpd.style.display === 'flex') {
                closeSkpdModal();
            }
        });
    </script>
</body>
</html>
