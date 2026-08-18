<?php
require 'config.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nama_skpd'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        header("Location: skpd.php?error_msg=" . urlencode("Token keamanan CSRF tidak valid. Silakan muat ulang halaman."));
        exit;
    }

    $nama = trim($_POST['nama_skpd'] ?? '');
    $no_wa = trim($_POST['no_wa'] ?? '');
    if (!empty($nama)) {
        $stmt = $conn->prepare("INSERT INTO skpd (nama_skpd, no_wa) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $nama, $no_wa);
            $success = $stmt->execute();
            $stmt->close();
            if ($success) {
                header("Location: skpd.php?notif=simpan_sukses");
                exit;
            }
        }
    }
    header("Location: skpd.php?error_msg=" . urlencode("Gagal menyimpan SKPD"));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quick_update_wa'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrf_token)) {
        $skpd_id = (int)$_POST['skpd_id'];
        $no_wa = trim($_POST['no_wa'] ?? '');
        $stmt = $conn->prepare("UPDATE skpd SET no_wa = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $no_wa, $skpd_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: skpd.php?notif=edit_sukses");
        exit;
    }
}

// Delete
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $stmt_del = $conn->prepare("DELETE FROM skpd WHERE id = ?");
    if ($stmt_del) {
        $stmt_del->bind_param("i", $id);
        $stmt_del->execute();
        $stmt_del->close();
    }
    header("Location: skpd.php?notif=hapus_sukses");
    exit;
}

$skpds = $conn->query("
    SELECT s.*, 
           (SELECT COUNT(*) FROM pesanan WHERE skpd_id = s.id AND status_bayar = 'Lunas') as total_lunas,
           (SELECT COUNT(*) FROM pesanan WHERE skpd_id = s.id AND status_bayar = 'Belum Lunas') as total_belum
    FROM skpd s 
    ORDER BY s.nama_skpd ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data SKPD - E-MutZ KORPRI</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <h1>Data SKPD</h1>
        </div>

        <div class="panel">
            <h2>Tambah SKPD Baru</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <div class="form-group flex gap-4 items-center" style="flex-wrap: wrap;">
                    <input type="text" name="nama_skpd" placeholder="Nama SKPD (contoh: Dinas Pendidikan)" required style="flex: 2; min-width: 260px;">
                    <input type="text" name="no_wa" placeholder="No. WA / Kontak (contoh: 08123456789)" style="flex: 1; min-width: 200px;">
                    <button type="submit" style="white-space:nowrap;">Tambah SKPD</button>
                </div>
            </form>
        </div>
        
        <div class="panel">
            <div class="flex justify-between items-center mb-4">
                <h2>Daftar SKPD Terdaftar</h2>
                <input type="text" id="searchSkpd" onkeyup="filterTable('searchSkpd', 'skpdTable')" placeholder="Cari nama SKPD..." style="width: 250px; padding: 0.5rem 1rem; border-radius: 20px; border: 1px solid var(--gray-light); font-size: 0.9rem;">
            </div>
            <div class="table-responsive">
                <table id="skpdTable">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>Nama SKPD</th>
                            <th>No. WhatsApp</th>
                            <th>Pesanan Lunas</th>
                            <th>Belum Lunas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; while($row = $skpds->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($row['nama_skpd']) ?></strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <?php if (!empty($row['no_wa'])): ?>
                                        <span style="color: #059669; font-weight: 500; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                            <?= htmlspecialchars($row['no_wa']) ?>
                                        </span>
                                        <button type="button" class="btn-quick-wa" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['nama_skpd'], ENT_QUOTES) ?>" data-wa="<?= htmlspecialchars($row['no_wa'], ENT_QUOTES) ?>" title="Ubah Nomor WA" style="background: none; border: none; cursor: pointer; color: var(--gray); padding: 2px 4px; display: inline-flex; align-items: center;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn-quick-wa" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['nama_skpd'], ENT_QUOTES) ?>" data-wa="" style="background: var(--light); border: 1px dashed #9CA3AF; color: var(--gray); font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                            + Tambah WA
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span style="display: inline-flex; align-items: center; gap: 4px; background-color: #F0FDF4; color: #15803D; padding: 3px 8px; border-radius: 20px; font-weight: 700; font-size: 0.75rem; border: 1px solid #BBF7D0;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    <?= $row['total_lunas'] ?> Lunas
                                </span>
                            </td>
                            <td>
                                <span style="display: inline-flex; align-items: center; gap: 4px; background-color: <?= $row['total_belum'] > 0 ? '#FEF2F2' : '#F3F4F6' ?>; color: <?= $row['total_belum'] > 0 ? '#B91C1C' : '#6B7280' ?>; padding: 3px 8px; border-radius: 20px; font-weight: 700; font-size: 0.75rem; border: 1px solid <?= $row['total_belum'] > 0 ? '#FECACA' : '#E5E7EB' ?>; <?= $row['total_belum'] > 0 ? 'box-shadow: 0 2px 4px rgba(185, 28, 28, 0.1);' : '' ?>">
                                    <?php if($row['total_belum'] > 0): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    <?php endif; ?>
                                    <?= $row['total_belum'] ?> Tunggakan
                                </span>
                            </td>
                            <td style="white-space: nowrap;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <?php if (!empty($row['no_wa'])): ?>
                                        <button type="button" 
                                                class="btn-wa btn-wa-notify" 
                                                data-skpd="<?= htmlspecialchars($row['nama_skpd'], ENT_QUOTES) ?>" 
                                                data-wa="<?= htmlspecialchars($row['no_wa'], ENT_QUOTES) ?>" 
                                                data-total-qty="<?= (int)$row['total_lunas'] + (int)$row['total_belum'] ?>" 
                                                data-total-rp="-" 
                                                data-status-bayar="<?= $row['total_belum'] == 0 ? 'Lunas' : 'Belum Lunas' ?>" 
                                                data-status-ambil="-" 
                                                title="Kirim Pesan WhatsApp"
                                                style="padding: 5px 8px; font-size: 0.75rem; border-radius: 6px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                            WA
                                        </button>
                                    <?php endif; ?>
                                    <a href="edit_skpd.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary" style="padding: 5px 10px; font-size: 0.8rem; border-radius: 6px;">Edit</a>
                                    <a href="skpd.php?del=<?= $row['id'] ?>" class="btn btn-sm btn-danger btn-confirm" data-confirm-title="Hapus SKPD" data-confirm-text="Hapus SKPD ini? Semua pesanan dari SKPD ini akan ikut terhapus secara permanen!" data-confirm-btn="Ya, Hapus" data-confirm-color="#EF4444" style="padding: 5px 10px; font-size: 0.8rem; border-radius: 6px;">Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($skpds->num_rows == 0): ?>
                        <tr><td colspan="3" style="text-align:center;">Belum ada data SKPD. Silakan tambahkan terlebih dahulu.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script src="assets/js/script.js?v=<?= time() ?>"></script>
    <script>
        document.querySelectorAll('.btn-quick-wa').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const currentWa = this.getAttribute('data-wa') || '';

                Swal.fire({
                    title: 'Atur Kontak WhatsApp',
                    html: `
                        <p style="font-size:0.9rem; color:var(--gray); margin-bottom:12px; text-align:left;">Masukkan nomor WhatsApp untuk <b>${name}</b>:</p>
                        <input id="swal-input-wa" class="swal2-input" placeholder="Contoh: 08123456789" value="${currentWa}" style="width:100%; margin:0; box-sizing:border-box;">
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Simpan Kontak',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#10B981',
                    preConfirm: () => {
                        return document.getElementById('swal-input-wa').value;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="quick_update_wa" value="1">
                            <input type="hidden" name="skpd_id" value="${id}">
                            <input type="hidden" name="no_wa" value="${result.value}">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>
