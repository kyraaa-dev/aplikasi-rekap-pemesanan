<?php
require 'config.php';

// Fetch tracking data per SKPD
$q_tracking = $conn->query("
    SELECT s.nama_skpd, s.id as skpd_id,
        SUM(p.jumlah) as total_pcs,
        SUM(CASE WHEN p.status_pengambilan = 'Menunggu Diproses' THEN p.jumlah ELSE 0 END) as menunggu,
        SUM(CASE WHEN p.status_pengambilan = 'Sedang Dibuat' THEN p.jumlah ELSE 0 END) as dibuat,
        SUM(CASE WHEN p.status_pengambilan = 'Siap Diambil' THEN p.jumlah ELSE 0 END) as siap,
        SUM(CASE WHEN p.status_pengambilan = 'Sudah Diambil' THEN p.jumlah ELSE 0 END) as sudah,
        SUM(CASE WHEN p.status_bayar = 'Lunas' THEN p.jumlah ELSE 0 END) as total_lunas
    FROM pesanan p
    JOIN skpd s ON p.skpd_id = s.id
    GROUP BY s.id
    ORDER BY total_pcs DESC
");

$skpd_data = [];
if ($q_tracking) {
    while ($row = $q_tracking->fetch_assoc()) {
        $skpd_data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Pengambilan - Rekap Mutz ASN</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header" style="flex-wrap: wrap; gap: 15px;">
            <div>
                <h1>Tracking Pengambilan Mutz</h1>
                <p style="color: var(--gray); margin-top: 5px;">Pantau progres produksi dan pengambilan mutz untuk masing-masing SKPD.</p>
            </div>
            
            <!-- Summary Stats -->
            <div style="display: flex; gap: 1rem; background: var(--white); padding: 10px 15px; border-radius: 8px; border: 1px solid var(--gray-light); flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="stat-dot pb-menunggu"></div><span style="font-size: 0.85rem;">Menunggu</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="stat-dot pb-dibuat"></div><span style="font-size: 0.85rem;">Dibuat</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="stat-dot pb-siap"></div><span style="font-size: 0.85rem;">Siap</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="stat-dot pb-sudah"></div><span style="font-size: 0.85rem;">Sudah Diambil</span>
                </div>
            </div>
        </div>

        <div class="tracking-grid">
            <?php foreach ($skpd_data as $s): 
                $total = (int)$s['total_pcs'];
                $w_menunggu = ($s['menunggu'] / $total) * 100;
                $w_dibuat = ($s['dibuat'] / $total) * 100;
                $w_siap = ($s['siap'] / $total) * 100;
                $w_sudah = ($s['sudah'] / $total) * 100;
                
                $is_lunas = $s['total_lunas'] == $total;
            ?>
            <div class="tracking-card">
                <div class="tc-header">
                    <h3 class="tc-title"><?= htmlspecialchars($s['nama_skpd']) ?></h3>
                    <div class="tc-total"><?= $total ?> Pcs</div>
                </div>

                <div class="progress-container">
                    <div class="progress-bar pb-sudah" style="width: 0%;" data-width="<?= $w_sudah ?>%" title="Sudah Diambil: <?= $s['sudah'] ?>"></div>
                    <div class="progress-bar pb-siap" style="width: 0%;" data-width="<?= $w_siap ?>%" title="Siap Diambil: <?= $s['siap'] ?>"></div>
                    <div class="progress-bar pb-dibuat" style="width: 0%;" data-width="<?= $w_dibuat ?>%" title="Sedang Dibuat: <?= $s['dibuat'] ?>"></div>
                    <div class="progress-bar pb-menunggu" style="width: 0%;" data-width="<?= $w_menunggu ?>%" title="Menunggu Diproses: <?= $s['menunggu'] ?>"></div>
                </div>

                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-dot pb-sudah"></div>
                        Diambil: <strong><?= $s['sudah'] ?></strong>
                    </div>
                    <div class="stat-item">
                        <div class="stat-dot pb-siap"></div>
                        Siap: <strong><?= $s['siap'] ?></strong>
                    </div>
                    <div class="stat-item">
                        <div class="stat-dot pb-dibuat"></div>
                        Dibuat: <strong><?= $s['dibuat'] ?></strong>
                    </div>
                    <div class="stat-item">
                        <div class="stat-dot pb-menunggu"></div>
                        Menunggu: <strong><?= $s['menunggu'] ?></strong>
                    </div>
                </div>

                <div class="tc-footer">
                    <?php if ($is_lunas): ?>
                        <div class="payment-status status-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            Lunas Penuh
                        </div>
                    <?php else: ?>
                        <div class="payment-status status-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            Lunas: <?= $s['total_lunas'] ?>/<?= $total ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="pesanan.php?filter_skpd=<?= $s['skpd_id'] ?>" class="btn-detail">Lihat Pesanan</a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($skpd_data)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: var(--white); border-radius: 12px; border: 1px dashed var(--gray);">
                <p style="color: var(--gray); font-size: 1.1rem;">Belum ada pesanan yang tercatat.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <script>
        // Trigger animation for progress bars
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                document.querySelectorAll('.progress-bar').forEach(bar => {
                    bar.style.width = bar.getAttribute('data-width');
                });
            }, 100);
        });
    </script>
</body>
</html>
