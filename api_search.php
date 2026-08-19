<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
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

$search_term = "%$query%";

// 1. Search Pesanan (by nama_pemesan, nama_skpd, catatan, status)
$pesanan_stmt = $conn->prepare("
    SELECT p.id, p.nama_pemesan, p.jenis_kelamin, p.ukuran, p.jumlah, p.subtotal, 
           p.status_bayar, p.status_pengambilan, p.catatan, p.created_at,
           s.id as skpd_id, s.nama_skpd, s.no_wa
    FROM pesanan p
    JOIN skpd s ON p.skpd_id = s.id
    WHERE p.nama_pemesan LIKE ? 
       OR s.nama_skpd LIKE ? 
       OR p.catatan LIKE ? 
       OR p.ukuran LIKE ?
    ORDER BY p.id DESC
    LIMIT 15
");
$pesanan_stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
$pesanan_stmt->execute();
$pesanan_res = $pesanan_stmt->get_result();

$pesanan_list = [];
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
        'subtotal_formatted' => 'Rp ' . number_format($row['subtotal'], 0, ',', '.'),
        'status_bayar' => $row['status_bayar'],
        'status_pengambilan' => $row['status_pengambilan'],
        'catatan' => $row['catatan'],
        'created_at' => $row['created_at'] ? date('d/m/Y', strtotime($row['created_at'])) : '-'
    ];
}
$pesanan_stmt->close();

// 2. Search SKPD (by nama_skpd or no_wa)
$skpd_stmt = $conn->prepare("
    SELECT s.id, s.nama_skpd, s.no_wa,
           (SELECT COUNT(*) FROM pesanan WHERE skpd_id = s.id) as total_pesanan,
           (SELECT COALESCE(SUM(jumlah), 0) FROM pesanan WHERE skpd_id = s.id) as total_qty,
           (SELECT COALESCE(SUM(subtotal), 0) FROM pesanan WHERE skpd_id = s.id) as total_rp,
           (SELECT COUNT(*) FROM pesanan WHERE skpd_id = s.id AND status_bayar = 'Belum Lunas') as total_belum_lunas
    FROM skpd s
    WHERE s.nama_skpd LIKE ? OR s.no_wa LIKE ?
    ORDER BY s.nama_skpd ASC
    LIMIT 8
");
$skpd_stmt->bind_param("ss", $search_term, $search_term);
$skpd_stmt->execute();
$skpd_res = $skpd_stmt->get_result();

$skpd_list = [];
while ($row = $skpd_res->fetch_assoc()) {
    $skpd_list[] = [
        'id' => (int)$row['id'],
        'nama_skpd' => $row['nama_skpd'],
        'no_wa' => $row['no_wa'],
        'total_pesanan' => (int)$row['total_pesanan'],
        'total_qty' => (int)$row['total_qty'],
        'total_rp_formatted' => 'Rp ' . number_format($row['total_rp'], 0, ',', '.'),
        'total_belum_lunas' => (int)$row['total_belum_lunas']
    ];
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
