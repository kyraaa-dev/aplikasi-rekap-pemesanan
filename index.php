<?php
require 'config.php';

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

// Get payment status per SKPD
$status_skpd = [];
$catatan_skpd = [];
$res_status = $conn->query("
    SELECT s.nama_skpd, 
           SUM(CASE WHEN p.status_bayar = 'Belum Lunas' THEN 1 ELSE 0 END) as jml_belum_lunas,
           COUNT(p.id) as total_pesanan,
           GROUP_CONCAT(DISTINCT NULLIF(TRIM(p.catatan), '') SEPARATOR '; ') as catatan_semua
    FROM pesanan p
    JOIN skpd s ON p.skpd_id = s.id
    GROUP BY s.nama_skpd
");
if ($res_status) {
    while ($row = $res_status->fetch_assoc()) {
        if ($row['jml_belum_lunas'] == 0 && $row['total_pesanan'] > 0) {
            $status_skpd[$row['nama_skpd']] = 'Lunas';
        } elseif ($row['jml_belum_lunas'] < $row['total_pesanan']) {
            $status_skpd[$row['nama_skpd']] = 'Sebagian Lunas';
        } else {
            $status_skpd[$row['nama_skpd']] = 'Belum Lunas';
        }
        $catatan_skpd[$row['nama_skpd']] = $row['catatan_semua'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - E-MutZ KORPRI</title>
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
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin-top: 1.5rem;">
                <?php if(!empty($rincian_skpd)): ?>
                    <?php foreach($rincian_skpd as $nama => $details): ?>
                        <div class="fade-up" style="border: 1px solid var(--gray-light); padding: 15px; border-radius: 8px; background: var(--white); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <h4 style="color: var(--primary); margin-bottom: 12px; font-size: 1.05rem; border-bottom: 2px solid var(--light); padding-bottom: 8px;">
                                <?= htmlspecialchars($nama) ?>
                            </h4>
                            <ul style="list-style-type: none; margin-left: 0; padding-left: 0; font-size: 0.9rem;">
                                <?php 
                                    $total_item = 0;
                                    $total_rupiah = 0;
                                    foreach($details as $d): 
                                        $total_item += $d['total_jumlah'];
                                        $total_rupiah += $d['total_tagihan'];
                                ?>
                                    <li style="display: flex; justify-content: space-between; margin-bottom: 6px; border-bottom: 1px dashed #eee; padding-bottom: 6px;">
                                        <span>
                                            <span style="display:inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: <?= $d['jenis_kelamin'] == 'Laki-laki' ? 'var(--primary)' : '#EC4899' ?>; margin-right: 5px;"></span>
                                            <?= $d['jenis_kelamin'] ?> (Uk: <?= $d['ukuran'] ?>) - <span style="font-size: 0.8rem; color: var(--gray);"><?= $d['jenis_mutz'] ?></span>
                                        </span>
                                        <strong><?= $d['total_jumlah'] ?> buah</strong>
                                    </li>
                                <?php endforeach; ?>
                                <li style="display: flex; justify-content: space-between; margin-top: 10px; font-weight: 600;">
                                    <span>Total Pesanan:</span>
                                    <span><?= $total_item ?> buah</span>
                                </li>
                                <li style="display: flex; justify-content: space-between; margin-top: 5px; font-weight: 700; color: #B45309; background: #FEF3C7; padding: 6px 8px; border-radius: 6px;">
                                    <span>Total Tagihan:</span>
                                    <span>Rp <?= number_format($total_rupiah, 0, ',', '.') ?></span>
                                </li>
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
                                ?>
                                <li style="display: flex; justify-content: space-between; margin-top: 5px; font-weight: 700; color: <?= $status_color ?>; background: <?= $status_bg ?>; padding: 6px 8px; border-radius: 6px;">
                                    <span>Status Pembayaran:</span>
                                    <span><?= $status ?></span>
                                </li>
                                <?php if (!empty($catatan_skpd[$nama])): ?>
                                <li style="margin-top: 10px; font-size: 0.85rem; color: var(--gray); background: #F9FAFB; padding: 8px; border-radius: 6px; border: 1px solid var(--gray-light);">
                                    <strong style="display: block; margin-bottom: 3px; color: var(--dark);">Catatan Tambahan:</strong>
                                    <span style="font-style: italic;"><?= htmlspecialchars($catatan_skpd[$nama]) ?></span>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--gray); grid-column: 1 / -1;">Belum ada SKPD yang melakukan pemesanan sejauh ini.</p>
                <?php endif; ?>
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
    </script>
</body>
</html>
