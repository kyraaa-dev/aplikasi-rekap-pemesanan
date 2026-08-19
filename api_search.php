<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = trim($_GET['q'] ?? '');
if (strlen($query) < 1) {
    echo json_encode([
        'pesanan' => [],
        'skpd' => [],
        'menus' => []
    ]);
    exit;
}

$q_clean = strtolower(trim($query));
$words = preg_split('/\s+/', $q_clean, -1, PREG_SPLIT_NO_EMPTY);
if (empty($words)) {
    echo json_encode(['pesanan' => [], 'skpd' => [], 'menus' => []]);
    exit;
}

$harga_kepala = defined('HARGA_KEPALA') ? HARGA_KEPALA : 150000;
$harga_biasa = defined('HARGA_BIASA') ? HARGA_BIASA : 55000;

// 1. Search Pesanan (by nama_pemesan, nama_skpd, catatan, ukuran, jenis_kelamin, status_bayar, status_pengambilan, no_wa)
$pesanan_where = [];
$pesanan_params = [];
$pesanan_types = '';

foreach ($words as $word) {
    $w_term = "%$word%";
    $pesanan_where[] = "(
        LOWER(p.nama_pemesan) LIKE ? OR
        LOWER(s.nama_skpd) LIKE ? OR
        LOWER(COALESCE(p.catatan, '')) LIKE ? OR
        CAST(p.ukuran AS CHAR) LIKE ? OR
        LOWER(p.jenis_kelamin) LIKE ? OR
        LOWER(p.status_bayar) LIKE ? OR
        LOWER(p.status_pengambilan) LIKE ? OR
        LOWER(COALESCE(s.no_wa, '')) LIKE ?
    )";
    for ($i = 0; $i < 8; $i++) {
        $pesanan_params[] = $w_term;
        $pesanan_types .= 's';
    }
}
$pesanan_where_sql = implode(' AND ', $pesanan_where);

$pesanan_stmt = $conn->prepare("
    SELECT p.id, p.nama_pemesan, p.jenis_kelamin, p.ukuran, p.jumlah, p.jenis_mutz,
           (CASE WHEN p.jenis_mutz = 'Kepala SKPD' THEN p.jumlah * $harga_kepala ELSE p.jumlah * $harga_biasa END) as subtotal, 
           p.status_bayar, p.status_pengambilan, p.catatan, p.created_at,
           s.id as skpd_id, s.nama_skpd, s.no_wa
    FROM pesanan p
    JOIN skpd s ON p.skpd_id = s.id
    WHERE $pesanan_where_sql
    ORDER BY p.id DESC
    LIMIT 25
");
if (!empty($pesanan_params)) {
    $pesanan_stmt->bind_param($pesanan_types, ...$pesanan_params);
}
$pesanan_stmt->execute();
$pesanan_res = $pesanan_stmt->get_result();

$pesanan_list = [];
if ($pesanan_res) {
    while ($row = $pesanan_res->fetch_assoc()) {
        $pesanan_list[] = [
            'id' => (int)$row['id'],
            'nama_pemesan' => $row['nama_pemesan'] ? $row['nama_pemesan'] : 'Umum / Tanpa Nama',
            'nama_skpd' => $row['nama_skpd'],
            'skpd_id' => (int)$row['skpd_id'],
            'no_wa' => $row['no_wa'],
            'jenis_kelamin' => $row['jenis_kelamin'],
            'ukuran' => $row['ukuran'],
            'jumlah' => (int)$row['jumlah'],
            'subtotal' => (int)$row['subtotal'],
            'subtotal_formatted' => 'Rp ' . number_format((int)$row['subtotal'], 0, ',', '.'),
            'status_bayar' => $row['status_bayar'],
            'status_pengambilan' => $row['status_pengambilan'],
            'catatan' => $row['catatan'],
            'created_at' => $row['created_at'] ? date('d/m/Y', strtotime($row['created_at'])) : '-'
        ];
    }
}
$pesanan_stmt->close();

// 2. Search SKPD (by nama_skpd or no_wa)
$skpd_where = [];
$skpd_params = [];
$skpd_types = '';

foreach ($words as $word) {
    $w_term = "%$word%";
    $skpd_where[] = "(LOWER(s.nama_skpd) LIKE ? OR LOWER(COALESCE(s.no_wa, '')) LIKE ?)";
    $skpd_params[] = $w_term;
    $skpd_params[] = $w_term;
    $skpd_types .= 'ss';
}
$skpd_where_sql = implode(' AND ', $skpd_where);

$skpd_stmt = $conn->prepare("
    SELECT s.id, s.nama_skpd, s.no_wa,
           (SELECT COUNT(*) FROM pesanan WHERE skpd_id = s.id) as total_pesanan,
           (SELECT COALESCE(SUM(jumlah), 0) FROM pesanan WHERE skpd_id = s.id) as total_qty,
           (SELECT COALESCE(SUM(CASE WHEN jenis_mutz = 'Kepala SKPD' THEN jumlah * $harga_kepala ELSE jumlah * $harga_biasa END), 0) FROM pesanan WHERE skpd_id = s.id) as total_rp,
           (SELECT COUNT(*) FROM pesanan WHERE skpd_id = s.id AND status_bayar = 'Belum Lunas') as total_belum_lunas
    FROM skpd s
    WHERE $skpd_where_sql
    ORDER BY s.nama_skpd ASC
    LIMIT 12
");
if (!empty($skpd_params)) {
    $skpd_stmt->bind_param($skpd_types, ...$skpd_params);
}
$skpd_stmt->execute();
$skpd_res = $skpd_stmt->get_result();

$skpd_list = [];
if ($skpd_res) {
    while ($row = $skpd_res->fetch_assoc()) {
        $skpd_list[] = [
            'id' => (int)$row['id'],
            'nama_skpd' => $row['nama_skpd'],
            'no_wa' => $row['no_wa'],
            'total_pesanan' => (int)$row['total_pesanan'],
            'total_qty' => (int)$row['total_qty'],
            'total_rp_formatted' => 'Rp ' . number_format((int)$row['total_rp'], 0, ',', '.'),
            'total_belum_lunas' => (int)$row['total_belum_lunas']
        ];
    }
}
$skpd_stmt->close();

// 3. Static Menu Navigation Match
$all_menus = [
    ['title' => 'Dashboard Utama', 'url' => 'index.php', 'icon' => 'grid', 'desc' => 'Ringkasan data, statistik grafik, dan kartu pesanan per SKPD'],
    ['title' => 'Input Pesanan Baru', 'url' => 'pesanan.php', 'icon' => 'file-plus', 'desc' => 'Tambah pesanan topi mutz baru per anggota SKPD'],
    ['title' => 'Rekapitulasi Matriks Ukuran', 'url' => 'rekap.php', 'icon' => 'bar-chart', 'desc' => 'Tabel rekapitulasi ukuran 55-60 L/P dan cetak invoice'],
    ['title' => 'Manajemen Stok Topi', 'url' => 'stok.php', 'icon' => 'tag', 'desc' => 'Kelola stok fisik topi mutz per ukuran dan peringatan batas minimum'],
    ['title' => 'Data Instansi SKPD', 'url' => 'skpd.php', 'icon' => 'home', 'desc' => 'Kelola daftar SKPD terdaftar dan nomor kontak WhatsApp'],
    ['title' => 'Riwayat & Proses Retur', 'url' => 'retur.php', 'icon' => 'refresh', 'desc' => 'Tukar ukuran topi, penyesuaian stok retur, dan riwayat'],
    ['title' => 'Pengaturan Akun & Harga', 'url' => 'pengaturan.php', 'icon' => 'settings', 'desc' => 'Atur harga topi mutz KORPRI dan ubah password admin']
];

$matched_menus = [];
$q_lower = strtolower($query);
foreach ($all_menus as $menu) {
    if (strpos(strtolower($menu['title']), $q_lower) !== false || strpos(strtolower($menu['desc']), $q_lower) !== false) {
        $matched_menus[] = $menu;
    }
}

echo json_encode([
    'query' => $query,
    'pesanan' => $pesanan_list,
    'skpd' => $skpd_list,
    'menus' => $matched_menus
], JSON_UNESCAPED_UNICODE);
