<?php
require 'config.php';

// Bulk Action: Tandai Semua Pesanan SKPD Sudah Diambil
if (isset($_GET['ambil_semua_skpd'])) {
    $skpd_id = (int)$_GET['ambil_semua_skpd'];
    if ($skpd_id > 0) {
        $conn->query("UPDATE pesanan SET status_pengambilan = 'Sudah Diambil' WHERE skpd_id = $skpd_id");
        header("Location: rekap.php?notif=ambil_semua_sukses");
        exit;
    }
}

// Bulk Action: Tandai Semua Pesanan SKPD Lunas
if (isset($_GET['lunas_semua_skpd'])) {
    $skpd_id = (int)$_GET['lunas_semua_skpd'];
    if ($skpd_id > 0) {
        $conn->query("UPDATE pesanan SET status_bayar = 'Lunas' WHERE skpd_id = $skpd_id");
        header("Location: rekap.php?notif=bayar_semua_sukses");
        exit;
    }
}

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';

$where = [];
if (!empty($start_date) && !empty($end_date)) {
    $where[] = "created_at >= '$start_date 00:00:00' AND created_at <= '$end_date 23:59:59'";
} elseif (!empty($start_date)) {
    $where[] = "created_at >= '$start_date 00:00:00'";
} elseif (!empty($end_date)) {
    $where[] = "created_at <= '$end_date 23:59:59'";
}

if ($status_filter == 'sudah') {
    $where[] = "status_pengambilan = 'Sudah Diambil'";
} elseif ($status_filter == 'belum') {
    $where[] = "status_pengambilan != 'Sudah Diambil'";
}

$where_clause = "";
if (count($where) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $where);
}

// Prepare data structure for recap
$rekap = [];
$skpds = $conn->query("SELECT * FROM skpd ORDER BY nama_skpd ASC");
$skpd_names = [];
$skpd_wa = [];
while ($s = $skpds->fetch_assoc()) {
    $skpd_names[$s['id']] = $s['nama_skpd'];
    $skpd_wa[$s['id']] = $s['no_wa'] ?? '';
}

// Fetch all orders
$query = "
    SELECT skpd_id, jenis_kelamin, ukuran, jenis_mutz, SUM(jumlah) as total_qty 
    FROM pesanan 
    $where_clause
    GROUP BY skpd_id, jenis_kelamin, ukuran, jenis_mutz
";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $sid = $row['skpd_id'];
    $jk = $row['jenis_kelamin'] == 'Laki-laki' ? 'L' : 'P';
    $ukuran = (int)$row['ukuran'];
    $jm = $row['jenis_mutz'];
    $qty = (int)$row['total_qty'];
    
    if (!isset($rekap[$sid])) {
        $rekap[$sid] = [];
    }
    if (!isset($rekap[$sid][$jm])) {
        $rekap[$sid][$jm] = [
            'L' => [55 => 0, 56 => 0, 57 => 0, 58 => 0, 59 => 0, 60 => 0],
            'P' => [58 => 0, 59 => 0, 60 => 0]
        ];
    }
    
    if (isset($rekap[$sid][$jm][$jk][$ukuran])) {
        $rekap[$sid][$jm][$jk][$ukuran] += $qty;
    }
}

foreach ($skpd_names as $id => $nama) {
    if (!isset($rekap[$id])) {
        $rekap[$id]['Biasa'] = [
            'L' => [55 => 0, 56 => 0, 57 => 0, 58 => 0, 59 => 0, 60 => 0],
            'P' => [58 => 0, 59 => 0, 60 => 0]
        ];
    }
}

// Fetch total tagihan per SKPD
$tagihan_skpd = [];
$status_skpd = [];
$status_ambil_skpd = [];
$res_tagihan = $conn->query("
    SELECT skpd_id, 
           SUM(CASE WHEN jenis_mutz = 'Kepala SKPD' THEN jumlah * " . HARGA_KEPALA . " ELSE jumlah * " . HARGA_BIASA . " END) as tagihan,
           SUM(CASE WHEN status_bayar = 'Belum Lunas' THEN 1 ELSE 0 END) as jml_belum_lunas,
           SUM(CASE WHEN status_pengambilan != 'Sudah Diambil' THEN 1 ELSE 0 END) as jml_belum_ambil,
           COUNT(id) as total_pesanan
    FROM pesanan
    $where_clause
    GROUP BY skpd_id
");
while ($t = $res_tagihan->fetch_assoc()) {
    $tagihan_skpd[$t['skpd_id']] = (int)$t['tagihan'];
    
    if ($t['jml_belum_lunas'] == 0 && $t['total_pesanan'] > 0) {
        $status_skpd[$t['skpd_id']] = 'Lunas';
    } elseif ($t['jml_belum_lunas'] < $t['total_pesanan']) {
        $status_skpd[$t['skpd_id']] = 'Sebagian Lunas';
    } else {
        $status_skpd[$t['skpd_id']] = 'Belum Lunas';
    }
    
    if ($t['jml_belum_ambil'] == 0 && $t['total_pesanan'] > 0) {
        $status_ambil_skpd[$t['skpd_id']] = 'Sudah Diambil';
    } elseif ($t['jml_belum_ambil'] < $t['total_pesanan']) {
        $status_ambil_skpd[$t['skpd_id']] = 'Sebagian Diambil';
    } else {
        $status_ambil_skpd[$t['skpd_id']] = 'Belum Diambil';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi - E-MutZ KORPRI</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4F46E5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-MutZ KORPRI">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <style>
        .table-rekap th, .table-rekap td {
            text-align: center;
            border: 1px solid var(--gray-light);
        }
        .table-rekap th { background-color: var(--light); }
        .table-rekap .th-skpd { text-align: left; }
        .table-rekap .td-skpd { text-align: left; font-weight: 500; }
        .table-rekap tbody { counter-reset: rowNumber; }
        .table-rekap tbody tr { counter-increment: rowNumber; }
        .table-rekap tbody tr td.row-number::before { content: counter(rowNumber); }
        .bg-blue { background-color: #EFF6FF !important; }
        .bg-pink { background-color: #FDF2F8 !important; }
        .total-col { font-weight: bold; background-color: #EFF6FF !important; }
        
        @media print {
            body.print-filter-active .row-empty {
                display: none !important;
            }
        }
    </style>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <div>
                <h1>Rekapitulasi Pesanan Mutz ASN per SKPD</h1>
                <p style="margin: 4px 0 0 0; color: var(--gray); font-size: 0.875rem;">Matriks ukuran lengkap L/P 55-60 dan cetak invoice/kwitansi</p>
            </div>
        </div>

        <div class="panel" style="margin-bottom: 2rem;">
            <div class="flex justify-between items-center hide-on-print" style="flex-wrap: wrap; gap: 15px;">
                <h2 style="margin: 0;">Tabel Rekapitulasi</h2>
                
                <form method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; background: var(--light); padding: 10px 15px; border-radius: 12px; border: 1px solid var(--gray-light);">
                    <div style="font-size: 0.9rem; font-weight: 500;">Filter Waktu:</div>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" style="padding: 0.4rem; border-radius: 6px; border: 1px solid #d1d5db; outline: none;">
                    <span>-</span>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" style="padding: 0.4rem; border-radius: 6px; border: 1px solid #d1d5db; outline: none;">
                    
                    <div style="font-size: 0.9rem; font-weight: 500; margin-left: 10px;">Status:</div>
                    <select name="status_filter" style="padding: 0.4rem; border-radius: 6px; border: 1px solid #d1d5db; outline: none;">
                        <option value="">Semua Status</option>
                        <option value="sudah" <?= $status_filter == 'sudah' ? 'selected' : '' ?>>Sudah Diambil</option>
                        <option value="belum" <?= $status_filter == 'belum' ? 'selected' : '' ?>>Belum Diambil</option>
                    </select>

                    <button type="submit" class="btn btn-primary" style="padding: 0.4rem 1rem;">Cari</button>
                    <?php if(!empty($start_date) || !empty($end_date) || !empty($status_filter)): ?>
                        <a href="rekap.php" class="btn btn-secondary" style="padding: 0.4rem 1rem; text-decoration: none;">Reset</a>
                    <?php endif; ?>
                </form>

                <div class="flex items-center gap-2">
                    <select id="printFilter" onchange="togglePrintFilter()" style="padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid var(--gray-light); font-size: 0.9rem; background-color: var(--white); color: var(--dark); cursor: pointer;">
                        <option value="active">Cetak SKPD Pemesan Saja</option>
                        <option value="all">Cetak Seluruh SKPD</option>
                    </select>
                    
                    <?php 
                        $excel_url = "export_excel.php?dummy=1";
                        if (!empty($start_date)) $excel_url .= "&start_date=" . urlencode($start_date);
                        if (!empty($end_date)) $excel_url .= "&end_date=" . urlencode($end_date);
                        if (!empty($status_filter)) $excel_url .= "&status_filter=" . urlencode($status_filter);
                    ?>
                    <a href="<?= $excel_url ?>" download target="_blank" onclick="window.Toast.fire({icon: 'success', title: 'File Excel sedang diunduh!'})" class="btn" style="padding: 0.7rem 1.4rem; font-size: 1.05rem; font-weight: 600; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); background-color: #10B981; border-color: #10B981; color: white; border-radius: 10px; transition: transform 0.2s, box-shadow 0.2s; text-decoration: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Unduh Excel
                    </a>
                    <button onclick="window.downloadPDF()" class="btn btn-primary" style="padding: 0.7rem 1.4rem; font-size: 1.05rem; font-weight: 600; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 10px rgba(220, 38, 38, 0.3); background-color: #DC2626; border-color: #DC2626; border-radius: 10px; transition: transform 0.2s, box-shadow 0.2s; color: white;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Unduh PDF
                    </button>
                    <button onclick="window.print()" class="btn btn-secondary" style="padding: 0.7rem 1.4rem; font-size: 1.05rem; font-weight: 600; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); border-radius: 10px; transition: transform 0.2s, box-shadow 0.2s;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        Cetak Halaman
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <div class="print-only-header" style="display: none;">
                    <div style="text-align: center; border-bottom: 4px solid black; padding-bottom: 15px; margin-bottom: 15px; width: 100%;">
                        <img src="assets/img/logo-korpri.png" alt="Logo KORPRI" style="width: 80px; height: auto; margin: 0 auto 10px auto; display: block;" onerror="this.style.display='none'">
                        <h2 style="font-size: 16pt; margin: 0; color: black; font-weight: bold; line-height: 1.2;">DEWAN PENGURUS KORPRI</h2>
                        <h2 style="font-size: 16pt; margin: 0 0 6px 0; color: black; font-weight: bold; line-height: 1.2;">KABUPATEN BANJAR</h2>
                        <p style="margin: 0; font-size: 10pt; color: black;">Jl. A. Yani No. 2 Martapura</p>
                        <p style="margin: 2px 0 0 0; font-size: 10pt; color: black;">Email: kabbanjarkorpri@gmail.com</p>
                    </div>
                    <h3 style="font-size: 14pt; text-align: center; margin-top: 0; margin-bottom: 15px; color: black; font-weight: bold; text-decoration: underline;">REKAPITULASI PESANAN MUTZ ASN PER SKPD</h3>
                </div>
                <table class="table-rekap">
                    <thead>
                        <tr>
                            <th rowspan="2" class="th-skpd">No</th>
                            <th rowspan="2" class="th-skpd">Nama SKPD</th>
                            <th rowspan="2" class="th-skpd">Jenis Mutz</th>
                            <th colspan="6" class="bg-blue" style="color: var(--primary);">Laki-laki</th>
                            <th rowspan="2" class="total-col bg-blue">Total L</th>
                            <th colspan="3" class="bg-pink" style="color: #EC4899;">Perempuan</th>
                            <th rowspan="2" class="total-col bg-pink">Total P</th>
                            <th rowspan="2" class="total-col" style="background: var(--light);">TOTAL KESELURUHAN</th>
                            <th rowspan="2" class="total-col" style="background: rgba(245, 158, 11, 0.15); color: #D97706;">Tagihan (Rp)</th>
                            <th rowspan="2" class="total-col" style="background: var(--light);">Status Bayar</th>
                            <th rowspan="2" class="total-col" style="background: rgba(59, 130, 246, 0.1); color: var(--primary);">Status Ambil</th>
                        </tr>
                        <tr>
                            <!-- Laki-laki -->
                            <th class="bg-blue">55</th>
                            <th class="bg-blue">56</th>
                            <th class="bg-blue">57</th>
                            <th class="bg-blue">58</th>
                            <th class="bg-blue">59</th>
                            <th class="bg-blue">60</th>
                            <!-- Perempuan -->
                            <th class="bg-pink">58</th>
                            <th class="bg-pink">59</th>
                            <th class="bg-pink">60</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $grand_L = [55=>0, 56=>0, 57=>0, 58=>0, 59=>0, 60=>0];
                        $grand_P = [58=>0, 59=>0, 60=>0];
                        $grand_total_L = 0;
                        $grand_total_P = 0;
                        $grand_total_All = 0;
                        $grand_total_Tagihan = 0;

                        if (empty($skpd_names)) {
                            echo "<tr><td colspan='17'>Belum ada data SKPD.</td></tr>";
                        }
                        
                        foreach($skpd_names as $id => $nama): 
                            $rows_for_skpd = $rekap[$id];
                            $rowspan = count($rows_for_skpd);
                            $is_first = true;
                            
                            $skpd_total = 0;
                            foreach($rows_for_skpd as $jm => $data) {
                                $skpd_total += array_sum($data['L']) + array_sum($data['P']);
                            }
                            $empty_class = $skpd_total == 0 ? 'row-empty' : '';
                            
                            $tagihan = isset($tagihan_skpd[$id]) ? $tagihan_skpd[$id] : 0;
                            $grand_total_Tagihan += $tagihan;
                            
                            foreach($rows_for_skpd as $jm => $data):
                                $total_L = array_sum($data['L']);
                                $total_P = array_sum($data['P']);
                                $total_All = $total_L + $total_P;
                                
                                // Add to grand totals
                                foreach($data['L'] as $sz => $val) { $grand_L[$sz] += $val; }
                                foreach($data['P'] as $sz => $val) { $grand_P[$sz] += $val; }
                                $grand_total_L += $total_L;
                                $grand_total_P += $total_P;
                                $grand_total_All += $total_All;
                        ?>
                        <tr class="<?= $empty_class ?>">
                            <?php if ($is_first): ?>
                            <td class="row-number" rowspan="<?= $rowspan ?>"><?= $no++ ?></td>
                            <td class="td-skpd" rowspan="<?= $rowspan ?>">
                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                    <strong><?= htmlspecialchars($nama) ?></strong>
                                    <?php if ($skpd_total > 0): ?>
                                        <button type="button" 
                                                class="btn-wa-pill btn-wa-notify hide-on-print" 
                                                title="Kirim Notifikasi WhatsApp ke <?= htmlspecialchars($nama, ENT_QUOTES) ?>"
                                                data-skpd="<?= htmlspecialchars($nama, ENT_QUOTES) ?>" 
                                                data-wa="<?= htmlspecialchars($skpd_wa[$id] ?? '', ENT_QUOTES) ?>" 
                                                data-total-qty="<?= $skpd_total ?>" 
                                                data-total-rp="Rp <?= number_format($tagihan, 0, ',', '.') ?>" 
                                                data-status-bayar="<?= $sb ?>" 
                                                data-status-ambil="<?= $sa ?>" 
                                                style="flex-shrink: 0;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" /><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" /></svg>
                                            WA
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                            
                            <td style="font-weight: 500;"><?= $jm ?></td>
                            
                            <?php foreach($data['L'] as $val): ?>
                                <td><?= $val > 0 ? $val : '-' ?></td>
                            <?php endforeach; ?>
                            <td class="total-col text-primary"><?= $total_L > 0 ? $total_L : '-' ?></td>
                            
                            <?php foreach($data['P'] as $val): ?>
                                <td><?= $val > 0 ? $val : '-' ?></td>
                            <?php endforeach; ?>
                            <td class="total-col" style="color: #EC4899;"><?= $total_P > 0 ? $total_P : '-' ?></td>
                            
                            <td class="total-col" style="background: var(--light); font-weight: bold;"><?= $total_All > 0 ? $total_All : '-' ?></td>
                            
                            <?php if ($is_first): ?>
                            <td class="total-col" rowspan="<?= $rowspan ?>" style="background: rgba(245, 158, 11, 0.15); color: #D97706; text-align: right; padding-right: 1rem; font-weight: bold;">
                                <?= $tagihan > 0 ? number_format($tagihan, 0, ',', '.') : '-' ?>
                            </td>
                            
                            <?php 
                                $sb = isset($status_skpd[$id]) ? $status_skpd[$id] : '-';
                                $sb_color = $sb == 'Lunas' ? '#10B981' : ($sb == 'Sebagian Lunas' ? '#D97706' : '#DC2626');
                                
                                $sa = isset($status_ambil_skpd[$id]) ? $status_ambil_skpd[$id] : '-';
                                $sa_color = $sa == 'Sudah Diambil' ? '#10B981' : ($sa == 'Sebagian Diambil' ? '#2563EB' : '#4B5563');
                            ?>
                            <td class="total-col" rowspan="<?= $rowspan ?>" style="text-align: center; vertical-align: middle;">
                                <div style="font-weight: bold; color: <?= $sb_color ?>; margin-bottom: <?= ($sb !== 'Lunas' && $skpd_total > 0) ? '4px' : '0' ?>;"><?= $sb ?></div>
                                <?php if ($sb !== 'Lunas' && $skpd_total > 0): ?>
                                    <a href="rekap.php?lunas_semua_skpd=<?= $id ?>" 
                                       class="btn btn-sm btn-confirm hide-on-print" 
                                       data-confirm-title="Konfirmasi Pembayaran Lunas"
                                       data-confirm-text="Tandai SEMUA pesanan dari '<?= htmlspecialchars($nama, ENT_QUOTES) ?>' sebagai LUNAS?"
                                       data-confirm-btn="Ya, Sudah Lunas"
                                       data-confirm-color="#D97706"
                                       style="background: #D97706; color: white; border: none; border-radius: 4px; padding: 3px 8px; font-size: 0.725rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 3px; box-shadow: 0 1px 3px rgba(217,119,6,0.2);">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        Lunas
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="total-col" rowspan="<?= $rowspan ?>" style="text-align: center; vertical-align: middle;">
                                <div style="font-weight: bold; color: <?= $sa_color ?>; margin-bottom: <?= ($sa !== 'Sudah Diambil' && $skpd_total > 0) ? '4px' : '0' ?>;"><?= $sa ?></div>
                                <?php if ($sa !== 'Sudah Diambil' && $skpd_total > 0): ?>
                                    <a href="rekap.php?ambil_semua_skpd=<?= $id ?>" 
                                       class="btn btn-sm btn-confirm hide-on-print" 
                                       data-confirm-title="Konfirmasi Pengambilan Barang"
                                       data-confirm-text="Tandai SEMUA pesanan dari '<?= htmlspecialchars($nama, ENT_QUOTES) ?>' sebagai SUDAH DIAMBIL?"
                                       data-confirm-btn="Ya, Sudah Diambil"
                                       data-confirm-color="#10B981"
                                       style="background: #10B981; color: white; border: none; border-radius: 4px; padding: 3px 8px; font-size: 0.725rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 3px; box-shadow: 0 1px 3px rgba(16,185,129,0.2);">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        Diambil
                                    </a>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php 
                                $is_first = false;
                            endforeach; 
                        endforeach; 
                        ?>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: var(--dark); color: white; font-weight: bold;">
                            <td colspan="3" style="text-align: right; padding-right: 1rem;">TOTAL KESELURUHAN</td>
                            <?php foreach($grand_L as $val): ?>
                                <td><?= $val > 0 ? $val : '-' ?></td>
                            <?php endforeach; ?>
                            <td style="color: #60A5FA;"><?= $grand_total_L > 0 ? $grand_total_L : '-' ?></td>
                            
                            <?php foreach($grand_P as $val): ?>
                                <td><?= $val > 0 ? $val : '-' ?></td>
                            <?php endforeach; ?>
                            <td style="color: #F472B6;"><?= $grand_total_P > 0 ? $grand_total_P : '-' ?></td>
                            
                            <td><?= $grand_total_All > 0 ? $grand_total_All : '-' ?></td>
                            
                            <td style="text-align: right; padding-right: 1rem; color: #FCD34D;">
                                <?= $grand_total_Tagihan > 0 ? 'Rp ' . number_format($grand_total_Tagihan, 0, ',', '.') : '-' ?>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </main>
    <!-- Script HTML2PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <script>
        function togglePrintFilter() {
            const filter = document.getElementById('printFilter').value;
            if (filter === 'active') {
                document.body.classList.add('print-filter-active');
            } else {
                document.body.classList.remove('print-filter-active');
            }
        }
        // Initialize on load
        document.addEventListener('DOMContentLoaded', togglePrintFilter);

        window.downloadPDF = function() {
            // Show loading toast
            window.Toast.fire({
                icon: 'info',
                title: 'Sedang memproses PDF...',
                html: 'Mohon tunggu sebentar.',
                timer: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const element = document.querySelector('.table-responsive');
            
            // Backup current theme
            const currentTheme = document.documentElement.getAttribute('data-theme');
            
            // Apply exporting class which triggers our print CSS styles
            document.body.classList.add('exporting-pdf');
            if(currentTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'light');
            }

            const opt = {
                margin:       [10, 5, 10, 5], // mm
                filename:     'Rekapitulasi_Pesanan_Korpri.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
            };

            // Let browser apply styles for a split second before capturing
            setTimeout(() => {
                html2pdf().set(opt).from(element).save().then(() => {
                    // Revert classes and theme
                    document.body.classList.remove('exporting-pdf');
                    if(currentTheme === 'dark') {
                        document.documentElement.setAttribute('data-theme', 'dark');
                    }

                    // Success notification
                    window.Toast.fire({
                        icon: 'success',
                        title: 'PDF Berhasil Diunduh!'
                    });
                }).catch(err => {
                    console.error(err);
                    document.body.classList.remove('exporting-pdf');
                    if(currentTheme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
                    
                    window.Toast.fire({
                        icon: 'error',
                        title: 'Gagal membuat PDF.'
                    });
                });
            }, 100);
        }
    </script>
</body>
</html>
