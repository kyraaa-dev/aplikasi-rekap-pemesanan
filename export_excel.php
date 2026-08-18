<?php

require 'config.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Rekapitulasi_Mutz_ASN_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// BOM to fix UTF-8 in Excel
fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Header row
fputcsv($output, ['No', 'Nama SKPD/UPT', 'Pria Uk 55', 'Pria Uk 56', 'Pria Uk 57', 'Pria Uk 58', 'Pria Uk 59', 'Pria Uk 60', 'Jumlah Pria', 'Wanita Uk 58', 'Wanita Uk 59', 'Wanita Uk 60', 'Jumlah Wanita', 'Total Keseluruhan', 'Tagihan (Rp)', 'Status Bayar', 'Status Ambil']);

// Date and Status filters
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

// Fetch data
$rekap = [];
$skpds = $conn->query("SELECT * FROM skpd ORDER BY nama_skpd ASC");
while ($s = $skpds->fetch_assoc()) {
    $rekap[$s['id']] = [
        'nama_skpd' => $s['nama_skpd'],
        'L' => [55 => 0, 56 => 0, 57 => 0, 58 => 0, 59 => 0, 60 => 0],
        'P' => [58 => 0, 59 => 0, 60 => 0]
    ];
}

$query = "
    SELECT skpd_id, jenis_kelamin, ukuran, SUM(jumlah) as total_qty 
    FROM pesanan 
    $where_clause
    GROUP BY skpd_id, jenis_kelamin, ukuran
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $sid = $row['skpd_id'];
    $jk = $row['jenis_kelamin'] == 'Laki-laki' ? 'L' : 'P';
    $ukuran = (int)$row['ukuran'];
    $qty = (int)$row['total_qty'];
    if (isset($rekap[$sid][$jk][$ukuran])) {
        $rekap[$sid][$jk][$ukuran] += $qty;
    }
}

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

$no = 1;
$grand_total = 0;
$grand_tagihan = 0;

foreach ($rekap as $sid => $data) {
    $sum_l = array_sum($data['L']);
    $sum_p = array_sum($data['P']);
    $total = $sum_l + $sum_p;
    
    // Skip empty rows to match print view
    if ($total == 0) continue;
    
    $tagihan = isset($tagihan_skpd[$sid]) ? $tagihan_skpd[$sid] : 0;
    $status_bayar = isset($status_skpd[$sid]) ? $status_skpd[$sid] : '-';
    $status_ambil = isset($status_ambil_skpd[$sid]) ? $status_ambil_skpd[$sid] : '-';
    
    $grand_total += $total;
    $grand_tagihan += $tagihan;

    fputcsv($output, [
        $no++,
        $data['nama_skpd'],
        $data['L'][55] ?: '-',
        $data['L'][56] ?: '-',
        $data['L'][57] ?: '-',
        $data['L'][58] ?: '-',
        $data['L'][59] ?: '-',
        $data['L'][60] ?: '-',
        $sum_l,
        $data['P'][58] ?: '-',
        $data['P'][59] ?: '-',
        $data['P'][60] ?: '-',
        $sum_p,
        $total,
        $tagihan,
        $status_bayar,
        $status_ambil
    ]);
}

// Grand Total Row
fputcsv($output, [
    '',
    'TOTAL KESELURUHAN',
    '', '', '', '', '', '', '', '', '', '', '', 
    $grand_total,
    $grand_tagihan
]);

fclose($output);
?>
