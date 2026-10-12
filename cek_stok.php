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

// Default admin WhatsApp
$admin_wa_default = '6281258902720';

// Calculate absolute base URL for Open Graph (WhatsApp, Telegram, Facebook crawlers)
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$og_protocol = $is_https ? "https://" : "http://";
$og_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$og_dir = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
$og_dir_encoded = $og_dir ? implode('/', array_map('rawurlencode', explode('/', $og_dir))) : '';
$og_base_url = $og_protocol . $og_host . ($og_dir_encoded ? $og_dir_encoded : '') . '/';

$og_image_square = $og_base_url . 'assets/images/og-emutz-square.png';
$og_image_cover  = $og_base_url . 'assets/images/og-emutz-cover.png';
$og_page_url     = $og_protocol . $og_host . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Katalog Ketersediaan Stok Mutz ASN | Dewan Pengurus KORPRI Kab. Banjar</title>
    
    <!-- Primary SEO Meta Tags -->
    <meta name="title" content="Katalog Ketersediaan Stok Mutz ASN | Dewan Pengurus KORPRI Kab. Banjar">
    <meta name="description" content="Katalog resmi & ketersediaan stok fisik topi Mutz ASN Standar dan Kepala SKPD (Eselon II) Dewan Pengurus KORPRI Kabupaten Banjar secara Real-Time.">
    <meta name="keywords" content="mutz asn, korpri kabupaten banjar, mutz standar, dewan pengurus korpri, martapura, eselon II, stok topi asn">
    <meta name="author" content="Dewan Pengurus KORPRI Kabupaten Banjar">
    <meta name="theme-color" content="#0B1E3B">

    <!-- Open Graph / WhatsApp / Facebook Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($og_page_url) ?>">
    <meta property="og:title" content="Katalog & Stok Mutz ASN | Dewan Pengurus KORPRI Kab. Banjar">
    <meta property="og:description" content="Informasi stok Mutz Standar ASN & Kepala SKPD Dewan Pengurus KORPRI Kab. Banjar secara Real-Time. Cek ukuran lingkar kepala & pesan via WhatsApp.">
    <meta property="og:site_name" content="Dewan Pengurus KORPRI Kabupaten Banjar">
    <meta property="og:locale" content="id_ID">
    
    <!-- WhatsApp Chat Thumbnail (Square 1:1) -->
    <meta property="og:image" content="<?= htmlspecialchars($og_image_square) ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($og_image_square) ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="600">
    <meta property="og:image:height" content="600">
    <meta property="og:image:alt" content="Logo Dewan Pengurus KORPRI Kabupaten Banjar">

    <!-- Large Social Media Card (1200x630) -->
    <meta property="og:image" content="<?= htmlspecialchars($og_image_cover) ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($og_image_cover) ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Katalog Stok Mutz ASN KORPRI Kabupaten Banjar">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= htmlspecialchars($og_page_url) ?>">
    <meta name="twitter:title" content="Katalog & Stok Mutz ASN | Dewan Pengurus KORPRI Kab. Banjar">
    <meta name="twitter:description" content="Informasi stok Mutz Standar ASN & Kepala SKPD Dewan Pengurus KORPRI Kab. Banjar secara Real-Time.">
    <meta name="twitter:image" content="<?= htmlspecialchars($og_image_cover) ?>">

    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    
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
            
            --transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
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
            scroll-behavior: smooth;
            -webkit-text-size-adjust: 100%;
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

        /* Official Architectural Background Texture (Dignified & Subtle) */
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
            background-image: 
                radial-gradient(ellipse at 50% 0%, rgba(11, 30, 59, 0.04) 0%, transparent 70%),
                linear-gradient(180deg, #F8FAFC 0%, #F1F5F9 100%);
        }
        [data-theme="dark"] .gov-bg-backdrop {
            background-image: 
                radial-gradient(ellipse at 50% 0%, rgba(30, 58, 138, 0.15) 0%, transparent 70%),
                linear-gradient(180deg, #090F1D 0%, #060A14 100%);
        }

        /* Subtle KORPRI Watermark */
        .gov-bg-seal {
            position: fixed;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: min(70vw, 420px);
            height: min(70vw, 420px);
            background-image: url('assets/images/korpri-badge.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.025;
            z-index: -2;
            pointer-events: none;
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
        }

        .gov-header {
            position: sticky;
            top: 3px;
            z-index: 100;
            background: var(--gov-surface);
            border-bottom: 1px solid var(--gov-hairline);
            box-shadow: var(--shadow-subtle);
            padding: 0.75rem 1.5rem;
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
            width: 42px;
            height: 42px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            background: #FFFFFF;
            border: 1px solid var(--gov-hairline);
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            flex-shrink: 0;
            padding: 3px;
        }
        .gov-brand-seal img {
            width: 100%;
            height: 100%;
            object-fit: contain;
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
            background-color: #0B1E3B;
            background-image: 
                linear-gradient(135deg, rgba(7, 21, 43, 0.88) 0%, rgba(11, 30, 59, 0.82) 50%, rgba(15, 41, 77, 0.88) 100%),
                url('assets/images/batik-korpri.jpg');
            background-size: cover;
            background-position: center;
            border: 1px solid rgba(212, 175, 55, 0.4);
            border-radius: var(--radius-lg);
            padding: 2.25rem 2rem 2rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 24px -4px rgba(11, 30, 59, 0.16), 0 2px 6px -2px rgba(11, 30, 59, 0.08);
            text-align: center;
            overflow: hidden;
            color: #FFFFFF;
        }
        [data-theme="dark"] .gov-hero {
            background-image: 
                linear-gradient(135deg, rgba(4, 12, 26, 0.92) 0%, rgba(8, 20, 42, 0.86) 50%, rgba(11, 30, 59, 0.92) 100%),
                url('assets/images/batik-korpri.jpg');
            border-color: rgba(212, 175, 55, 0.35);
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
            box-shadow: 0 0 0 1px var(--korpri-blue), var(--shadow-card);
        }
        [data-theme="dark"] .product-choice-card.active.choice-standar {
            border-color: #3B82F6;
            box-shadow: 0 0 0 1px #3B82F6, var(--shadow-card);
        }

        /* Active State - Kepala SKPD */
        .product-choice-card.active.choice-kepala {
            border-color: var(--korpri-gold);
            background: var(--gov-surface);
            box-shadow: 0 0 0 1px var(--korpri-gold), var(--shadow-card);
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
        }
        [data-theme="dark"] .tag-micro-gold {
            color: #FDE68A;
        }
        .tag-micro-green {
            background: var(--status-green-bg);
            color: var(--status-green);
            border: 1px solid var(--status-green-border);
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
            gap: 12px;
            flex-wrap: wrap;
            box-shadow: var(--shadow-subtle);
        }

        .gender-segmented-control {
            display: inline-flex;
            background: var(--gov-hairline-subtle);
            border: 1px solid var(--gov-hairline);
            border-radius: var(--radius-sm);
            padding: 3px;
            gap: 3px;
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
            padding: 2px 0;
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
        }
        .stock-item-card:hover {
            transform: translateY(-2px);
            border-color: #CBD5E1;
            box-shadow: var(--shadow-card);
        }
        [data-theme="dark"] .stock-item-card:hover {
            border-color: #334155;
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
            margin-bottom: 4px;
        }
        .body-row:last-child {
            margin-bottom: 0;
        }
        .body-label {
            color: var(--gov-muted);
            font-weight: 500;
        }
        .body-value {
            font-weight: 700;
            color: var(--gov-slate);
        }
        .body-include-pin {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: var(--korpri-navy);
            font-weight: 700;
            font-size: 0.78rem;
        }
        [data-theme="dark"] .body-include-pin {
            color: #93C5FD;
        }
        .body-include-pin svg {
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
        .spec-list li {
            margin-bottom: 4px;
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
            z-index: 1000;
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
           MINIMALIST OFFICIAL FOOTER (No Wasted Space, 100% Dignified)
           ========================================================================== */
        .gov-footer {
            background: var(--gov-surface);
            border-top: 1px solid var(--gov-hairline);
            padding: 1.75rem 1.5rem;
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
            background: linear-gradient(90deg, #0B1E3B 0%, #B48228 50%, #0B1E3B 100%);
        }

        .gov-footer-inner {
            max-width: 1140px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            flex-wrap: wrap;
        }

        .footer-identity {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .footer-seal {
            width: 32px;
            height: 32px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .footer-name {
            font-size: 0.92rem;
            font-weight: 800;
            color: var(--gov-slate);
            line-height: 1.2;
        }
        .footer-address-txt {
            font-size: 0.78rem;
            color: var(--gov-subtle);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 2px;
        }
        .footer-address-txt svg {
            color: var(--korpri-gold);
            flex-shrink: 0;
        }

        .footer-nav-links {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 0.82rem;
            font-weight: 600;
        }
        .footer-nav-links a {
            color: var(--gov-muted);
            text-decoration: none;
            transition: var(--transition);
        }
        .footer-nav-links a:hover {
            color: var(--korpri-blue);
        }

        .footer-copyright-bar {
            max-width: 1140px;
            margin: 1.25rem auto 0 auto;
            padding-top: 1rem;
            border-top: 1px solid var(--gov-hairline);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.76rem;
            color: var(--gov-subtle);
            flex-wrap: wrap;
            gap: 8px;
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
            }
            .gender-segmented-control {
                width: 100%;
                display: flex;
            }
            .btn-segment {
                flex: 1;
                justify-content: center;
                padding: 8px 10px;
                font-size: 0.8rem;
            }
            .size-chips-scroller {
                gap: 6px;
                padding: 2px 0 4px 0;
            }
            .btn-size-chip {
                padding: 6px 10px;
                font-size: 0.78rem;
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
            .body-include-pin {
                font-size: 0.68rem;
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

            /* Floating Button on Mobile */
            .gov-floating-actions {
                bottom: 14px;
                right: 14px;
                gap: 8px;
            }
            .btn-floating-wa {
                padding: 9px 14px;
                font-size: 0.82rem;
            }

            /* Footer on Mobile (Clean Minimalist) */
            .gov-footer {
                padding: 1.25rem 1rem;
            }
            .gov-footer-inner {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.85rem;
            }
            .footer-nav-links {
                gap: 10px;
                flex-wrap: wrap;
                font-size: 0.78rem;
            }
            .footer-copyright-bar {
                margin-top: 1rem;
                padding-top: 0.85rem;
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
        <!-- Hero Section -->
        <section class="gov-hero">
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
                <div class="gov-info-location">
                    <div class="loc-pin-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    </div>
                    <div class="loc-text-block">
                        <span class="loc-instansi">Sekretariat KORPRI Kabupaten Banjar</span>
                        <span class="loc-divider">•</span>
                        <span class="loc-alamat">Jl. A. Yani Nomor 2 Martapura</span>
                    </div>
                </div>

                <div class="gov-info-meta-row">
                    <div class="gov-meta-chip">
                        <span class="meta-pulse-dot"></span>
                        <span>Status: <strong>Update Real-Time</strong></span>
                    </div>
                    <div class="gov-meta-chip">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                        <span>Total Stok: <strong><?= number_format($total_stok_tersedia, 0, ',', '.') ?> Pcs</strong></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Product Type Selector (Tactile 2-Card Segmented Choice) -->
        <div class="gov-product-selector">
            <!-- Card Standar ASN -->
            <div class="product-choice-card choice-standar active" id="cardChoiceStandar" onclick="selectCategory('Biasa')">
                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <div class="choice-check-radio"></div>
                    <div>
                        <div class="choice-meta-title">Mutz Standar ASN</div>
                        <div class="choice-meta-desc">Diperuntukkan bagi seluruh ASN (PNS &amp; PPPK)</div>
                        <div class="choice-tags-row">
                            <span class="tag-micro tag-micro-blue">Include Pin Kab. Banjar</span>
                            <span class="tag-micro tag-micro-green"><?= number_format($total_stok_biasa, 0, ',', '.') ?> Pcs Tersedia</span>
                        </div>
                    </div>
                </div>
                <div class="choice-price-block">
                    <div class="choice-price-val">Rp <?= number_format(HARGA_BIASA, 0, ',', '.') ?></div>
                    <div class="choice-price-label">Harga Satuan</div>
                </div>
            </div>

            <!-- Card Kepala SKPD (Eselon II) -->
            <div class="product-choice-card choice-kepala" id="cardChoiceKepala" onclick="selectCategory('Kepala SKPD')">
                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <div class="choice-check-radio"></div>
                    <div>
                        <div class="choice-meta-title">Mutz Kepala SKPD (Eselon II)</div>
                        <div class="choice-meta-desc">Pejabat Pimpinan Tinggi Pratama / Kepala Dinas</div>
                        <div class="choice-tags-row">
                            <span class="tag-micro tag-micro-green">Ready Stock</span>
                            <span class="tag-micro tag-micro-gold">Bordir List Benang Emas</span>
                            <span class="tag-micro tag-micro-blue">Include Pin</span>
                        </div>
                    </div>
                </div>
                <div class="choice-price-block">
                    <div class="choice-price-val">Rp <?= number_format(HARGA_KEPALA, 0, ',', '.') ?></div>
                    <div class="choice-price-label">Harga Satuan</div>
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
                <div class="eselon-alert-title">Spesifikasi Khusus Pejabat Eselon II (Ready Stock)</div>
                <div class="eselon-alert-desc">Dilengkapi aksen bordir list benang emas tebal resmi KORPRI untuk Kepala Dinas, Kepala Badan, Asisten Sekda, Staf Ahli, dan Sekda. Seluruh ukuran ready dan include Pin Lambang Kabupaten Banjar.</div>
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

            <div class="gov-form-group">
                <label class="gov-form-label" for="orderNama">Nama Pemesan (Opsional):</label>
                <input type="text" id="orderNama" class="gov-form-input" placeholder="Contoh: Budi Santoso, S.IP">
            </div>

            <div class="gov-form-group">
                <label class="gov-form-label" for="orderSkpd">Asal Dinas / SKPD (Opsional):</label>
                <input type="text" id="orderSkpd" class="gov-form-input" placeholder="Contoh: Dinas Pendidikan">
            </div>

            <button type="button" onclick="submitWhatsAppOrder()" class="btn-order-action ready-wa" style="padding: 12px; font-size: 0.94rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                <span>Lanjutkan ke WhatsApp &rarr;</span>
            </button>
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
                <img src="assets/images/korpri-badge.png" alt="Logo KORPRI" class="footer-seal" width="32" height="32">
                <div>
                    <div class="footer-name">Sekretariat KORPRI Kabupaten Banjar</div>
                    <div class="footer-address-txt">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <span>Jl. A. Yani Nomor 2 Martapura</span>
                    </div>
                </div>
            </div>

            <nav class="footer-nav-links" aria-label="Navigasi Footer">
                <a href="#panduan">Panduan Ukuran</a>
                <span>&bull;</span>
                <a href="#spesifikasi">Spesifikasi Eselon II</a>
                <span>&bull;</span>
                <a href="login.php">Portal Petugas</a>
            </nav>
        </div>

        <div class="footer-copyright-bar">
            <div>&copy; <?= date('Y') ?> Sekretariat KORPRI Kabupaten Banjar. Seluruh Hak Cipta Dilindungi.</div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="width: 7px; height: 7px; border-radius: 50%; background: #16A34A; display: inline-block;"></span>
                <span>Layanan Publik Resmi Aktif</span>
            </div>
        </div>
    </footer>

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

            renderSizeChips();
            renderStockCards();
        }

        function updateCounters() {
            const dataCat = STOK_DATA[currentCategory] || {};
            
            let totalPria = 0;
            const priaObj = dataCat['Laki-laki'] || {};
            for (let sz in priaObj) { totalPria += (priaObj[sz] || 0); }
            
            let totalWanita = 0;
            const wanitaObj = dataCat['Perempuan'] || {};
            for (let sz in wanitaObj) { totalWanita += (wanitaObj[sz] || 0); }

            if (currentCategory === 'Kepala SKPD') {
                document.getElementById('counterPria').textContent = 'Ready';
                document.getElementById('counterWanita').textContent = 'Ready';
            } else {
                document.getElementById('counterPria').textContent = totalPria + ' Pcs';
                document.getElementById('counterWanita').textContent = totalWanita + ' Pcs';
            }
        }

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
            };
            track.appendChild(btnAll);

            // Per Size Chip
            allSizes.forEach(sz => {
                const qty = stockObj[sz] !== undefined ? stockObj[sz] : 0;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn-size-chip' + (selectedSizeFilter === sz ? ' active' : '');
                
                const label = (currentCategory === 'Kepala SKPD') 
                    ? `No. ${sz}` 
                    : `No. ${sz} (${qty})`;

                btn.textContent = label;
                btn.onclick = function() {
                    selectedSizeFilter = (selectedSizeFilter === sz) ? null : sz;
                    renderSizeChips();
                    renderStockCards();
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

            sizes.forEach(sz => {
                const qty = stockObj[sz] !== undefined ? stockObj[sz] : 0;

                let statusPillHtml = '';
                let btnActionHtml = '';
                let stockText = '';

                if (isKepala) {
                    // Eselon II selalu Ready Stock
                    statusPillHtml = `
                        <span class="status-pill pill-avail-ready">
                            <span class="status-dot-sm"></span>
                            <span class="pill-text-full">Ready Stock</span>
                            <span class="pill-text-short">Ready</span>
                        </span>
                    `;
                    stockText = 'Ready Stock di Sekretariat';
                    btnActionHtml = `
                        <button type="button" class="btn-order-action ready-wa" onclick="openOrderModal('${currentCategory}', '${currentGender}', ${sz}, ${qty > 0 ? qty : 1})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                            <span class="btn-text-full">Pesan Ukuran ${sz} via WA</span>
                            <span class="btn-text-short">Pesan WA</span>
                        </button>
                    `;
                } else if (qty > 5) {
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
                        <div class="body-row">
                            <span class="body-label">Kelengkapan</span>
                            <span class="body-include-pin">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                <span class="pin-text-full">Include Pin Kab. Banjar</span>
                                <span class="pin-text-short">Include Pin</span>
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
            if (category === 'Kepala SKPD' || stock > 0) {
                statusElem.textContent = 'Ready Stock (Tersedia)';
                statusElem.style.color = '#15803D';
            } else {
                statusElem.textContent = 'Pemesanan (Stok Fisik Habis)';
                statusElem.style.color = '#B91C1C';
            }

            document.getElementById('waOrderModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeOrderModal(event) {
            if (!event || event.target.id === 'waOrderModal' || event.target.classList.contains('btn-modal-close') || event.target.tagName === 'BUTTON') {
                document.getElementById('waOrderModal').classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        function submitWhatsAppOrder() {
            const nama = document.getElementById('orderNama').value.trim();
            const skpd = document.getElementById('orderSkpd').value.trim();

            const isKepala = (selectedOrderData.category === 'Kepala SKPD');
            const catText = isKepala ? 'Mutz Kepala SKPD (Eselon II)' : 'Mutz Standar ASN (Biasa)';
            const statusPesanan = (isKepala || selectedOrderData.stock > 0) ? 'Pemesanan Ready Stock' : 'Pemesanan Mutz ASN';

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
            window.open(url, '_blank');
            closeOrderModal();
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

        // Scroll to Top Listener
        let scrollTicking = false;
        window.addEventListener('scroll', function() {
            if (!scrollTicking) {
                window.requestAnimationFrame(function() {
                    const btn = document.getElementById('btnScrollTop');
                    if (btn) {
                        if (window.scrollY > 300) {
                            btn.classList.add('visible');
                        } else {
                            btn.classList.remove('visible');
                        }
                    }
                    scrollTicking = false;
                });
                scrollTicking = true;
            }
        }, { passive: true });

        // Init on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            selectCategory('Biasa');
            updateCounters();
            renderSizeChips();
        });
    </script>
</body>
</html>
