<?php
require 'config.php';

// Handle stock update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stok'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        header("Location: stok.php?error_msg=" . urlencode("Token keamanan CSRF tidak valid. Silakan muat ulang halaman."));
        exit;
    }

    $stmt = $conn->prepare("UPDATE stok_mutz SET jumlah_stok = ? WHERE id = ?");
    if ($stmt && isset($_POST['stok']) && is_array($_POST['stok'])) {
        foreach ($_POST['stok'] as $id => $jumlah) {
            $id_int = (int)$id;
            $jumlah_int = (int)$jumlah;
            $stmt->bind_param("ii", $jumlah_int, $id_int);
            $stmt->execute();
        }
        $stmt->close();
    }
    header("Location: stok.php?notif=edit_sukses");
    exit;
}

// Fetch all stock data
$stok_data = $conn->query("SELECT * FROM stok_mutz ORDER BY jenis_mutz ASC, jenis_kelamin ASC, ukuran ASC");

// Fetch totals
$q_tot_l = $conn->query("SELECT SUM(jumlah_stok) as tot FROM stok_mutz WHERE jenis_kelamin = 'Laki-laki'");
$tot_l = $q_tot_l->fetch_assoc()['tot'] ?? 0;

$q_tot_p = $conn->query("SELECT SUM(jumlah_stok) as tot FROM stok_mutz WHERE jenis_kelamin = 'Perempuan'");
$tot_p = $q_tot_p->fetch_assoc()['tot'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Stok - E-MutZ KORPRI</title>
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
            width: 80px;
            padding: 5px;
            text-align: center;
            border: 1px solid var(--gray-light);
            border-radius: 4px;
        }
        .stok-input:focus {
            border-color: var(--primary);
            outline: none;
        }
        .stok-rendah {
            color: #DC2626;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <div>
                <h1>Manajemen Stok Gudang</h1>
                <p style="margin: 4px 0 0 0; color: var(--gray); font-size: 0.875rem;">Pantau stok fisik per ukuran topi dan peringatan minimum stok</p>
            </div>
        </div>
        
        <div class="panel">
            <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                <div style="flex: 1; background: transparent; border: var(--brutal-border); padding: 15px; border-radius: 6px; border-left: 8px solid #2563EB; box-shadow: 4px 4px 0 #000;">
                    <h3 style="margin: 0 0 5px 0; font-size: 0.95rem; color: var(--gray);">Total Stok Laki-laki</h3>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--dark);"><?= number_format($tot_l) ?> <span style="font-size: 1rem; font-weight: 400;">Pcs</span></div>
                </div>
                <div style="flex: 1; background: transparent; border: var(--brutal-border); padding: 15px; border-radius: 6px; border-left: 8px solid #EC4899; box-shadow: 4px 4px 0 #000;">
                    <h3 style="margin: 0 0 5px 0; font-size: 0.95rem; color: var(--gray);">Total Stok Perempuan</h3>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--dark);"><?= number_format($tot_p) ?> <span style="font-size: 1rem; font-weight: 400;">Pcs</span></div>
                </div>
                <div style="flex: 1; background: transparent; border: var(--brutal-border); padding: 15px; border-radius: 6px; border-left: 8px solid #10B981; box-shadow: 4px 4px 0 #000;">
                    <h3 style="margin: 0 0 5px 0; font-size: 0.95rem; color: var(--gray);">Total Keseluruhan</h3>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--dark);"><?= number_format($tot_l + $tot_p) ?> <span style="font-size: 1rem; font-weight: 400;">Pcs</span></div>
                </div>
            </div>

            <div class="flex justify-between items-center mb-4">
                <h2>Data Stok Mutz Saat Ini</h2>
                <p style="color: var(--gray); font-size: 0.9rem;">
                    *Stok akan otomatis berkurang saat ada pesanan masuk.
                </p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="update_stok" value="1">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Jenis Mutz</th>
                                <th>Jenis Kelamin</th>
                                <th>Ukuran</th>
                                <th>Sisa Stok Fisik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while($row = $stok_data->fetch_assoc()): 
                                $isRendah = $row['jumlah_stok'] <= 5;
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $row['jenis_mutz'] ?></td>
                                <td><?= $row['jenis_kelamin'] ?></td>
                                <td style="font-weight: 600;"><?= $row['ukuran'] ?></td>
                                <td>
                                    <input type="number" name="stok[<?= $row['id'] ?>]" value="<?= $row['jumlah_stok'] ?>" class="stok-input <?= $isRendah ? 'stok-rendah' : '' ?>">
                                    <?php if($isRendah): ?>
                                        <span style="font-size: 0.8rem; color: #DC2626; margin-left: 5px;">(Stok Menipis)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 1.5rem; text-align: right; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn-card-primary" style="padding: 0.85rem 1.75rem; border-radius: 6px; font-size: 1.05rem; ;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Simpan Perubahan Stok
                    </button>
                </div>
            </form>
        </div>
    </main>
    <script src="assets/js/script.js?v=<?= filemtime("assets/css/app.css") ?>"></script>
</body>
</html>
