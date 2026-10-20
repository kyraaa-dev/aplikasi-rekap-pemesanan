<?php
define('ALLOW_DB_FAIL', true);
require_once 'config.php';

// Fetch current stock from database
$q_stok = $conn ? $conn->query("
    SELECT jenis_mutz, jenis_kelamin, ukuran, jumlah_stok 
    FROM stok_mutz 
    ORDER BY jenis_mutz ASC, jenis_kelamin ASC, ukuran ASC
") : null;

$stok_data = [
    'Biasa' => [
        'Laki-laki' => [],
        'Perempuan' => []
    ],
    'Kepala SKPD' => [
        'Laki-laki' => [],
        'Perempuan' => []
    ]
];

$total_stok_tersedia = 0;
$total_stok_biasa = 0;
$total_stok_kepala = 0;

if ($q_stok) {
    while ($row = $q_stok->fetch_assoc()) {
        $jm = $row['jenis_mutz'];
        $jk = $row['jenis_kelamin'];
        $uk = (int)$row['ukuran'];
        $qty = (int)$row['jumlah_stok'];

        if (isset($stok_data[$jm][$jk])) {
            $stok_data[$jm][$jk][$uk] = $qty;
        }

        $total_stok_tersedia += $qty;
        if ($jm === 'Biasa') {
            $total_stok_biasa += $qty;
        } else {
            $total_stok_kepala += $qty;
        }
    }
}

// Default standard sizes
$sizes_pria = [55, 56, 57, 58, 59, 60];
$sizes_wanita = [58, 59, 60];

// Fetch list of SKPD from database with fallback list
$skpd_options = [];
if ($conn) {
    @$conn->query("UPDATE skpd SET nama_skpd = 'BAPPERIDA' WHERE nama_skpd = 'BAPPEDALITBANG'");
    $q_skpd = @$conn->query("SELECT nama_skpd FROM skpd ORDER BY nama_skpd ASC");
    if ($q_skpd) {
        while ($s = $q_skpd->fetch_assoc()) {
            if (!empty($s['nama_skpd'])) {
                $nama = trim($s['nama_skpd']);
                if (strcasecmp($nama, 'BAPPEDALITBANG') === 0) {
                    $nama = 'BAPPERIDA';
                }
                $skpd_options[] = $nama;
            }
        }
    }
}

// Fallback comprehensive list if DB not connected or empty
if (empty($skpd_options)) {
    $skpd_options = [
        'BAPPERIDA', 'BKPSDM', 'BPBD', 'BPKPAD', 
        'DINAS KESEHATAN', 'DINAS PENDIDIKAN', 'DINAS PERTANIAN', 'DINSOSP3AKP2B', 
        'DISBUDPORAPAR', 'DISDUKCAPIL', 'DISHUB', 'DISKOMINFO/DKISP', 
        'DISNAKERTRANS', 'DISPERSIP', 'DKPP', 'DKUMPP', 
        'DPKP/DAMKAR', 'DPMD', 'DPMPTSP', 'DPRKPLH', 'DPUPRP', 
        'INSPEKTORAT', 'KESBANGPOL', 'RS RAZA', 'SATPOLPP', 'SETDA', 'SETWAN',
        'KEC ALUH -ALUH', 'KEC ARANIO', 'KEC ASTAMBUL', 'KEC BERUNTUNG BARU', 
        'KEC CINTAPURI DARUSSALAM', 'KEC GAMBUT', 'KEC KARANG INTAN', 'KEC KERTAK HANYAR', 
        'KEC MARTAPURA', 'KEC MARTAPURA BARAT', 'KEC MARTAPURA TIMUR', 'KEC MATARAMAN', 
        'KEC PARAMASAN', 'KEC PENGARON', 'KEC SAMBUNG MAKMUR', 'KEC SIMPANG EMPAT', 
        'KEC SUNGAI PINANG', 'KEC SUNGAI TABUK', 'KEC TATAH MAKMUR', 'KEC TELAGA BAUNTUNG'
    ];
    sort($skpd_options);
}

// Default admin WhatsApp
$admin_wa_default = '6281258902720';

// Sekretariat KORPRI Kabupaten Banjar Coordinates & Maps URLs
$office_lat = -3.4088348290815493;
$office_lng = 114.84823236685016;
$maps_search_url = "https://www.google.com/maps/search/?api=1&query={$office_lat},{$office_lng}";
$maps_dir_url = "https://www.google.com/maps/dir/?api=1&destination={$office_lat},{$office_lng}";
$maps_embed_url = "https://maps.google.com/maps?q={$office_lat},{$office_lng}&hl=id&z=17&output=embed";

// Service Hours check in WITA (UTC+8)
try {
    $wita_tz = new DateTimeZone('Asia/Makassar');
    $wita_now = new DateTime('now', $wita_tz);
    $current_day_wita = (int)$wita_now->format('N'); // 1 (Senin) to 7 (Minggu)
    $current_hi_wita = (int)$wita_now->format('Hi'); // e.g. 0930
    $current_time_str = $wita_now->format('H:i');

    $is_office_open = false;
    if ($current_day_wita >= 1 && $current_day_wita <= 4) {
        // Senin s/d Kamis: 08:00 - 15:00 WITA
        $is_office_open = ($current_hi_wita >= 800 && $current_hi_wita <= 1500);
    } elseif ($current_day_wita === 5) {
        // Jumat: 08:00 - 11:30 WITA
        $is_office_open = ($current_hi_wita >= 800 && $current_hi_wita <= 1130);
    }
} catch (Exception $e) {
    $is_office_open = false;
    $current_time_str = date('H:i');
}

// Calculate absolute base URL for Open Graph (WhatsApp, Telegram, Facebook crawlers)
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'vercel.app') !== false);
$og_protocol = $is_https ? "https://" : "http://";
$og_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'aplikasi-rekap-pemesanan.vercel.app';
$og_dir = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
$og_dir_encoded = $og_dir ? implode('/', array_map('rawurlencode', explode('/', $og_dir))) : '';
$og_base_url = $og_protocol . $og_host . ($og_dir_encoded ? $og_dir_encoded : '') . '/';

$og_image_square = $og_base_url . 'assets/images/og-emutz-square.png';
$og_image_logo   = $og_base_url . 'assets/images/logo.png';
$og_image_cover  = $og_base_url . 'assets/images/og-emutz-cover.png';
$og_page_url     = $og_protocol . $og_host . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Pemesanan Mutz | Dewan Pengurus KORPRI Kab. Banjar</title>
    
    <!-- Primary SEO Meta Tags -->
    <meta name="title" content="Pemesanan Mutz | Dewan Pengurus KORPRI Kab. Banjar">
    <meta name="description" content="Layanan resmi pemesanan & katalog ketersediaan stok fisik topi Mutz ASN Standar dan Kepala SKPD (Eselon II) Dewan Pengurus KORPRI Kabupaten Banjar secara Real-Time.">
    <meta name="keywords" content="pemesanan mutz, mutz asn, korpri kabupaten banjar, mutz standar, dewan pengurus korpri, martapura, eselon II, stok topi asn">
    <meta name="author" content="Dewan Pengurus KORPRI Kabupaten Banjar">
    <meta name="theme-color" content="#0B1E3B">

    <!-- Open Graph / WhatsApp / Facebook Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($og_page_url) ?>">
    <meta property="og:title" content="Pemesanan Mutz | Dewan Pengurus KORPRI Kab. Banjar">
    <meta property="og:description" content="Layanan resmi pemesanan topi Mutz Standar ASN & Kepala SKPD Dewan Pengurus KORPRI Kab. Banjar. Cek ketersediaan ukuran lingkar kepala & pesan langsung.">
    <meta property="og:site_name" content="Pemesanan Mutz ASN - KORPRI Kabupaten Banjar">
    <meta property="og:locale" content="id_ID">
    
    <!-- WhatsApp Chat Thumbnail (Square 1:1) -->
    <meta property="og:image" content="<?= htmlspecialchars($og_image_square) ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($og_image_square) ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="600">
    <meta property="og:image:height" content="600">
    <meta property="og:image:alt" content="Logo Pemesanan Mutz KORPRI Kabupaten Banjar">

    <!-- Large Social Media Card (1200x630) -->
    <meta property="og:image" content="<?= htmlspecialchars($og_image_cover) ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($og_image_cover) ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Pemesanan Mutz ASN KORPRI Kabupaten Banjar">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= htmlspecialchars($og_page_url) ?>">
    <meta name="twitter:title" content="Pemesanan Mutz | Dewan Pengurus KORPRI Kab. Banjar">
    <meta name="twitter:description" content="Layanan resmi pemesanan topi Mutz Standar ASN & Kepala SKPD Dewan Pengurus KORPRI Kab. Banjar.">
    <meta name="twitter:image" content="<?= htmlspecialchars($og_image_cover) ?>">

    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="assets/images/logo.png">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-MutZ KORPRI">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* ==========================================================================
           AUTHENTIC OFFICIAL GOVERNMENT PORTAL DESIGN SYSTEM
           Dewan Pengurus KORPRI Kabupaten Banjar
           No AI Slop: Clean, Authoritative, High Legibility, Crisp Hairline Borders
           ========================================================================== */
        :root {
            --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            
            /* Official Dignified Government Palette */
            --korpri-navy: #0B1E3B;
            --korpri-navy-dark: #07152B;
            --korpri-navy-light: #162E56;
            --korpri-blue: #1D4ED8;
            --korpri-blue-subtle: #EFF6FF;
            
            --korpri-gold: #B48228;
            --korpri-gold-light: #FBF6E9;
            --korpri-gold-border: #E8D5A3;
            
            --gov-slate: #0F172A;
            --gov-muted: #475569;
            --gov-subtle: #64748B;
            --gov-hairline: #E2E8F0;
            --gov-hairline-subtle: #F1F5F9;
            --gov-canvas: #F8FAFC;
            --gov-surface: #FFFFFF;
            
            --status-green: #15803D;
            --status-green-bg: #F0FDF4;
            --status-green-border: #BBF7D0;
            
            --status-amber: #B45309;
            --status-amber-bg: #FFFBEB;
            --status-amber-border: #FDE68A;
            
            --status-red: #B91C1C;
            --status-red-bg: #FEF2F2;
            --status-red-border: #FECACA;
            
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --radius-full: 9999px;
            
            --shadow-subtle: 0 1px 3px rgba(15, 23, 42, 0.05), 0 1px 2px rgba(15, 23, 42, 0.03);
            --shadow-card: 0 4px 12px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -2px rgba(15, 23, 42, 0.03);
            --shadow-float: 0 12px 28px -4px rgba(11, 30, 59, 0.12), 0 4px 10px -2px rgba(11, 30, 59, 0.06);
            
            --transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.18s ease, background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
        }

        /* Dignified Dark Mode */
        [data-theme="dark"] {
            --korpri-navy: #0F203C;
            --korpri-navy-dark: #081122;
            --korpri-navy-light: #1A345E;
            --korpri-blue: #3B82F6;
            --korpri-blue-subtle: rgba(59, 130, 246, 0.12);
            
            --korpri-gold: #D4AF37;
            --korpri-gold-light: rgba(212, 175, 55, 0.12);
            --korpri-gold-border: rgba(212, 175, 55, 0.3);
            
            --gov-slate: #F8FAFC;
            --gov-muted: #94A3B8;
            --gov-subtle: #64748B;
            --gov-hairline: #1E293B;
            --gov-hairline-subtle: #172033;
            --gov-canvas: #090F1D;
            --gov-surface: #0F172A;
            
            --status-green: #34D399;
            --status-green-bg: rgba(16, 185, 129, 0.12);
            --status-green-border: rgba(16, 185, 129, 0.3);
            
            --status-amber: #FBBF24;
            --status-amber-bg: rgba(245, 158, 11, 0.12);
            --status-amber-border: rgba(245, 158, 11, 0.3);
            
            --status-red: #F87171;
            --status-red-bg: rgba(239, 68, 68, 0.12);
            --status-red-border: rgba(239, 68, 68, 0.3);
            
            --shadow-subtle: 0 1px 3px rgba(0, 0, 0, 0.3);
            --shadow-card: 0 4px 12px -2px rgba(0, 0, 0, 0.4);
            --shadow-float: 0 12px 30px -4px rgba(0, 0, 0, 0.6);
        }

        /* Reset & Base */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            -webkit-text-size-adjust: 100%;
        }

        /* Smooth scroll aktif khusus navigasi target/anchor tanpa memberatkan gesture scroll manual */
        @media (prefers-reduced-motion: no-preference) {
            :root:has(:target) {
                scroll-behavior: smooth;
            }
        }

        body.gov-portal {
            font-family: var(--font-sans);
            background-color: var(--gov-canvas);
            color: var(--gov-slate);
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Official Architectural Background Texture (Dignified, Crisp & Feather-Light) */
        .gov-bg-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            z-index: -3;
            pointer-events: none;
            contain: strict;
            transform: translateZ(0);
            background-image: 
                radial-gradient(ellipse at 15% 15%, rgba(29, 78, 216, 0.08) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 25%, rgba(212, 175, 55, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 90%, rgba(11, 30, 59, 0.06) 0%, transparent 60%),
                linear-gradient(180deg, #F8FAFC 0%, #F1F5F9 100%);
            background-size: 100% 100%;
        }
        [data-theme="dark"] .gov-bg-backdrop {
            background-image: 
                radial-gradient(ellipse at 15% 15%, rgba(59, 130, 246, 0.18) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 25%, rgba(212, 175, 55, 0.14) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 90%, rgba(15, 32, 60, 0.3) 0%, transparent 60%),
                linear-gradient(180deg, #090F1D 0%, #060A14 100%);
        }

        /* Subtle KORPRI Watermark */
        .gov-bg-seal {
            position: fixed;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) translateZ(0);
            width: min(70vw, 420px);
            height: min(70vw, 420px);
            background-image: url('assets/images/korpri-badge.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.025;
            z-index: -2;
            pointer-events: none;
            contain: strict;
        }
        [data-theme="dark"] .gov-bg-seal {
            opacity: 0.035;
        }

        /* ==========================================================================
           TOP OFFICIAL HEADER (Clean, Prestigious Government Standard)
           ========================================================================== */
        .gov-top-accent {
            height: 3px;
            width: 100%;
            background: linear-gradient(90deg, #0B1E3B 0%, #B48228 35%, #D4AF37 50%, #B48228 65%, #0B1E3B 100%);
            position: sticky;
            top: 0;
            z-index: 101;
            transform: translateZ(0);
        }

        .gov-header {
            position: sticky;
            top: 3px;
            z-index: 100;
            background: var(--gov-surface);
            border-bottom: 1px solid var(--gov-hairline);
            box-shadow: var(--shadow-subtle);
            padding: 0.75rem 1.5rem;
            transform: translateZ(0);
            will-change: transform;
        }

        .gov-header-inner {
            max-width: 1140px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .gov-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .gov-brand-seal {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            box-shadow: none;
            flex-shrink: 0;
            padding: 0;
        }
        .gov-brand-seal img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.08));
        }

        .gov-brand-text {
            display: flex;
            flex-direction: column;
        }
        .gov-brand-title {
            font-size: 0.98rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            color: var(--korpri-navy);
            line-height: 1.2;
            text-transform: uppercase;
        }
        [data-theme="dark"] .gov-brand-title {
            color: #93C5FD;
        }
        .gov-brand-sub {
            font-size: 0.76rem;
            font-weight: 600;
            color: var(--gov-subtle);
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .gov-nav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-gov-nav {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-family: var(--font-sans);
            transition: var(--transition);
            border: 1px solid var(--gov-hairline);
            background: var(--gov-surface);
            color: var(--gov-slate);
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        .btn-gov-nav:hover {
            border-color: var(--korpri-blue);
            color: var(--korpri-blue);
            background: var(--gov-hairline-subtle);
        }

        .btn-gov-icon {
            width: 38px;
            height: 38px;
            padding: 0;
        }

        .btn-gov-primary {
            background: var(--korpri-navy);
            border-color: var(--korpri-navy);
            color: #FFFFFF;
        }
        .btn-gov-primary:hover {
            background: var(--korpri-navy-light);
            border-color: var(--korpri-navy-light);
            color: #FFFFFF;
        }
        [data-theme="dark"] .btn-gov-primary {
            background: #1D4ED8;
            border-color: #1D4ED8;
            color: #FFFFFF;
        }
        [data-theme="dark"] .btn-gov-primary:hover {
            background: #2563EB;
            border-color: #2563EB;
        }

        /* ==========================================================================
           MAIN CONTAINER
           ========================================================================== */
        .gov-container {
            width: 100%;
            max-width: 1140px;
            margin: 0 auto;
            padding: 2rem 1.5rem 3.5rem 1.5rem;
            box-sizing: border-box;
            flex: 1;
        }

        /* ==========================================================================
           OFFICIAL HERO & INSTITUTIONAL HEADER
           ========================================================================== */
        .gov-hero {
            position: relative;
            background-color: #07172F;
            border: 1px solid rgba(212, 175, 55, 0.5);
            border-radius: var(--radius-lg);
            padding: 2.25rem 2rem 2rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 14px 36px -4px rgba(7, 21, 43, 0.4), 0 0 20px rgba(212, 175, 55, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            text-align: center;
            overflow: hidden;
            color: #FFFFFF;
        }
        [data-theme="dark"] .gov-hero {
            border-color: rgba(212, 175, 55, 0.45);
            box-shadow: 0 16px 40px -4px rgba(0, 0, 0, 0.7), 0 0 20px rgba(212, 175, 55, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        /* Animated Batik Background Layer - Ken Burns Cinematic Effect */
        .gov-hero-batik {
            position: absolute;
            top: -15%;
            left: -15%;
            width: 130%;
            height: 130%;
            background-image: url('assets/images/batik-korpri.jpg?v=2');
            background-size: cover;
            background-position: center;
            z-index: 0;
            pointer-events: none;
            will-change: transform;
            transform-origin: center center;
            animation: heroKenBurnsCinematic 32s cubic-bezier(0.4, 0, 0.2, 1) infinite alternate;
        }

        /* Ambient Glass/Radial Overlay for perfect text contrast */
        .gov-hero-overlay {
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 50% 35%, rgba(13, 38, 80, 0.52) 0%, rgba(7, 20, 46, 0.76) 60%, rgba(3, 10, 24, 0.92) 100%);
            z-index: 1;
            pointer-events: none;
        }
        [data-theme="dark"] .gov-hero-overlay {
            background: radial-gradient(ellipse at 50% 35%, rgba(10, 28, 62, 0.60) 0%, rgba(5, 16, 38, 0.82) 60%, rgba(2, 6, 18, 0.95) 100%);
        }

        /* Content Container (Keeps text crisp & interactive on top) */
        .gov-hero-content {
            position: relative;
            z-index: 2;
        }

        /* Keyframes for Ken Burns Cinematic Effect: Multi-stage slow zoom & diagonal pan */
        @keyframes heroKenBurnsCinematic {
            0% {
                transform: scale(1.06) translate3d(0%, 0%, 0);
            }
            25% {
                transform: scale(1.14) translate3d(-3%, -2.5%, 0);
            }
            50% {
                transform: scale(1.20) translate3d(2.5%, -3.5%, 0);
            }
            75% {
                transform: scale(1.14) translate3d(-2%, 2%, 0);
            }
            100% {
                transform: scale(1.06) translate3d(3%, 3%, 0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .gov-hero-batik {
                animation: none;
                transform: none;
            }
        }

        /* Subtle gold accent top highlight */
        .gov-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 10%;
            right: 10%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #D4AF37, #FBBF24, transparent);
            border-radius: var(--radius-full);
            z-index: 3;
            pointer-events: none;
        }

        .gov-authority-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(11, 30, 59, 0.7);
            border: 1px solid rgba(212, 175, 55, 0.45);
            padding: 6px 14px;
            border-radius: var(--radius-full);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #FDE68A;
            margin-bottom: 1.25rem;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        [data-theme="dark"] .gov-authority-badge {
            background: rgba(8, 20, 42, 0.8);
            border-color: rgba(212, 175, 55, 0.4);
            color: #FDE68A;
        }

        .gov-hero-h1 {
            font-size: clamp(1.65rem, 3.2vw, 2.35rem);
            font-weight: 800;
            color: #FFFFFF;
            line-height: 1.2;
            letter-spacing: -0.025em;
            margin: 0 auto 0.85rem auto;
            max-width: 820px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.35);
        }
        [data-theme="dark"] .gov-hero-h1 {
            color: #FFFFFF;
        }

        .gov-hero-sub {
            font-size: 0.98rem;
            color: #E2E8F0;
            max-width: 720px;
            margin: 0 auto 1.5rem auto;
            line-height: 1.6;
            font-weight: 500;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
        }

        /* Official Info Strip (Integrated with Batik background) */
        .gov-info-strip {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.25rem;
            flex-wrap: wrap;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            font-size: 0.85rem;
            color: #CBD5E1;
        }

        .gov-info-location {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 9999px;
            padding: 6px 16px;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            transition: var(--transition);
        }
        .gov-info-location:hover {
            background: rgba(15, 23, 42, 0.78);
            border-color: rgba(212, 175, 55, 0.55);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
        }
        .loc-pin-icon {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.2);
            border: 1px solid rgba(212, 175, 55, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FBBF24;
            flex-shrink: 0;
        }
        .loc-text-block {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
        }
        .loc-instansi {
            color: #FFFFFF;
        }
        .loc-divider {
            color: #FBBF24;
            opacity: 0.7;
            font-size: 0.75rem;
        }
        .loc-alamat {
            color: #FEF08A;
            font-weight: 600;
        }

        .gov-info-meta-row {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .gov-meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(15, 23, 42, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 9999px;
            padding: 5px 12px;
            font-size: 0.8rem;
            color: #E2E8F0;
            font-weight: 600;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
        .gov-meta-chip strong {
            color: #FFFFFF;
        }
        .gov-meta-chip svg {
            color: #FBBF24;
            flex-shrink: 0;
        }
        .meta-pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22C55E;
            box-shadow: 0 0 6px #22C55E;
            display: inline-block;
            flex-shrink: 0;
        }

        /* ==========================================================================
           PRODUCT SELECTION CARDS (Mutz Standar vs Kepala SKPD)
           Tactile, Clear, Authoritative Presentation
           ========================================================================== */
        .gov-product-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 1.5rem;
        }

        .product-choice-card {
            background: var(--gov-surface);
            border: 2px solid var(--gov-hairline);
            border-radius: var(--radius-lg);
            padding: 18px 20px;
            cursor: pointer;
            text-align: left;
            transition: var(--transition);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            position: relative;
        }
        .product-choice-card:hover {
            border-color: #CBD5E1;
            transform: translateY(-2px);
            box-shadow: var(--shadow-card);
        }
        [data-theme="dark"] .product-choice-card:hover {
            border-color: #334155;
        }

        /* Active State - Standar */
        .product-choice-card.active.choice-standar {
            border-color: var(--korpri-blue);
            background: var(--gov-surface);
            box-shadow: 0 0 0 1.5px var(--korpri-blue), 0 8px 24px -4px rgba(29, 78, 216, 0.2);
            transform: translateY(-2px);
        }
        [data-theme="dark"] .product-choice-card.active.choice-standar {
            border-color: #3B82F6;
            box-shadow: 0 0 0 1.5px #3B82F6, 0 10px 28px -4px rgba(59, 130, 246, 0.25);
        }

        /* Active State - Kepala SKPD */
        .product-choice-card.active.choice-kepala {
            border-color: var(--korpri-gold);
            background: var(--gov-surface);
            box-shadow: 0 0 0 1.5px var(--korpri-gold), 0 8px 24px -4px rgba(212, 175, 55, 0.25);
            transform: translateY(-2px);
        }
        [data-theme="dark"] .product-choice-card.active.choice-kepala {
            border-color: #D4AF37;
            box-shadow: 0 0 0 1.5px #D4AF37, 0 10px 28px -4px rgba(212, 175, 55, 0.3);
        }

        .choice-check-radio {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid #CBD5E1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
            transition: var(--transition);
        }
        .choice-check-radio::after {
            content: '';
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: transparent;
            transition: var(--transition);
        }
        .product-choice-card.active.choice-standar .choice-check-radio {
            border-color: var(--korpri-blue);
        }
        .product-choice-card.active.choice-standar .choice-check-radio::after {
            background: var(--korpri-blue);
        }
        .product-choice-card.active.choice-kepala .choice-check-radio {
            border-color: var(--korpri-gold);
        }
        .product-choice-card.active.choice-kepala .choice-check-radio::after {
            background: var(--korpri-gold);
        }

        .choice-meta-title {
            font-size: 1.08rem;
            font-weight: 800;
            color: var(--gov-slate);
            line-height: 1.25;
            margin-bottom: 3px;
        }
        .choice-meta-desc {
            font-size: 0.82rem;
            color: var(--gov-muted);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .choice-tags-row {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .tag-micro {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: var(--radius-sm);
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .tag-micro-blue {
            background: var(--korpri-blue-subtle);
            color: var(--korpri-blue);
            border: 1px solid rgba(29, 78, 216, 0.2);
        }
        .tag-micro-gold {
            background: var(--korpri-gold-light);
            color: #8D6415;
            border: 1px solid var(--korpri-gold-border);
            position: relative;
            overflow: hidden;
        }
        .tag-micro-gold::after {
            content: '';
            position: absolute;
            top: -50%;
            left: 0;
            width: 70%;
            height: 200%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.65), transparent);
            transform: translateX(-200%) rotate(25deg);
            animation: goldShimmerSweep 4.2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
            pointer-events: none;
            will-change: transform;
        }
        @keyframes goldShimmerSweep {
            0%, 40% { transform: translateX(-200%) rotate(25deg); }
            70%, 100% { transform: translateX(350%) rotate(25deg); }
        }
        [data-theme="dark"] .tag-micro-gold {
            color: #FDE68A;
        }
        .tag-micro-green {
            background: var(--status-green-bg);
            color: var(--status-green);
            border: 1px solid var(--status-green-border);
            position: relative;
            overflow: hidden;
        }
        .tag-micro-green::after {
            content: '';
            position: absolute;
            top: -50%;
            left: 0;
            width: 70%;
            height: 200%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.5), transparent);
            transform: translateX(-200%) rotate(25deg);
            animation: goldShimmerSweep 5s cubic-bezier(0.4, 0, 0.2, 1) infinite 1.5s;
            pointer-events: none;
            will-change: transform;
        }

        .choice-price-block {
            text-align: right;
            flex-shrink: 0;
        }
        .choice-price-val {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--gov-slate);
            line-height: 1.1;
            letter-spacing: -0.02em;
        }
        .choice-price-label {
            font-size: 0.72rem;
            color: var(--gov-subtle);
            font-weight: 600;
            margin-top: 2px;
            text-transform: uppercase;
        }

        /* ==========================================================================
           UNIFIED FILTER TOOLBAR (Gender & Size Filter in One Crisp Strip)
           ========================================================================== */
        .gov-filter-toolbar {
            background: var(--gov-surface);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-md);
            padding: 10px 14px;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: nowrap;
            box-shadow: var(--shadow-subtle);
            box-sizing: border-box;
            max-width: 100%;
            position: relative;
        }

        .gender-segmented-control {
            display: inline-flex;
            background: var(--gov-hairline-subtle);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-sm);
            padding: 3px;
            gap: 3px;
            flex-shrink: 0;
            box-sizing: border-box;
        }

        .btn-segment {
            border: none;
            background: transparent;
            padding: 7px 16px;
            border-radius: 4px;
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--gov-muted);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
            font-family: var(--font-sans);
            user-select: none;
        }
        .btn-segment:hover:not(.active) {
            color: var(--gov-slate);
        }
        .btn-segment.active {
            background: var(--gov-surface);
            color: var(--korpri-navy);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        [data-theme="dark"] .btn-segment.active {
            background: #1E293B;
            color: #93C5FD;
        }

        .segment-counter {
            font-size: 0.74rem;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 4px;
            background: var(--gov-hairline);
            color: var(--gov-muted);
        }
        .btn-segment.active .segment-counter {
            background: var(--korpri-blue-subtle);
            color: var(--korpri-blue);
        }
        [data-theme="dark"] .btn-segment.active .segment-counter {
            background: rgba(59, 130, 246, 0.2);
            color: #93C5FD;
        }

        .size-chips-scroller {
            display: flex;
            align-items: center;
            gap: 6px;
            overflow-x: auto;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
            padding: 2px 2px;
            flex: 1;
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
        }
        .size-chips-scroller::-webkit-scrollbar {
            display: none;
        }

        .btn-size-chip {
            border: 1px solid var(--gov-hairline);
            background: var(--gov-surface);
            color: var(--gov-muted);
            border-radius: var(--radius-sm);
            padding: 6px 12px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: var(--transition);
            font-family: var(--font-sans);
            user-select: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
            box-sizing: border-box;
        }
        .btn-size-chip:hover:not(.active) {
            border-color: #94A3B8;
            color: var(--gov-slate);
        }
        .btn-size-chip.active {
            background: var(--korpri-navy);
            border-color: var(--korpri-navy);
            color: #FFFFFF;
        }
        [data-theme="dark"] .btn-size-chip.active {
            background: #1D4ED8;
            border-color: #1D4ED8;
            color: #FFFFFF;
        }

        /* Responsive Filter Toolbar: Clean Stacking on Tablet & Medium Screens */
        @media (max-width: 1039px) {
            .gov-filter-toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                padding: 10px 12px;
            }
            .gender-segmented-control {
                width: 100%;
                display: flex;
            }
            .btn-segment {
                flex: 1;
                justify-content: center;
            }
            .size-chips-scroller {
                width: 100%;
                min-width: 0;
                max-width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 2px;
            }
        }

        /* Eselon II Callout Bar (Clean Governmental Notice) */
        .gov-eselon-alert {
            background: var(--korpri-gold-light);
            border: 1px solid var(--korpri-gold-border);
            border-radius: var(--radius-md);
            padding: 14px 18px;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            gap: 14px;
            color: #784C0B;
        }
        [data-theme="dark"] .gov-eselon-alert {
            background: rgba(212, 175, 55, 0.08);
            border-color: rgba(212, 175, 55, 0.25);
            color: #FEF3C7;
        }
        .eselon-icon-wrap {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            background: var(--korpri-gold);
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .eselon-alert-title {
            font-size: 0.92rem;
            font-weight: 800;
            margin-bottom: 2px;
        }
        .eselon-alert-desc {
            font-size: 0.84rem;
            line-height: 1.45;
            font-weight: 500;
        }

        /* ==========================================================================
           AUTHENTIC STOCK GRID (Clean Product Presentation, Zero Slop)
           ========================================================================== */
        .gov-stock-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 3rem;
            min-height: 320px;
        }
        @media (max-width: 960px) {
            .gov-stock-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .gov-stock-grid > .stock-item-card:only-child {
            grid-column: 1 / -1;
            max-width: 360px;
            margin: 0 auto;
            width: 100%;
        }

        .pill-text-short,
        .btn-text-short,
        .pin-text-short,
        .chip-text-short {
            display: none;
        }
        .pill-text-full,
        .btn-text-full,
        .pin-text-full,
        .chip-text-full {
            display: inline;
        }

        .stock-item-card {
            background: var(--gov-surface);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-md);
            padding: 20px;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            box-shadow: var(--shadow-subtle);
            position: relative;
            overflow: hidden;
        }
        .stock-item-card:hover {
            transform: translateY(-4px);
            border-color: rgba(29, 78, 216, 0.35);
            box-shadow: 0 12px 28px -4px rgba(11, 30, 59, 0.12), 0 0 1px rgba(11, 30, 59, 0.2);
        }
        [data-theme="dark"] .stock-item-card:hover {
            border-color: rgba(212, 175, 55, 0.35);
            box-shadow: 0 14px 32px -4px rgba(0, 0, 0, 0.7), 0 0 15px rgba(212, 175, 55, 0.1);
        }

        @keyframes cardFadeUp {
            0% {
                opacity: 0;
                transform: translateY(16px) scale(0.97);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .stock-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .size-display-wrap {
            display: flex;
            flex-direction: column;
        }
        .size-num-title {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--gov-slate);
            line-height: 1;
            letter-spacing: -0.03em;
        }
        .size-circumference {
            font-size: 0.8rem;
            color: var(--gov-subtle);
            font-weight: 600;
            margin-top: 4px;
        }

        /* Clean Status Badges */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            white-space: nowrap;
        }
        .status-dot-sm {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        .pill-avail-ready {
            background: var(--status-green-bg);
            color: var(--status-green);
            border: 1px solid var(--status-green-border);
        }
        .pill-avail-ready .status-dot-sm {
            background: #16A34A;
        }

        .pill-avail-low {
            background: var(--status-amber-bg);
            color: var(--status-amber);
            border: 1px solid var(--status-amber-border);
        }
        .pill-avail-low .status-dot-sm {
            background: #D97706;
        }

        .pill-avail-empty {
            background: var(--status-red-bg);
            color: var(--status-red);
            border: 1px solid var(--status-red-border);
        }
        .pill-avail-empty .status-dot-sm {
            background: #DC2626;
        }

        /* Middle Body of Card */
        .stock-card-body {
            background: var(--gov-hairline-subtle);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            margin-bottom: 16px;
        }
        .body-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.82rem;
            margin-bottom: 5px;
        }
        .body-row:last-child {
            margin-bottom: 0;
        }
        .body-row-pin {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed var(--gov-hairline);
        }
        .body-label {
            color: var(--gov-muted);
            font-weight: 500;
        }
        .body-value {
            font-weight: 700;
            color: var(--gov-slate);
        }
        .body-pin-val {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 700;
            color: var(--korpri-blue);
            font-size: 0.78rem;
            white-space: nowrap;
        }
        [data-theme="dark"] .body-pin-val {
            color: #93C5FD;
        }
        .body-pin-val svg {
            color: var(--korpri-gold);
            flex-shrink: 0;
        }

        /* Card Action Button (Crisp WhatsApp CTA) */
        .btn-order-action {
            margin-top: auto;
            width: 100%;
            padding: 10px 16px;
            border-radius: var(--radius-sm);
            border: 1px solid transparent;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: var(--transition);
            font-family: var(--font-sans);
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            text-decoration: none;
        }

        .btn-order-action.ready-wa {
            background: #15803D;
            color: #FFFFFF;
            border-color: #15803D;
        }
        .btn-order-action.ready-wa:hover {
            background: #166534;
            border-color: #166534;
            transform: translateY(-1px);
        }

        .btn-order-action.preorder-wa {
            background: var(--gov-surface);
            color: var(--gov-slate);
            border-color: var(--gov-hairline);
        }
        .btn-order-action.preorder-wa:hover {
            background: var(--gov-hairline-subtle);
            border-color: #94A3B8;
            transform: translateY(-1px);
        }

        /* ==========================================================================
           EDITORIAL SIZE GUIDE & SPECIFICATION SECTION
           ========================================================================== */
        .gov-section-card {
            background: var(--gov-surface);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-subtle);
        }

        /* High-performance scroll optimization: skip rendering offscreen sections */
        #panduan, #lokasi-pelayanan, .gov-footer {
            content-visibility: auto;
            contain-intrinsic-size: auto 650px;
        }

        .section-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid var(--gov-hairline);
        }
        .section-h2 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--korpri-navy);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        [data-theme="dark"] .section-h2 {
            color: #F8FAFC;
        }

        .gov-editorial-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
            margin-top: 1rem;
        }
        .gov-editorial-table th, 
        .gov-editorial-table td {
            padding: 11px 14px;
            text-align: center;
            border: 1px solid var(--gov-hairline);
        }
        .gov-editorial-table th {
            background: var(--gov-hairline-subtle);
            color: var(--korpri-navy);
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.03em;
        }
        [data-theme="dark"] .gov-editorial-table th {
            color: #93C5FD;
        }
        .gov-editorial-table td.subhead-col {
            text-align: left;
            font-weight: 700;
            background: var(--gov-hairline-subtle);
            color: var(--gov-slate);
            white-space: nowrap;
        }

        .hijab-callout-box {
            background: var(--korpri-blue-subtle);
            border: 1px solid rgba(29, 78, 216, 0.2);
            border-radius: var(--radius-sm);
            padding: 14px 16px;
            margin-top: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.86rem;
            color: var(--gov-slate);
            line-height: 1.5;
        }
        .hijab-callout-box svg {
            color: var(--korpri-blue);
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* Comparison 2-Column Grid */
        .gov-spec-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 1.25rem;
        }
        .spec-box {
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-md);
            padding: 18px;
            background: var(--gov-hairline-subtle);
        }
        .spec-box.eselon {
            border-color: var(--korpri-gold-border);
            background: var(--korpri-gold-light);
        }
        [data-theme="dark"] .spec-box.eselon {
            background: rgba(212, 175, 55, 0.06);
            border-color: rgba(212, 175, 55, 0.25);
        }

        .spec-badge {
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
            display: inline-block;
        }
        .spec-badge.standar { color: var(--korpri-blue); }
        .spec-badge.eselon { color: var(--korpri-gold); }

        .spec-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--gov-slate);
            margin-bottom: 10px;
        }
        .spec-list {
            padding-left: 18px;
            font-size: 0.85rem;
            line-height: 1.6;
            color: var(--gov-muted);
            font-weight: 500;
        }
        /* ==========================================================================
           OFFICIAL SERVICE HOURS & GOOGLE MAPS LOCATION SECTION
           ========================================================================== */
        .gov-office-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 1.25rem;
        }
        @media (max-width: 860px) {
            .gov-office-grid {
                grid-template-columns: 1fr;
            }
        }

        .office-card {
            background: var(--gov-hairline-subtle);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-md);
            padding: 20px;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
        }
        [data-theme="dark"] .office-card {
            background: rgba(15, 32, 60, 0.45);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .office-subhead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
        }
        .office-subhead-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            font-weight: 800;
            color: var(--gov-slate);
        }
        [data-theme="dark"] .office-subhead-title {
            color: #F8FAFC;
        }

        .schedule-list {
            list-style: none;
            padding: 0;
            margin: 0 0 16px 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .schedule-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 12px;
            background: var(--gov-surface);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-sm);
            font-size: 0.86rem;
        }
        .schedule-day {
            font-weight: 600;
            color: var(--gov-slate);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .schedule-hours {
            font-weight: 700;
            color: var(--korpri-blue);
        }
        [data-theme="dark"] .schedule-hours {
            color: #93C5FD;
        }
        .schedule-hours.closed {
            color: var(--status-red);
            font-weight: 600;
        }

        .office-notice-box {
            background: var(--korpri-gold-light);
            border: 1px solid var(--korpri-gold-border);
            border-radius: var(--radius-sm);
            padding: 11px 13px;
            margin-top: auto;
            font-size: 0.82rem;
            color: #784C0B;
            line-height: 1.45;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        [data-theme="dark"] .office-notice-box {
            background: rgba(212, 175, 55, 0.08);
            border-color: rgba(212, 175, 55, 0.25);
            color: #FEF3C7;
        }

        .location-map-wrap {
            width: 100%;
            height: 200px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 1px solid var(--gov-hairline);
            margin-bottom: 14px;
            position: relative;
            background: #E2E8F0;
        }
        .location-map-wrap iframe {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
        }

        .location-meta-details {
            margin-bottom: 14px;
        }
        .loc-instansi-name {
            font-size: 0.96rem;
            font-weight: 800;
            color: var(--gov-slate);
            margin-bottom: 3px;
        }
        .loc-full-address {
            font-size: 0.86rem;
            color: var(--gov-muted);
            line-height: 1.5;
            margin-bottom: 8px;
        }
        .loc-coords-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.76rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            background: var(--gov-surface);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-sm);
            padding: 3px 8px;
            color: var(--gov-subtle);
        }

        .location-actions-row {
            display: flex;
            gap: 10px;
            margin-top: auto;
            flex-wrap: wrap;
        }
        .btn-map-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            flex: 1;
            min-width: 140px;
            user-select: none;
        }
        .btn-map-primary {
            background: #1D4ED8;
            color: #FFFFFF;
            border: 1px solid #1D4ED8;
        }
        .btn-map-primary:hover {
            background: #1E40AF;
            border-color: #1E40AF;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(29, 78, 216, 0.25);
            color: #FFFFFF;
        }
        .btn-map-secondary {
            background: var(--gov-surface);
            color: var(--gov-slate);
            border: 1px solid var(--gov-hairline);
        }
        .btn-map-secondary:hover {
            background: var(--gov-hairline-subtle);
            border-color: #94A3B8;
            transform: translateY(-1px);
            color: var(--korpri-blue);
        }

        /* ==========================================================================
           MODAL & FLOATING DIALOG
           ========================================================================== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(11, 30, 59, 0.65);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .modal-window {
            background: var(--gov-surface);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-float);
            max-width: 460px;
            width: 100%;
            padding: 24px;
            transform: scale(0.97);
            transition: transform 0.2s ease;
        }
        .modal-overlay.active .modal-window {
            transform: scale(1);
        }

        .modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--gov-hairline);
            margin-bottom: 14px;
        }
        .modal-title-text {
            font-size: 1.12rem;
            font-weight: 800;
            color: var(--korpri-navy);
        }
        [data-theme="dark"] .modal-title-text {
            color: #F8FAFC;
        }
        .btn-modal-close {
            background: none;
            border: none;
            font-size: 1.4rem;
            cursor: pointer;
            color: var(--gov-subtle);
            line-height: 1;
            padding: 2px 6px;
        }

        .modal-summary-box {
            background: var(--gov-hairline-subtle);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 0.84rem;
        }
        .modal-summary-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .modal-summary-line:last-child {
            margin-bottom: 0;
            border-top: 1px dashed var(--gov-hairline);
            padding-top: 6px;
            margin-top: 6px;
        }

        .gov-form-group {
            margin-bottom: 14px;
        }
        .gov-form-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--gov-slate);
            margin-bottom: 5px;
        }
        .gov-form-input {
            width: 100%;
            padding: 9px 12px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--gov-hairline);
            background: var(--gov-surface);
            color: var(--gov-slate);
            font-size: 0.88rem;
            font-family: var(--font-sans);
            outline: none;
            transition: var(--transition);
        }
        .gov-form-input:focus {
            border-color: var(--korpri-blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
        }
        select.gov-form-input {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 36px;
            appearance: none;
            -webkit-appearance: none;
        }

        /* Floating Actions Container (No Collision with Cards) */
        .gov-floating-actions {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 99;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-floating-wa {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #15803D;
            color: #FFFFFF;
            padding: 10px 18px;
            border-radius: var(--radius-full);
            font-size: 0.86rem;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 6px 18px rgba(21, 128, 61, 0.35);
            transition: var(--transition);
            user-select: none;
        }
        .btn-floating-wa:hover {
            background: #166534;
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(21, 128, 61, 0.45);
        }

        .btn-gov-top {
            width: 42px;
            height: 42px;
            border-radius: var(--radius-full);
            background: var(--gov-surface);
            border: 1px solid var(--gov-hairline);
            color: var(--gov-slate);
            box-shadow: var(--shadow-card);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px);
        }
        .btn-gov-top.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .btn-gov-top:hover {
            border-color: var(--korpri-blue);
            color: var(--korpri-blue);
        }

        /* Mobile Floating Quick-Action Dock (Fitur Tampilan #7 - Desktop Hidden) */
        .gov-mobile-dock {
            display: none;
        }

        /* Floating Toast Notification */
        .gov-toast {
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: var(--korpri-navy);
            color: #FFFFFF;
            padding: 10px 20px;
            border-radius: var(--radius-full);
            font-size: 0.86rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-float);
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            pointer-events: none;
        }
        .gov-toast.show {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        /* ==========================================================================
           MINIMALIST OFFICIAL FOOTER (Precise, Dignified, Zero Overlap)
           ========================================================================== */
        .gov-footer {
            background: var(--gov-surface);
            border-top: 1px solid var(--gov-hairline);
            padding: 2.25rem 1.5rem 2.25rem 1.5rem;
            margin-top: auto;
            position: relative;
        }
        .gov-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #0B1E3B 0%, #B48228 35%, #0B1E3B 70%, #15803D 100%);
        }

        .gov-footer-inner {
            max-width: 1140px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .footer-identity {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .footer-seal {
            width: 38px;
            height: 38px;
            object-fit: contain;
            flex-shrink: 0;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.08));
        }
        .footer-identity-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .footer-title-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .footer-name {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--gov-slate);
            line-height: 1.25;
            letter-spacing: -0.01em;
        }
        .footer-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(22, 163, 74, 0.08);
            border: 1px solid rgba(22, 163, 74, 0.22);
            color: #15803D;
            padding: 2px 9px;
            border-radius: var(--radius-full);
            font-weight: 700;
            font-size: 0.72rem;
            letter-spacing: 0.01em;
            vertical-align: middle;
        }
        [data-theme="dark"] .footer-status-pill {
            background: rgba(34, 197, 94, 0.12);
            border-color: rgba(34, 197, 94, 0.3);
            color: #4ADE80;
        }
        .status-indicator-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #16A34A;
            display: inline-block;
            box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2);
        }
        .footer-address-txt {
            font-size: 0.80rem;
            color: var(--gov-muted);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .footer-address-txt svg {
            color: var(--korpri-gold);
            flex-shrink: 0;
        }

        .footer-nav-links {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.84rem;
            font-weight: 600;
        }
        .footer-nav-links a {
            color: var(--gov-muted);
            text-decoration: none;
            transition: var(--transition);
            padding: 5px 10px;
            border-radius: var(--radius-sm);
        }
        .footer-nav-links a:hover {
            color: var(--korpri-blue);
            background: var(--korpri-blue-subtle);
        }
        .footer-nav-dot {
            color: var(--gov-hairline);
            font-size: 0.75rem;
        }

        .footer-copyright-bar {
            max-width: 1140px;
            margin: 1.5rem auto 0 auto;
            padding-top: 1.15rem;
            border-top: 1px solid var(--gov-hairline);
            font-size: 0.78rem;
            color: var(--gov-subtle);
            padding-right: 250px; /* Ruang aman presisi agar tombol floating WA & Top tidak menutupi teks */
        }
        .footer-copy-text {
            line-height: 1.45;
        }

        /* ==========================================================================
           RESPONSIVE MOBILE BREAKPOINTS (Max Width: 680px)
           ========================================================================== */
        @media (max-width: 680px) {
            .gov-header {
                padding: 0.65rem 1rem;
            }
            .gov-brand-seal {
                width: 36px;
                height: 36px;
            }
            .gov-brand-title {
                font-size: 0.88rem;
            }
            .gov-brand-sub {
                display: none;
            }
            .btn-gov-nav {
                padding: 7px 11px;
                font-size: 0.78rem;
            }
            .btn-nav-label-long {
                display: none;
            }

            .gov-container {
                padding: 1.25rem 1rem 5.5rem 1rem;
            }

            .gov-hero {
                padding: 1.5rem 1.15rem;
                margin-bottom: 1.25rem;
            }
            .gov-authority-badge {
                padding: 4px 10px;
                font-size: 0.68rem;
                margin-bottom: 0.85rem;
            }
            .gov-hero-h1 {
                font-size: 1.45rem;
                margin-bottom: 0.5rem;
            }
            .gov-hero-sub {
                font-size: 0.86rem;
                margin-bottom: 1rem;
            }
            .gov-info-strip {
                gap: 0.75rem;
                padding-top: 1rem;
                flex-direction: column;
                align-items: center;
            }
            .gov-info-location {
                width: 100%;
                max-width: 380px;
                border-radius: 12px;
                padding: 8px 12px;
                gap: 10px;
                text-align: left;
                align-items: center;
                justify-content: flex-start;
                box-sizing: border-box;
            }
            .loc-pin-icon {
                width: 28px;
                height: 28px;
            }
            .loc-text-block {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 1px;
                flex: 1;
                min-width: 0;
            }
            .loc-divider {
                display: none;
            }
            .loc-instansi {
                font-size: 0.82rem;
                font-weight: 800;
                color: #FFFFFF;
                line-height: 1.25;
            }
            .loc-alamat {
                font-size: 0.74rem;
                font-weight: 600;
                color: #FDE68A;
                line-height: 1.25;
            }
            .gov-info-meta-row {
                width: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }
            .gov-meta-chip {
                padding: 4px 10px;
                font-size: 0.72rem;
            }

            /* Product Selector on Mobile */
            .gov-product-selector {
                grid-template-columns: 1fr;
                gap: 10px;
                margin-bottom: 1.25rem;
            }
            .product-choice-card {
                padding: 14px 16px;
            }
            .choice-meta-title {
                font-size: 1rem;
            }
            .choice-price-val {
                font-size: 1.15rem;
            }

            /* Filter Toolbar on Mobile */
            .gov-filter-toolbar {
                padding: 8px 10px;
                margin-bottom: 1.25rem;
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                width: 100%;
                box-sizing: border-box;
            }
            .gender-segmented-control {
                width: 100%;
                display: flex;
                box-sizing: border-box;
            }
            .btn-segment {
                flex: 1;
                justify-content: center;
                padding: 8px 10px;
                font-size: 0.8rem;
            }
            .size-chips-scroller {
                width: 100%;
                min-width: 0;
                max-width: 100%;
                gap: 6px;
                padding: 2px 2px 4px 2px;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                overscroll-behavior-x: contain;
                box-sizing: border-box;
            }
            .btn-size-chip {
                padding: 6px 10px;
                font-size: 0.78rem;
                flex-shrink: 0;
            }

            /* Stock Grid on Mobile: Dynamic, Crisp 2 Columns */
            .gov-stock-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 2rem;
            }
            .stock-item-card {
                padding: 12px 10px;
                border-radius: var(--radius-sm);
            }
            .stock-card-head {
                margin-bottom: 6px;
                align-items: flex-start;
                gap: 4px;
            }
            .size-num-title {
                font-size: 1.26rem;
                line-height: 1;
            }
            .size-circumference {
                font-size: 0.68rem;
                margin-top: 2px;
            }
            .status-pill {
                padding: 2px 6px;
                font-size: 0.68rem;
                gap: 4px;
                flex-shrink: 0;
            }
            .status-dot-sm {
                width: 5px;
                height: 5px;
            }
            .stock-card-body {
                padding: 8px 8px;
                margin-bottom: 10px;
                border-radius: 4px;
            }
            .body-row {
                font-size: 0.73rem;
                margin-bottom: 2px;
            }
            .body-row-cat {
                display: none; /* redundant in 2-column mobile cards */
            }
            .body-value {
                font-size: 0.76rem;
            }
            .body-row-pin {
                margin-top: 5px;
                padding-top: 5px;
                border-top: 1px dashed var(--gov-hairline);
                flex-direction: column;
                align-items: flex-start;
                gap: 2px;
            }
            .body-row-pin .body-label {
                font-size: 0.67rem;
                color: var(--gov-muted);
                line-height: 1.2;
            }
            .body-pin-val {
                font-size: 0.72rem;
                gap: 3.5px;
                white-space: normal;
                line-height: 1.25;
                color: var(--korpri-blue);
                width: 100%;
            }
            .body-pin-val svg {
                width: 11px;
                height: 11px;
                flex-shrink: 0;
            }
            .btn-order-action {
                padding: 7px 6px;
                font-size: 0.76rem;
                gap: 4px;
                border-radius: 6px;
            }
            .btn-order-action svg {
                width: 14px;
                height: 14px;
                flex-shrink: 0;
            }

            /* Toggle Text Full / Short on Mobile */
            .pill-text-full,
            .btn-text-full,
            .pin-text-full,
            .chip-text-full {
                display: none !important;
            }
            .pill-text-short,
            .btn-text-short,
            .pin-text-short,
            .chip-text-short {
                display: inline !important;
            }

            /* Size Guide & Spec on Mobile */
            .gov-section-card {
                padding: 1.25rem 1rem;
                margin-bottom: 1.5rem;
            }
            .gov-spec-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            /* Mobile Floating Quick-Action Dock (Fitur Tampilan #7) */
            .gov-mobile-dock {
                display: block;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 995;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border-top: 1px solid rgba(226, 232, 240, 0.95);
                box-shadow: 0 -6px 24px rgba(11, 30, 59, 0.12);
                padding: 10px 14px;
                padding-bottom: max(10px, env(safe-area-inset-bottom, 10px));
                transform: translateY(115%);
                transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
                opacity: 0;
                pointer-events: none;
            }
            .gov-mobile-dock.dock-visible {
                transform: translateY(0);
                opacity: 1;
                pointer-events: auto;
            }
            [data-theme="dark"] .gov-mobile-dock {
                background: rgba(11, 22, 44, 0.96);
                border-top-color: rgba(255, 255, 255, 0.12);
                box-shadow: 0 -8px 28px rgba(0, 0, 0, 0.65);
            }
            .mobile-dock-inner {
                max-width: 500px;
                margin: 0 auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }
            .mobile-dock-info {
                display: flex;
                flex-direction: column;
                gap: 3px;
                min-width: 0;
                flex: 1;
            }
            .dock-meta-line {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: nowrap;
                overflow: hidden;
            }
            .dock-cat-badge {
                font-size: 0.68rem;
                font-weight: 800;
                padding: 2px 7px;
                border-radius: var(--radius-sm);
                text-transform: uppercase;
                letter-spacing: 0.02em;
                white-space: nowrap;
                background: var(--korpri-blue-subtle);
                color: var(--korpri-blue);
                border: 1px solid rgba(29, 78, 216, 0.2);
            }
            .dock-cat-badge.eselon {
                background: var(--korpri-gold-light);
                color: #8D6415;
                border-color: var(--korpri-gold-border);
            }
            [data-theme="dark"] .dock-cat-badge.eselon {
                color: #FDE68A;
                background: rgba(212, 175, 55, 0.18);
            }
            .dock-size-pill {
                display: inline-flex;
                align-items: center;
            }
            .dock-size-select {
                font-family: var(--font-sans);
                font-size: 0.8rem;
                font-weight: 800;
                color: var(--gov-slate);
                background: var(--gov-hairline-subtle);
                border: 1.5px solid var(--gov-hairline);
                border-radius: var(--radius-sm);
                padding: 3px 22px 3px 7px;
                cursor: pointer;
                outline: none;
                appearance: none;
                -webkit-appearance: none;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.5'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 6px center;
                transition: var(--transition);
            }
            .dock-size-select:focus {
                border-color: var(--korpri-blue);
            }
            [data-theme="dark"] .dock-size-select {
                background-color: #1E293B;
                color: #F8FAFC;
                border-color: #334155;
            }
            .dock-stock-tag {
                font-size: 0.72rem;
                font-weight: 700;
                white-space: nowrap;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            .dock-stock-tag.ready {
                color: var(--status-green);
            }
            .dock-stock-tag.low {
                color: var(--status-amber);
            }
            .dock-stock-tag.empty {
                color: var(--status-red);
            }
            .dock-price-line {
                display: flex;
                align-items: baseline;
                gap: 6px;
                line-height: 1.15;
            }
            .dock-price-val {
                font-size: 1.08rem;
                font-weight: 800;
                color: var(--gov-slate);
                letter-spacing: -0.015em;
            }
            .dock-include-tag {
                font-size: 0.7rem;
                font-weight: 600;
                color: var(--gov-subtle);
            }
            .btn-dock-order {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 7px;
                background: linear-gradient(135deg, #16A34A 0%, #15803D 100%);
                color: #FFFFFF;
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: var(--radius-md);
                padding: 10px 18px;
                font-size: 0.88rem;
                font-weight: 800;
                font-family: var(--font-sans);
                cursor: pointer;
                white-space: nowrap;
                box-shadow: 0 4px 14px rgba(21, 128, 61, 0.4);
                transition: var(--transition);
                user-select: none;
                -webkit-tap-highlight-color: transparent;
                min-height: 44px;
                flex-shrink: 0;
            }
            .btn-dock-order:hover {
                background: linear-gradient(135deg, #15803D 0%, #166534 100%);
                transform: translateY(-1px);
                box-shadow: 0 6px 18px rgba(21, 128, 61, 0.48);
            }
            .btn-dock-order:active {
                transform: scale(0.97);
            }

            /* Floating Button on Mobile */
            .gov-floating-actions {
                bottom: calc(76px + env(safe-area-inset-bottom, 10px)) !important;
                right: 14px !important;
                z-index: 994 !important;
                gap: 8px;
            }
            .btn-floating-wa {
                display: none !important; /* Digantikan secara sempurna oleh Mobile Quick Action Dock */
            }

            /* Footer on Mobile (Centered, Balanced, Zero Floating Button Overlap) */
            .gov-footer {
                padding: 2rem 1.25rem calc(6.5rem + env(safe-area-inset-bottom, 10px)) 1.25rem !important;
                text-align: center;
            }
            .gov-footer-inner {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 1.25rem;
            }
            .footer-identity {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 10px;
            }
            .footer-seal {
                width: 40px;
                height: 40px;
            }
            .footer-identity-details {
                align-items: center;
                text-align: center;
                gap: 6px;
            }
            .footer-title-row {
                flex-direction: column;
                align-items: center;
                gap: 6px;
            }
            .footer-name {
                font-size: 0.92rem;
            }
            .footer-address-txt {
                justify-content: center;
                font-size: 0.76rem;
                text-align: center;
            }
            .footer-nav-links {
                justify-content: center;
                gap: 8px;
                font-size: 0.80rem;
            }
            .footer-copyright-bar {
                margin-top: 1.25rem;
                padding-top: 1rem;
                padding-right: 0;
                text-align: center;
            }
            .footer-copy-text {
                font-size: 0.75rem;
                line-height: 1.5;
            }

            /* Bottom Sheet on Mobile */
            .modal-overlay {
                align-items: flex-end;
                padding: 0;
            }
            .modal-window {
                max-width: 100%;
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
                padding: 20px 18px 26px 18px;
            }
        }

        /* ==========================================================================
           PWA INSTALL PROMPT & MOBILE BANNER (Fitur Tampilan #5)
           ========================================================================== */
        .gov-pwa-banner {
            position: fixed;
            bottom: 16px;
            left: 14px;
            right: 14px;
            max-width: 480px;
            margin: 0 auto;
            background: linear-gradient(135deg, rgba(11, 30, 59, 0.98) 0%, rgba(7, 21, 43, 0.98) 100%);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1.5px solid rgba(212, 175, 55, 0.45);
            border-radius: 14px;
            padding: 12px 14px;
            box-shadow: 0 16px 36px rgba(7, 21, 43, 0.5), 0 0 20px rgba(212, 175, 55, 0.15);
            z-index: 996;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            transform: translateY(160%);
            opacity: 0;
            transition: transform 0.38s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease, bottom 0.3s ease;
            pointer-events: none;
            color: #FFFFFF;
        }
        .gov-pwa-banner.banner-visible {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }
        body.has-dock-visible .gov-pwa-banner {
            bottom: calc(76px + env(safe-area-inset-bottom, 10px));
        }
        [data-theme="dark"] .gov-pwa-banner {
            background: linear-gradient(135deg, rgba(6, 15, 30, 0.98) 0%, rgba(3, 9, 20, 0.98) 100%);
            border-color: rgba(212, 175, 55, 0.55);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.75), 0 0 20px rgba(212, 175, 55, 0.12);
        }
        .pwa-banner-left {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 0;
            flex: 1;
        }
        .pwa-banner-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(212, 175, 55, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
        }
        .pwa-banner-icon img {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }
        .pwa-banner-text {
            min-width: 0;
        }
        .pwa-banner-title {
            font-size: 0.84rem;
            font-weight: 800;
            color: #FFFFFF;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .pwa-banner-desc {
            font-size: 0.72rem;
            color: rgba(255, 255, 255, 0.78);
            margin-top: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .pwa-banner-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }
        .btn-pwa-install {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #D4AF37 0%, #B8860B 100%);
            color: #07152B;
            border: none;
            border-radius: var(--radius-sm);
            padding: 7px 13px;
            font-size: 0.78rem;
            font-weight: 800;
            font-family: var(--font-sans);
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.35);
            transition: var(--transition);
            white-space: nowrap;
        }
        .btn-pwa-install:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(212, 175, 55, 0.48);
        }
        .btn-pwa-install:active {
            transform: scale(0.97);
        }
        .btn-pwa-close {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            width: 28px;
            height: 28px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.25rem;
            line-height: 1;
            transition: var(--transition);
        }
        .btn-pwa-close:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.12);
        }

        /* PWA Guide Modal */
        .pwa-modal-guide-card {
            background: var(--gov-hairline-subtle);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-md);
            padding: 12px 14px;
            margin-bottom: 12px;
        }
        .pwa-guide-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 800;
            font-size: 0.88rem;
            margin-bottom: 8px;
        }
        .pwa-guide-header.ios {
            color: #1D4ED8;
        }
        [data-theme="dark"] .pwa-guide-header.ios {
            color: #93C5FD;
        }
        .pwa-guide-header.android {
            color: #15803D;
        }
        [data-theme="dark"] .pwa-guide-header.android {
            color: #86EFAC;
        }
        .pwa-guide-steps {
            margin: 0;
            padding-left: 20px;
            font-size: 0.82rem;
            color: var(--gov-slate);
            line-height: 1.55;
        }
        .pwa-guide-steps li {
            margin-bottom: 6px;
        }
        .pwa-guide-steps li:last-child {
            margin-bottom: 0;
        }
        .pwa-guide-highlight {
            font-weight: 700;
            color: var(--gov-navy);
            background: rgba(212, 175, 55, 0.15);
            padding: 1px 5px;
            border-radius: 3px;
        }
        [data-theme="dark"] .pwa-guide-highlight {
            color: #FDE68A;
            background: rgba(212, 175, 55, 0.22);
        }

        /* ==========================================================================
           OFFICIAL PRODUCT PHOTO GALLERY & LIGHTBOX (5 FOTO FISIK MUTZ & PIN)
           ========================================================================== */
        .gov-gallery-section {
            margin-bottom: 2rem;
            position: relative;
        }

        .gallery-badge-top {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--korpri-gold);
            background: var(--korpri-gold-light);
            border: 1px solid var(--korpri-gold-border);
            padding: 3px 10px;
            border-radius: var(--radius-full);
            margin-bottom: 6px;
        }
        [data-theme="dark"] .gallery-badge-top {
            background: rgba(212, 175, 55, 0.12);
            border-color: rgba(212, 175, 55, 0.3);
            color: #FDE68A;
        }

        .gallery-lead-desc {
            font-size: 0.88rem;
            color: var(--gov-muted);
            line-height: 1.6;
            margin: 0.4rem 0 1.25rem;
        }

        .gallery-filter-pills {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-gallery-tab {
            padding: 6px 14px;
            font-size: 0.8rem;
            font-weight: 700;
            border-radius: var(--radius-full);
            border: 1px solid var(--gov-hairline);
            background: var(--gov-surface);
            color: var(--gov-muted);
            cursor: pointer;
            transition: var(--transition);
        }
        .btn-gallery-tab:hover {
            border-color: var(--korpri-blue);
            color: var(--korpri-blue);
        }
        .btn-gallery-tab.active {
            background: var(--korpri-navy);
            border-color: var(--korpri-navy);
            color: #FFFFFF;
        }
        [data-theme="dark"] .btn-gallery-tab.active {
            background: var(--korpri-blue);
            border-color: var(--korpri-blue);
            color: #FFFFFF;
        }

        .gallery-cards-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
            margin-top: 1.25rem;
        }
        @media (max-width: 1200px) {
            .gallery-cards-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 860px) {
            .gallery-cards-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }
        @media (max-width: 520px) {
            .gallery-cards-grid {
                grid-template-columns: 1fr;
            }
        }

        .gallery-card-item {
            background: var(--gov-hairline-subtle);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-md);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s ease, border-color 0.25s ease;
            position: relative;
        }
        .gallery-card-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-float);
            border-color: rgba(212, 175, 55, 0.5);
        }
        .gallery-card-item.card-eselon {
            border-color: rgba(212, 175, 55, 0.35);
        }
        .gallery-card-item.card-pin {
            border-color: rgba(21, 128, 61, 0.35);
        }

        .gallery-img-box {
            position: relative;
            width: 100%;
            aspect-ratio: 3 / 2;
            overflow: hidden;
            background: #F3F4F6;
            cursor: pointer;
        }
        [data-theme="dark"] .gallery-img-box {
            background: #111827;
        }

        .gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .gallery-img-box:hover .gallery-img {
            transform: scale(1.08);
        }

        .gallery-floating-tags {
            position: absolute;
            top: 8px;
            left: 8px;
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            z-index: 2;
        }
        .tag-pill-gold {
            background: rgba(180, 130, 40, 0.94);
            color: #FFFFFF;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        .tag-pill-blue {
            background: rgba(29, 78, 216, 0.94);
            color: #FFFFFF;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        .tag-pill-emerald {
            background: rgba(21, 128, 61, 0.94);
            color: #FFFFFF;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        .tag-pill-dark {
            background: rgba(15, 23, 42, 0.85);
            color: #FFFFFF;
            font-size: 0.66rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 20px;
        }

        .gallery-hover-overlay {
            position: absolute;
            inset: 0;
            background: rgba(11, 30, 59, 0.45);
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.22s ease;
            z-index: 3;
        }
        .gallery-img-box:hover .gallery-hover-overlay {
            opacity: 1;
        }

        .overlay-zoom-btn {
            background: #FFFFFF;
            color: var(--korpri-navy);
            padding: 7px 15px;
            border-radius: 30px;
            font-size: 0.78rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
            transform: scale(0.95);
            transition: transform 0.2s ease;
        }
        .gallery-img-box:hover .overlay-zoom-btn {
            transform: scale(1);
        }

        .gallery-card-content {
            padding: 12px 13px 13px;
            display: flex;
            flex-direction: column;
            flex: 1;
            justify-content: space-between;
        }

        .gallery-card-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 6px;
            margin-bottom: 4px;
        }
        .gallery-card-title {
            font-size: 0.92rem;
            font-weight: 800;
            color: var(--gov-slate);
            line-height: 1.35;
            margin: 0;
        }
        .gallery-num-badge {
            font-size: 0.68rem;
            font-weight: 800;
            color: var(--gov-subtle);
            background: var(--gov-hairline);
            padding: 1px 6px;
            border-radius: 4px;
            white-space: nowrap;
        }

        .gallery-card-desc {
            font-size: 0.78rem;
            color: var(--gov-muted);
            line-height: 1.5;
            margin: 4px 0 10px;
        }

        .gallery-card-features {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 12px;
        }
        .feature-chip {
            font-size: 0.72rem;
            color: var(--gov-slate);
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
        }
        .feature-chip svg {
            color: var(--status-green);
            flex-shrink: 0;
        }

        .gallery-card-footer {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px dashed var(--gov-hairline);
        }
        .gallery-price-wrap {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }
        .gallery-price-val {
            font-size: 0.94rem;
            font-weight: 800;
            color: var(--korpri-navy);
        }
        [data-theme="dark"] .gallery-price-val {
            color: #93C5FD;
        }
        .gallery-price-sub {
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--status-green);
            background: var(--status-green-bg);
            padding: 1px 6px;
            border-radius: 3px;
        }

        .btn-gallery-action {
            width: 100%;
            padding: 7px 10px;
            border-radius: var(--radius-sm);
            font-size: 0.78rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            border: none;
            transition: var(--transition);
        }
        .btn-action-gold {
            background: var(--korpri-gold-light);
            color: var(--korpri-gold);
            border: 1px solid var(--korpri-gold-border);
        }
        .btn-action-gold:hover {
            background: var(--korpri-gold);
            color: #FFFFFF;
        }
        .btn-action-blue {
            background: var(--korpri-blue-subtle);
            color: var(--korpri-blue);
            border: 1px solid rgba(29, 78, 216, 0.2);
        }
        .btn-action-blue:hover {
            background: var(--korpri-blue);
            color: #FFFFFF;
        }
        .btn-action-emerald {
            background: var(--status-green-bg);
            color: var(--status-green);
            border: 1px solid var(--status-green-border);
        }
        .btn-action-emerald:hover {
            background: var(--status-green);
            color: #FFFFFF;
        }

        /* Lightbox Modal */
        .gov-lightbox-overlay {
            position: fixed;
            inset: 0;
            background: rgba(7, 15, 30, 0.88);
            backdrop-filter: blur(8px);
            z-index: 2500;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s ease;
        }
        .gov-lightbox-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .gov-lightbox-window {
            background: var(--gov-surface);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-lg);
            max-width: 960px;
            width: 100%;
            max-height: 92vh;
            overflow-y: auto;
            position: relative;
            box-shadow: var(--shadow-float);
            display: flex;
            flex-direction: column;
            animation: zoomModalIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes zoomModalIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .btn-lightbox-close {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.72);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #FFFFFF;
            font-size: 1.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 25;
            transition: var(--transition);
            backdrop-filter: blur(4px);
        }
        .btn-lightbox-close:hover {
            background: #EF4444;
            color: #FFFFFF;
            border-color: #EF4444;
            transform: scale(1.08);
        }

        .btn-lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.75);
            color: #FFFFFF;
            border: 1px solid rgba(255, 255, 255, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 15;
            transition: var(--transition);
            backdrop-filter: blur(4px);
        }
        .btn-lightbox-nav.prev { left: 12px; }
        .btn-lightbox-nav.next { right: 12px; }
        .btn-lightbox-nav:hover {
            background: var(--korpri-blue);
            transform: translateY(-50%) scale(1.08);
            border-color: rgba(255, 255, 255, 0.6);
        }

        .lightbox-body {
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            min-height: 480px;
            width: 100%;
        }

        .lightbox-media-wrap {
            position: relative;
            background: #F3F4F6;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow: hidden;
            border-radius: var(--radius-lg) 0 0 var(--radius-lg);
        }
        [data-theme="dark"] .lightbox-media-wrap {
            background: #0B1424;
        }

        .lightbox-main-img {
            max-width: 100%;
            max-height: 52vh;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: center;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-card);
            transition: transform 0.3s ease;
            display: block;
        }

        .lightbox-counter-badge {
            position: absolute;
            bottom: 12px;
            left: 12px;
            background: rgba(15, 23, 42, 0.85);
            color: #FFFFFF;
            font-size: 0.74rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            z-index: 12;
            backdrop-filter: blur(4px);
        }

        @media (max-width: 768px) {
            .gov-lightbox-overlay {
                padding: 10px 8px;
            }
            .gov-lightbox-window {
                max-height: 92vh;
                border-radius: 18px;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            .lightbox-body {
                display: flex;
                flex-direction: column;
                min-height: auto;
            }
            .lightbox-media-wrap {
                width: 100%;
                height: 270px;
                min-height: 270px;
                max-height: 300px;
                flex-shrink: 0;
                padding: 14px;
                border-radius: 18px 18px 0 0;
                background: #F1F5F9;
            }
            [data-theme="dark"] .lightbox-media-wrap {
                background: #0B132B;
            }
            .lightbox-main-img {
                width: 100%;
                height: 100%;
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
                object-position: center;
                border-radius: 8px;
            }
            .btn-lightbox-nav {
                width: 36px;
                height: 36px;
            }
            .btn-lightbox-nav.prev { left: 8px; }
            .btn-lightbox-nav.next { right: 8px; }
            .lightbox-counter-badge {
                bottom: 8px;
                left: 8px;
            }
            .lightbox-info-panel {
                padding: 16px 14px 20px;
                display: flex;
                flex-direction: column;
                gap: 14px;
            }
            .lightbox-footer-action {
                margin-top: 8px;
                padding: 12px;
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .btn-lightbox-select {
                width: 100%;
                justify-content: center;
                padding: 12px;
                font-size: 0.9rem;
            }
        }
        @media (max-width: 420px) {
            .lightbox-media-wrap {
                height: 230px;
                min-height: 230px;
                padding: 10px;
            }
        }

        .lightbox-info-panel {
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .lightbox-tag-pill {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--korpri-gold);
            background: var(--korpri-gold-light);
            border: 1px solid var(--korpri-gold-border);
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 8px;
        }
        .lightbox-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--gov-slate);
            margin: 0 0 6px;
            line-height: 1.35;
        }
        .lightbox-spec-badge {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--korpri-blue);
            margin-bottom: 12px;
        }
        .lightbox-desc {
            font-size: 0.86rem;
            color: var(--gov-muted);
            line-height: 1.6;
            margin-bottom: 14px;
        }
        .lightbox-features-title {
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--gov-subtle);
            letter-spacing: 0.04em;
            margin-bottom: 6px;
        }
        .lightbox-features-ul {
            margin: 0;
            padding-left: 18px;
            font-size: 0.82rem;
            color: var(--gov-slate);
            line-height: 1.6;
        }
        .lightbox-features-ul li {
            margin-bottom: 4px;
        }

        .lightbox-footer-action {
            background: var(--gov-hairline-subtle);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-md);
            padding: 14px;
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .lightbox-price-label {
            font-size: 0.74rem;
            color: var(--gov-subtle);
            display: block;
        }
        .lightbox-price-val {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--korpri-navy);
        }
        [data-theme="dark"] .lightbox-price-val {
            color: #F8FAFC;
        }
        .lightbox-pin-note {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--status-green);
            display: block;
            margin-top: 2px;
        }
        .btn-lightbox-select {
            background: var(--korpri-blue);
            color: #FFFFFF;
            padding: 10px 18px;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            font-weight: 800;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }
        .btn-lightbox-select:hover {
            background: #1E40AF;
            transform: translateY(-1px);
        }

        .lightbox-thumbs-strip {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid var(--gov-hairline);
            overflow-x: auto;
        }
        .lightbox-thumb-btn {
            width: 54px;
            height: 38px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 2px solid transparent;
            padding: 0;
            cursor: pointer;
            flex-shrink: 0;
            background: #E5E7EB;
            transition: var(--transition);
        }
        .lightbox-thumb-btn img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .lightbox-thumb-btn.active {
            border-color: var(--korpri-gold);
            transform: scale(1.08);
            box-shadow: 0 2px 8px rgba(212, 175, 55, 0.4);
        }

        /* Selector Thumbnail Previews */
        .choice-info-wrap {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            flex: 1;
            min-width: 0;
        }
        .choice-side-meta {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }
        .choice-thumb-box {
            display: flex;
            align-items: center;
            position: relative;
            flex-shrink: 0;
        }
        .choice-thumb-img {
            width: 64px;
            height: 42px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--gov-hairline);
            background: #FFF;
            box-shadow: var(--shadow-subtle);
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .choice-thumb-img:hover {
            transform: scale(1.05);
        }
        .choice-thumb-pin {
            position: absolute;
            bottom: -5px;
            right: -5px;
            width: 22px;
            height: 22px;
            object-fit: cover;
            border-radius: 4px;
            border: 1.5px solid #FFFFFF;
            box-shadow: 0 2px 6px rgba(0,0,0,0.25);
            background: #FFF;
        }

        @media (max-width: 860px) {
            .gov-product-selector {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }
        @media (max-width: 520px) {
            .product-choice-card {
                padding: 12px 14px;
                flex-wrap: wrap;
                gap: 10px;
            }
            .choice-info-wrap {
                width: 100%;
                flex: 1 1 100%;
            }
            .choice-side-meta {
                width: 100%;
                justify-content: space-between;
                padding-top: 8px;
                border-top: 1px dashed var(--gov-hairline);
                gap: 10px;
            }
            .choice-thumb-img {
                width: 54px;
                height: 36px;
            }
        }

        /* Spec Box Photos Row */
        .spec-photos-row {
            display: flex;
            gap: 8px;
            margin: 12px 0 6px;
            flex-wrap: wrap;
        }
        .spec-photo-thumb {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            cursor: pointer;
            background: var(--gov-surface);
            border: 1px solid var(--gov-hairline);
            border-radius: 6px;
            padding: 4px;
            transition: var(--transition);
        }
        .spec-photo-thumb:hover {
            border-color: var(--korpri-gold);
            transform: translateY(-2px);
            box-shadow: var(--shadow-subtle);
        }
        .spec-photo-thumb img {
            width: 58px;
            height: 38px;
            object-fit: cover;
            border-radius: 4px;
        }
        .spec-photo-thumb span {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--gov-muted);
        }

        /* Order Modal Photo Preview */
        .modal-summary-grid {
            display: flex;
            gap: 14px;
            align-items: center;
        }
        @media (max-width: 480px) {
            .modal-summary-grid {
                flex-direction: column;
                align-items: stretch;
            }
        }
        .modal-summary-thumb-box {
            position: relative;
            width: 82px;
            height: 56px;
            flex-shrink: 0;
            cursor: pointer;
            margin: 0 auto;
        }
        .modal-order-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--gov-hairline);
            box-shadow: var(--shadow-subtle);
        }
        .modal-order-pin-badge {
            position: absolute;
            bottom: -6px;
            right: -6px;
            background: #FFFFFF;
            border: 1px solid var(--gov-hairline);
            border-radius: 20px;
            padding: 1px 6px;
            display: flex;
            align-items: center;
            gap: 3px;
            font-size: 0.62rem;
            font-weight: 800;
            color: var(--status-green);
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        }
        [data-theme="dark"] .modal-order-pin-badge {
            background: #0F172A;
            border-color: #334155;
        }
        .modal-order-pin-badge img {
            width: 14px;
            height: 14px;
            object-fit: cover;
            border-radius: 2px;
        }
        .modal-summary-lines {
            flex: 1;
        }

        .gov-chip-gallery {
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            transition: var(--transition);
        }
        .gov-chip-gallery:hover {
            border-color: var(--korpri-gold);
            color: var(--korpri-gold);
            transform: translateY(-1px);
        }

        /* Toast feedback */
        .gov-toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-100px);
            background: #0B1E3B;
            color: #FFFFFF;
            border: 1px solid #D4AF37;
            border-radius: 30px;
            padding: 10px 22px;
            font-size: 0.85rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 28px rgba(0,0,0,0.3);
            z-index: 2000;
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
        }
        .gov-toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
    </style>
</head>
<body class="gov-portal">
    <!-- Top Accent Bar -->
    <div class="gov-top-accent"></div>

    <!-- Official Dignified Backdrop -->
    <div class="gov-bg-backdrop" aria-hidden="true"></div>
    <div class="gov-bg-seal" aria-hidden="true"></div>

    <!-- Top Header -->
    <header class="gov-header">
        <div class="gov-header-inner">
            <a href="cek_stok.php" class="gov-brand" title="Dewan Pengurus KORPRI Kabupaten Banjar">
                <div class="gov-brand-seal">
                    <img src="assets/images/korpri-badge.png" alt="Logo KORPRI" width="36" height="36">
                </div>
                <div class="gov-brand-text">
                    <span class="gov-brand-title">DEWAN PENGURUS KORPRI</span>
                    <span class="gov-brand-sub">KABUPATEN BANJAR</span>
                </div>
            </a>

            <div class="gov-nav-actions">
                <!-- Theme Toggle -->
                <button id="themeToggleBtn" class="btn-gov-nav btn-gov-icon" title="Ganti Tema Tampilan" aria-label="Ganti Tema">
                    <svg id="themeIconSvg" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                </button>

                <!-- Share Button -->
                <button onclick="shareKatalog()" class="btn-gov-nav" title="Bagikan Tautan Katalog">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                    <span class="btn-nav-label-long">Bagikan</span>
                </button>

                <!-- PWA Install Button in Header -->
                <button id="headerPwaInstallBtn" class="btn-gov-nav" onclick="triggerPwaInstall()" title="Pasang Aplikasi E-MutZ di Layar Depan HP" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line><polyline points="9 9 12 12 15 9"></polyline><line x1="12" y1="6" x2="12" y2="12"></line></svg>
                    <span class="btn-nav-label-long">Pasang App</span>
                </button>

                <!-- Admin Link -->
                <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
                    <a href="index.php" class="btn-gov-nav btn-gov-primary">
                        <span>Dashboard</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-gov-nav btn-gov-primary">
                        <span>Portal Petugas</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="gov-container">
        <!-- Hero Section with Animated Batik Background -->
        <section class="gov-hero">
            <div class="gov-hero-batik" aria-hidden="true"></div>
            <div class="gov-hero-overlay" aria-hidden="true"></div>
            <div class="gov-hero-content">
                <div class="gov-authority-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    <span>LAYANAN RESMI PENGADAAN MUTZ ASN</span>
                </div>

                <h1 class="gov-hero-h1">
                    Katalog Ketersediaan Stok Mutz ASN
                </h1>

                <p class="gov-hero-sub">
                    Informasi ketersediaan fisik topi Mutz resmi bagi Aparatur Sipil Negara (PNS &amp; PPPK) serta Kepala SKPD (Pejabat Eselon II) di lingkungan Pemerintah Kabupaten Banjar secara Real-Time.
                </p>

                <div class="gov-info-strip">
                    <a href="#lokasi-pelayanan" class="gov-info-location" title="Lihat Jam Pelayanan &amp; Lokasi Kantor Sekretariat KORPRI">
                        <div class="loc-pin-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        </div>
                        <div class="loc-text-block">
                            <span class="loc-instansi">Sekretariat KORPRI Kabupaten Banjar</span>
                            <span class="loc-divider">•</span>
                            <span class="loc-alamat">Jl. A. Yani Nomor 2 Martapura</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="opacity: 0.75; margin-left: 2px;"><path d="m6 9 6 6 6-6"/></svg>
                    </a>

                    <div class="gov-info-meta-row">
                        <div class="gov-meta-chip">
                            <span class="meta-pulse-dot"></span>
                            <span>Status: <strong>Update Real-Time</strong></span>
                        </div>
                        <div class="gov-meta-chip">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                            <span>Total Stok: <strong id="heroTotalStockVal"><?= number_format($total_stok_tersedia, 0, ',', '.') ?> Pcs</strong></span>
                        </div>
                        <a href="#galeri-produk" class="gov-meta-chip gov-chip-gallery" title="Lihat 5 Foto Fisik Resmi Topi Mutz & Pin">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            <span>Galeri <strong>5 Foto Fisik</strong></span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Galeri Fisik & Visual Produk Resmi (5 Foto Fisik & Pin Daerah) -->
        <section class="gov-section-card gov-gallery-section" id="galeri-produk">
            <div class="section-header-row">
                <div>
                    <div class="gallery-badge-top">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <span>DOKUMENTASI FOTO FISIK RESMI PENGURUS KORPRI</span>
                    </div>
                    <h2 class="section-h2" style="margin-top: 4px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--korpri-gold);"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                        <span>Galeri Fisik Topi Mutz &amp; Pin Lambang Daerah</span>
                    </h2>
                </div>
                <div class="gallery-filter-pills" role="tablist" aria-label="Filter Galeri Produk">
                    <button type="button" class="btn-gallery-tab active" onclick="filterGalleryCards('all', this)">Semua (5)</button>
                    <button type="button" class="btn-gallery-tab" onclick="filterGalleryCards('Kepala SKPD', this)">Kepala SKPD (2)</button>
                    <button type="button" class="btn-gallery-tab" onclick="filterGalleryCards('Biasa', this)">Standar ASN (2)</button>
                    <button type="button" class="btn-gallery-tab" onclick="filterGalleryCards('Pin', this)">Pin Banjar (1)</button>
                </div>
            </div>

            <p class="gallery-lead-desc">
                Dokumentasi resolusi tinggi wujud fisik topi Mutz resmi ASN Kabupaten Banjar. Klik foto untuk memperbesar, melihat detail kerapian bordir benang emas, bentuk lengkung hijab, serta emblem pin logam kuningan lambang kebanggaan daerah.
            </p>

            <div class="gallery-cards-grid" id="galleryCardsGrid">
                <!-- 1. Mutz Kepala SKPD Eselon 2 Pria -->
                <article class="gallery-card-item card-eselon" data-cat="Kepala SKPD" data-gender="Laki-laki">
                    <div class="gallery-img-box" onclick="openProductLightbox(0)" role="button" tabindex="0" title="Klik untuk memperbesar foto Mutz Kepala SKPD Pria">
                        <img src="assets/images/mutz-eselon-pria.jpg" alt="Foto Resmi Mutz Kepala SKPD Eselon 2 Pria" class="gallery-img" loading="lazy" decoding="async">
                        <div class="gallery-floating-tags">
                            <span class="tag-pill-gold">Eselon II • Pria</span>
                            <span class="tag-pill-dark">Bordir Emas</span>
                        </div>
                        <div class="gallery-hover-overlay">
                            <div class="overlay-zoom-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                                <span>Perbesar Foto</span>
                            </div>
                        </div>
                    </div>
                    <div class="gallery-card-content">
                        <div>
                            <div class="gallery-card-title-row">
                                <h3 class="gallery-card-title">Mutz Kepala SKPD (Eselon II) Pria</h3>
                                <span class="gallery-num-badge">Foto #1</span>
                            </div>
                            <p class="gallery-card-desc">Bentuk lurus tegak berwibawa dengan aksen bordir pita benang emas tebal ganda untuk Pejabat Eselon II Pria.</p>
                            <div class="gallery-card-features">
                                <span class="feature-chip"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Bordir Benang Emas Ganda</span>
                                <span class="feature-chip"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> + Free Pin Kab. Banjar</span>
                            </div>
                        </div>
                        <div class="gallery-card-footer">
                            <div class="gallery-price-wrap">
                                <span class="gallery-price-val">Rp <?= number_format(HARGA_KEPALA, 0, ',', '.') ?></span>
                                <span class="gallery-price-sub">Ready Stock</span>
                            </div>
                            <button type="button" class="btn-gallery-action btn-action-gold" onclick="selectProductFromGallery(0)">
                                <span>Pilih Model &amp; Cek Stok</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>
                    </div>
                </article>

                <!-- 2. Mutz Kepala SKPD Eselon 2 Wanita -->
                <article class="gallery-card-item card-eselon" data-cat="Kepala SKPD" data-gender="Perempuan">
                    <div class="gallery-img-box" onclick="openProductLightbox(1)" role="button" tabindex="0" title="Klik untuk memperbesar foto Mutz Kepala SKPD Wanita">
                        <img src="assets/images/mutz-eselon-wanita.jpg" alt="Foto Resmi Mutz Kepala SKPD Eselon 2 Wanita" class="gallery-img" loading="lazy" decoding="async">
                        <div class="gallery-floating-tags">
                            <span class="tag-pill-gold">Eselon II • Wanita</span>
                            <span class="tag-pill-dark">Bordir Emas</span>
                        </div>
                        <div class="gallery-hover-overlay">
                            <div class="overlay-zoom-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                                <span>Perbesar Foto</span>
                            </div>
                        </div>
                    </div>
                    <div class="gallery-card-content">
                        <div>
                            <div class="gallery-card-title-row">
                                <h3 class="gallery-card-title">Mutz Kepala SKPD (Eselon II) Wanita</h3>
                                <span class="gallery-num-badge">Foto #2</span>
                            </div>
                            <p class="gallery-card-desc">Siluet lengkung perahu anggun &amp; ergonomis dengan bordir benang emas tebal. Nyaman saat berhijab / bersanggul.</p>
                            <div class="gallery-card-features">
                                <span class="feature-chip"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Siluet Lengkung Hijab</span>
                                <span class="feature-chip"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> + Free Pin Kab. Banjar</span>
                            </div>
                        </div>
                        <div class="gallery-card-footer">
                            <div class="gallery-price-wrap">
                                <span class="gallery-price-val">Rp <?= number_format(HARGA_KEPALA, 0, ',', '.') ?></span>
                                <span class="gallery-price-sub">Ready Stock</span>
                            </div>
                            <button type="button" class="btn-gallery-action btn-action-gold" onclick="selectProductFromGallery(1)">
                                <span>Pilih Model &amp; Cek Stok</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>
                    </div>
                </article>

                <!-- 3. Mutz Standar ASN Pria -->
                <article class="gallery-card-item card-standar" data-cat="Biasa" data-gender="Laki-laki">
                    <div class="gallery-img-box" onclick="openProductLightbox(2)" role="button" tabindex="0" title="Klik untuk memperbesar foto Mutz Standar ASN Pria">
                        <img src="assets/images/mutz-standar-pria.jpg" alt="Foto Resmi Mutz Standar ASN Pria" class="gallery-img" loading="lazy" decoding="async">
                        <div class="gallery-floating-tags">
                            <span class="tag-pill-blue">Standar • Pria</span>
                            <span class="tag-pill-dark">Lis Kuning</span>
                        </div>
                        <div class="gallery-hover-overlay">
                            <div class="overlay-zoom-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                                <span>Perbesar Foto</span>
                            </div>
                        </div>
                    </div>
                    <div class="gallery-card-content">
                        <div>
                            <div class="gallery-card-title-row">
                                <h3 class="gallery-card-title">Mutz Standar ASN Pria</h3>
                                <span class="gallery-num-badge">Foto #3</span>
                            </div>
                            <p class="gallery-card-desc">Topi mutz kedinasan resmi KORPRI bentuk tegak lurus dengan lis pita kuning standar bagi seluruh ASN (PNS &amp; PPPK) pria.</p>
                            <div class="gallery-card-features">
                                <span class="feature-chip"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Seluruh ASN (PNS &amp; PPPK)</span>
                                <span class="feature-chip"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> + Free Pin Kab. Banjar</span>
                            </div>
                        </div>
                        <div class="gallery-card-footer">
                            <div class="gallery-price-wrap">
                                <span class="gallery-price-val">Rp <?= number_format(HARGA_BIASA, 0, ',', '.') ?></span>
                                <span class="gallery-price-sub">Ready Stock</span>
                            </div>
                            <button type="button" class="btn-gallery-action btn-action-blue" onclick="selectProductFromGallery(2)">
                                <span>Pilih Model &amp; Cek Stok</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>
                    </div>
                </article>

                <!-- 4. Mutz Standar ASN Wanita -->
                <article class="gallery-card-item card-standar" data-cat="Biasa" data-gender="Perempuan">
                    <div class="gallery-img-box" onclick="openProductLightbox(3)" role="button" tabindex="0" title="Klik untuk memperbesar foto Mutz Standar ASN Wanita">
                        <img src="assets/images/mutz-standar-wanita.jpg" alt="Foto Resmi Mutz Standar ASN Wanita" class="gallery-img" loading="lazy" decoding="async">
                        <div class="gallery-floating-tags">
                            <span class="tag-pill-blue">Standar • Wanita</span>
                            <span class="tag-pill-dark">Lis Kuning</span>
                        </div>
                        <div class="gallery-hover-overlay">
                            <div class="overlay-zoom-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                                <span>Perbesar Foto</span>
                            </div>
                        </div>
                    </div>
                    <div class="gallery-card-content">
                        <div>
                            <div class="gallery-card-title-row">
                                <h3 class="gallery-card-title">Mutz Standar ASN Wanita</h3>
                                <span class="gallery-num-badge">Foto #4</span>
                            </div>
                            <p class="gallery-card-desc">Model siluet melengkung ergonomis dengan lis kuning standar, dirancang pas dan rapi saat mengenakan jilbab atau sanggul.</p>
                            <div class="gallery-card-features">
                                <span class="feature-chip"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Siluet Lengkung Hijab</span>
                                <span class="feature-chip"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> + Free Pin Kab. Banjar</span>
                            </div>
                        </div>
                        <div class="gallery-card-footer">
                            <div class="gallery-price-wrap">
                                <span class="gallery-price-val">Rp <?= number_format(HARGA_BIASA, 0, ',', '.') ?></span>
                                <span class="gallery-price-sub">Ready Stock</span>
                            </div>
                            <button type="button" class="btn-gallery-action btn-action-blue" onclick="selectProductFromGallery(3)">
                                <span>Pilih Model &amp; Cek Stok</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>
                    </div>
                </article>

                <!-- 5. Pin Logam Kabupaten Banjar -->
                <article class="gallery-card-item card-pin" data-cat="Pin" data-gender="Semua">
                    <div class="gallery-img-box" onclick="openProductLightbox(4)" role="button" tabindex="0" title="Klik untuk memperbesar foto Pin Logam Kabupaten Banjar">
                        <img src="assets/images/pin-kab-banjar.jpg" alt="Foto Resmi Pin Logam Lambang Kabupaten Banjar" class="gallery-img" loading="lazy" decoding="async">
                        <div class="gallery-floating-tags">
                            <span class="tag-pill-emerald">Include Gratis</span>
                            <span class="tag-pill-dark">Kuningan Emas</span>
                        </div>
                        <div class="gallery-hover-overlay">
                            <div class="overlay-zoom-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                                <span>Perbesar Foto</span>
                            </div>
                        </div>
                    </div>
                    <div class="gallery-card-content">
                        <div>
                            <div class="gallery-card-title-row">
                                <h3 class="gallery-card-title">Pin Logam Lambang Kab. Banjar</h3>
                                <span class="gallery-num-badge">Foto #5</span>
                            </div>
                            <p class="gallery-card-desc">Lencana resmi logam kuningan emas berkilau tulisan "BANJAR - BARAKAT". Sudah otomatis disertakan di setiap pembelian mutz.</p>
                            <div class="gallery-card-features">
                                <span class="feature-chip"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Logam Kuningan Tahan Karat</span>
                                <span class="feature-chip"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Gratis di Setiap Pembelian</span>
                            </div>
                        </div>
                        <div class="gallery-card-footer">
                            <div class="gallery-price-wrap">
                                <span class="gallery-price-val" style="color: var(--status-green);">SUDAH INCLUDE</span>
                                <span class="gallery-price-sub">Gratis / Lengkap</span>
                            </div>
                            <button type="button" class="btn-gallery-action btn-action-emerald" onclick="openProductLightbox(4)">
                                <span>Lihat Detail Pin</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            </button>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <!-- Product Type Selector (Tactile 2-Card Segmented Choice) -->
        <div class="gov-product-selector">
            <!-- Card Standar ASN -->
            <div class="product-choice-card choice-standar active" id="cardChoiceStandar" onclick="selectCategory('Biasa')">
                <div class="choice-info-wrap">
                    <div class="choice-check-radio"></div>
                    <div>
                        <div class="choice-meta-title">Mutz Standar ASN</div>
                        <div class="choice-meta-desc">Diperuntukkan bagi seluruh ASN (PNS &amp; PPPK)</div>
                        <div class="choice-tags-row">
                            <span class="tag-micro tag-micro-green">Ready Stock</span>
                            <span class="tag-micro tag-micro-blue">Include Pin Kab. Banjar</span>
                        </div>
                    </div>
                </div>
                <div class="choice-side-meta">
                    <div class="choice-thumb-box" title="Foto Resmi Mutz Standar ASN + Pin (Klik untuk memperbesar)" onclick="event.stopPropagation(); openProductLightbox(currentGender === 'Perempuan' ? 3 : 2);">
                        <img src="assets/images/mutz-standar-pria.jpg" alt="Mutz Standar ASN" class="choice-thumb-img" id="thumbChoiceStandar" loading="lazy" decoding="async">
                        <img src="assets/images/pin-kab-banjar.jpg" alt="Pin Banjar" class="choice-thumb-pin" title="Include Pin Logam" loading="lazy" decoding="async">
                    </div>
                    <div class="choice-price-block">
                        <div class="choice-price-val">Rp <?= number_format(HARGA_BIASA, 0, ',', '.') ?></div>
                        <div class="choice-price-label">Harga Satuan</div>
                    </div>
                </div>
            </div>

            <!-- Card Kepala SKPD (Eselon II) -->
            <div class="product-choice-card choice-kepala" id="cardChoiceKepala" onclick="selectCategory('Kepala SKPD')">
                <div class="choice-info-wrap">
                    <div class="choice-check-radio"></div>
                    <div>
                        <div class="choice-meta-title">Mutz Kepala SKPD (Eselon II)</div>
                        <div class="choice-meta-desc">Pejabat Pimpinan Tinggi Pratama / Kepala Dinas</div>
                        <div class="choice-tags-row">
                            <span class="tag-micro tag-micro-green">Ready Stock</span>
                            <span class="tag-micro tag-micro-gold">Bordir List Benang Emas</span>
                            <span class="tag-micro tag-micro-blue">Include Pin Kab. Banjar</span>
                        </div>
                    </div>
                </div>
                <div class="choice-side-meta">
                    <div class="choice-thumb-box" title="Foto Resmi Mutz Kepala SKPD + Pin (Klik untuk memperbesar)" onclick="event.stopPropagation(); openProductLightbox(currentGender === 'Perempuan' ? 1 : 0);">
                        <img src="assets/images/mutz-eselon-pria.jpg" alt="Mutz Kepala SKPD" class="choice-thumb-img" id="thumbChoiceKepala" loading="lazy" decoding="async">
                        <img src="assets/images/pin-kab-banjar.jpg" alt="Pin Banjar" class="choice-thumb-pin" title="Include Pin Logam" loading="lazy" decoding="async">
                    </div>
                    <div class="choice-price-block">
                        <div class="choice-price-val">Rp <?= number_format(HARGA_KEPALA, 0, ',', '.') ?></div>
                        <div class="choice-price-label">Harga Satuan</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unified Filter Toolbar (Gender & Size) -->
        <div class="gov-filter-toolbar">
            <!-- Gender Segmented Switch -->
            <div class="gender-segmented-control" role="group" aria-label="Pilihan Jenis Kelamin">
                <button type="button" class="btn-segment active" id="btnGenderPria" onclick="selectGender('Laki-laki')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span>Pria</span>
                    <span class="segment-counter" id="counterPria">...</span>
                </button>
                <button type="button" class="btn-segment" id="btnGenderWanita" onclick="selectGender('Perempuan')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2a5 5 0 0 1 5 5c0 2.22-1.2 4.16-3 5.2V14a2 2 0 0 1-2 2h0a2 2 0 0 1-2-2v-1.8C8.2 11.16 7 9.22 7 7a5 5 0 0 1 5-5z"></path><path d="M6 21v-2a6 6 0 0 1 12 0v2"></path></svg>
                    <span>Wanita</span>
                    <span class="segment-counter" id="counterWanita">...</span>
                </button>
            </div>

            <!-- Size Filter Chips -->
            <div class="size-chips-scroller" id="sizeChipsTrack">
                <!-- Injected via JavaScript -->
            </div>
        </div>

        <!-- Eselon II Callout Bar -->
        <div class="gov-eselon-alert" id="eselonNoticeBar" style="display: none;">
            <div class="eselon-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
            </div>
            <div>
                <div class="eselon-alert-title">Spesifikasi Khusus Pejabat Eselon II</div>
                <div class="eselon-alert-desc">Dilengkapi aksen bordir list benang emas tebal resmi KORPRI untuk Kepala Dinas, Kepala Badan, Asisten Sekda, Staf Ahli, dan Sekda. Sudah include Pin Logam Lambang Kabupaten Banjar.</div>
            </div>
        </div>

        <!-- Stock Cards Grid Container -->
        <div class="gov-stock-grid" id="stockCardsContainer">
            <!-- Dynamically populated by JavaScript -->
        </div>

        <!-- Official Size Guide & Specifications -->
        <section class="gov-section-card" id="panduan">
            <div class="section-header-row">
                <h2 class="section-h2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--korpri-blue);"><path d="M21.3 8.7 8.7 21.3c-1 1-2.5 1-3.4 0l-2.6-2.6c-1-1-1-2.5 0-3.4L15.3 2.7c1-1 2.5-1 3.4 0l2.6 2.6c1 1 1 2.5 0 3.4Z"/><path d="m7.5 10.5 2 2"/><path d="m10.5 7.5 2 2"/><path d="m13.5 4.5 2 2"/><path d="m4.5 13.5 2 2"/></svg>
                    <span>Panduan Nomor Ukuran &amp; Cara Mengukur</span>
                </h2>
                <span class="tag-micro tag-micro-gold">Pedoman Resmi</span>
            </div>

            <p style="font-size: 0.9rem; color: var(--gov-muted); line-height: 1.6;">
                Nomor ukuran topi Mutz ASN mengacu pada <strong>lingkar kepala dalam satuan sentimeter (cm)</strong>. Ukur menggunakan pita meteran kain melingkari kepala sekitar 1 cm di atas telinga dan tepat di atas alis.
            </p>

            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="gov-editorial-table">
                    <thead>
                        <tr>
                            <th>Nomor Mutz</th>
                            <th>No. 55</th>
                            <th>No. 56</th>
                            <th>No. 57</th>
                            <th>No. 58</th>
                            <th>No. 59</th>
                            <th>No. 60</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="subhead-col">Lingkar Kepala</td>
                            <td>54.5 – 55.4 cm</td>
                            <td>55.5 – 56.4 cm</td>
                            <td>56.5 – 57.4 cm</td>
                            <td>57.5 – 58.4 cm</td>
                            <td>58.5 – 59.4 cm</td>
                            <td>59.5 – 60.5 cm</td>
                        </tr>
                        <tr>
                            <td class="subhead-col">Rekomendasi Pemakaian</td>
                            <td>Ukuran S (Kecil)</td>
                            <td>Ukuran M (Sedang)</td>
                            <td>Standar ASN Pria</td>
                            <td>Standar ASN Wanita</td>
                            <td>Berhijab / Rambut Tebal</td>
                            <td>Ekstra / Berhijab Longgar</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="hijab-callout-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <div>
                    <strong>Petunjuk untuk ASN Wanita (Berhijab / Sanggul):</strong> Disarankan memilih <strong>1 hingga 2 nomor lebih besar</strong> (umumnya No. 58 sampai No. 60) untuk memberikan kelonggaran lipatan kain hijab agar topi tidak menekan dahi dan tetap berdiri tegak rapi.
                </div>
            </div>

            <!-- Perbedaan Spesifikasi Standar vs Kepala SKPD -->
            <div style="margin-top: 2rem;" id="spesifikasi">
                <h3 style="font-size: 1.12rem; font-weight: 800; color: var(--gov-slate); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--korpri-gold);"><circle cx="12" cy="12" r="10"></circle><path d="m16 10-4 4-2-2"></path></svg>
                    <span>Perbedaan Mutz Standar dan Kepala SKPD (Eselon II)</span>
                </h3>

                <div class="gov-spec-grid">
                    <div class="spec-box">
                        <span class="spec-badge standar">Standar ASN (Biasa)</span>
                        <div class="spec-title">Mutz Standar KORPRI</div>

                        <div class="spec-photos-row">
                            <div class="spec-photo-thumb" onclick="openProductLightbox(2)" title="Klik untuk memperbesar foto Mutz Standar ASN Pria">
                                <img src="assets/images/mutz-standar-pria.jpg" alt="Mutz Standar Pria" loading="lazy" decoding="async">
                                <span>Pria</span>
                            </div>
                            <div class="spec-photo-thumb" onclick="openProductLightbox(3)" title="Klik untuk memperbesar foto Mutz Standar ASN Wanita">
                                <img src="assets/images/mutz-standar-wanita.jpg" alt="Mutz Standar Wanita" loading="lazy" decoding="async">
                                <span>Wanita</span>
                            </div>
                            <div class="spec-photo-thumb" onclick="openProductLightbox(4)" title="Klik untuk memperbesar foto Pin Logam Lambang Kab. Banjar">
                                <img src="assets/images/pin-kab-banjar.jpg" alt="Pin Kab Banjar" loading="lazy" decoding="async">
                                <span style="color: var(--status-green);">+ Pin Free</span>
                            </div>
                        </div>

                        <ul class="spec-list">
                            <li>Diperuntukkan bagi seluruh ASN (PNS dan PPPK).</li>
                            <li><strong>Sudah termasuk (include) Pin Logam Lambang Kab. Banjar</strong>.</li>
                            <li>Bahan standar kedinasan resmi rapi &amp; tahan lama.</li>
                            <li>Harga resmi: <strong>Rp <?= number_format(HARGA_BIASA, 0, ',', '.') ?></strong>.</li>
                        </ul>
                    </div>

                    <div class="spec-box eselon">
                        <span class="spec-badge eselon">Pejabat Eselon II</span>
                        <div class="spec-title">Mutz Kepala SKPD</div>

                        <div class="spec-photos-row">
                            <div class="spec-photo-thumb" onclick="openProductLightbox(0)" title="Klik untuk memperbesar foto Mutz Kepala SKPD Pria">
                                <img src="assets/images/mutz-eselon-pria.jpg" alt="Mutz Kepala SKPD Pria" loading="lazy" decoding="async">
                                <span>Pria</span>
                            </div>
                            <div class="spec-photo-thumb" onclick="openProductLightbox(1)" title="Klik untuk memperbesar foto Mutz Kepala SKPD Wanita">
                                <img src="assets/images/mutz-eselon-wanita.jpg" alt="Mutz Kepala SKPD Wanita" loading="lazy" decoding="async">
                                <span>Wanita</span>
                            </div>
                            <div class="spec-photo-thumb" onclick="openProductLightbox(4)" title="Klik untuk memperbesar foto Pin Logam Lambang Kab. Banjar">
                                <img src="assets/images/pin-kab-banjar.jpg" alt="Pin Kab Banjar" loading="lazy" decoding="async">
                                <span style="color: var(--status-green);">+ Pin Free</span>
                            </div>
                        </div>

                        <ul class="spec-list">
                            <li>Khusus Kepala Dinas, Kepala Badan, Asisten Sekda, Staf Ahli &amp; Sekda.</li>
                            <li>Aksen <strong>Bordir List Benang Emas Tebal</strong> di sekeliling mutz.</li>
                            <li><strong>Sudah termasuk (include) Pin Logam Lambang Kab. Banjar</strong>.</li>
                            <li>Bahan kokoh premium tahan lipatan.</li>
                            <li>Harga resmi: <strong>Rp <?= number_format(HARGA_KEPALA, 0, ',', '.') ?></strong> (Ready Stock).</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Official Service Hours & Google Maps Location Section -->
        <section class="gov-section-card" id="lokasi-pelayanan">
            <div class="section-header-row">
                <h2 class="section-h2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--korpri-blue);"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span>Jam Pelayanan &amp; Lokasi Sekretariat KORPRI</span>
                </h2>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <?php if ($is_office_open): ?>
                        <span class="tag-micro tag-micro-green" title="Waktu Saat Ini: <?= htmlspecialchars($current_time_str) ?> WITA">
                            <span class="meta-pulse-dot" style="width: 7px; height: 7px;"></span>
                            <span>Buka Pelayanan (WITA)</span>
                        </span>
                    <?php else: ?>
                        <span class="tag-micro" style="background: rgba(100, 116, 139, 0.15); color: var(--gov-muted);" title="Waktu Saat Ini: <?= htmlspecialchars($current_time_str) ?> WITA">
                            <span class="status-indicator-dot" style="background: #94A3B8; width: 6px; height: 6px;"></span>
                            <span>Di Luar Jam Layanan (WITA)</span>
                        </span>
                    <?php endif; ?>
                    <span class="tag-micro tag-micro-gold">Layanan Langsung</span>
                </div>
            </div>

            <p style="font-size: 0.9rem; color: var(--gov-muted); line-height: 1.6; margin-bottom: 0.5rem;">
                Sekretariat Dewan Pengurus KORPRI melayani pengambilan fisik topi Mutz resmi, penukaran ukuran, serta konsultasi pemesanan bagi seluruh ASN dan perwakilan SKPD Kabupaten Banjar.
            </p>

            <div class="gov-office-grid">
                <!-- Kolom Kiri: Jam Kerja Kedinasan -->
                <div class="office-card">
                    <div class="office-subhead">
                        <div class="office-subhead-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--korpri-blue);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            <span>Jadwal Jam Kerja Kedinasan</span>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--gov-subtle); font-weight: 600;">Zona WITA (GMT+8)</span>
                    </div>

                    <ul class="schedule-list">
                        <li class="schedule-item">
                            <span class="schedule-day">
                                <strong>Senin – Kamis</strong>
                            </span>
                            <span class="schedule-hours">08.00 – 15.00 WITA</span>
                        </li>
                        <li class="schedule-item">
                            <span class="schedule-day">
                                <strong>Jumat</strong>
                            </span>
                            <span class="schedule-hours">08.00 – 11.30 WITA</span>
                        </li>
                        <li class="schedule-item">
                            <span class="schedule-day">
                                <strong>Sabtu &amp; Minggu</strong>
                            </span>
                            <span class="schedule-hours closed">Tutup (Libur Akhir Pekan)</span>
                        </li>
                        <li class="schedule-item">
                            <span class="schedule-day">
                                <strong>Hari Libur Nasional</strong>
                            </span>
                            <span class="schedule-hours closed">Tutup Pelayanan</span>
                        </li>
                    </ul>

                    <div class="office-notice-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 1px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <div>
                            <strong>Catatan:</strong> Pemesanan via WhatsApp dapat dikirimkan sewaktu-waktu (24 jam) dan akan segera ditindaklanjuti admin pada hari &amp; jam kerja kedinasan.
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Lokasi & Navigasi Google Maps -->
                <div class="office-card">
                    <div class="office-subhead">
                        <div class="office-subhead-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--korpri-gold);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <span>Lokasi Kantor Sekretariat</span>
                        </div>
                        <a href="<?= htmlspecialchars($maps_search_url) ?>" target="_blank" rel="noopener" class="tag-micro tag-micro-blue" style="text-decoration: none;">
                            <span>Google Maps &nearr;</span>
                        </a>
                    </div>

                    <!-- Embed Google Maps -->
                    <div class="location-map-wrap">
                        <iframe 
                            src="<?= htmlspecialchars($maps_embed_url) ?>" 
                            title="Peta Lokasi Sekretariat KORPRI Kabupaten Banjar"
                            loading="lazy" 
                            allowfullscreen="" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>

                    <div class="location-meta-details">
                        <div class="loc-instansi-name">Sekretariat Dewan Pengurus KORPRI Kab. Banjar</div>
                        <div class="loc-full-address">
                            Jl. Ahmad Yani Nomor 2, Martapura, Kabupaten Banjar, Kalimantan Selatan 70611
                        </div>
                        <div class="loc-coords-pill" title="Koordinat GPS Resmi">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
                            <span><?= number_format($office_lat, 6) ?>, <?= number_format($office_lng, 6) ?></span>
                        </div>
                    </div>

                    <div class="location-actions-row">
                        <a href="<?= htmlspecialchars($maps_search_url) ?>" target="_blank" rel="noopener" class="btn-map-action btn-map-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <span>Buka di Google Maps</span>
                        </a>
                        <a href="<?= htmlspecialchars($maps_dir_url) ?>" target="_blank" rel="noopener" class="btn-map-action btn-map-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>
                            <span>Petunjuk Arah</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal Form WhatsApp Booking -->
    <div class="modal-overlay" id="waOrderModal" onclick="closeOrderModal(event)">
        <div class="modal-window" onclick="event.stopPropagation()">
            <div class="modal-head">
                <span class="modal-title-text" id="modalTitle">Konfirmasi Pemesanan</span>
                <button type="button" class="btn-modal-close" onclick="closeOrderModal()">&times;</button>
            </div>

            <p style="font-size: 0.84rem; color: var(--gov-muted); margin-bottom: 14px; font-weight: 500;">
                Format pesanan akan dikirimkan langsung ke nomor WhatsApp Pengurus / Admin Sekretariat KORPRI.
            </p>

            <div class="modal-summary-box">
                <div class="modal-summary-grid">
                    <div class="modal-summary-thumb-box" title="Foto Model Mutz Terpilih + Pin Logam (Klik untuk memperbesar)" onclick="openLightboxFromOrder()">
                        <img src="assets/images/mutz-standar-pria.jpg" id="modalOrderProductImg" alt="Foto Model Mutz" class="modal-order-img">
                        <div class="modal-order-pin-badge" title="Sudah Termasuk Pin Logam Lambang Kab. Banjar">
                            <img src="assets/images/pin-kab-banjar.jpg" alt="Pin">
                            <span>+ Pin</span>
                        </div>
                    </div>
                    <div class="modal-summary-lines">
                        <div class="modal-summary-line">
                            <span style="color: var(--gov-subtle);">Jenis Mutz:</span>
                            <strong id="modalCat">-</strong>
                        </div>
                        <div class="modal-summary-line">
                            <span style="color: var(--gov-subtle);">Peruntukan:</span>
                            <strong id="modalGender">-</strong>
                        </div>
                        <div class="modal-summary-line">
                            <span style="color: var(--gov-subtle);">Nomor Ukuran:</span>
                            <strong style="color: var(--korpri-blue);" id="modalSize">-</strong>
                        </div>
                        <div class="modal-summary-line">
                            <span style="color: var(--gov-subtle);">Status:</span>
                            <strong id="modalStockStatus">-</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="gov-form-group">
                <label class="gov-form-label" for="orderNama">Nama Pemesan (Opsional):</label>
                <input type="text" id="orderNama" class="gov-form-input" placeholder="Contoh: Budi Santoso, S.IP">
            </div>

            <div class="gov-form-group">
                <label class="gov-form-label" for="orderSkpd">Asal Dinas / SKPD (Opsional):</label>
                <select id="orderSkpd" class="gov-form-input" onchange="toggleManualSkpd(this.value)">
                    <option value="">-- Pilih Asal Dinas / SKPD --</option>
                    <?php foreach ($skpd_options as $opt): ?>
                        <option value="<?= htmlspecialchars($opt, ENT_QUOTES) ?>"><?= htmlspecialchars($opt) ?></option>
                    <?php endforeach; ?>
                    <option value="LAINNYA">Lainnya / UPT / Unit Lain (Ketik Manual)...</option>
                </select>
                <div id="wrapperSkpdManual" style="display: none; margin-top: 8px;">
                    <input type="text" id="orderSkpdManual" class="gov-form-input" placeholder="Ketik nama Dinas / UPT / Instansi Anda">
                </div>
            </div>

            <button type="button" onclick="submitWhatsAppOrder()" class="btn-order-action ready-wa" style="padding: 12px; font-size: 0.94rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                <span>Lanjutkan ke WhatsApp &rarr;</span>
            </button>
        </div>
    </div>

    <!-- Mobile Floating Quick-Action Dock (Fitur Tampilan #7) -->
    <div class="gov-mobile-dock" id="mobileDock" aria-label="Aksi Cepat Pemesanan Mobile">
        <div class="mobile-dock-inner">
            <div class="mobile-dock-info">
                <div class="dock-meta-line">
                    <span class="dock-cat-badge" id="dockCatBadge">Standar Pria</span>
                    <span class="dock-size-pill">
                        <select id="dockSizeSelect" class="dock-size-select" onchange="onDockSizeChange(this.value)" aria-label="Pilih Ukuran Cepat">
                            <!-- Populated dynamically via JS -->
                        </select>
                    </span>
                    <span class="dock-stock-tag" id="dockStockTag">
                        <span class="meta-pulse-dot" id="dockPulseDot" style="width: 6px; height: 6px;"></span>
                        <span id="dockStockText">...</span>
                    </span>
                </div>
                <div class="dock-price-line">
                    <span class="dock-price-val" id="dockPriceVal">Rp 55.000</span>
                    <span class="dock-include-tag">+ Pin Kab. Banjar</span>
                </div>
            </div>
            <div class="mobile-dock-btn-wrap">
                <button type="button" class="btn-dock-order" onclick="openOrderModalFromDock()" id="btnDockOrder" title="Pesan Ukuran Terpilih via WhatsApp">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    <span>Pesan WA</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Floating Actions (Bottom-Right) -->
    <div class="gov-floating-actions">
        <button type="button" id="btnScrollTop" class="btn-gov-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" title="Kembali ke atas">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 15l-6-6-6 6"/></svg>
        </button>

        <a href="#" onclick="openDirectWhatsApp(); return false;" class="btn-floating-wa" title="Hubungi Sekretariat KORPRI via WhatsApp">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            <span>Tanya Admin WA</span>
        </a>
    </div>

    <!-- Minimalist Official Footer -->
    <footer class="gov-footer">
        <div class="gov-footer-inner">
            <div class="footer-identity">
                <img src="assets/images/korpri-badge.png" alt="Logo KORPRI" class="footer-seal" width="38" height="38">
                <div class="footer-identity-details">
                    <div class="footer-title-row">
                        <span class="footer-name">Sekretariat KORPRI Kabupaten Banjar</span>
                        <span class="footer-status-pill">
                            <span class="status-indicator-dot"></span>
                            <span>Layanan Publik Resmi</span>
                        </span>
                    </div>
                    <a href="<?= htmlspecialchars($maps_search_url) ?>" target="_blank" rel="noopener" class="footer-address-txt" title="Buka Lokasi di Google Maps" style="text-decoration: none; color: inherit;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <span>Jl. A. Yani Nomor 2 Martapura, Kabupaten Banjar</span>
                    </a>
                </div>
            </div>

            <nav class="footer-nav-links" aria-label="Navigasi Footer">
                <a href="#galeri-produk">Galeri Fisik Mutz</a>
                <span class="footer-nav-dot">&bull;</span>
                <a href="#panduan">Panduan Ukuran</a>
                <span class="footer-nav-dot">&bull;</span>
                <a href="#lokasi-pelayanan">Jam Layanan &amp; Peta</a>
                <span class="footer-nav-dot">&bull;</span>
                <a href="login.php">Portal Petugas</a>
            </nav>
        </div>

        <div class="footer-copyright-bar">
            <div class="footer-copy-text">&copy; <?= date('Y') ?> Sekretariat KORPRI Kabupaten Banjar. Seluruh Hak Cipta Dilindungi.</div>
        </div>
    </footer>

    <!-- PWA Install Banner Mobile (Fitur Tampilan #5) -->
    <aside class="gov-pwa-banner" id="pwaBanner" role="region" aria-label="Pasang Aplikasi E-MutZ KORPRI">
        <div class="pwa-banner-left">
            <div class="pwa-banner-icon">
                <img src="assets/images/korpri-badge.png" alt="Logo KORPRI" width="32" height="32">
            </div>
            <div class="pwa-banner-text">
                <div class="pwa-banner-title">
                    <span>Pasang E-MutZ KORPRI</span>
                </div>
                <div class="pwa-banner-desc">Buka instan dari layar utama HP Anda</div>
            </div>
        </div>
        <div class="pwa-banner-actions">
            <button type="button" class="btn-pwa-install" onclick="triggerPwaInstall()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>Pasang</span>
            </button>
            <button type="button" class="btn-pwa-close" onclick="dismissPwaBanner()" title="Tutup pemberitahuan" aria-label="Tutup">
                &times;
            </button>
        </div>
    </aside>

    <!-- Modal Panduan Pasang PWA (Khusus Safari iOS & Panduan Manual) -->
    <div class="modal-overlay" id="pwaGuideModal" onclick="closePwaGuideModal(event)">
        <div class="modal-window" onclick="event.stopPropagation()" style="max-width: 500px;">
            <div class="modal-head">
                <span class="modal-title-text" style="display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="color: var(--korpri-gold);"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line><polyline points="9 9 12 12 15 9"></polyline><line x1="12" y1="6" x2="12" y2="12"></line></svg>
                    <span>Pasang E-MutZ di Layar Depan HP</span>
                </span>
                <button type="button" class="btn-modal-close" onclick="closePwaGuideModal()">&times;</button>
            </div>

            <p style="font-size: 0.85rem; color: var(--gov-slate); margin: 0 0 14px 0; line-height: 1.5;">
                Pasang <strong>E-MutZ KORPRI</strong> di layar utama ponsel / tablet Anda agar dapat dibuka cepat seperti aplikasi resmi tanpa perlu mengetik link lagi:
            </p>

            <!-- Guide Safari iOS -->
            <div class="pwa-modal-guide-card">
                <div class="pwa-guide-header ios">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                    <span>Pengguna iPhone / iPad (Safari)</span>
                </div>
                <ol class="pwa-guide-steps">
                    <li>Ketuk tombol <strong>Bagikan / Share</strong> (<span class="pwa-guide-highlight">ikon kotak panah ke atas &uarr;</span>) pada bilah navigasi bawah Safari.</li>
                    <li>Gulir menu ke bawah lalu pilih opsi <span class="pwa-guide-highlight">"Tambahkan ke Layar Utama" (Add to Home Screen)</span>.</li>
                    <li>Ketuk <span class="pwa-guide-highlight">"Tambah" (Add)</span> di sudut kanan atas layar. Selesai!</li>
                </ol>
            </div>

            <!-- Guide Android Chrome / Edge -->
            <div class="pwa-modal-guide-card" style="margin-bottom: 18px;">
                <div class="pwa-guide-header android">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span>Pengguna Android (Chrome / Edge / Samsung)</span>
                </div>
                <ol class="pwa-guide-steps">
                    <li>Ketuk ikon <strong>menu titik tiga (&vellip;)</strong> di sudut kanan atas browser.</li>
                    <li>Pilih menu <span class="pwa-guide-highlight">"Install app"</span> atau <span class="pwa-guide-highlight">"Tambahkan ke Layar Utama"</span>.</li>
                    <li>Konfirmasi dengan menekan tombol <strong>Install / Tambah</strong>.</li>
                </ol>
            </div>

            <button type="button" class="btn-modal-submit" onclick="closePwaGuideModal()" style="width: 100%; justify-content: center;">
                <span>Saya Mengerti &#128077;</span>
            </button>
        </div>
    </div>

    <!-- Lightbox Modal Fullscreen Photo Viewer (5 Foto Resmi) -->
    <div class="gov-lightbox-overlay" id="productLightboxModal" onclick="closeProductLightbox(event)" role="dialog" aria-modal="true" aria-label="Penampil Foto Mutz &amp; Pin">
        <div class="gov-lightbox-window" onclick="event.stopPropagation()">
            <button type="button" class="btn-lightbox-close" onclick="closeProductLightbox()" title="Tutup Galeri (Esc)" aria-label="Tutup">&times;</button>

            <div class="lightbox-body">
                <!-- Large Image Container -->
                <div class="lightbox-media-wrap">
                    <button type="button" class="btn-lightbox-nav prev" onclick="lightboxPrev()" title="Foto Sebelumnya (Panah Kiri)" aria-label="Sebelumnya">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button type="button" class="btn-lightbox-nav next" onclick="lightboxNext()" title="Foto Selanjutnya (Panah Kanan)" aria-label="Selanjutnya">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                    <img src="assets/images/mutz-eselon-pria.jpg" alt="Foto Detail Mutz" id="lightboxMainImg" class="lightbox-main-img">
                    <div class="lightbox-counter-badge" id="lightboxCounter">Foto 1 dari 5</div>
                </div>

                <!-- Product Details Panel -->
                <div class="lightbox-info-panel">
                    <div>
                        <div class="lightbox-head">
                            <span class="lightbox-tag-pill" id="lightboxTag">Eselon II Pria</span>
                            <h3 class="lightbox-title" id="lightboxTitle">Mutz Kepala SKPD (Eselon II) Pria</h3>
                            <div class="lightbox-spec-badge" id="lightboxSpec">Bordir List Benang Emas Ganda</div>
                        </div>

                        <p class="lightbox-desc" id="lightboxDesc">
                            Deskripsi spesifikasi produk...
                        </p>

                        <div class="lightbox-features-list">
                            <div class="lightbox-features-title">Karakteristik &amp; Kelengkapan:</div>
                            <ul id="lightboxFeaturesUl" class="lightbox-features-ul">
                                <!-- Populated via JS -->
                            </ul>
                        </div>
                    </div>

                    <div>
                        <div class="lightbox-footer-action">
                            <div class="lightbox-price-block">
                                <span class="lightbox-price-label">Harga Satuan Resmi:</span>
                                <span class="lightbox-price-val" id="lightboxPrice">Rp 75.000</span>
                                <span class="lightbox-pin-note" id="lightboxPinNote">✓ Sudah termasuk Pin Logam Kab. Banjar</span>
                            </div>
                            <button type="button" class="btn-lightbox-select" id="btnLightboxSelect" onclick="selectFromLightboxCurrent()">
                                <span>Pilih Model &amp; Cek Stok &rarr;</span>
                            </button>
                        </div>

                        <!-- Mini Thumbnails Carousel Strip -->
                        <div class="lightbox-thumbs-strip" id="lightboxThumbsStrip">
                            <!-- Populated via JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Toast -->
    <div id="govToast" class="gov-toast" role="status" aria-live="polite">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <span id="govToastMsg">Aplikasi Terpasang!</span>
    </div>

    <!-- Client-side Logic -->
    <script>
        const STOK_DATA = <?= json_encode($stok_data) ?>;
        const SIZES_PRIA = <?= json_encode($sizes_pria) ?>;
        const SIZES_WANITA = <?= json_encode($sizes_wanita) ?>;
        const HARGA_BIASA = <?= (int)HARGA_BIASA ?>;
        const HARGA_KEPALA = <?= (int)HARGA_KEPALA ?>;

        let currentCategory = 'Biasa'; 
        let currentGender = 'Laki-laki'; 
        let selectedSizeFilter = null; // null = tampilkan semua ukuran

        let selectedOrderData = {
            category: '',
            gender: '',
            size: 0,
            stock: 0
        };

        // Theme Toggle
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        function updateThemeIcon() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (themeToggleBtn) {
                themeToggleBtn.innerHTML = isDark 
                    ? '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>'
                    : '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';
            }
        }
        updateThemeIcon();

        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', function() {
                if (document.documentElement.getAttribute('data-theme') === 'dark') {
                    document.documentElement.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                }
                updateThemeIcon();
            });
        }

        // Category Switcher
        function selectCategory(cat) {
            currentCategory = cat;
            selectedSizeFilter = null; // Reset size filter

            const cardStandar = document.getElementById('cardChoiceStandar');
            const cardKepala = document.getElementById('cardChoiceKepala');
            const eselonBar = document.getElementById('eselonNoticeBar');

            if (cat === 'Biasa') {
                cardStandar.classList.add('active');
                cardKepala.classList.remove('active');
                eselonBar.style.display = 'none';
            } else {
                cardKepala.classList.add('active');
                cardStandar.classList.remove('active');
                eselonBar.style.display = 'flex';
            }

            renderSizeChips();
            renderStockCards();
            updateCounters();
            updateMobileDock();
        }

        // Gender Switcher
        function selectGender(gender) {
            currentGender = gender;
            selectedSizeFilter = null;

            const btnPria = document.getElementById('btnGenderPria');
            const btnWanita = document.getElementById('btnGenderWanita');

            if (gender === 'Laki-laki') {
                btnPria.classList.add('active');
                btnWanita.classList.remove('active');
            } else {
                btnWanita.classList.add('active');
                btnPria.classList.remove('active');
            }

            // Update product choice thumbnails based on selected gender
            const thumbStandar = document.getElementById('thumbChoiceStandar');
            const thumbKepala = document.getElementById('thumbChoiceKepala');
            if (gender === 'Perempuan') {
                if (thumbStandar) thumbStandar.src = 'assets/images/mutz-standar-wanita.jpg';
                if (thumbKepala) thumbKepala.src = 'assets/images/mutz-eselon-wanita.jpg';
            } else {
                if (thumbStandar) thumbStandar.src = 'assets/images/mutz-standar-pria.jpg';
                if (thumbKepala) thumbKepala.src = 'assets/images/mutz-eselon-pria.jpg';
            }

            renderSizeChips();
            renderStockCards();
            updateMobileDock();
        }

        function updateCounters(animate = true) {
            const dataCat = STOK_DATA[currentCategory] || {};
            
            let totalPria = 0;
            const priaObj = dataCat['Laki-laki'] || {};
            for (let sz in priaObj) { totalPria += (priaObj[sz] || 0); }
            
            let totalWanita = 0;
            const wanitaObj = dataCat['Perempuan'] || {};
            for (let sz in wanitaObj) { totalWanita += (wanitaObj[sz] || 0); }

            const elPria = document.getElementById('counterPria');
            const elWanita = document.getElementById('counterWanita');

            if (animate) {
                animateCountUp(elPria, totalPria, 600, 'Pcs');
                animateCountUp(elWanita, totalWanita, 600, 'Pcs');
            } else {
                if (elPria) elPria.textContent = totalPria + ' Pcs';
                if (elWanita) elWanita.textContent = totalWanita + ' Pcs';
            }
        }

        /* ==========================================================================
           OFFICIAL PRODUCT PHOTO GALLERY & LIGHTBOX LOGIC
           ========================================================================== */
        const GALLERY_DATA = [
            {
                id: 'eselon-pria',
                category: 'Kepala SKPD',
                gender: 'Laki-laki',
                title: 'Mutz Kepala SKPD (Eselon II) Pria',
                shortTitle: 'Eselon II Pria',
                tag: 'Eselon II • Pria',
                spec: 'Bordir List Benang Emas Ganda',
                price: 'Rp <?= number_format(HARGA_KEPALA, 0, ',', '.') ?>',
                image: 'assets/images/mutz-eselon-pria.jpg',
                desc: 'Topi Mutz resmi khusus Pejabat Pimpinan Tinggi Pratama / Eselon II Pria (Kepala Dinas, Kepala Badan, Asisten Sekda, Staf Ahli, dan Sekda Kabupaten Banjar). Desain bentuk lurus tegak berwibawa dengan aksen bordir pita benang emas ganda tebal di sekeliling mutz.',
                features: [
                    'Khusus Pejabat Pimpinan Tinggi Pratama / Eselon II Pria',
                    'Aksen bordir benang emas ganda tebal resmi kedinasan',
                    'Bahan kain drill cokelat premium tegap dan kokoh',
                    'Bentuk lurus standar resmi keprotokolan Pemkab Banjar',
                    'Sudah termasuk (include) Pin Logam Lambang Kab. Banjar'
                ]
            },
            {
                id: 'eselon-wanita',
                category: 'Kepala SKPD',
                gender: 'Perempuan',
                title: 'Mutz Kepala SKPD (Eselon II) Wanita',
                shortTitle: 'Eselon II Wanita',
                tag: 'Eselon II • Wanita / Hijab',
                spec: 'Bordir List Benang Emas Ganda',
                price: 'Rp <?= number_format(HARGA_KEPALA, 0, ',', '.') ?>',
                image: 'assets/images/mutz-eselon-wanita.jpg',
                desc: 'Topi Mutz resmi khusus Pejabat Eselon II Wanita di lingkungan Pemerintah Kabupaten Banjar. Dirancang dengan siluet lengkung perahu yang anggun dan ergonomis untuk memastikan kenyamanan saat mengenakan jilbab maupun sanggul.',
                features: [
                    'Khusus Pejabat Pimpinan Tinggi Pratama / Eselon II Wanita',
                    'Siluet lengkung ergonomis tidak menekan dahi saat berhijab',
                    'Aksen bordir benang emas ganda tebal resmi kedinasan',
                    'Tegak rapi dan anggun saat acara upacara maupun kedinasan',
                    'Sudah termasuk (include) Pin Logam Lambang Kab. Banjar'
                ]
            },
            {
                id: 'standar-pria',
                category: 'Biasa',
                gender: 'Laki-laki',
                title: 'Mutz Standar ASN Pria',
                shortTitle: 'Standar ASN Pria',
                tag: 'Standar ASN • Pria',
                spec: 'Lis Pita Kuning Standar Kedinasan',
                price: 'Rp <?= number_format(HARGA_BIASA, 0, ',', '.') ?>',
                image: 'assets/images/mutz-standar-pria.jpg',
                desc: 'Topi Mutz resmi kedinasan KORPRI bagi seluruh Pegawai ASN (PNS & PPPK) pria di lingkungan Pemerintah Kabupaten Banjar. Desain bentuk tegak lurus dengan lis pita kuning standar kedinasan.',
                features: [
                    'Diperuntukkan bagi seluruh ASN (PNS & PPPK) Pria',
                    'Desain bentuk tegak lurus resmi KORPRI',
                    'Bahan kain drill kedinasan cokelat kokoh dan rapi',
                    'Mudah dirawat dan nyaman untuk pemakaian dinas harian',
                    'Sudah termasuk (include) Pin Logam Lambang Kab. Banjar'
                ]
            },
            {
                id: 'standar-wanita',
                category: 'Biasa',
                gender: 'Perempuan',
                title: 'Mutz Standar ASN Wanita',
                shortTitle: 'Standar ASN Wanita',
                tag: 'Standar ASN • Wanita / Hijab',
                spec: 'Lis Pita Kuning Standar Kedinasan',
                price: 'Rp <?= number_format(HARGA_BIASA, 0, ',', '.') ?>',
                image: 'assets/images/mutz-standar-wanita.jpg',
                desc: 'Topi Mutz resmi kedinasan KORPRI bagi seluruh Pegawai ASN (PNS & PPPK) wanita. Dibuat dengan siluet lengkung ergonomis untuk kenyamanan maksimal saat berhijab atau bersanggul tanpa merusak kerapian jilbab.',
                features: [
                    'Diperuntukkan bagi seluruh ASN (PNS & PPPK) Wanita',
                    'Siluet lengkung ergonomis khusus untuk berhijab / bersanggul',
                    'Lis pita kuning standar kedinasan KORPRI',
                    'Kain lentur rapi tidak meninggalkan bekas tekanan di dahi',
                    'Sudah termasuk (include) Pin Logam Lambang Kab. Banjar'
                ]
            },
            {
                id: 'pin-banjar',
                category: 'Pin',
                gender: 'Semua',
                title: 'Pin Logam Lambang Kabupaten Banjar',
                shortTitle: 'Pin Kab. Banjar',
                tag: 'Kelengkapan Resmi • Gratis',
                spec: 'Logam Kuningan Emas Timbul "BARAKAT"',
                price: 'Sudah Include (Gratis)',
                image: 'assets/images/pin-kab-banjar.jpg',
                desc: 'Pin lencana resmi logam kuningan berlapis warna emas presisi tinggi menampilkan lambang kebanggaan Kabupaten Banjar bertuliskan semboyan "BANJAR - BARAKAT". Otomatis disertakan di setiap pembelian topi Mutz ASN!',
                features: [
                    'Bahan logam kuningan asli warna emas berkilau tahan karat',
                    'Embos detail lambang intan, padi-kapas, dan bintang Banjar',
                    'Peniti pengait belakang kokoh tidak mudah lepas',
                    'Sudah otomatis didapatkan di setiap pesanan Mutz tanpa biaya tambahan'
                ]
            }
        ];

        let currentLightboxIndex = 0;

        function openProductLightbox(index) {
            currentLightboxIndex = (index >= 0 && index < GALLERY_DATA.length) ? index : 0;
            renderLightboxContent();
            const modal = document.getElementById('productLightboxModal');
            if (modal) {
                modal.classList.add('active');
                const win = modal.querySelector('.gov-lightbox-window');
                if (win) win.scrollTop = 0;
            }
            document.body.style.overflow = 'hidden';
        }

        function closeProductLightbox(event) {
            if (!event || event.target.id === 'productLightboxModal' || event.target.classList.contains('btn-lightbox-close')) {
                const modal = document.getElementById('productLightboxModal');
                if (modal) modal.classList.remove('active');
                if (!document.getElementById('waOrderModal')?.classList.contains('active')) {
                    document.body.style.overflow = '';
                }
            }
        }

        function lightboxNext() {
            currentLightboxIndex = (currentLightboxIndex + 1) % GALLERY_DATA.length;
            renderLightboxContent();
        }

        function lightboxPrev() {
            currentLightboxIndex = (currentLightboxIndex - 1 + GALLERY_DATA.length) % GALLERY_DATA.length;
            renderLightboxContent();
        }

        function renderLightboxContent() {
            const item = GALLERY_DATA[currentLightboxIndex];
            if (!item) return;

            const imgEl = document.getElementById('lightboxMainImg');
            const counterEl = document.getElementById('lightboxCounter');
            const tagEl = document.getElementById('lightboxTag');
            const titleEl = document.getElementById('lightboxTitle');
            const specEl = document.getElementById('lightboxSpec');
            const descEl = document.getElementById('lightboxDesc');
            const featuresEl = document.getElementById('lightboxFeaturesUl');
            const priceEl = document.getElementById('lightboxPrice');
            const selectBtn = document.getElementById('btnLightboxSelect');
            const pinNote = document.getElementById('lightboxPinNote');

            if (imgEl) {
                imgEl.src = item.image;
                imgEl.alt = item.title;
            }
            if (counterEl) counterEl.textContent = `Foto ${currentLightboxIndex + 1} dari ${GALLERY_DATA.length}`;
            if (tagEl) tagEl.textContent = item.tag;
            if (titleEl) titleEl.textContent = item.title;
            if (specEl) specEl.textContent = item.spec;
            if (descEl) descEl.textContent = item.desc;
            if (priceEl) priceEl.textContent = item.price;

            if (featuresEl) {
                featuresEl.innerHTML = item.features.map(f => `<li>${f}</li>`).join('');
            }

            if (selectBtn) {
                if (item.category === 'Pin') {
                    selectBtn.innerHTML = '<span>Pesan Mutz (Include Pin) &rarr;</span>';
                } else {
                    selectBtn.innerHTML = `<span>Pilih ${item.shortTitle} &amp; Cek Stok &rarr;</span>`;
                }
            }

            if (pinNote) {
                pinNote.textContent = (item.category === 'Pin') ? '✓ Disertakan gratis di setiap pesanan topi Mutz' : '✓ Sudah termasuk Pin Logam Kab. Banjar';
            }

            // Render mini thumbnails strip
            const thumbsStrip = document.getElementById('lightboxThumbsStrip');
            if (thumbsStrip) {
                thumbsStrip.innerHTML = GALLERY_DATA.map((g, idx) => `
                    <button type="button" class="lightbox-thumb-btn ${idx === currentLightboxIndex ? 'active' : ''}" onclick="openProductLightbox(${idx})" title="${g.title}">
                        <img src="${g.image}" alt="${g.title}">
                    </button>
                `).join('');
            }
        }

        function selectFromLightboxCurrent() {
            const item = GALLERY_DATA[currentLightboxIndex];
            closeProductLightbox();
            if (item) {
                if (item.category === 'Pin') {
                    selectCategory('Biasa');
                } else {
                    selectCategory(item.category);
                    selectGender(item.gender);
                }
                setTimeout(() => {
                    const targetEl = document.querySelector('.gov-filter-toolbar') || document.getElementById('stockCardsContainer');
                    if (targetEl) {
                        targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 80);
            }
        }

        function selectProductFromGallery(index) {
            const item = GALLERY_DATA[index];
            if (!item) return;
            if (item.category === 'Pin') {
                openProductLightbox(index);
                return;
            }
            selectCategory(item.category);
            selectGender(item.gender);
            
            setTimeout(() => {
                const targetEl = document.querySelector('.gov-filter-toolbar') || document.getElementById('stockCardsContainer');
                if (targetEl) {
                    targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 50);
            showNotification(`Model terpilih: ${item.title}`);
        }

        function filterGalleryCards(cat, btn) {
            document.querySelectorAll('.btn-gallery-tab').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');

            const cards = document.querySelectorAll('#galleryCardsGrid .gallery-card-item');
            cards.forEach(card => {
                const cardCat = card.getAttribute('data-cat');
                if (cat === 'all' || cardCat === cat) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Keyboard listener for Lightbox navigation
        document.addEventListener('keydown', function(e) {
            const lightbox = document.getElementById('productLightboxModal');
            if (lightbox && lightbox.classList.contains('active')) {
                if (e.key === 'Escape') closeProductLightbox();
                else if (e.key === 'ArrowRight') lightboxNext();
                else if (e.key === 'ArrowLeft') lightboxPrev();
            }
        });

        // Size Filter Chips
        function renderSizeChips() {
            const track = document.getElementById('sizeChipsTrack');
            if (!track) return;
            track.innerHTML = '';

            const allSizes = (currentGender === 'Laki-laki') ? SIZES_PRIA : SIZES_WANITA;
            const stockObj = (STOK_DATA[currentCategory] && STOK_DATA[currentCategory][currentGender]) 
                ? STOK_DATA[currentCategory][currentGender] 
                : {};

            // Chip "Semua Ukuran"
            const btnAll = document.createElement('button');
            btnAll.type = 'button';
            btnAll.className = 'btn-size-chip' + (selectedSizeFilter === null ? ' active' : '');
            btnAll.innerHTML = '<span class="chip-text-full">Semua Ukuran (' + allSizes.length + ')</span><span class="chip-text-short">Semua (' + allSizes.length + ')</span>';
            btnAll.onclick = function() {
                selectedSizeFilter = null;
                renderSizeChips();
                renderStockCards();
                updateMobileDock();
            };
            track.appendChild(btnAll);

            // Per Size Chip
            allSizes.forEach(sz => {
                const qty = stockObj[sz] !== undefined ? stockObj[sz] : 0;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn-size-chip' + (selectedSizeFilter === sz ? ' active' : '');
                btn.textContent = `No. ${sz} (${qty})`;
                btn.onclick = function() {
                    selectedSizeFilter = (selectedSizeFilter === sz) ? null : sz;
                    renderSizeChips();
                    renderStockCards();
                    updateMobileDock();
                };
                track.appendChild(btn);
            });
        }

        // Stock Cards Rendering (Crisp, Dignified, No AI Slop Progress Bars)
        function renderStockCards() {
            const container = document.getElementById('stockCardsContainer');
            container.innerHTML = '';

            const allSizes = (currentGender === 'Laki-laki') ? SIZES_PRIA : SIZES_WANITA;
            const sizes = (selectedSizeFilter !== null && allSizes.includes(selectedSizeFilter))
                ? [selectedSizeFilter]
                : allSizes;

            const stockObj = (STOK_DATA[currentCategory] && STOK_DATA[currentCategory][currentGender]) 
                ? STOK_DATA[currentCategory][currentGender] 
                : {};

            const isKepala = (currentCategory === 'Kepala SKPD');
            const harga = isKepala ? HARGA_KEPALA : HARGA_BIASA;

            sizes.forEach((sz, idx) => {
                const qty = stockObj[sz] !== undefined ? stockObj[sz] : 0;

                let statusPillHtml = '';
                let btnActionHtml = '';
                let stockText = '';

                if (qty > 5) {
                    statusPillHtml = `
                        <span class="status-pill pill-avail-ready">
                            <span class="status-dot-sm"></span>
                            <span class="pill-text-full">Tersedia (${qty} pcs)</span>
                            <span class="pill-text-short">${qty} pcs</span>
                        </span>
                    `;
                    stockText = `Tersedia ${qty} pcs`;
                    btnActionHtml = `
                        <button type="button" class="btn-order-action ready-wa" onclick="openOrderModal('${currentCategory}', '${currentGender}', ${sz}, ${qty})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                            <span class="btn-text-full">Pesan Ukuran ${sz} via WA</span>
                            <span class="btn-text-short">Pesan WA</span>
                        </button>
                    `;
                } else if (qty > 0) {
                    statusPillHtml = `
                        <span class="status-pill pill-avail-low">
                            <span class="status-dot-sm"></span>
                            <span class="pill-text-full">Sisa ${qty} pcs</span>
                            <span class="pill-text-short">${qty} pcs</span>
                        </span>
                    `;
                    stockText = `Tersisa ${qty} pcs`;
                    btnActionHtml = `
                        <button type="button" class="btn-order-action ready-wa" onclick="openOrderModal('${currentCategory}', '${currentGender}', ${sz}, ${qty})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                            <span class="btn-text-full">Pesan Ukuran ${sz} via WA</span>
                            <span class="btn-text-short">Pesan WA</span>
                        </button>
                    `;
                } else {
                    statusPillHtml = `
                        <span class="status-pill pill-avail-empty">
                            <span class="status-dot-sm"></span>
                            <span class="pill-text-full">Pesan (Stok Habis)</span>
                            <span class="pill-text-short">Pesan</span>
                        </span>
                    `;
                    stockText = 'Stok Fisik Habis';
                    btnActionHtml = `
                        <button type="button" class="btn-order-action ready-wa" onclick="openOrderModal('${currentCategory}', '${currentGender}', ${sz}, 0)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                            <span class="btn-text-full">Pesan Ukuran ${sz} via WA</span>
                            <span class="btn-text-short">Pesan WA</span>
                        </button>
                    `;
                }

                const card = document.createElement('div');
                card.className = 'stock-item-card';
                card.style.animation = 'cardFadeUp 0.38s cubic-bezier(0.16, 1, 0.3, 1) backwards';
                card.style.animationDelay = ((typeof idx === 'number' ? idx : 0) * 0.045) + 's';
                card.innerHTML = `
                    <div class="stock-card-head">
                        <div class="size-display-wrap">
                            <div class="size-num-title">No. ${sz}</div>
                            <div class="size-circumference">Lingkar ~${sz} cm</div>
                        </div>
                        ${statusPillHtml}
                    </div>

                    <div class="stock-card-body">
                        <div class="body-row body-row-cat">
                            <span class="body-label">Kategori</span>
                            <span class="body-value">${isKepala ? 'Kepala SKPD' : 'Standar ASN'}</span>
                        </div>
                        <div class="body-row">
                            <span class="body-label">Harga Resmi</span>
                            <span class="body-value">Rp ${harga.toLocaleString('id-ID')}</span>
                        </div>
                        <div class="body-row body-row-pin">
                            <span class="body-label">Kelengkapan</span>
                            <span class="body-pin-val">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                <span>+ Pin Kab. Banjar</span>
                            </span>
                        </div>
                    </div>

                    ${btnActionHtml}
                `;

                container.appendChild(card);
            });
        }

        // WhatsApp Ordering Dialog
        function openOrderModal(category, gender, size, stock) {
            selectedOrderData = { category, gender, size, stock };

            document.getElementById('modalCat').textContent = (category === 'Kepala SKPD') ? 'Kepala SKPD (Eselon II)' : 'Mutz Standar ASN (Biasa)';
            document.getElementById('modalGender').textContent = (gender === 'Laki-laki') ? 'Pria' : 'Wanita';
            document.getElementById('modalSize').textContent = 'Nomor ' + size + ' (Lingkar ~' + size + ' cm)';
            
            const statusElem = document.getElementById('modalStockStatus');
            if (stock > 0) {
                statusElem.textContent = `Ready Stock (${stock} Pcs Tersedia)`;
                statusElem.style.color = '#15803D';
            } else {
                statusElem.textContent = 'Pemesanan (Stok Fisik Habis / Pre-Order)';
                statusElem.style.color = '#B91C1C';
            }

            // Update photo preview inside order modal
            const orderImg = document.getElementById('modalOrderProductImg');
            if (orderImg) {
                if (category === 'Kepala SKPD') {
                    orderImg.src = (gender === 'Laki-laki') ? 'assets/images/mutz-eselon-pria.jpg' : 'assets/images/mutz-eselon-wanita.jpg';
                    orderImg.alt = (gender === 'Laki-laki') ? 'Mutz Kepala SKPD Eselon 2 Pria' : 'Mutz Kepala SKPD Eselon 2 Wanita';
                } else {
                    orderImg.src = (gender === 'Laki-laki') ? 'assets/images/mutz-standar-pria.jpg' : 'assets/images/mutz-standar-wanita.jpg';
                    orderImg.alt = (gender === 'Laki-laki') ? 'Mutz Standar ASN Pria' : 'Mutz Standar ASN Wanita';
                }
            }

            document.getElementById('waOrderModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function openLightboxFromOrder() {
            if (!selectedOrderData) return;
            const isKepala = (selectedOrderData.category === 'Kepala SKPD');
            const isPria = (selectedOrderData.gender === 'Laki-laki');
            let idx = 2; // Default Standar Pria
            if (isKepala && isPria) idx = 0;
            else if (isKepala && !isPria) idx = 1;
            else if (!isKepala && isPria) idx = 2;
            else if (!isKepala && !isPria) idx = 3;
            openProductLightbox(idx);
        }

        function closeOrderModal(event) {
            if (!event || event.target.id === 'waOrderModal' || event.target.classList.contains('btn-modal-close') || event.target.tagName === 'BUTTON') {
                document.getElementById('waOrderModal').classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        function toggleManualSkpd(val) {
            const wrap = document.getElementById('wrapperSkpdManual');
            const input = document.getElementById('orderSkpdManual');
            if (val === 'LAINNYA') {
                wrap.style.display = 'block';
                if (input) input.focus();
            } else {
                wrap.style.display = 'none';
                if (input) input.value = '';
            }
        }

        function submitWhatsAppOrder() {
            const nama = document.getElementById('orderNama').value.trim();
            const selSkpd = document.getElementById('orderSkpd').value.trim();
            let skpd = '';
            if (selSkpd === 'LAINNYA') {
                skpd = document.getElementById('orderSkpdManual').value.trim();
            } else {
                skpd = selSkpd;
            }

            const isKepala = (selectedOrderData.category === 'Kepala SKPD');
            const catText = isKepala ? 'Mutz Kepala SKPD (Eselon II)' : 'Mutz Standar ASN (Biasa)';
            const statusPesanan = (selectedOrderData.stock > 0) ? 'Pemesanan Ready Stock' : 'Pemesanan Mutz ASN (Pre-Order)';

            let msg = `Halo Admin Sekretariat KORPRI Kab. Banjar,\n\n`;
            msg += `Saya ingin mengajukan *${statusPesanan}* dengan rincian berikut:\n`;
            msg += `• *Kategori Mutz:* ${catText}\n`;
            msg += `• *Peruntukan:* ${selectedOrderData.gender === 'Laki-laki' ? 'Pria' : 'Wanita'}\n`;
            msg += `• *Nomor Ukuran:* No. ${selectedOrderData.size} (Lingkar ~${selectedOrderData.size} cm)\n`;
            
            if (nama) msg += `• *Nama Pemesan:* ${nama}\n`;
            if (skpd) msg += `• *Asal SKPD:* ${skpd}\n`;
            
            msg += `\nMohon informasi kesiapan fisik dan petunjuk pengambilannya di Sekretariat KORPRI Kabupaten Banjar Jl. A.Yani Nomor 2 Martapura. Terima kasih!`;

            const phone = '<?= $admin_wa_default ?>';
            const url = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
            triggerConfettiBurst();
            setTimeout(function() {
                window.open(url, '_blank');
                closeOrderModal();
            }, 650);
        }

        function openDirectWhatsApp() {
            const msg = `Halo Admin Sekretariat KORPRI Kab. Banjar,\n\nSaya ingin menanyakan informasi ketersediaan stok topi Mutz ASN resmi.`;
            const phone = '<?= $admin_wa_default ?>';
            const url = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
            window.open(url, '_blank');
        }

        // Share & Toast
        let toastTimer = null;
        function showNotification(msg) {
            let toast = document.getElementById('govToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'govToast';
                toast.className = 'gov-toast';
                document.body.appendChild(toast);
            }
            toast.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span>${msg}</span>
            `;
            toast.classList.add('show');
            if (toastTimer) clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                toast.classList.remove('show');
            }, 2600);
        }

        async function shareKatalog() {
            const shareTitle = 'Katalog Ketersediaan Stok Mutz ASN | Dewan Pengurus KORPRI Kab. Banjar';
            const shareText = 'Cek ketersediaan stok fisik topi Mutz ASN Standar & Kepala SKPD Dewan Pengurus KORPRI Kab. Banjar secara Real-Time:';
            const shareUrl = window.location.href;
            const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

            if (isMobile && navigator.share) {
                try {
                    await navigator.share({
                        title: shareTitle,
                        text: shareText,
                        url: shareUrl
                    });
                    return;
                } catch (err) {
                    if (err.name === 'AbortError') return;
                }
            }

            let copied = false;
            if (navigator.clipboard) {
                try {
                    await navigator.clipboard.writeText(shareUrl);
                    copied = true;
                } catch (e) {}
            }
            if (!copied) {
                try {
                    const temp = document.createElement('input');
                    temp.value = shareUrl;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                    copied = true;
                } catch (e) {}
            }

            if (copied) {
                showNotification('Tautan katalog berhasil disalin ke clipboard!');
            } else {
                prompt('Salin tautan ini untuk dibagikan:', shareUrl);
            }
        }

        // ==========================================================================
        // MOBILE FLOATING QUICK-ACTION DOCK LOGIC (Fitur Tampilan #7)
        // ==========================================================================
        let currentDockSize = null;

        function updateMobileDock() {
            const dock = document.getElementById('mobileDock');
            if (!dock) return;

            const isKepala = (currentCategory === 'Kepala SKPD');
            const harga = isKepala ? HARGA_KEPALA : HARGA_BIASA;
            const allSizes = (currentGender === 'Laki-laki') ? SIZES_PRIA : SIZES_WANITA;
            const stockObj = (STOK_DATA[currentCategory] && STOK_DATA[currentCategory][currentGender]) 
                ? STOK_DATA[currentCategory][currentGender] 
                : {};

            // Prioritaskan ukuran yang dipilih dari chips halaman jika ada
            if (selectedSizeFilter !== null && allSizes.includes(selectedSizeFilter)) {
                currentDockSize = selectedSizeFilter;
            } else if (!currentDockSize || !allSizes.includes(currentDockSize)) {
                // Default ke ukuran standar yang paling umum
                currentDockSize = (currentGender === 'Laki-laki') ? 57 : 59;
                if (!allSizes.includes(currentDockSize)) {
                    currentDockSize = allSizes[0];
                }
            }

            // Update Badge Kategori & Gender
            const catBadge = document.getElementById('dockCatBadge');
            if (catBadge) {
                if (isKepala) {
                    catBadge.textContent = 'Eselon II ' + (currentGender === 'Laki-laki' ? 'Pria' : 'Wanita');
                    catBadge.classList.add('eselon');
                } else {
                    catBadge.textContent = 'Standar ' + (currentGender === 'Laki-laki' ? 'Pria' : 'Wanita');
                    catBadge.classList.remove('eselon');
                }
            }

            // Populate Dropdown Ukuran Cepat di Dock
            const sizeSelect = document.getElementById('dockSizeSelect');
            if (sizeSelect) {
                sizeSelect.innerHTML = '';
                allSizes.forEach(sz => {
                    const qty = stockObj[sz] !== undefined ? stockObj[sz] : 0;
                    const opt = document.createElement('option');
                    opt.value = sz;
                    opt.textContent = `No. ${sz} (${qty > 0 ? qty + ' pcs' : 'Habis'})`;
                    if (sz === currentDockSize) opt.selected = true;
                    sizeSelect.appendChild(opt);
                });
            }

            // Update Tampilan Harga Resmi
            const priceVal = document.getElementById('dockPriceVal');
            if (priceVal) {
                priceVal.textContent = 'Rp ' + harga.toLocaleString('id-ID');
            }

            // Update Indikator Status Ketersediaan Fisik
            const qty = stockObj[currentDockSize] !== undefined ? stockObj[currentDockSize] : 0;
            const stockTag = document.getElementById('dockStockTag');
            const stockText = document.getElementById('dockStockText');
            const pulseDot = document.getElementById('dockPulseDot');

            if (stockTag && stockText) {
                stockTag.classList.remove('ready', 'low', 'empty');
                if (qty > 5) {
                    stockTag.classList.add('ready');
                    stockText.textContent = `Ready (${qty} pcs)`;
                    if (pulseDot) {
                        pulseDot.style.background = '#22C55E';
                        pulseDot.style.boxShadow = '0 0 6px #22C55E';
                    }
                } else if (qty > 0) {
                    stockTag.classList.add('low');
                    stockText.textContent = `Sisa ${qty} pcs`;
                    if (pulseDot) {
                        pulseDot.style.background = '#F59E0B';
                        pulseDot.style.boxShadow = '0 0 6px #F59E0B';
                    }
                } else {
                    stockTag.classList.add('empty');
                    stockText.textContent = 'Pre-Order';
                    if (pulseDot) {
                        pulseDot.style.background = '#EF4444';
                        pulseDot.style.boxShadow = '0 0 6px #EF4444';
                    }
                }
            }
        }

        function onDockSizeChange(newSize) {
            currentDockSize = parseInt(newSize, 10);
            selectedSizeFilter = currentDockSize;
            renderSizeChips();
            renderStockCards();
            updateMobileDock();
        }

        function openOrderModalFromDock() {
            const allSizes = (currentGender === 'Laki-laki') ? SIZES_PRIA : SIZES_WANITA;
            if (!currentDockSize || !allSizes.includes(currentDockSize)) {
                currentDockSize = (currentGender === 'Laki-laki') ? 57 : 59;
                if (!allSizes.includes(currentDockSize)) currentDockSize = allSizes[0];
            }
            const stockObj = (STOK_DATA[currentCategory] && STOK_DATA[currentCategory][currentGender]) 
                ? STOK_DATA[currentCategory][currentGender] 
                : {};
            const qty = stockObj[currentDockSize] !== undefined ? stockObj[currentDockSize] : 0;
            openOrderModal(currentCategory, currentGender, currentDockSize, qty);
        }

        // Scroll Listener: Mengatur visibilitas Scroll-to-Top dan Mobile Dock (High Performance & Zero Jank)
        let scrollTicking = false;
        let lastScrollTopState = null;
        let lastDockState = null;
        const btnScrollTopEl = document.getElementById('btnScrollTop');
        const mobileDockEl = document.getElementById('mobileDock');

        window.addEventListener('scroll', function() {
            if (!scrollTicking) {
                window.requestAnimationFrame(function() {
                    const y = window.scrollY || window.pageYOffset || 0;

                    // Scroll-to-Top (> 300px)
                    const shouldShowTop = (y > 300);
                    if (shouldShowTop !== lastScrollTopState) {
                        lastScrollTopState = shouldShowTop;
                        if (btnScrollTopEl) {
                            btnScrollTopEl.classList.toggle('visible', shouldShowTop);
                        }
                    }

                    // Mobile Dock (> 180px)
                    const shouldShowDock = (y > 180);
                    if (shouldShowDock !== lastDockState) {
                        lastDockState = shouldShowDock;
                        if (mobileDockEl) {
                            mobileDockEl.classList.toggle('dock-visible', shouldShowDock);
                            document.body.classList.toggle('has-dock-visible', shouldShowDock);
                        }
                    }

                    scrollTicking = false;
                });
                scrollTicking = true;
            }
        }, { passive: true });

        // ==========================================================================
        // PWA (PROGRESSIVE WEB APP) INSTALLATION LOGIC (Fitur Tampilan #5)
        // ==========================================================================
        let deferredPwaPrompt = null;
        const PWA_DISMISS_KEY = 'emutz_pwa_dismissed_until';

        function isRunningStandalone() {
            return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) 
                || (window.navigator.standalone === true);
        }

        function isPwaDismissedRecently() {
            try {
                const until = localStorage.getItem(PWA_DISMISS_KEY);
                if (!until) return false;
                return (Date.now() < parseInt(until, 10));
            } catch (e) {
                return false;
            }
        }

        function showPwaBanner() {
            if (isRunningStandalone()) return;
            if (isPwaDismissedRecently()) return;
            const banner = document.getElementById('pwaBanner');
            if (banner) {
                banner.classList.add('banner-visible');
            }
        }

        function dismissPwaBanner() {
            const banner = document.getElementById('pwaBanner');
            if (banner) {
                banner.classList.remove('banner-visible');
            }
            try {
                // Jangan munculkan banner otomatis selama 7 hari jika sudah ditutup pengguna
                const sevenDaysLater = Date.now() + (7 * 24 * 60 * 60 * 1000);
                localStorage.setItem(PWA_DISMISS_KEY, sevenDaysLater.toString());
            } catch (e) {}
        }

        function triggerPwaInstall() {
            if (deferredPwaPrompt) {
                deferredPwaPrompt.prompt();
                deferredPwaPrompt.userChoice.then(function(choiceResult) {
                    if (choiceResult && choiceResult.outcome === 'accepted') {
                        dismissPwaBanner();
                        showToast('Memasang E-MutZ KORPRI...');
                    }
                    deferredPwaPrompt = null;
                });
            } else {
                openPwaGuideModal();
            }
        }

        function openPwaGuideModal() {
            const modal = document.getElementById('pwaGuideModal');
            if (modal) modal.classList.add('active');
        }

        function closePwaGuideModal(event) {
            if (!event || event.target.id === 'pwaGuideModal' || event.target.classList.contains('btn-modal-close') || event.target.tagName === 'BUTTON') {
                const modal = document.getElementById('pwaGuideModal');
                if (modal) modal.classList.remove('active');
            }
        }

        function showToast(msg) {
            const toast = document.getElementById('govToast');
            const toastMsg = document.getElementById('govToastMsg');
            if (toast && toastMsg) {
                toastMsg.textContent = msg;
                toast.classList.add('show');
                setTimeout(function() {
                    toast.classList.remove('show');
                }, 4000);
            }
        }

        // Tangkap trigger native install browser (Chrome, Edge, Samsung Internet)
        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            deferredPwaPrompt = e;
            const headerBtn = document.getElementById('headerPwaInstallBtn');
            if (headerBtn) headerBtn.style.display = 'inline-flex';
            setTimeout(showPwaBanner, 1500);
        });

        // Event saat aplikasi telah selesai dipasang
        window.addEventListener('appinstalled', function() {
            deferredPwaPrompt = null;
            dismissPwaBanner();
            const headerBtn = document.getElementById('headerPwaInstallBtn');
            if (headerBtn) headerBtn.style.display = 'none';
            showToast('E-MutZ KORPRI Berhasil Terpasang di HP! 🎉');
        });

        // Daftarkan Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('sw.js').catch(function() {});
            });
        }

        // ==========================================================================
        // EYE-CANDY VISUAL UTILITIES: Odometer Count-Up & Micro-Confetti Burst
        // ==========================================================================
        function animateCountUp(el, target, duration = 800, suffix = '') {
            if (!el) return;
            const start = 0;
            const startTime = performance.now();
            function update(now) {
                const elapsed = now - startTime;
                const progress = Math.min(elapsed / duration, 1);
                // EaseOutExpo
                const ease = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
                const current = Math.round(start + (target - start) * ease);
                el.textContent = current.toLocaleString('id-ID') + (suffix ? ' ' + suffix : '');
                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    el.textContent = target.toLocaleString('id-ID') + (suffix ? ' ' + suffix : '');
                }
            }
            requestAnimationFrame(update);
        }

        function triggerConfettiBurst() {
            try {
                const canvas = document.createElement('canvas');
                canvas.style.position = 'fixed';
                canvas.style.top = '0';
                canvas.style.left = '0';
                canvas.style.width = '100vw';
                canvas.style.height = '100vh';
                canvas.style.pointerEvents = 'none';
                canvas.style.zIndex = '99999';
                document.body.appendChild(canvas);

                const ctx = canvas.getContext('2d');
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;

                const colors = ['#D4AF37', '#1D4ED8', '#F59E0B', '#22C55E', '#3B82F6', '#FFFFFF'];
                const particles = [];
                const count = 65;

                for (let i = 0; i < count; i++) {
                    particles.push({
                        x: window.innerWidth / 2,
                        y: window.innerHeight * 0.45,
                        vx: (Math.random() - 0.5) * 16,
                        vy: (Math.random() - 0.75) * 18,
                        size: Math.random() * 8 + 4,
                        color: colors[Math.floor(Math.random() * colors.length)],
                        rotation: Math.random() * 360,
                        rotationSpeed: (Math.random() - 0.5) * 12,
                        opacity: 1,
                        gravity: 0.38
                    });
                }

                const startTime = performance.now();
                function animate(now) {
                    const elapsed = now - startTime;
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    particles.forEach(p => {
                        p.x += p.vx;
                        p.y += p.vy;
                        p.vy += p.gravity;
                        p.rotation += p.rotationSpeed;
                        p.opacity = Math.max(0, 1 - (elapsed / 1600));

                        ctx.save();
                        ctx.translate(p.x, p.y);
                        ctx.rotate((p.rotation * Math.PI) / 180);
                        ctx.fillStyle = p.color;
                        ctx.globalAlpha = p.opacity;
                        ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.65);
                        ctx.restore();
                    });

                    if (elapsed < 1650) {
                        requestAnimationFrame(animate);
                    } else {
                        canvas.remove();
                    }
                }
                requestAnimationFrame(animate);
            } catch (e) {}
        }

        // Init on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            selectCategory('Biasa');
            updateCounters();
            renderSizeChips();
            updateMobileDock();

            // Animasi Count-Up Total Stok di Hero
            const heroStockEl = document.getElementById('heroTotalStockVal');
            if (heroStockEl) {
                animateCountUp(heroStockEl, <?= (int)$total_stok_tersedia ?>, 1200, 'Pcs');
            }

            // Cek instalasi PWA
            if (!isRunningStandalone()) {
                const headerBtn = document.getElementById('headerPwaInstallBtn');
                if (headerBtn) headerBtn.style.display = 'inline-flex';
                // Tampilkan banner di layar mobile setelah 2.5 detik
                if (window.innerWidth <= 768 && !isPwaDismissedRecently()) {
                    setTimeout(showPwaBanner, 2500);
                }
            }
        });
    </script>
</body>
</html>
