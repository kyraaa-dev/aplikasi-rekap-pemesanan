<?php

require 'config.php';

if (!isset($_GET['id'])) {
    die("ID Pesanan tidak ditemukan.");
}

$id = (int)$_GET['id'];
$q = $conn->query("
    SELECT p.*, s.nama_skpd 
    FROM pesanan p 
    JOIN skpd s ON p.skpd_id = s.id 
    WHERE p.id = $id
");

if ($q->num_rows == 0) {
    die("Data pesanan tidak ditemukan.");
}

$row = $q->fetch_assoc();

if ($row['status_bayar'] != 'Lunas') {
    die("Kwitansi hanya bisa dicetak untuk pesanan yang sudah LUNAS.");
}

$harga_satuan = ($row['jenis_mutz'] == 'Kepala SKPD') ? HARGA_KEPALA : HARGA_BIASA;
$total_bayar = $row['jumlah'] * $harga_satuan;
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
    <title>Kwitansi - <?= htmlspecialchars($row['nama_pemesan']) ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; margin: 0; padding: 40px 20px; background: #e5e7eb; }
        .kwitansi-container { max-width: 800px; margin: 0 auto; background: #fff; border: 2px solid #333; padding: 40px; position: relative; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; box-sizing: border-box; }
        @media (max-width: 600px) {
            body { padding: 10px; }
            .kwitansi-container { padding: 20px 15px; }
            .row { flex-direction: column; gap: 4px; }
            .label { width: 100%; }
            .header h1 { font-size: 20px; }
        }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 28px; color: #111827; text-transform: uppercase; letter-spacing: 2px; }
        .header p { margin: 5px 0 0; color: #6b7280; font-size: 14px; }
        .no-kwitansi { font-size: 16px; font-weight: bold; color: #b91c1c; }
        
        .content { font-size: 16px; line-height: 1.8; }
        .row { display: flex; margin-bottom: 15px; }
        .label { width: 220px; font-weight: bold; }
        .value { flex: 1; border-bottom: 1px dotted #9ca3af; text-transform: capitalize; }
        .value.terbilang { font-style: italic; background: #f3f4f6; border: none; padding: 0 10px; border-radius: 4px; }
        
        .amount-box { margin-top: 30px; background: #f9fafb; border: 2px solid #333; padding: 15px 30px; display: inline-block; font-size: 24px; font-weight: bold; letter-spacing: 1px; }
        
        .footer { margin-top: 50px; display: flex; justify-content: flex-end; text-align: center; }
        .signature { width: 200px; }
        .signature p { margin: 0; }
        .signature .name { margin-top: 80px; font-weight: bold; text-decoration: underline; }
        
        .cap-lunas {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            font-size: 120px;
            color: rgba(16, 185, 129, 0.1);
            border: 8px solid rgba(16, 185, 129, 0.1);
            padding: 20px 40px;
            border-radius: 6px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 15px;
            pointer-events: none;
            z-index: 0;
        }

        .content, .header, .footer, .amount-box { position: relative; z-index: 1; }

        @media print {
            body { padding: 0; background: none; }
            .kwitansi-container { border: none; padding: 0; box-shadow: none; }
            .btn-print { display: none !important; }
            .cap-lunas { color: rgba(16, 185, 129, 0.2); border-color: rgba(16, 185, 129, 0.2); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .amount-box { background: #f9fafb; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .value.terbilang { background: #f3f4f6; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
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
            Cetak Kwitansi
        </button>
        <button class="btn-print" onclick="downloadPDF()" style="margin: 0; background-color: #DC2626;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Unduh PDF
        </button>
    </div>
    <div class="kwitansi-container">
        <div class="cap-lunas">LUNAS</div>
        <div class="header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <img src="assets/images/logo.png?v=2" alt="Logo" style="height: 60px; object-fit: contain;">
                <div>
                    <h1>Kwitansi Pembayaran</h1>
                    <p style="font-weight: 600; color: #1e3a8a;">E-MutZ KORPRI</p>
                </div>
            </div>
            <div style="text-align: right;">
                <div class="no-kwitansi">No: #<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></div>
                <p><?= $tanggal ?></p>
            </div>
        </div>
        
        <div class="content">
            <div class="row">
                <div class="label">Telah terima dari</div>
                <div class="value">: <?= htmlspecialchars($row['nama_pemesan']) ?> (<?= htmlspecialchars($row['nama_skpd']) ?>)</div>
            </div>
            <div class="row">
                <div class="label">Uang sejumlah</div>
                <div class="value terbilang"><?= trim(terbilang($total_bayar)) ?> Rupiah</div>
            </div>
            <div class="row">
                <div class="label">Untuk pembayaran</div>
                <div class="value">: Pemesanan Mutz ASN (<?= $row['jenis_mutz'] ?>)</div>
            </div>
            <div class="row">
                <div class="label">Rincian Pesanan</div>
                <div class="value">: <?= $row['jumlah'] ?> pcs, Ukuran: <?= $row['ukuran'] ?> (<?= $row['jenis_kelamin'] ?>)</div>
            </div>
            <?php if(!empty($row['catatan'])): ?>
            <div class="row">
                <div class="label">Catatan</div>
                <div class="value">: <?= htmlspecialchars($row['catatan']) ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="amount-box">
            Rp <?= number_format($total_bayar, 0, ',', '.') ?>
        </div>
        
        <div class="footer">
            <div class="signature" style="width: auto; min-width: 250px;">
                <p style="margin-bottom: 5px;">Dewan Pengurus Korpri Kabupaten Banjar</p>
                <p>Sekretariat,</p>
                <div class="name" style="margin-top: 70px;">Angga Wiranata, S.Kom</div>
            </div>
        </div>
    </div>


    <!-- Script HTML2PDF & SweetAlert2 for Toast -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: 'var(--panel-bg)',
            color: 'var(--text)'
        });

        window.downloadPDF = function() {
            Toast.fire({
                icon: 'info',
                title: 'Sedang memproses PDF...',
                timer: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const element = document.querySelector('.kwitansi-container');
            const noKwitansi = "<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?>";

            const opt = {
                margin:       [10, 10, 10, 10], // mm
                filename:     `Kwitansi_Korpri_#${noKwitansi}.pdf`,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a5', orientation: 'landscape' }
            };

            setTimeout(() => {
                html2pdf().set(opt).from(element).save().then(() => {
                    Toast.fire({
                        icon: 'success',
                        title: 'Kwitansi PDF berhasil diunduh!'
                    });
                }).catch(err => {
                    console.error(err);
                    Toast.fire({
                        icon: 'error',
                        title: 'Gagal membuat PDF.'
                    });
                });
            }, 100);
        }
    </script>
</body>
</html>
