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
$skpd_order_items_by_id = [];
$skpd_id_map = [];
$skpd_wa_map = [];

// Data for Marquee Ticker
$q_tunggakan = $conn->query("
    SELECT SUM(CASE WHEN jenis_mutz = 'Kepala SKPD' THEN jumlah * 150000 ELSE jumlah * 55000 END) as tunggakan 
    FROM pesanan 
    WHERE status_bayar = 'Belum Lunas'
");
$tunggakan = 0;
if ($q_tunggakan && $row = $q_tunggakan->fetch_assoc()) {
    $tunggakan = (float)$row['tunggakan'];
}

$q_siap = $conn->query("SELECT SUM(jumlah) as siap FROM pesanan WHERE status_pengambilan = 'Siap Diambil'");
$total_siap = 0;
if ($q_siap && $row = $q_siap->fetch_assoc()) {
    $total_siap = (int)$row['siap'];
}

// Fetch all SKPD metadata including WA contacts
$q_skpd_meta = $conn->query("SELECT id, nama_skpd, no_wa FROM skpd");
if ($q_skpd_meta) {
    while ($sm = $q_skpd_meta->fetch_assoc()) {
        $skpd_wa_map[$sm['nama_skpd']] = $sm['no_wa'] ?? '';
        $skpd_wa_map[$sm['id']] = $sm['no_wa'] ?? '';
        $skpd_id_map[$sm['nama_skpd']] = $sm['id'];
    }
}

if ($q_all_orders) {
    while ($row = $q_all_orders->fetch_assoc()) {
        $skpd_order_items[$row['nama_skpd']][] = $row;
        $skpd_order_items_by_id[$row['skpd_id']][] = $row;
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
    <meta name="theme-color" content="#2563EB">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-MutZ KORPRI">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/app.css?v=<?= time() ?>">
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
        
        /* Marquee Styles */
        .marquee-container {
            background: var(--primary);
            border-top: 3px solid var(--dark);
            border-bottom: 3px solid var(--dark);
            padding: 8px 0;
            margin: -2rem -2rem 2rem -2rem; /* Stretch across main content */
            overflow: hidden;
            white-space: nowrap;
            box-shadow: 0 4px 0px var(--dark);
            position: relative;
            z-index: 80;
        }
        [data-theme="dark"] .marquee-container {
            border-color: var(--white);
            box-shadow: 0 4px 0px var(--white);
        }
        .marquee-content {
            display: inline-block;
            animation: marquee 30s linear infinite;
            font-weight: 800;
            font-size: 0.95rem;
            text-transform: uppercase;
            color: var(--dark);
        }
        @keyframes marquee {
            0% { transform: translateX(100vw); }
            100% { transform: translateX(-100%); }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content page-transition">
        <div class="marquee-container">
            <div class="marquee-content">
                ⚠️ INFO REKAP: Terdapat <?= $total_siap ?> pesanan Mutz yang SIAP DIAMBIL namun belum diambil. &nbsp;&nbsp; • &nbsp;&nbsp; 💸 TOTAL TUNGGAKAN PEMBAYARAN SAAT INI: Rp <?= number_format($tunggakan, 0, ',', '.') ?>. &nbsp;&nbsp; • &nbsp;&nbsp; 🧢 TOTAL PESANAN KESELURUHAN: <?= number_format((int)$t_all, 0, ',', '.') ?> PCS. &nbsp;&nbsp; • &nbsp;&nbsp; ⚡ E-MUTZ KORPRI DASHBOARD SYSTEM
            </div>
        </div>

        <div class="header">
            <div>
                <h1>Dashboard</h1>
                <p style="margin: 4px 0 0 0; color: var(--gray); font-size: 0.875rem;">Ringkasan pemesanan topi mutz dan status SKPD terkini</p>
            </div>
        </div>

        <div class="dashboard-stats" style="margin-top: 1rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div class="card fade-up delay-1" style="background-color: #00E5FF; border: 3px solid #000; box-shadow: 6px 6px 0px #000; color: #000;">
                <h3 style="font-weight: 800; font-size: 1.1rem;">Mutz Laki-laki</h3>
                <div class="value count-up" data-target="<?= (int)$t_l ?>" style="font-weight: 900;"><?= number_format((int)$t_l, 0, ',', '.') ?></div>
            </div>
            <div class="card fade-up delay-2" style="background-color: #4ADE80; border: 3px solid #000; box-shadow: 6px 6px 0px #000; color: #000;">
                <h3 style="font-weight: 800; font-size: 1.1rem;">Kepala SKPD</h3>
                <div class="value count-up" data-target="<?= (int)$t_k ?>" style="font-weight: 900;"><?= number_format((int)$t_k, 0, ',', '.') ?></div>
            </div>
            <div class="card fade-up delay-3" style="background-color: #FF007F; border: 3px solid #000; box-shadow: 6px 6px 0px #000; color: #FFF;">
                <h3 style="font-weight: 800; font-size: 1.1rem; color: #FFF;">Mutz Perempuan</h3>
                <div class="value count-up" data-target="<?= (int)$t_p ?>" style="font-weight: 900;"><?= number_format((int)$t_p, 0, ',', '.') ?></div>
            </div>
            <div class="card fade-up delay-4" style="background-color: #FFE600; border: 3px solid #000; box-shadow: 6px 6px 0px #000; color: #000;">
                <h3 style="font-weight: 800; font-size: 1.1rem;">Total (Pcs)</h3>
                <div class="value count-up" data-target="<?= (int)$t_all ?>" style="font-weight: 900;"><?= number_format((int)$t_all, 0, ',', '.') ?></div>
            </div>
            <div class="card fade-up delay-5" style="background-color: #B4A0FF; border: 3px solid #000; box-shadow: 6px 6px 0px #000; color: #000;">
                <h3 style="font-weight: 800; font-size: 1.1rem;">Tagihan (Rp)</h3>
                <div class="value" style="font-weight: 900;">Rp <span class="count-up-money" data-target="<?= (float)$t_tagihan ?>"><?= number_format((float)$t_tagihan, 0, ',', '.') ?></span></div>
            </div>
        </div>
        
        <div class="panel fade-up" style="border: 3px solid #000; box-shadow: 6px 6px 0px #000; border-radius: 8px; margin-top: 1.5rem;">
            <h2 style="font-weight: 900; text-transform: uppercase;">Selamat Datang di E-MutZ KORPRI</h2>
            <p style="font-weight: 600; font-size: 1.05rem;">Gunakan menu di samping untuk mengelola Data SKPD, Menginput Pesanan, dan Melihat Rekapitulasi.</p>
        </div>

        <div class="panel fade-up delay-1" style="border: 3px solid #000; box-shadow: 6px 6px 0px #000; border-radius: 8px; margin-top: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="margin-bottom: 0; font-weight: 900; text-transform: uppercase;">Statistik Pemesanan Mutz</h2>
                <button id="toggleChartBtn" onclick="toggleCharts()" class="btn btn-secondary" style="display: flex; align-items: center; gap: 6px; padding: 0.35rem 0.8rem; font-size: 0.8rem; border-radius: 4px; font-weight: 800; border: 2px solid #000; box-shadow: 2px 2px 0px #000; background: #FFF; color: #000;">
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

        <div class="panel fade-up delay-2" style="border: 3px solid #000; box-shadow: 6px 6px 0px #000; border-radius: 8px; margin-top: 1.5rem; background: #FFF;">
            <h2 style="font-weight: 900; text-transform: uppercase; border-bottom: 4px solid #000; padding-bottom: 0.5rem;">Rincian Pesanan per SKPD</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; margin-top: 1.5rem;">
                <?php if(!empty($rincian_skpd)): ?>
                    <?php foreach($rincian_skpd as $nama => $details): ?>
                        <?php 
                            $status = isset($status_skpd[$nama]) ? $status_skpd[$nama] : '-';
                            $status_bg = '#E5E7EB';
                            $status_color = '#000';
                            if ($status == 'Lunas') {
                                $status_bg = '#4ADE80';
                            } elseif ($status == 'Sebagian Lunas') {
                                $status_bg = '#FDE047';
                            } else {
                                $status_bg = '#F87171';
                            }

                            $status_ambil = isset($status_ambil_skpd[$nama]) ? $status_ambil_skpd[$nama] : '-';
                            $ambil_bg = '#E5E7EB';
                            $ambil_color = '#000';
                            if ($status_ambil == 'Sudah Diambil') {
                                $ambil_bg = '#6EE7B7';
                            } elseif ($status_ambil == 'Sebagian Diambil') {
                                $ambil_bg = '#93C5FD';
                            } else {
                                $ambil_bg = '#E5E7EB';
                            }
                            $current_skpd_id = $skpd_id_map[$nama] ?? 0;

                            $total_item = 0;
                            $total_rupiah = 0;
                            foreach($details as $d) {
                                $total_item += $d['total_jumlah'];
                                $total_rupiah += $d['total_tagihan'];
                            }
                        ?>
                        <div class="fade-up skpd-card">
                            <div class="skpd-card-header">
                                <h4 class="skpd-card-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M9 8h1"></path><path d="M9 12h1"></path><path d="M9 16h1"></path><path d="M14 8h1"></path><path d="M14 12h1"></path><path d="M14 16h1"></path><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path></svg>
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
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" /><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" /></svg>
                                        <?= htmlspecialchars($skpd_wa_map[$nama]) ?>
                                    </button>
                                <?php endif; ?>
                            </div>

                            <ul class="skpd-card-list">
                                <?php foreach($details as $d): ?>
                                    <li class="skpd-card-list-item">
                                        <span>
                                            <span style="display:inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: <?= $d['jenis_kelamin'] == 'Laki-laki' ? 'var(--primary)' : '#EC4899' ?>; margin-right: 5px;"></span>
                                            <?= $d['jenis_kelamin'] ?> (Uk: <?= $d['ukuran'] ?>) - <span style="font-size: 0.78rem; color: var(--gray);"><?= $d['jenis_mutz'] ?></span>
                                        </span>
                                        <strong><?= $d['total_jumlah'] ?> buah</strong>
                                    </li>
                                <?php endforeach; ?>
                                <li style="display: flex; justify-content: space-between; margin-top: 10px; font-weight: 700; font-size: 0.95rem;">
                                    <span>Total Pesanan:</span>
                                    <span style="color: var(--primary);"><?= $total_item ?> buah</span>
                                </li>
                                <li style="display: flex; justify-content: space-between; margin-top: 6px; font-weight: 800; color: #000; background: #FEF08A; padding: 6px 10px; border-radius: 4px; border: 2px solid #000; box-shadow: 2px 2px 0px #000; font-size: 0.95rem;">
                                    <span>Total Tagihan:</span>
                                    <span>Rp <?= number_format($total_rupiah, 0, ',', '.') ?></span>
                                </li>
                                <li style="display: flex; justify-content: space-between; margin-top: 6px; font-weight: 800; color: #000; background: <?= $status_bg ?>; padding: 6px 10px; border-radius: 4px; border: 2px solid #000; box-shadow: 2px 2px 0px #000; font-size: 0.85rem;">
                                    <span>Status Bayar:</span>
                                    <span><?= $status ?></span>
                                </li>
                                <li style="display: flex; justify-content: space-between; margin-top: 6px; font-weight: 800; color: #000; background: <?= $ambil_bg ?>; padding: 6px 10px; border-radius: 4px; border: 2px solid #000; box-shadow: 2px 2px 0px #000; font-size: 0.85rem;">
                                    <span>Status Pengambilan:</span>
                                    <span><?= $status_ambil ?></span>
                                </li>
                                <?php if (!empty($catatan_skpd[$nama])): ?>
                                <li style="margin-top: 10px; font-size: 0.825rem; color: var(--gray); background: var(--light); padding: 8px 10px; border-radius: 6px; border: 1px solid var(--gray-light);">
                                    <strong style="display: flex; align-items: center; gap: 4px; margin-bottom: 4px; color: var(--dark); font-size: 0.75rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        Catatan:
                                    </strong>
                                    <span style="font-style: italic; display: block; line-height: 1.4;"><?= htmlspecialchars($catatan_skpd[$nama]) ?></span>
                                </li>
                                <?php endif; ?>
                            </ul>
                            
                            <div class="skpd-card-actions">
                                <button type="button" class="btn-card-primary btn-detail-skpd" 
                                        onclick="window.openSkpdFromButton(this)"
                                        data-skpd="<?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?>" 
                                        data-skpd-id="<?= (int)$current_skpd_id ?>"
                                        data-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                                        data-status-color="#000"
                                        data-status-bg="<?= $status_bg ?>"
                                        data-status-ambil="<?= htmlspecialchars($status_ambil, ENT_QUOTES, 'UTF-8') ?>"
                                        data-status-ambil-color="#000"
                                        data-status-ambil-bg="<?= $ambil_bg ?>"
                                        data-total-qty="<?= (int)$total_item ?>"
                                        data-total-rp="Rp <?= number_format($total_rupiah, 0, ',', '.') ?>"
                                        style="border: 2px solid #000; box-shadow: 2px 2px 0px #000; border-radius: 4px; font-weight: 800;">
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
                                            style="padding: 7px 8px; font-size: 0.8rem; border: 2px solid #000; box-shadow: 2px 2px 0px #000; border-radius: 4px; font-weight: 800;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" /><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" /></svg>
                                        Notif WA
                                    </button>

                                    <?php if ($status !== 'Lunas'): ?>
                                        <a href="index.php?lunas_semua_skpd=<?= $current_skpd_id ?>" 
                                           class="btn-card-amber btn-confirm" 
                                           data-confirm-title="Konfirmasi Pembayaran Lunas" 
                                           data-confirm-text="Tandai SEMUA pesanan dari '<?= htmlspecialchars($nama, ENT_QUOTES) ?>' sebagai LUNAS?" 
                                           data-confirm-btn="Ya, Lunas" 
                                           data-confirm-color="#D97706" 
                                           title="Tandai Semua Lunas"
                                           style="border: 2px solid #000; box-shadow: 2px 2px 0px #000; border-radius: 4px; font-weight: 800; text-align: center; justify-content: center; background: #FDE047; color: #000;">
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
                                        <div style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; border-radius: 6px; font-size: 0.78rem; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 4px; padding: 6px 8px;">
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
        <div id="modalDetailSkpd" class="modal-skpd-overlay" style="display: none;" onclick="if(event.target===this) window.closeSkpdModal();">
            <div class="modal-skpd-content" style="border: 4px solid #000; box-shadow: 8px 8px 0px #000; border-radius: 0; background: #fff;">
                <!-- Modal Header -->
                <div style="padding: 1.25rem 1.5rem; border-bottom: 4px solid #000; display: flex; justify-content: space-between; align-items: flex-start; background: #FEF08A;">
                    <div>
                        <h3 id="modalSkpdName" style="margin: 0; color: #000; font-size: 1.4rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">-</h3>
                        <div style="display: flex; gap: 8px; margin-top: 8px; font-size: 0.825rem; flex-wrap: wrap;" id="modalSkpdBadges">
                            <!-- Badges -->
                        </div>
                    </div>
                    <button id="closeModalSkpd" type="button" onclick="window.closeSkpdModal()" title="Tutup" style="background: #F87171; border: 2px solid #000; font-size: 1.4rem; line-height: 1; color: #000; font-weight: 900; cursor: pointer; padding: 4px 12px; border-radius: 0; box-shadow: 2px 2px 0px #000; transition: transform 0.1s;">&times;</button>
                </div>

                <!-- Modal Body Table -->
                <div style="padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1; background: #fff;">
                    <div class="table-responsive" style="margin-top: 0; border: 2px solid #000; border-radius: 0;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem; margin-top: 0;">
                            <thead>
                                <tr style="background: #E5E7EB; border-bottom: 2px solid #000;">
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
                        <button id="btnModalWa" type="button" class="btn-wa btn-wa-notify" style="padding: 7px 14px; font-size: 0.825rem; border: 2px solid #000; box-shadow: 2px 2px 0px #000; font-weight: 800; border-radius: 4px; background: #FFF;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" /><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" /></svg>
                            Kirim WA
                        </button>
                        <a id="btnModalLunasSemua" href="#" onclick="window.closeSkpdModal && window.closeSkpdModal()" class="btn-card-amber btn-confirm" data-confirm-title="Konfirmasi Pembayaran Lunas" data-confirm-text="Tandai SEMUA pesanan SKPD ini sebagai LUNAS?" data-confirm-btn="Ya, Sudah Lunas" data-confirm-color="#D97706" style="width: auto; padding: 7px 14px; border: 2px solid #000; box-shadow: 2px 2px 0px #000; font-weight: 800; border-radius: 4px; background: #FDE047; color: #000;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            Tandai Semua Lunas
                        </a>
                        <a id="btnModalAmbilSemua" href="#" onclick="window.closeSkpdModal && window.closeSkpdModal()" class="btn-card-emerald btn-confirm" data-confirm-title="Konfirmasi Pengambilan Barang" data-confirm-text="Tandai SEMUA pesanan SKPD ini sebagai SUDAH DIAMBIL?" data-confirm-btn="Ya, Sudah Diambil" data-confirm-color="#10B981" style="width: auto; padding: 7px 14px; border: 2px solid #000; box-shadow: 2px 2px 0px #000; font-weight: 800; border-radius: 4px; background: #6EE7B7; color: #000;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Tandai Semua Sudah Diambil
                        </a>
                        <a id="btnFilterKePesanan" href="#" onclick="window.closeSkpdModal && window.closeSkpdModal()" class="btn-card-primary" style="width: auto; padding: 7px 14px; text-decoration: none; border: 2px solid #000; box-shadow: 2px 2px 0px #000; font-weight: 800; border-radius: 4px; background: #FFF; color: #000;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            Kelola di Halaman Pesanan
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. SCRIPT MODAL DETAIL SKPD (Eksekusi Utama & Mandiri) -->
        <script>
            window.skpdOrdersData = <?= json_encode($skpd_order_items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
            window.skpdOrdersById = <?= json_encode($skpd_order_items_by_id, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
            window.skpdWaData = <?= json_encode($skpd_wa_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;

            window.openSkpdFromButton = function(btn) {
                if (!btn) return;
                const skpdName = btn.getAttribute('data-skpd') || '';
                const skpdId = btn.getAttribute('data-skpd-id') || '0';
                const status = btn.getAttribute('data-status') || '-';
                const statusColor = btn.getAttribute('data-status-color') || '#374151';
                const statusBg = btn.getAttribute('data-status-bg') || '#F3F4F6';
                const statusAmbil = btn.getAttribute('data-status-ambil') || 'Belum Diambil';
                const statusAmbilColor = btn.getAttribute('data-status-ambil-color') || '#374151';
                const statusAmbilBg = btn.getAttribute('data-status-ambil-bg') || '#F3F4F6';
                const totalQty = btn.getAttribute('data-total-qty') || '0';
                const totalRp = btn.getAttribute('data-total-rp') || 'Rp 0';
                
                window.openSkpdModal(skpdName, skpdId, status, statusColor, statusBg, statusAmbil, statusAmbilColor, statusAmbilBg, totalQty, totalRp);
            };

            window.openSkpdModal = function(skpdName, skpdId, status, statusColor, statusBg, statusAmbil, statusAmbilColor, statusAmbilBg, totalQty, totalRp) {
                const modalDetailSkpd = document.getElementById('modalDetailSkpd');
                const modalSkpdName = document.getElementById('modalSkpdName');
                const modalSkpdBadges = document.getElementById('modalSkpdBadges');
                const modalSkpdTableBody = document.getElementById('modalSkpdTableBody');
                const modalSkpdFooterSummary = document.getElementById('modalSkpdFooterSummary');
                const btnFilterKePesanan = document.getElementById('btnFilterKePesanan');
                const btnModalAmbilSemua = document.getElementById('btnModalAmbilSemua');
                const btnModalLunasSemua = document.getElementById('btnModalLunasSemua');
                const btnModalWa = document.getElementById('btnModalWa');

                if (!modalDetailSkpd) return;

                const cleanName = (skpdName || '').trim();
                const cleanId = String(skpdId || '');

                if (modalSkpdName) modalSkpdName.innerText = cleanName || '-';
                
                // Set Badges
                if (modalSkpdBadges) {
                    modalSkpdBadges.innerHTML = `
                        <span style="background: #E5E7EB; color: #000; border: 2px solid #000; box-shadow: 2px 2px 0px #000; padding: 4px 8px; border-radius: 0; font-weight: 800; font-family: monospace;">Total: ${totalQty || 0} buah</span>
                        <span style="background: #FEF08A; color: #000; border: 2px solid #000; box-shadow: 2px 2px 0px #000; padding: 4px 8px; border-radius: 0; font-weight: 800; font-family: monospace;">${totalRp || 'Rp 0'}</span>
                        <span style="background: ${statusBg || '#E5E7EB'}; color: #000; border: 2px solid #000; box-shadow: 2px 2px 0px #000; padding: 4px 8px; border-radius: 0; font-weight: 800; font-family: monospace;">Bayar: ${status || '-'}</span>
                        <span style="background: ${statusAmbilBg || '#E5E7EB'}; color: #000; border: 2px solid #000; box-shadow: 2px 2px 0px #000; padding: 4px 8px; border-radius: 0; font-weight: 800; font-family: monospace;">Ambil: ${statusAmbil || '-'}</span>
                    `;
                }
                
                // Set Action Link
                if (btnFilterKePesanan) {
                    btnFilterKePesanan.href = `pesanan.php?filter_skpd=${encodeURIComponent(cleanId)}`;
                }

                // Set WhatsApp Modal Link
                if (btnModalWa) {
                    let targetWa = '';
                    if (window.skpdWaData) {
                        targetWa = window.skpdWaData[cleanId] || window.skpdWaData[cleanName] || '';
                    }
                    btnModalWa.setAttribute('data-skpd', cleanName);
                    btnModalWa.setAttribute('data-wa', targetWa);
                    btnModalWa.setAttribute('data-total-qty', totalQty || '0');
                    btnModalWa.setAttribute('data-total-rp', totalRp || 'Rp 0');
                    btnModalWa.setAttribute('data-status-bayar', status || '-');
                    btnModalWa.setAttribute('data-status-ambil', statusAmbil || '-');
                }

                // Set Bulk Lunas Action Link
                if (btnModalLunasSemua) {
                    if (status === 'Lunas') {
                        btnModalLunasSemua.style.display = 'none';
                    } else {
                        btnModalLunasSemua.style.display = 'inline-flex';
                        btnModalLunasSemua.href = `index.php?lunas_semua_skpd=${encodeURIComponent(cleanId)}`;
                        btnModalLunasSemua.setAttribute('data-confirm-text', `Tandai SEMUA pesanan dari ${cleanName} sebagai LUNAS?`);
                    }
                }

                // Set Bulk Ambil Action Link
                if (btnModalAmbilSemua) {
                    if (statusAmbil === 'Sudah Diambil') {
                        btnModalAmbilSemua.style.display = 'none';
                    } else {
                        btnModalAmbilSemua.style.display = 'inline-flex';
                        btnModalAmbilSemua.href = `index.php?ambil_semua_skpd=${encodeURIComponent(cleanId)}`;
                        btnModalAmbilSemua.setAttribute('data-confirm-text', `Tandai SEMUA pesanan dari ${cleanName} sebagai SUDAH DIAMBIL?`);
                    }
                }
                
                // Populate Table (Multi-strategy lookup)
                let items = [];
                if (window.skpdOrdersById && window.skpdOrdersById[cleanId] && window.skpdOrdersById[cleanId].length > 0) {
                    items = window.skpdOrdersById[cleanId];
                } else if (window.skpdOrdersData && window.skpdOrdersData[cleanName] && window.skpdOrdersData[cleanName].length > 0) {
                    items = window.skpdOrdersData[cleanName];
                } else if (window.skpdOrdersData) {
                    for (let k in window.skpdOrdersData) {
                        if (k.trim().toLowerCase() === cleanName.toLowerCase()) {
                            items = window.skpdOrdersData[k];
                            break;
                        }
                    }
                }

                if (modalSkpdTableBody) {
                    if (!items || items.length === 0) {
                        modalSkpdTableBody.innerHTML = `<tr><td colspan="10" style="text-align: center; padding: 24px; color: var(--gray);">Tidak ada rincian data pemesan untuk SKPD ini.</td></tr>`;
                    } else {
                        let html = '';
                        items.forEach((item, index) => {
                            const genderColor = item.jenis_kelamin === 'Laki-laki' ? 'var(--primary)' : '#EC4899';
                            const bayarBg = item.status_bayar === 'Lunas' ? '#4ADE80' : '#F87171';
                            
                            let ambilBg = '#E5E7EB';
                            if (item.status_pengambilan === 'Sudah Diambil') { ambilBg = '#6EE7B7'; }
                            else if (item.status_pengambilan === 'Siap Diambil') { ambilBg = '#FDE047'; }
                            else if (item.status_pengambilan === 'Sedang Dibuat') { ambilBg = '#93C5FD'; }

                            const subtotalFormatted = 'Rp ' + parseInt(item.subtotal || 0).toLocaleString('id-ID');
                            const pemesan = item.nama_pemesan && item.nama_pemesan.trim() !== '' ? item.nama_pemesan : '-';
                            const catatan = item.catatan && item.catatan.trim() !== '' ? item.catatan : '-';
                            const mutzLabel = item.jenis_mutz || 'Biasa';

                            html += `
                                <tr style="border-bottom: 2px solid #000;">
                                    <td style="padding: 10px 12px; text-align: center; color: #000; font-weight: 800;">${index + 1}</td>
                                    <td style="padding: 10px 12px; font-weight: 900; color: #000;">${pemesan}</td>
                                    <td style="padding: 10px 12px; text-align: center; font-weight: 800; color: #000;">
                                        ${item.jenis_kelamin}
                                    </td>
                                    <td style="padding: 10px 12px; text-align: center;"><span style="font-size:0.8rem; background:#E5E7EB; border: 2px solid #000; padding:2px 6px; font-weight: 800; box-shadow: 2px 2px 0px #000;">${mutzLabel}</span></td>
                                    <td style="padding: 10px 12px; text-align: center; font-weight: 900; font-size: 1.1rem; color: #000;">${item.ukuran}</td>
                                    <td style="padding: 10px 12px; text-align: center; font-weight: 900; font-size: 1.1rem; color: #000;">${item.jumlah}</td>
                                    <td style="padding: 10px 12px; text-align: right; font-weight: 900; font-family: monospace; font-size: 0.95rem; color:#000;">${subtotalFormatted}</td>
                                    <td style="padding: 10px 12px; text-align: center;">
                                        <span style="background:${bayarBg}; color:#000; padding:3px 8px; border:2px solid #000; box-shadow:2px 2px 0px #000; font-size:0.75rem; font-weight:900;">${item.status_bayar}</span>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: center;">
                                        <span style="background:${ambilBg}; color:#000; padding:3px 8px; border:2px solid #000; box-shadow:2px 2px 0px #000; font-size:0.75rem; font-weight:900;">${item.status_pengambilan}</span>
                                    </td>
                                    <td style="padding: 10px 12px; font-size:0.8rem; color:#000; font-weight: 600;">${catatan}</td>
                                </tr>
                            `;
                        });
                        modalSkpdTableBody.innerHTML = html;
                    }
                }
                
                if (modalSkpdFooterSummary) {
                    modalSkpdFooterSummary.innerText = `Menampilkan ${items.length} rincian pemesan (${totalQty || 0} buah mutz)`;
                }
                
                modalDetailSkpd.classList.add('active');
                modalDetailSkpd.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            };

            window.closeSkpdModal = function() {
                const modal = document.getElementById('modalDetailSkpd');
                if (modal) {
                    modal.classList.remove('active');
                    modal.style.display = 'none';
                }
                document.body.style.overflow = '';
            };

            // Global Keydown Escape Handler
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    window.closeSkpdModal();
                }
            });
        </script>

        <!-- 2. SCRIPT CHART.JS & STATISTIK ANIMASI -->
        <script>
            (function() {
                try {
                    // Data from PHP
                    const t_l = <?= (int)$t_l ?>;
                    const t_p = <?= (int)$t_p ?>;
                    const skpdLabels = <?= json_encode($top_skpd_labels) ?>;
                    const skpdData = <?= json_encode($top_skpd_data) ?>;
                    const sizeLabels = <?= json_encode($size_labels) ?>;
                    const sizeData = <?= json_encode($size_data) ?>;

                    if (typeof Chart !== 'undefined') {
                        // Destroy existing canvas charts if any
                        const exG = Chart.getChart('genderChart'); if (exG) exG.destroy();
                        const exS = Chart.getChart('topSkpdChart'); if (exS) exS.destroy();
                        const exZ = Chart.getChart('sizeChart'); if (exZ) exZ.destroy();

                        // Render Pie Chart
                        const genderCanvas = document.getElementById('genderChart');
                        if (genderCanvas) {
                            new Chart(genderCanvas.getContext('2d'), {
                                type: 'doughnut',
                                data: {
                                    labels: ['Laki-laki', 'Perempuan'],
                                    datasets: [{
                                        data: [t_l, t_p],
                                        backgroundColor: ['#2563EB', '#EC4899'],
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
                        }

                        // Render Bar Chart
                        const topSkpdCanvas = document.getElementById('topSkpdChart');
                        if (topSkpdCanvas) {
                            new Chart(topSkpdCanvas.getContext('2d'), {
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
                        }

                        // Render Size Chart
                        const sizeCanvas = document.getElementById('sizeChart');
                        if (sizeCanvas) {
                            new Chart(sizeCanvas.getContext('2d'), {
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
                        }
                    }
                } catch (err) {
                    console.warn('Chart rendering caught error:', err);
                }
            })();

            // Chart Toggle Logic (Global for inline onclick)
            window.setChartVisibility = function(show) {
                const chartContainer = document.getElementById('chartContainer');
                const chartBtnText = document.getElementById('chartBtnText');
                const chartIconOn = document.getElementById('chartIconOn');
                const chartIconOff = document.getElementById('chartIconOff');
                
                if (!chartContainer) return;
                if (show) {
                    chartContainer.style.display = 'flex';
                    if (chartBtnText) chartBtnText.innerText = 'Sembunyikan Grafik';
                    if (chartIconOn) chartIconOn.style.display = 'block';
                    if (chartIconOff) chartIconOff.style.display = 'none';
                } else {
                    chartContainer.style.display = 'none';
                    if (chartBtnText) chartBtnText.innerText = 'Tampilkan Grafik';
                    if (chartIconOn) chartIconOn.style.display = 'none';
                    if (chartIconOff) chartIconOff.style.display = 'block';
                }
            };

            window.toggleCharts = function() {
                const chartContainer = document.getElementById('chartContainer');
                if (!chartContainer) return;
                const isCurrentlyVisible = chartContainer.style.display !== 'none';
                window.setChartVisibility(!isCurrentlyVisible);
                localStorage.setItem('showCharts', !isCurrentlyVisible);
            };

            (function() {
                const savedPreference = localStorage.getItem('showCharts');
                if (savedPreference === 'false') {
                    window.setChartVisibility(false);
                } else {
                    window.setChartVisibility(true);
                }
            })();

            // Animasi Data Muncul Perlahan & Angka Bergerak
            (function initCountersAndFade() {
                const observer = new IntersectionObserver((entries, obs) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible');
                            obs.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.05 });

                document.querySelectorAll('.fade-up').forEach((el) => {
                    observer.observe(el);
                });

                function animateCountUp(elements) {
                    elements.forEach(el => {
                        const target = parseInt(el.getAttribute('data-target')) || 0;
                        const duration = 1800;
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
                        setTimeout(() => updateCounter(), 300);
                    });
                }
                
                animateCountUp(document.querySelectorAll('.count-up'));
                animateCountUp(document.querySelectorAll('.count-up-money'));
            })();
        </script>
    </main>
</body>
</html>
