<?php
require 'config.php';

if (!isset($_GET['skpd_id'])) {
    die("ID SKPD tidak ditemukan.");
}

$skpd_id = (int)$_GET['skpd_id'];

// Get SKPD Data
$q_skpd = $conn->query("SELECT * FROM skpd WHERE id = $skpd_id");
if ($q_skpd->num_rows == 0) {
    die("Data SKPD tidak ditemukan.");
}
$skpd = $q_skpd->fetch_assoc();

// Get all orders for this SKPD
$q_pesanan = $conn->query("
    SELECT * 
    FROM pesanan 
    WHERE skpd_id = $skpd_id
    ORDER BY jenis_kelamin ASC, ukuran ASC, jenis_mutz ASC, nama_pemesan ASC
");

if ($q_pesanan->num_rows == 0) {
    die("Belum ada pesanan untuk SKPD ini.");
}

$semua_lunas = true;
$total_keseluruhan = 0;
$total_item = 0;
$items = [];

while ($p = $q_pesanan->fetch_assoc()) {
    if ($p['status_bayar'] !== 'Lunas') {
        $semua_lunas = false;
    }
    $harga_satuan = ($p['jenis_mutz'] == 'Kepala SKPD') ? 150000 : 55000;
    $subtotal = $p['jumlah'] * $harga_satuan;
    
    $total_keseluruhan += $subtotal;
    $total_item += $p['jumlah'];
    
    $items[] = [
        'nama' => $p['nama_pemesan'],
        'jk' => $p['jenis_kelamin'],
        'ukuran' => $p['ukuran'],
        'jenis' => $p['jenis_mutz'],
        'jumlah' => $p['jumlah'],
        'harga' => $harga_satuan,
        'subtotal' => $subtotal,
        'catatan' => $p['catatan']
    ];
}

$tanggal = date('d F Y');

function terbilang($x) {
    $x = (int)abs($x);
    $angka = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
    $temp = "";
    if ($x < 12) {
        $temp = " " . $angka[$x];
    } else if ($x < 20) {
        $temp = terbilang($x - 10) . " belas";
    } else if ($x < 100) {
        $temp = terbilang(intdiv($x, 10)) . " puluh" . terbilang($x % 10);
    } else if ($x < 200) {
        $temp = " seratus" . terbilang($x - 100);
    } else if ($x < 1000) {
        $temp = terbilang(intdiv($x, 100)) . " ratus" . terbilang($x % 100);
    } else if ($x < 2000) {
        $temp = " seribu" . terbilang($x - 1000);
    } else if ($x < 1000000) {
        $temp = terbilang(intdiv($x, 1000)) . " ribu" . terbilang($x % 1000);
    } else if ($x < 1000000000) {
        $temp = terbilang(intdiv($x, 1000000)) . " juta" . terbilang($x % 1000000);
    } else if ($x < 1000000000000) {
        $temp = terbilang(intdiv($x, 1000000000)) . " milyar" . terbilang(fmod($x, 1000000000));
    }
    return $temp;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?= htmlspecialchars($skpd['nama_skpd']) ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; margin: 0; padding: 40px 20px; background: #e5e7eb; }
        .kwitansi-container { max-width: 900px; margin: 0 auto; background: #fff; border: 2px solid #333; padding: 40px; position: relative; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; box-sizing: border-box; }
        @media (max-width: 600px) {
            body { padding: 10px; }
            .kwitansi-container { padding: 20px 15px; }
            .header h1 { font-size: 20px; }
        }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 26px; color: #111827; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 5px 0 0; color: #6b7280; font-size: 14px; }
        .no-kwitansi { font-size: 16px; font-weight: bold; color: #b91c1c; text-align: right; }
        
        .info-box { background: #f9fafb; border: 1px solid #e5e7eb; padding: 15px; border-radius: 6px; margin-bottom: 30px; display: flex; justify-content: space-between; }
        .info-box div p { margin: 4px 0; font-size: 14px; }
        .info-box strong { color: #111827; }

        table.invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 14px; }
        table.invoice-table th { background: #f3f4f6; color: #111827; text-align: left; padding: 10px; border-bottom: 2px solid #d1d5db; text-transform: uppercase; font-size: 12px; }
        table.invoice-table td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        table.invoice-table .text-right { text-align: right; }
        table.invoice-table .text-center { text-align: center; }
        table.invoice-table tr.total-row td { background: #f9fafb; font-weight: bold; font-size: 16px; border-top: 2px solid #333; border-bottom: 2px solid #333; }

        .terbilang-box { background: #f3f4f6; padding: 12px 15px; font-style: italic; border-left: 4px solid #2563eb; margin-bottom: 40px; font-size: 15px; }

        .footer { display: flex; justify-content: flex-end; text-align: center; }
        .signature { width: 220px; }
        .signature p { margin: 0; }
        .signature .name { margin-top: 80px; font-weight: bold; text-decoration: underline; }
        
        .cap-lunas {
            position: absolute;
            top: 55%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            font-size: 140px;
            color: rgba(16, 185, 129, 0.08);
            border: 10px solid rgba(16, 185, 129, 0.08);
            padding: 20px 50px;
            border-radius: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 20px;
            pointer-events: none;
            z-index: 0;
        }

        .content, .header, .footer, .info-box, .invoice-table, .terbilang-box { position: relative; z-index: 1; }

        @media print {
            body { padding: 0; background: none; }
            .kwitansi-container { border: none; padding: 0; box-shadow: none; }
            .btn-print { display: none !important; }
            .cap-lunas { color: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.15); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            table.invoice-table th { background: #f3f4f6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .terbilang-box { background: #f3f4f6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        
        .btn-print {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 200px; margin: 0 auto 20px; padding: 12px;
            background-color: #2563EB; color: white; text-align: center;
            text-decoration: none; font-weight: 600; border-radius: 6px; cursor: pointer; border: none;
            font-size: 16px; transition: background 0.2s;
        }
        .btn-print:hover { background-color: #1D4ED8; }
    </style>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
    <div style="display: flex; justify-content: center; gap: 15px; margin-bottom: 20px;" class="hide-on-print">
        <button class="btn-print" onclick="window.print()" style="margin: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Cetak Invoice
        </button>
        <button class="btn-print" onclick="downloadPDF()" style="margin: 0; background-color: #DC2626;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Unduh PDF
        </button>
    </div>
    
    <div class="kwitansi-container" id="invoice">
        <?php if($semua_lunas): ?>
            <div class="cap-lunas">LUNAS</div>
        <?php endif; ?>
        
        <div class="header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <img src="assets/images/logo.png?v=2" alt="Logo" style="height: 65px; object-fit: contain;">
                <div>
                    <h1>Invoice Kolektif</h1>
                    <p style="font-weight: 600; color: #1e3a8a; font-size: 16px;">E-MutZ KORPRI KABUPATEN BANJAR</p>
                </div>
            </div>
            <div style="text-align: right;">
                <div class="no-kwitansi">INV/SKPD/<?= date('Ym') ?>/<?= str_pad($skpd_id, 3, '0', STR_PAD_LEFT) ?></div>
                <p>Tanggal: <strong><?= $tanggal ?></strong></p>
            </div>
        </div>
        
        <div class="info-box">
            <div>
                <p>Ditagihkan Kepada:</p>
                <p style="font-size: 18px;"><strong><?= htmlspecialchars($skpd['nama_skpd']) ?></strong></p>
                <?php if(!empty($skpd['no_wa'])): ?>
                    <p>No. Telp / WA: <?= htmlspecialchars($skpd['no_wa']) ?></p>
                <?php endif; ?>
            </div>
            <div style="text-align: right;">
                <p>Status Pembayaran:</p>
                <p style="font-size: 18px; color: <?= $semua_lunas ? '#10B981' : '#DC2626' ?>;">
                    <strong><?= $semua_lunas ? 'LUNAS SEPENUHNYA' : 'BELUM LUNAS / SEBAGIAN' ?></strong>
                </p>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Pemesan</th>
                    <th>Spesifikasi</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-right">Harga Satuan</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach($items as $i): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <strong><?= htmlspecialchars($i['nama']) ?></strong>
                        <?php if(!empty($i['catatan'])): ?>
                            <br><span style="font-size: 12px; color: #6b7280;">Catatan: <?= htmlspecialchars($i['catatan']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        Ukuran <?= $i['ukuran'] ?> (<?= $i['jk'] ?>)<br>
                        <span style="font-size: 12px; color: #6b7280;"><?= $i['jenis'] ?></span>
                    </td>
                    <td class="text-center"><?= $i['jumlah'] ?> pcs</td>
                    <td class="text-right">Rp <?= number_format($i['harga'], 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format($i['subtotal'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                
                <tr class="total-row">
                    <td colspan="3" class="text-right">TOTAL KESELURUHAN</td>
                    <td class="text-center"><?= $total_item ?> pcs</td>
                    <td></td>
                    <td class="text-right" style="color: #b91c1c;">Rp <?= number_format($total_keseluruhan, 0, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="terbilang-box">
            <strong>Terbilang:</strong> <em><?= ucwords(trim(terbilang($total_keseluruhan))) ?> Rupiah</em>
        </div>
        
        <div class="footer">
            <div class="signature">
                <p style="margin-bottom: 5px;">Dewan Pengurus Korpri Kabupaten Banjar</p>
                <p>Sekretariat,</p>
                <div class="name">Angga Wiranata, S.Kom</div>
            </div>
        </div>
    </div>

    <!-- Script HTML2PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <script>
        function downloadPDF() {
            const element = document.getElementById('invoice');
            const opt = {
                margin:       [0, 0, 0, 0],
                filename:     'Invoice_Mutz_<?= preg_replace("/[^a-zA-Z0-9]+/", "_", $skpd['nama_skpd']) ?>.pdf',
                image:        { type: 'jpeg', quality: 1 },
                html2canvas:  { scale: 2, useCORS: true, logging: false },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
            };

            Swal.fire({
                title: 'Menyiapkan PDF...',
                text: 'Mohon tunggu sebentar, file sedang dibuat.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            html2pdf().set(opt).from(element).save().then(() => {
                Swal.close();
            }).catch(err => {
                Swal.fire('Error', 'Gagal membuat PDF', 'error');
            });
        }
    </script>
    <!-- SweetAlert2 for Toast -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
