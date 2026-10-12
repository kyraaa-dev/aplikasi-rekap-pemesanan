<?php
require_once 'config.php';

// Fetch current stock from database
$q_stok = $conn->query("
    SELECT jenis_mutz, jenis_kelamin, ukuran, jumlah_stok 
    FROM stok_mutz 
    ORDER BY jenis_mutz ASC, jenis_kelamin ASC, ukuran ASC
");

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

// Default standard sizes if empty in DB
$sizes_pria = [55, 56, 57, 58, 59, 60];
$sizes_wanita = [58, 59, 60];

// Default admin WhatsApp
$admin_wa_default = '6281258902720';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Katalog & Stok Mutz ASN KORPRI | Dewan Pengurus KORPRI Kab. Banjar</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    
    <style>
        /* ==========================================================================
           MODERN EXECUTIVE GLASSMORPHISM DESIGN SYSTEM (NO BRUTALISM)
           Dewan Pengurus KORPRI Kabupaten Banjar
           ========================================================================== */
        :root {
            --font-main: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            
            /* Modern Palette */
            --primary: #1D4ED8;
            --primary-hover: #1E40AF;
            --primary-light: #EFF6FF;
            --primary-gradient: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
            
            --korpri-gold: #D97706;
            --korpri-gold-light: #FEF3C7;
            --korpri-gold-gradient: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            
            --success: #059669;
            --success-light: #D1FAE5;
            --success-gradient: linear-gradient(135deg, #10B981 0%, #059669 100%);
            
            --warning: #D97706;
            --warning-light: #FEF3C7;
            
            --danger: #DC2626;
            --danger-light: #FEE2E2;
            
            /* Glassmorphism High-Performance Light Tokens */
            --bg-body: #F8FAFC;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --text-subtle: #94A3B8;
            
            --glass-card: rgba(255, 255, 255, 0.94);
            --glass-card-hover: #FFFFFF;
            --glass-nav: rgba(255, 255, 255, 0.92);
            --glass-border: rgba(255, 255, 255, 0.85);
            --glass-border-subtle: rgba(226, 232, 240, 0.85);
            
            --shadow-sm: 0 2px 8px -2px rgba(15, 23, 42, 0.05);
            --shadow-md: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            --shadow-lg: 0 20px 35px -10px rgba(15, 23, 42, 0.12), 0 10px 15px -5px rgba(15, 23, 42, 0.04);
            --shadow-glow: 0 0 25px -5px rgba(37, 99, 235, 0.25);
            
            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --radius-xl: 28px;
            --radius-full: 9999px;
            
            --transition-smooth: all 0.2s ease;
        }

        /* Dark Mode Tokens */
        [data-theme="dark"] {
            --bg-body: #090D16;
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --text-subtle: #64748B;
            
            --glass-card: rgba(17, 24, 39, 0.92);
            --glass-card-hover: rgba(24, 32, 47, 0.96);
            --glass-nav: rgba(15, 23, 42, 0.92);
            --glass-border: rgba(255, 255, 255, 0.12);
            --glass-border-subtle: rgba(255, 255, 255, 0.08);
            
            --shadow-sm: 0 2px 8px -2px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 12px 30px -5px rgba(0, 0, 0, 0.45);
            --shadow-lg: 0 24px 45px -10px rgba(0, 0, 0, 0.6);
            --shadow-glow: 0 0 30px -5px rgba(59, 130, 246, 0.35);
        }

        /* Base Resets & Ultra-Smooth Scrolling */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }

        body.public-catalog-page {
            font-family: var(--font-main);
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            position: relative;
        }

        /* ==========================================================================
           AUTHENTIC EXECUTIVE GOVERNMENT BACKGROUND (Clean, Dignified, No AI-Slop)
           GPU Hardware Accelerated (Zero Scroll Jitter)
           ========================================================================== */
        .public-bg-fixed {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            background: linear-gradient(180deg, #F8FAFC 0%, #F1F5F9 45%, #E2E8F0 100%);
            z-index: -4;
            pointer-events: none;
        }

        .public-bg-heritage {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            background-image: url('assets/images/bg-bupati.jpg');
            background-size: cover;
            background-position: center 30%;
            background-repeat: no-repeat;
            opacity: 0.12;
            filter: grayscale(85%) contrast(110%);
            z-index: -3;
            transform: translateZ(0);
            pointer-events: none;
        }

        .public-bg-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            z-index: -2;
            pointer-events: none;
            /* High-performance pure GPU alpha gradient */
            background: radial-gradient(circle at 50% 0%, rgba(37, 99, 235, 0.08) 0%, transparent 65%),
                        radial-gradient(circle at 15% 45%, rgba(217, 119, 6, 0.05) 0%, transparent 55%);
            transition: background 0.3s ease;
        }

        [data-theme="dark"] .public-bg-fixed {
            background: linear-gradient(180deg, #090E17 0%, #0F172A 50%, #060911 100%);
        }
        [data-theme="dark"] .public-bg-heritage {
            opacity: 0.08;
            filter: grayscale(100%) brightness(75%);
        }
        [data-theme="dark"] .public-bg-overlay {
            background: radial-gradient(circle at 50% 0%, rgba(59, 130, 246, 0.12) 0%, transparent 65%),
                        radial-gradient(circle at 85% 45%, rgba(245, 158, 11, 0.06) 0%, transparent 55%);
        }

        /* Official Authentic KORPRI Seal Watermark (GPU-Accelerated) */
        .public-bg-watermark {
            position: fixed;
            top: 48%;
            left: 50%;
            transform: translate(-50%, -50%) translateZ(0);
            width: min(65vw, 460px);
            height: min(65vw, 460px);
            background-image: url('assets/images/korpri-badge.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.035;
            z-index: -2;
            pointer-events: none;
        }
        [data-theme="dark"] .public-bg-watermark {
            opacity: 0.045;
        }

        /* High-Performance Frosted Glass Surface Utility */
        .glass-panel {
            background: var(--glass-card);
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-md);
            border-radius: var(--radius-lg);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            transform: translateZ(0);
        }

        /* ==========================================================================
           MODERN FROSTED NAVBAR
           ========================================================================== */
        .public-navbar {
            position: sticky;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 100;
            background: var(--glass-nav);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--glass-border-subtle);
            box-shadow: var(--shadow-sm);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            transition: transform 0.2s ease;
            transform: translateZ(0);
        }

        .brand-public {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
            min-width: 0;
        }

        .brand-logo-wrap {
            position: relative;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #FFFFFF;
            padding: 2px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        .brand-public:hover .brand-logo-wrap {
            transform: scale(1.05);
        }
        .brand-logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand-title {
            font-size: 1.12rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--text-main);
            line-height: 1.15;
            margin: 0;
        }
        .brand-title span {
            color: var(--primary);
        }
        .brand-sub {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 1px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        /* Modern Pill Buttons */
        .nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 8px 14px;
            border-radius: var(--radius-full);
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: var(--transition-smooth);
            font-family: var(--font-main);
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .nav-btn-icon {
            width: 38px;
            height: 38px;
            padding: 0;
            border-radius: var(--radius-full);
            background: var(--glass-card);
            color: var(--text-main);
            border: 1px solid var(--glass-border-subtle);
            box-shadow: var(--shadow-sm);
        }
        .nav-btn-icon:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: var(--primary);
        }

        .nav-btn-share {
            background: rgba(37, 99, 235, 0.08);
            color: var(--primary);
            border-color: rgba(37, 99, 235, 0.2);
        }
        .nav-btn-share:hover {
            background: var(--primary);
            color: #FFFFFF;
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
        }

        .nav-btn-admin {
            background: var(--primary-gradient);
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .nav-btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.35);
        }

        .nav-btn-short {
            display: none;
        }

        /* ==========================================================================
           MAIN CONTENT CONTAINER
           ========================================================================== */
        .catalog-container {
            width: 100%;
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 1.5rem;
            box-sizing: border-box;
        }

        /* ==========================================================================
           HERO SECTION WITH INSTITUTION BADGE & LIVE PULSE
           ========================================================================== */
        .hero-section {
            padding: 2.25rem 0 1.5rem 0;
            text-align: center;
        }

        .hero-card-banner {
            position: relative;
            padding: 2.5rem 2rem 2.25rem 2rem;
            margin-bottom: 2rem;
            border-radius: var(--radius-xl);
            overflow: hidden;
            text-align: center;
        }

        /* Top subtle glowing accent line */
        .hero-card-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 10%;
            right: 10%;
            height: 3px;
            background: linear-gradient(90deg, transparent, #3B82F6, #F59E0B, transparent);
            border-radius: 999px;
        }

        /* Institution Header Ribbon */
        .institution-ribbon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
            background: rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(37, 99, 235, 0.2);
            padding: 7px 20px;
            border-radius: var(--radius-full);
            margin-bottom: 1.25rem;
            text-align: center;
            transition: var(--transition-smooth);
        }
        [data-theme="dark"] .institution-ribbon {
            background: rgba(37, 99, 235, 0.15);
            border-color: rgba(59, 130, 246, 0.3);
        }
        .ribbon-text-main {
            font-size: 0.84rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--primary);
            line-height: 1.2;
        }
        [data-theme="dark"] .ribbon-text-main {
            color: #93C5FD;
        }
        .ribbon-text-sub {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.02em;
        }
        [data-theme="dark"] .ribbon-text-sub {
            color: rgba(255, 255, 255, 0.7);
        }

        /* Live Realtime Indicator Pill */
        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--success-light);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 6px 16px;
            border-radius: var(--radius-full);
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            letter-spacing: 0.01em;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.15);
        }
        [data-theme="dark"] .live-badge {
            background: rgba(6, 78, 59, 0.7);
            color: #A7F3D0;
            border-color: rgba(52, 211, 153, 0.3);
        }
        .live-pulse {
            width: 8px;
            height: 8px;
            background: #10B981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-ring 2s infinite;
            flex-shrink: 0;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 7px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .hero-title {
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 900;
            line-height: 1.18;
            letter-spacing: -0.03em;
            margin: 0 auto 1rem auto;
            max-width: 860px;
            color: var(--text-main);
        }
        .hero-title .gradient-text {
            background: linear-gradient(135deg, #1E40AF 0%, #2563EB 50%, #0284C7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        [data-theme="dark"] .hero-title .gradient-text {
            background: linear-gradient(135deg, #60A5FA 0%, #93C5FD 50%, #38BDF8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-desc {
            font-size: 1.05rem;
            color: var(--text-muted);
            max-width: 720px;
            margin: 0 auto;
            line-height: 1.6;
            font-weight: 500;
        }
        .hero-desc strong {
            color: var(--text-main);
            font-weight: 700;
        }

        /* ==========================================================================
           3 STAT HIGHLIGHT CARDS (EXECUTIVE GLASS)
           ========================================================================== */
        .stat-banner-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 2rem;
        }
        @media (max-width: 840px) {
            .stat-banner-row {
                grid-template-columns: 1fr 1fr;
            }
            .stat-banner-box:first-child {
                grid-column: 1 / -1;
            }
        }

        .stat-banner-box {
            padding: 1.25rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 16px;
            text-align: left;
            position: relative;
            overflow: hidden;
        }
        .stat-banner-box:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .stat-banner-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.25s ease;
        }
        .stat-banner-icon svg {
            width: 24px;
            height: 24px;
            stroke: currentColor;
            flex-shrink: 0;
        }
        .stat-banner-box:hover .stat-banner-icon {
            transform: scale(1.08);
        }

        .stat-banner-icon.emerald {
            background: var(--success-gradient);
            color: #FFFFFF;
        }
        .stat-banner-icon.blue {
            background: var(--primary-gradient);
            color: #FFFFFF;
        }
        .stat-banner-icon.gold {
            background: var(--korpri-gold-gradient);
            color: #FFFFFF;
        }

        .stat-val {
            font-size: 1.45rem;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.02em;
            color: var(--text-main);
        }
        .stat-lbl {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* ==========================================================================
           CATEGORY SWITCHER & FILTER CONTROLS (iOS STYLE SEGMENTED)
           ========================================================================== */
        .category-switcher-wrap {
            margin-bottom: 2rem;
            position: sticky;
            top: 62px;
            z-index: 85;
            padding: 12px 1.5rem 14px 1.5rem;
            margin-left: -1.5rem;
            margin-right: -1.5rem;
            background: rgba(248, 250, 252, 0.88);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            transition: var(--transition-smooth);
        }
        [data-theme="dark"] .category-switcher-wrap {
            background: rgba(9, 13, 22, 0.88);
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }

        .category-tabs-container {
            max-width: 1100px;
            margin: 0 auto;
            background: var(--glass-card);
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-md);
            border-radius: var(--radius-lg);
            padding: 8px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .tab-btn {
            background: transparent;
            border: none;
            border-radius: var(--radius-md);
            padding: 14px 18px;
            cursor: pointer;
            text-align: left;
            transition: var(--transition-smooth);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: var(--text-muted);
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            font-family: var(--font-main);
            position: relative;
        }
        .tab-btn:hover:not(.active) {
            background: rgba(0, 0, 0, 0.03);
            color: var(--text-main);
        }
        [data-theme="dark"] .tab-btn:hover:not(.active) {
            background: rgba(255, 255, 255, 0.05);
        }

        /* Active State - Standar ASN */
        .tab-btn.active.tab-biasa {
            background: #FFFFFF;
            color: var(--primary);
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.15), 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(37, 99, 235, 0.2);
        }
        [data-theme="dark"] .tab-btn.active.tab-biasa {
            background: rgba(30, 41, 59, 0.9);
            color: #93C5FD;
            border-color: rgba(59, 130, 246, 0.3);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
        }

        /* Active State - Kepala SKPD */
        .tab-btn.active.tab-kepala {
            background: #FFFFFF;
            color: #B45309;
            box-shadow: 0 4px 16px rgba(245, 158, 11, 0.2), 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        [data-theme="dark"] .tab-btn.active.tab-kepala {
            background: rgba(45, 33, 10, 0.85);
            color: #FCD34D;
            border-color: rgba(245, 158, 11, 0.4);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
        }

        .tab-title {
            font-size: 1.05rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -0.01em;
            margin-bottom: 2px;
            color: inherit;
        }
        .tab-title-full, .tab-title-short {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .tab-icon-svg {
            flex-shrink: 0;
            stroke: currentColor;
        }
        .tab-title-short {
            display: none;
        }
        .tab-sub {
            font-size: 0.8rem;
            font-weight: 600;
            opacity: 0.8;
            color: var(--text-muted);
        }
        .tab-btn.active .tab-sub {
            color: inherit;
            opacity: 0.9;
        }

        .tab-price-tag {
            background: rgba(15, 23, 42, 0.06);
            color: var(--text-main);
            padding: 6px 14px;
            border-radius: var(--radius-full);
            font-weight: 800;
            font-size: 0.88rem;
            white-space: nowrap;
            letter-spacing: -0.01em;
            transition: var(--transition-smooth);
        }
        .tab-btn.active.tab-biasa .tab-price-tag {
            background: var(--primary-gradient);
            color: #FFFFFF;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
        }
        .tab-btn.active.tab-kepala .tab-price-tag {
            background: var(--korpri-gold-gradient);
            color: #FFFFFF;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }
        [data-theme="dark"] .tab-price-tag {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-main);
        }

        /* Gender Filter Bar */
        .gender-filter-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 1rem;
            margin-bottom: 0.85rem;
            flex-wrap: wrap;
        }

        .gender-pill {
            background: var(--glass-card);
            color: var(--text-main);
            border: 1px solid var(--glass-border-subtle);
            box-shadow: var(--shadow-sm);
            padding: 10px 22px;
            border-radius: var(--radius-full);
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            font-family: var(--font-main);
        }
        .gender-pill:hover:not(.active) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: var(--primary);
        }
        .gender-pill.active {
            background: var(--primary-gradient);
            color: #FFFFFF;
            border-color: transparent;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.3);
            transform: translateY(-1px);
        }
        .gender-pill.active-gold {
            background: var(--korpri-gold-gradient);
            color: #FFFFFF;
            border-color: transparent;
            box-shadow: 0 4px 16px rgba(245, 158, 11, 0.35);
            transform: translateY(-1px);
        }

        .pill-badge {
            background: rgba(0, 0, 0, 0.08);
            color: inherit;
            padding: 2px 8px;
            border-radius: var(--radius-full);
            font-size: 0.78rem;
            font-weight: 700;
        }
        .gender-pill.active .pill-badge,
        .gender-pill.active-gold .pill-badge {
            background: rgba(255, 255, 255, 0.25);
            color: #FFFFFF;
        }
        .pill-label-full, .pill-label-short {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .pill-icon-svg {
            flex-shrink: 0;
            stroke: currentColor;
        }
        .pill-label-short {
            display: none;
        }

        /* Interactive Quick Size Chips */
        .quick-size-track {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            overflow-x: auto;
            padding: 6px 4px;
            margin-bottom: 0.5rem;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        @media (min-width: 641px) {
            .quick-size-track {
                justify-content: center;
            }
        }
        .quick-size-track::-webkit-scrollbar {
            display: none;
        }

        .size-chip-btn {
            background: var(--glass-card);
            color: var(--text-main);
            border: 1px solid var(--glass-border-subtle);
            box-shadow: var(--shadow-sm);
            border-radius: var(--radius-full);
            padding: 7px 16px;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
            user-select: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            -webkit-tap-highlight-color: transparent;
            font-family: var(--font-main);
        }
        .size-chip-btn:hover:not(.active) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }
        .size-chip-btn.active {
            background: var(--text-main);
            color: var(--bg-body);
            border-color: var(--text-main);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }
        [data-theme="dark"] .size-chip-btn.active {
            background: #FFFFFF;
            color: #0F172A;
            border-color: #FFFFFF;
            box-shadow: 0 4px 14px rgba(255, 255, 255, 0.2);
        }

        /* ==========================================================================
           ESELON II SPECIAL INFO BANNER
           ========================================================================== */
        .eselon-banner {
            background: linear-gradient(135deg, rgba(254, 243, 199, 0.95) 0%, rgba(253, 230, 138, 0.85) 100%);
            border: 1px solid rgba(245, 158, 11, 0.35);
            box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.15);
            border-radius: var(--radius-lg);
            padding: 18px 22px;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            gap: 18px;
            color: #78350F;
            text-align: left;
            transition: var(--transition-smooth);
        }
        [data-theme="dark"] .eselon-banner {
            background: linear-gradient(135deg, rgba(45, 33, 10, 0.95) 0%, rgba(61, 43, 0, 0.85) 100%);
            border-color: rgba(245, 158, 11, 0.4);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
            color: #FEF3C7;
        }

        .eselon-badge-icon {
            width: 50px;
            height: 50px;
            background: var(--korpri-gold-gradient);
            color: #FFFFFF;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(217, 119, 6, 0.3);
        }

        /* ==========================================================================
           MODERN STOCK CARDS GRID
           ========================================================================== */
        .stock-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            margin-bottom: 3.5rem;
        }
        @media (max-width: 960px) {
            .stock-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @keyframes cardPopIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .size-card {
            background: var(--glass-card);
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-md);
            border-radius: var(--radius-lg);
            padding: 24px 22px;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            animation: cardPopIn 0.25s ease-out backwards;
            contain: content;
        }
        .size-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(37, 99, 235, 0.35);
        }
        [data-theme="dark"] .size-card:hover {
            border-color: rgba(59, 130, 246, 0.4);
            box-shadow: 0 16px 35px -5px rgba(0, 0, 0, 0.5);
        }


        .card-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .size-number-badge {
            font-size: 2.1rem;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.03em;
            color: var(--text-main);
        }
        .size-sub-info {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 5px;
        }

        /* Modern Status Pill Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: var(--radius-full);
            font-size: 0.78rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            letter-spacing: 0.01em;
        }
        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }
        .status-dot.green { background: #10B981; box-shadow: 0 0 6px rgba(16, 185, 129, 0.6); }
        .status-dot.yellow { background: #F59E0B; box-shadow: 0 0 6px rgba(245, 158, 11, 0.6); }
        .status-dot.red { background: #EF4444; box-shadow: 0 0 6px rgba(239, 68, 68, 0.6); }
        .badge-ready {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        [data-theme="dark"] .badge-ready {
            background: rgba(6, 78, 59, 0.5);
            color: #6EE7B7;
            border-color: rgba(52, 211, 153, 0.3);
        }

        .badge-low {
            background: var(--warning-light);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        [data-theme="dark"] .badge-low {
            background: rgba(120, 53, 15, 0.5);
            color: #FCD34D;
            border-color: rgba(245, 158, 11, 0.3);
        }

        .badge-empty {
            background: var(--danger-light);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        [data-theme="dark"] .badge-empty {
            background: rgba(127, 29, 29, 0.5);
            color: #FCA5A5;
            border-color: rgba(248, 113, 113, 0.3);
        }

        /* Stock Quantity Meter */
        .stock-qty-wrap {
            margin: 12px 0 16px 0;
            padding: 16px;
            background: rgba(241, 245, 249, 0.6);
            border: 1px solid var(--glass-border-subtle);
            border-radius: var(--radius-md);
            text-align: center;
        }
        [data-theme="dark"] .stock-qty-wrap {
            background: rgba(15, 23, 42, 0.6);
        }

        .qty-number {
            font-size: 2.2rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 3px;
            letter-spacing: -0.02em;
        }
        .qty-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.01em;
        }

        .stock-progress-bar {
            height: 7px;
            background: rgba(203, 213, 225, 0.6);
            border-radius: var(--radius-full);
            overflow: hidden;
            margin-top: 10px;
        }
        [data-theme="dark"] .stock-progress-bar {
            background: rgba(51, 65, 85, 0.8);
        }
        .stock-progress-fill {
            height: 100%;
            border-radius: var(--radius-full);
            transition: width 0.4s ease;
        }

        /* Modern CTA Buttons */
        .btn-order-wa {
            margin-top: auto;
            width: 100%;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            border: none;
            font-weight: 800;
            font-size: 0.92rem;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition-smooth);
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            font-family: var(--font-main);
        }
        .btn-order-wa.ready {
            background: var(--success-gradient);
            color: #FFFFFF;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
        }
        .btn-order-wa.ready:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        .btn-order-wa.preorder {
            background: rgba(100, 116, 139, 0.1);
            color: var(--text-main);
            border: 1px solid var(--glass-border-subtle);
        }
        .btn-order-wa.preorder:hover {
            background: rgba(100, 116, 139, 0.18);
            transform: translateY(-2px);
        }
        [data-theme="dark"] .btn-order-wa.preorder {
            background: rgba(255, 255, 255, 0.08);
            color: #F8FAFC;
        }

        /* ==========================================================================
           SIZE GUIDE & COMPARISON SECTION
           ========================================================================== */
        .guide-box {
            padding: 30px;
            margin-bottom: 3.5rem;
        }

        .guide-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1.25rem;
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid var(--glass-border-subtle);
        }
        .guide-table th, .guide-table td {
            padding: 12px 14px;
            text-align: center;
            font-size: 0.88rem;
            border-bottom: 1px solid var(--glass-border-subtle);
        }
        .guide-table th {
            background: rgba(37, 99, 235, 0.08);
            color: var(--primary);
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.03em;
        }
        [data-theme="dark"] .guide-table th {
            background: rgba(37, 99, 235, 0.2);
            color: #93C5FD;
        }
        .guide-table td.subhead {
            background: rgba(241, 245, 249, 0.5);
            font-weight: 700;
            color: var(--text-main);
            text-align: left;
        }
        [data-theme="dark"] .guide-table td.subhead {
            background: rgba(30, 41, 59, 0.5);
        }
        .guide-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Comparison Grid */
        .comparison-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-top: 1.5rem;
        }
        .comparison-card {
            border-radius: var(--radius-md);
            padding: 22px;
            background: rgba(248, 250, 252, 0.7);
            border: 1px solid var(--glass-border-subtle);
            position: relative;
            overflow: hidden;
        }
        [data-theme="dark"] .comparison-card {
            background: rgba(15, 23, 42, 0.6);
        }
        .comparison-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        .comparison-card.card-standar::before {
            background: var(--primary-gradient);
        }
        .comparison-card.card-eselon::before {
            background: var(--korpri-gold-gradient);
        }

        .badge-include-pin {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.74rem;
            font-weight: 800;
            color: var(--primary);
            background: rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(37, 99, 235, 0.22);
            padding: 3px 9px;
            border-radius: var(--radius-full);
            letter-spacing: -0.01em;
        }
        .badge-include-pin.gold {
            color: #B45309;
            background: rgba(245, 158, 11, 0.12);
            border-color: rgba(217, 119, 6, 0.28);
        }
        [data-theme="dark"] .badge-include-pin {
            color: #93C5FD;
            background: rgba(37, 99, 235, 0.2);
            border-color: rgba(59, 130, 246, 0.35);
        }
        [data-theme="dark"] .badge-include-pin.gold {
            color: #FDE68A;
            background: rgba(245, 158, 11, 0.18);
            border-color: rgba(245, 158, 11, 0.35);
        }

        /* ==========================================================================
           MODAL DIALOG & RESPONSIVE BOTTOM-SHEET
           ========================================================================== */
        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.65);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            box-sizing: border-box;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            opacity: 0;
            transition: opacity 0.25s ease;
        }
        .modal-backdrop.active {
            display: flex;
            opacity: 1;
        }
        .modal-drag-handle {
            display: none;
        }
        .modal-card {
            background: var(--glass-card);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-lg);
            border-radius: var(--radius-lg);
            max-width: 480px;
            width: 100%;
            padding: 26px;
            position: relative;
            box-sizing: border-box;
            color: var(--text-main);
            transform: scale(0.95);
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .modal-backdrop.active .modal-card {
            transform: scale(1);
        }

        .modal-card input {
            width: 100%;
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--glass-border-subtle);
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-main);
            font-family: var(--font-main);
            font-size: 0.95rem;
            font-weight: 600;
            transition: var(--transition-smooth);
            outline: none;
        }
        .modal-card input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        [data-theme="dark"] .modal-card input {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(255, 255, 255, 0.12);
        }

        /* ==========================================================================
           FLOATING ACTIONS GROUP (WHATSAPP + SCROLL TO TOP)
           ========================================================================== */
        .floating-actions-wrap {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
            z-index: 95;
            pointer-events: none;
        }
        .floating-actions-wrap > * {
            pointer-events: auto;
        }

        .floating-wa-btn {
            background: var(--success-gradient);
            color: #FFFFFF;
            border: none;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            border-radius: var(--radius-full);
            padding: 12px 22px;
            font-weight: 800;
            font-size: 0.94rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition-smooth);
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            font-family: var(--font-main);
        }
        .floating-wa-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.5);
        }

        .btn-scroll-top {
            display: none !important;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--glass-card) !important;
            color: var(--text-main) !important;
            border: 1px solid var(--glass-border-subtle) !important;
            box-shadow: var(--shadow-md) !important;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: var(--transition-smooth);
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        .btn-scroll-top.visible {
            display: inline-flex !important;
        }
        .btn-scroll-top:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg) !important;
            color: var(--primary) !important;
        }

        /* ==========================================================================
           MODERN EXECUTIVE KORPRI FOOTER
           ========================================================================== */
        .public-footer {
            position: relative;
            background: var(--glass-nav);
            border-top: 1px solid var(--glass-border-subtle);
            padding: 2.25rem 1.5rem;
            width: 100%;
            box-sizing: border-box;
            margin-top: 3.5rem;
            color: var(--text-main);
            overflow: hidden;
        }

        /* Official KORPRI Royal Gold & Navy Accent Line */
        .public-footer::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #1E40AF 0%, #D97706 35%, #F59E0B 50%, #D97706 65%, #2563EB 100%);
            opacity: 0.95;
        }

        .footer-desktop-wrap {
            display: block;
        }

        .footer-mobile-wrap {
            display: none;
        }

        .footer-inner {
            max-width: 1140px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .footer-brand-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .footer-logo-mini {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #FFFFFF;
            padding: 3px;
            border: 1.5px solid rgba(217, 119, 6, 0.4);
            box-shadow: 0 2px 10px rgba(30, 58, 138, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .footer-logo-mini img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .footer-brand-title {
            font-size: 0.98rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.2;
            letter-spacing: -0.01em;
        }
        .footer-brand-sub {
            font-size: 0.74rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 1px;
        }
        .footer-address {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.76rem;
            color: var(--text-muted);
            margin-top: 5px;
            font-weight: 500;
        }
        .footer-address svg {
            color: var(--korpri-gold);
            flex-shrink: 0;
        }

        .footer-links-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .footer-link-pill {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: var(--radius-full);
            background: rgba(15, 23, 42, 0.03);
            border: 1px solid var(--glass-border-subtle);
            transition: var(--transition-smooth);
        }
        .footer-link-pill:hover {
            color: var(--primary);
            background: rgba(37, 99, 235, 0.08);
            border-color: rgba(37, 99, 235, 0.25);
            transform: translateY(-1px);
        }
        [data-theme="dark"] .footer-link-pill {
            background: rgba(255, 255, 255, 0.05);
        }
        [data-theme="dark"] .footer-link-pill:hover {
            color: #93C5FD;
            background: rgba(37, 99, 235, 0.15);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .footer-bottom-bar {
            max-width: 1140px;
            margin: 1.5rem auto 0 auto;
            padding-top: 1.25rem;
            border-top: 1px solid var(--glass-border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 0.76rem;
            color: var(--text-subtle);
            font-weight: 500;
        }

        .footer-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.74rem;
            font-weight: 700;
            color: var(--success);
            background: var(--success-light);
            padding: 3px 10px;
            border-radius: var(--radius-full);
        }
        [data-theme="dark"] .footer-status-pill {
            background: rgba(6, 78, 59, 0.5);
            color: #6EE7B7;
        }
        .footer-status-dot {
            width: 6px;
            height: 6px;
            background: #10B981;
            border-radius: 50%;
        }

        /* ==========================================================================
           COMPREHENSIVE MOBILE & TABLET RESPONSIVENESS (Max Width: 640px)
           ========================================================================== */
        @media (max-width: 640px) {
            .public-navbar {
                padding: 0.65rem 1rem;
                gap: 0.5rem;
            }
            .brand-logo-wrap {
                width: 36px;
                height: 36px;
                border-radius: 10px;
            }
            .brand-title {
                font-size: 0.98rem;
            }
            .brand-sub {
                display: none;
            }
            .nav-actions {
                gap: 6px;
            }
            .nav-btn {
                padding: 7px 11px;
                font-size: 0.78rem;
            }
            .nav-btn-text {
                display: none;
            }
            .nav-btn-short {
                display: inline;
            }

            .catalog-container {
                padding: 0 0.85rem;
            }

            .hero-section {
                padding: 1.25rem 0 0.75rem 0;
            }
            .hero-card-banner {
                padding: 1.25rem 1rem;
                border-radius: 20px;
                margin-bottom: 1.25rem;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            }
            .institution-ribbon {
                padding: 5px 12px;
                margin-bottom: 0.85rem;
                gap: 5px;
            }
            .ribbon-text-main {
                font-size: 0.72rem;
            }
            .ribbon-text-sub {
                font-size: 0.68rem;
                display: inline;
            }
            .hero-title {
                font-size: 1.4rem;
                margin-bottom: 0.5rem;
            }
            .hero-desc {
                font-size: 0.84rem;
                line-height: 1.45;
            }

            /* Stat cards on mobile - Sleek & Minimalist */
            .stat-banner-row {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                margin-bottom: 1.25rem;
            }
            .stat-banner-box:first-child {
                grid-column: 1 / -1;
            }
            .stat-banner-box {
                padding: 10px 12px;
                gap: 10px;
                border-radius: 14px;
            }
            .stat-banner-icon {
                width: 38px;
                height: 38px;
                font-size: 1.15rem;
                border-radius: 10px;
            }
            .stat-banner-icon svg {
                width: 19px;
                height: 19px;
            }
            .stat-val {
                font-size: 1.15rem;
                font-weight: 800;
            }
            .stat-lbl {
                font-size: 0.71rem;
            }

            /* Sticky Tab Controls on Mobile - Ultra Minimalist */
            .category-switcher-wrap {
                top: 48px;
                padding: 6px 0.85rem 8px 0.85rem;
                margin-left: -0.85rem;
                margin-right: -0.85rem;
            }
            .category-tabs-container {
                gap: 6px;
                padding: 4px;
                border-radius: 16px;
            }
            .tab-btn {
                padding: 8px 10px;
                flex-direction: column;
                align-items: flex-start;
                gap: 3px;
                border-radius: 12px;
            }
            .tab-title {
                font-size: 0.84rem;
            }
            .tab-title-full {
                display: none !important;
            }
            .tab-title-short {
                display: inline-flex !important;
                align-items: center;
                gap: 5px;
            }
            .tab-title-short .tab-icon-svg {
                width: 13px;
                height: 13px;
            }
            .tab-sub {
                display: none;
            }
            .tab-price-tag {
                padding: 2px 7px;
                font-size: 0.71rem;
                align-self: flex-start;
                border-radius: var(--radius-full);
            }

            /* Gender Pills on Mobile - Clean Capsule */
            .gender-filter-bar {
                margin: 8px 0 10px 0;
                gap: 8px;
            }
            .gender-pill {
                padding: 7px 15px;
                font-size: 0.82rem;
                border-radius: var(--radius-full);
            }
            .pill-label-full {
                display: none !important;
            }
            .pill-label-short {
                display: inline-flex !important;
                align-items: center;
                gap: 5px;
            }
            .pill-label-short .pill-icon-svg {
                width: 13px;
                height: 13px;
            }

            /* Stock Cards on Mobile - Modern Clean Card */
            .stock-grid {
                grid-template-columns: 1fr;
                gap: 11px;
                margin-bottom: 2rem;
            }
            .size-card {
                padding: 14px 14px 15px 14px;
                border-radius: 18px;
            }
            .size-number-badge {
                font-size: 1.7rem;
            }
            .btn-order-wa {
                padding: 11px 14px;
                font-size: 0.88rem;
                border-radius: 12px;
            }

            /* Guide & Comparison Grid on Mobile */
            .guide-box {
                padding: 16px 14px;
                border-radius: 18px;
                margin-bottom: 2.25rem;
            }
            .comparison-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .comparison-card {
                padding: 16px 14px;
                border-radius: 18px;
            }

            /* Mobile Bottom Sheet Modal */
            .modal-backdrop {
                align-items: flex-end;
                padding: 0;
            }
            .modal-drag-handle {
                display: block;
                width: 44px;
                height: 5px;
                background: var(--text-subtle);
                opacity: 0.5;
                border-radius: var(--radius-full);
                margin: 0 auto 16px auto;
            }
            .modal-card {
                max-width: 100%;
                border-radius: 24px 24px 0 0;
                border-bottom: none;
                padding: 16px 20px calc(24px + env(safe-area-inset-bottom, 0px)) 20px;
                animation: slideUpSheet 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            }
            @keyframes slideUpSheet {
                from { transform: translateY(100%); }
                to { transform: translateY(0); }
            }

            /* Floating Actions on Mobile */
            .floating-actions-wrap {
                bottom: calc(16px + env(safe-area-inset-bottom, 0px));
                right: 14px;
                gap: 8px;
            }
            .floating-wa-btn {
                padding: 10px 16px;
                font-size: 0.85rem;
            }
            .btn-scroll-top {
                width: 40px;
                height: 40px;
            }

            /* Executive KORPRI Mobile Footer Experience */
            .footer-desktop-wrap {
                display: none !important;
            }
            .footer-mobile-wrap {
                display: flex !important;
                flex-direction: column;
                align-items: center;
                width: 100%;
                max-width: 460px;
                margin: 0 auto;
                text-align: center;
                gap: 1.15rem;
            }

            .public-footer {
                padding: 2.25rem 1.15rem 7rem 1.15rem;
                margin-top: 2.5rem;
            }

            .footer-mobile-header {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 7px;
            }

            .footer-mobile-emblem {
                width: 52px;
                height: 52px;
                border-radius: 50%;
                background: #FFFFFF;
                padding: 4px;
                border: 2px solid rgba(217, 119, 6, 0.45);
                box-shadow: 0 4px 16px rgba(30, 58, 138, 0.15);
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .footer-mobile-emblem img {
                width: 100%;
                height: 100%;
                object-fit: contain;
            }

            .footer-mobile-badge {
                display: inline-block;
                font-size: 0.68rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #B45309;
                background: rgba(245, 158, 11, 0.12);
                border: 1px solid rgba(217, 119, 6, 0.25);
                padding: 3px 11px;
                border-radius: var(--radius-full);
            }
            [data-theme="dark"] .footer-mobile-badge {
                color: #FBBF24;
                background: rgba(245, 158, 11, 0.18);
            }

            .footer-mobile-title {
                font-size: 1.05rem;
                font-weight: 900;
                color: var(--text-main);
                line-height: 1.25;
                letter-spacing: -0.01em;
                margin: 0;
            }

            .footer-mobile-desc {
                font-size: 0.77rem;
                color: var(--text-muted);
                line-height: 1.45;
                margin: 0;
                max-width: 320px;
            }

            .footer-location-chip {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 0.75rem;
                font-weight: 700;
                color: var(--korpri-gold);
                background: rgba(245, 158, 11, 0.1);
                border: 1px solid rgba(217, 119, 6, 0.25);
                padding: 4px 12px;
                border-radius: var(--radius-full);
                margin-top: 6px;
            }
            [data-theme="dark"] .footer-location-chip {
                color: #FCD34D;
                background: rgba(245, 158, 11, 0.18);
                border-color: rgba(245, 158, 11, 0.35);
            }

            /* 3 Touch Cards on Mobile */
            .footer-mobile-nav-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 7px;
                width: 100%;
            }

            .footer-mobile-card {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 14px;
                background: var(--glass-card);
                border: 1px solid var(--glass-border);
                border-radius: 14px;
                text-decoration: none;
                transition: var(--transition-smooth);
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
                text-align: left;
            }
            .footer-mobile-card:active {
                transform: scale(0.98);
                background: rgba(37, 99, 235, 0.06);
            }
            .footer-card-icon {
                font-size: 1.25rem;
                line-height: 1;
                flex-shrink: 0;
                width: 34px;
                height: 34px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(37, 99, 235, 0.08);
                border-radius: 10px;
            }
            [data-theme="dark"] .footer-card-icon {
                background: rgba(255, 255, 255, 0.08);
            }
            .footer-card-body {
                flex: 1;
                min-width: 0;
            }
            .footer-card-title {
                font-size: 0.84rem;
                font-weight: 700;
                color: var(--text-main);
                line-height: 1.2;
            }
            .footer-card-sub {
                font-size: 0.71rem;
                color: var(--text-muted);
                margin-top: 1px;
            }
            .footer-card-arrow {
                color: var(--text-subtle);
                font-size: 0.85rem;
                font-weight: 700;
            }

            /* Sekretariat Address Chip */
            .footer-mobile-sekretariat {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 7px;
                font-size: 0.73rem;
                color: var(--text-muted);
                background: rgba(15, 23, 42, 0.03);
                border: 1px solid var(--glass-border-subtle);
                padding: 8px 12px;
                border-radius: 12px;
                line-height: 1.4;
                width: 100%;
                box-sizing: border-box;
            }
            [data-theme="dark"] .footer-mobile-sekretariat {
                background: rgba(255, 255, 255, 0.04);
            }

            .footer-mobile-bottom {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
                width: 100%;
                padding-top: 0.85rem;
                border-top: 1px solid var(--glass-border-subtle);
                font-size: 0.73rem;
                color: var(--text-subtle);
            }
        }

        @media (max-width: 400px) {
            .floating-wa-text {
                display: none;
            }
            .floating-wa-btn {
                padding: 11px;
                border-radius: 50%;
                width: 44px;
                height: 44px;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="public-catalog-page">
    <script>
        if(localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    </script>

    <!-- Kantor Bupati / Dewan Pengurus KORPRI Background -->
    <div class="public-bg-fixed" aria-hidden="true"></div>
    <div class="public-bg-heritage" aria-hidden="true"></div>
    <div class="public-bg-watermark" aria-hidden="true"></div>
    <div class="public-bg-overlay" aria-hidden="true"></div>

    <!-- Frosted Top Navbar -->
    <header class="public-navbar">
        <a href="cek_stok.php" class="brand-public">
            <div class="brand-logo-wrap">
                <img src="assets/images/logo.png?v=2" alt="Logo KORPRI" width="40" height="40">
            </div>
            <div>
                <h1 class="brand-title">E-MutZ <span>KORPRI</span></h1>
                <p class="brand-sub">
                    <span>Sekretariat KORPRI Kab. Banjar</span>
                </p>
            </div>
        </a>

        <div class="nav-actions">
            <!-- Theme Toggle -->
            <button id="themeTogglePublic" class="nav-btn nav-btn-icon" title="Ganti Tema">
                <svg id="themeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </button>

            <!-- Quick Share Link Button -->
            <button onclick="copyKatalogLink()" class="nav-btn nav-btn-share" title="Salin Tautan Katalog">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                <span class="nav-btn-text">Bagikan</span>
                <span class="nav-btn-short">Share</span>
            </button>

            <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
                <a href="index.php" class="nav-btn nav-btn-admin">
                    <span class="nav-btn-text">Dashboard &rarr;</span>
                    <span class="nav-btn-short">Admin</span>
                </a>
            <?php else: ?>
                <a href="login.php" class="nav-btn nav-btn-admin">
                    <span class="nav-btn-text">Login Petugas</span>
                    <span class="nav-btn-short">Login</span>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <main class="catalog-container">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-card-banner glass-panel">
                <!-- Institution Badge -->
                <div class="institution-ribbon">
                    <span class="ribbon-text-main">DEWAN PENGURUS KORPRI KABUPATEN BANJAR</span>
                    <span class="ribbon-text-sub">&bull; Jl. A. Yani No. 2 Martapura</span>
                </div>

                <br>

                <!-- Live Update Indicator -->
                <div class="live-badge">
                    <span class="live-pulse"></span>
                    <span>Update Stok Real-Time dari Sekretariat KORPRI</span>
                </div>

                <h2 class="hero-title">
                    Ketersediaan Stok <span class="gradient-text">Mutz ASN</span>
                </h2>
                <p class="hero-desc">
                    Informasi ukuran yang tersedia untuk <strong>Mutz Standar ASN</strong> dan <strong>Mutz Khusus Kepala SKPD / Pejabat Eselon II</strong>. Cek nomor lingkar kepala Anda sebelum melakukan pemesanan.
                </p>
            </div>

            <!-- Stat Highlights -->
            <div class="stat-banner-row">
                <div class="stat-banner-box glass-panel">
                    <div class="stat-banner-icon emerald">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    </div>
                    <div>
                        <div class="stat-val"><?= number_format($total_stok_tersedia, 0, ',', '.') ?> Pcs</div>
                        <div class="stat-lbl">Total Stok di Sekretariat KORPRI</div>
                    </div>
                </div>

                <div class="stat-banner-box glass-panel">
                    <div class="stat-banner-icon blue">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                    </div>
                    <div>
                        <div class="stat-val">Rp <?= number_format(HARGA_BIASA, 0, ',', '.') ?></div>
                        <div class="stat-lbl">Mutz Standar ASN</div>
                    </div>
                </div>

                <div class="stat-banner-box glass-panel">
                    <div class="stat-banner-icon gold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                    </div>
                    <div>
                        <div class="stat-val">Rp <?= number_format(HARGA_KEPALA, 0, ',', '.') ?></div>
                        <div class="stat-lbl">Mutz Kepala SKPD (Eselon II)</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Category Switcher (Mutz Biasa vs Kepala SKPD) & Mobile Sticky Filter Bar -->
        <div class="category-switcher-wrap" id="categorySwitcher">
            <div class="category-tabs-container">
                <button type="button" class="tab-btn tab-biasa active" id="tabBtnBiasa" onclick="switchCategory('Biasa')">
                    <div>
                        <div class="tab-title">
                            <span class="tab-title-full">
                                <svg class="tab-icon-svg" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                                Standar ASN
                            </span>
                            <span class="tab-title-short">
                                <svg class="tab-icon-svg" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                                Standar ASN
                            </span>
                        </div>
                        <div class="tab-sub">Diperuntukkan bagi seluruh ASN (PNS &amp; PPPK)</div>
                    </div>
                    <div class="tab-price-tag">Rp <?= number_format(HARGA_BIASA, 0, ',', '.') ?></div>
                </button>

                <button type="button" class="tab-btn tab-kepala" id="tabBtnKepala" onclick="switchCategory('Kepala SKPD')">
                    <div>
                        <div class="tab-title">
                            <span class="tab-title-full">
                                <svg class="tab-icon-svg" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                                Kepala SKPD (Eselon II)
                            </span>
                            <span class="tab-title-short">
                                <svg class="tab-icon-svg" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                                Kepala SKPD
                            </span>
                        </div>
                        <div class="tab-sub">Pejabat Pimpinan Tinggi Pratama</div>
                    </div>
                    <div class="tab-price-tag">Rp <?= number_format(HARGA_KEPALA, 0, ',', '.') ?></div>
                </button>
            </div>

            <!-- Gender Selector Pills -->
            <div class="gender-filter-bar">
                <button type="button" class="gender-pill active" id="pillPria" onclick="switchGender('Laki-laki')">
                    <span class="pill-label-full">
                        <svg class="pill-icon-svg" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        Pria (Laki-laki)
                    </span>
                    <span class="pill-label-short">
                        <svg class="pill-icon-svg" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        Pria
                    </span>
                    <span id="badgeCountPria" class="pill-badge">...</span>
                </button>

                <button type="button" class="gender-pill" id="pillWanita" onclick="switchGender('Perempuan')">
                    <span class="pill-label-full">
                        <svg class="pill-icon-svg" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a5 5 0 0 1 5 5c0 2.22-1.2 4.16-3 5.2V14a2 2 0 0 1-2 2h0a2 2 0 0 1-2-2v-1.8C8.2 11.16 7 9.22 7 7a5 5 0 0 1 5-5z"></path><path d="M6 21v-2a6 6 0 0 1 12 0v2"></path></svg>
                        Wanita (Perempuan)
                    </span>
                    <span class="pill-label-short">
                        <svg class="pill-icon-svg" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a5 5 0 0 1 5 5c0 2.22-1.2 4.16-3 5.2V14a2 2 0 0 1-2 2h0a2 2 0 0 1-2-2v-1.8C8.2 11.16 7 9.22 7 7a5 5 0 0 1 5-5z"></path><path d="M6 21v-2a6 6 0 0 1 12 0v2"></path></svg>
                        Wanita
                    </span>
                    <span id="badgeCountWanita" class="pill-badge">...</span>
                </button>
            </div>

            <!-- Dynamic Interactive Quick Size Filter Chips -->
            <div class="quick-size-track" id="quickSizeChips">
                <!-- Injected dynamically by JavaScript -->
            </div>
        </div>

        <!-- Eselon II Special Info Banner -->
        <div id="eselonNotice" class="eselon-banner" style="display: none;">
            <div class="eselon-badge-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                    <span style="font-size: 0.74rem; font-weight: 800; background: rgba(217, 119, 6, 0.15); color: #B45309; padding: 2px 8px; border-radius: var(--radius-full); text-transform: uppercase; letter-spacing: 0.03em;">Eksklusif Eselon II</span>
                    <span style="font-size: 0.75rem; font-weight: 700; color: #92400E;">Bordir List Benang Emas</span>
                </div>
                <h3 style="margin: 0 0 4px 0; font-size: 1.05rem; font-weight: 800; color: inherit; line-height: 1.3;">
                    Spesifikasi Mutz Kepala Dinas / Badan / Asisten
                </h3>
                <p style="margin: 0; font-size: 0.85rem; font-weight: 500; opacity: 0.9; line-height: 1.45;">
                    Dirancang khusus dengan bordir list benang emas tebal resmi KORPRI bagi Pejabat Pimpinan Tinggi Pratama. Tersedia ukuran Pria &amp; Wanita.
                </p>
            </div>
        </div>

        <!-- Cards Grid Container -->
        <div class="stock-grid" id="stockGridContainer">
            <!-- Rendered dynamically by JavaScript -->
        </div>

        <!-- Size Guide & How to Measure Section -->
        <section class="guide-box glass-panel" id="panduan">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 1.3rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M21.3 8.7 8.7 21.3c-1 1-2.5 1-3.4 0l-2.6-2.6c-1-1-1-2.5 0-3.4L15.3 2.7c1-1 2.5-1 3.4 0l2.6 2.6c1 1 1 2.5 0 3.4Z"/><path d="m7.5 10.5 2 2"/><path d="m10.5 7.5 2 2"/><path d="m13.5 4.5 2 2"/><path d="m4.5 13.5 2 2"/></svg>
                    Panduan Ukuran &amp; Cara Mengukur Lingkar Kepala
                </h3>
                <span style="font-size: 0.8rem; font-weight: 700; background: var(--warning-light); color: var(--warning); padding: 4px 12px; border-radius: var(--radius-full);">
                    Tips Akurasi
                </span>
            </div>

            <p style="font-size: 0.92rem; line-height: 1.6; color: var(--text-muted); font-weight: 500;">
                Nomor ukuran topi mutz sesuai dengan <strong>lingkar kepala dalam satuan sentimeter (cm)</strong>. Gunakan pita meteran jahit melingkari kepala kira-kira 1 cm di atas telinga dan tepat di atas alis.
            </p>

            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="guide-table">
                    <thead>
                        <tr>
                            <th>Nomor Mutz</th>
                            <th>Ukuran 55</th>
                            <th>Ukuran 56</th>
                            <th>Ukuran 57</th>
                            <th>Ukuran 58</th>
                            <th>Ukuran 59</th>
                            <th>Ukuran 60</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="subhead">Lingkar Kepala (cm)</td>
                            <td>54.5 - 55.4 cm</td>
                            <td>55.5 - 56.4 cm</td>
                            <td>56.5 - 57.4 cm</td>
                            <td>57.5 - 58.4 cm</td>
                            <td>58.5 - 59.4 cm</td>
                            <td>59.5 - 60.5 cm</td>
                        </tr>
                        <tr>
                            <td class="subhead">Kategori Rekomendasi</td>
                            <td>S (Kecil)</td>
                            <td>M (Sedang)</td>
                            <td>L (Standar Pria)</td>
                            <td>L - XL (Standar Wanita)</td>
                            <td>XL (Besar / Berhijab)</td>
                            <td>XXL (Ekstra / Berhijab)</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1.5rem; background: rgba(37, 99, 235, 0.08); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: var(--radius-md); padding: 14px 18px; color: var(--primary);">
                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <div style="color: var(--primary); flex-shrink: 0; margin-top: 2px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    </div>
                    <div style="font-size: 0.88rem; line-height: 1.5; font-weight: 600; color: var(--text-main);">
                        <strong style="color: var(--primary);">Catatan untuk ASN Wanita (Berhijab/Sanggul):</strong> Disarankan memilih 1 hingga 2 nomor lebih besar (biasanya No. 58 s/d 60) untuk memberikan ruang hijab agar topi tidak menekan kepala dan tetap nyaman serta tegak rapi saat dikenakan.
                    </div>
                </div>
            </div>

            <!-- Comparison Table: Biasa dan Kepala SKPD -->
            <div style="margin-top: 2.25rem;" id="spesifikasi">
                <h4 style="margin: 0 0 10px 0; font-size: 1.15rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--korpri-gold);"><circle cx="12" cy="12" r="10"></circle><path d="m16 10-4 4-2-2"></path></svg>
                    Perbedaan Mutz Biasa dan Kepala SKPD (Eselon II)
                </h4>
                
                <div class="comparison-grid">
                    <div class="comparison-card card-standar">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 6px;">
                            <div style="font-size: 0.8rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.03em;">Standar ASN</div>
                            <span class="badge-include-pin">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                Include Pin Kab. Banjar
                            </span>
                        </div>
                        <h5 style="margin: 4px 0 8px 0; font-size: 1.1rem; font-weight: 800; color: var(--text-main);">Mutz Reguler (Biasa)</h5>
                        <ul style="padding-left: 18px; margin: 0; font-size: 0.875rem; line-height: 1.6; font-weight: 500; color: var(--text-muted);">
                            <li>Diperuntukkan bagi seluruh ASN (PNS dan PPPK).</li>
                            <li><strong>Sudah termasuk (include) Pin Lambang Kabupaten Banjar</strong>.</li>
                            <li>Harga resmi: <strong style="color: var(--text-main);">Rp <?= number_format(HARGA_BIASA, 0, ',', '.') ?></strong>.</li>
                        </ul>
                    </div>

                    <div class="comparison-card card-eselon">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 6px;">
                            <div style="font-size: 0.8rem; font-weight: 800; color: var(--korpri-gold); text-transform: uppercase; letter-spacing: 0.03em;">Pejabat Eselon II</div>
                            <span class="badge-include-pin gold">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                Include Pin Kab. Banjar
                            </span>
                        </div>
                        <h5 style="margin: 4px 0 8px 0; font-size: 1.1rem; font-weight: 800; color: var(--text-main);">Mutz Kepala SKPD</h5>
                        <ul style="padding-left: 18px; margin: 0; font-size: 0.875rem; line-height: 1.6; font-weight: 500; color: var(--text-muted);">
                            <li>Khusus Kepala Dinas, Kepala Badan, Asisten Sekda, Staf Ahli &amp; Sekda.</li>
                            <li>Bahan eksklusif premium dengan ketahanan bentuk lebih kokoh.</li>
                            <li>Aksen <strong style="color: var(--text-main);">Bordir List Benang Emas Tebal</strong> di sekeliling mutz.</li>
                            <li><strong>Sudah termasuk (include) Pin Lambang Kabupaten Banjar</strong>.</li>
                            <li>Harga resmi: <strong style="color: var(--text-main);">Rp <?= number_format(HARGA_KEPALA, 0, ',', '.') ?></strong>.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal Form WhatsApp Pre-order / Pemesanan (Responsive Bottom-Sheet on Mobile) -->
    <div class="modal-backdrop" id="waOrderModal" onclick="closeOrderModal(event)">
        <div class="modal-card" onclick="event.stopPropagation()">
            <div class="modal-drag-handle"></div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--glass-border-subtle); padding-bottom: 10px;">
                <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main);" id="modalTitle">Konfirmasi Pemesanan</h3>
                <button type="button" onclick="closeOrderModal()" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; font-weight: 800; color: var(--text-muted); padding: 0 4px; line-height: 1;">&times;</button>
            </div>

            <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0; font-weight: 500;" id="modalSubtitle">
                Pesanan akan langsung diteruskan ke WhatsApp Pengurus/Admin Rekap KORPRI.
            </p>

            <div style="background: rgba(241, 245, 249, 0.7); border: 1px solid var(--glass-border-subtle); border-radius: var(--radius-md); padding: 14px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted);">Kategori:</span>
                    <strong style="font-size: 0.88rem; color: var(--text-main);" id="modalCat">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted);">Peruntukan:</span>
                    <strong style="font-size: 0.88rem; color: var(--text-main);" id="modalGender">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted);">Nomor Ukuran:</span>
                    <strong style="font-size: 1.05rem; color: var(--primary);" id="modalSize">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--glass-border-subtle); padding-top: 6px; margin-top: 6px;">
                    <span style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted);">Status Stok:</span>
                    <strong style="font-size: 0.88rem;" id="modalStockStatus">-</strong>
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 6px; color: var(--text-main);">Nama Pemesan (Opsional):</label>
                <input type="text" id="orderNama" placeholder="Contoh: Budi Santoso, S.IP">
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 6px; color: var(--text-main);">Asal Dinas / SKPD (Opsional):</label>
                <input type="text" id="orderSkpd" placeholder="Contoh: Dinas Pendidikan">
            </div>

            <button type="button" onclick="sendWhatsAppOrder()" class="btn-order-wa ready" style="padding: 13px; font-size: 0.95rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                Lanjutkan ke WhatsApp &rarr;
            </button>
        </div>
    </div>

    <!-- Floating Actions (WhatsApp + Scroll To Top) -->
    <div class="floating-actions-wrap">
        <button type="button" id="btnScrollTop" class="btn-scroll-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" title="Kembali ke atas">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 15l-6-6-6 6"/></svg>
        </button>

        <a href="#" onclick="openGeneralWhatsApp(); return false;" class="floating-wa-btn" title="Hubungi Pengurus / Admin via WhatsApp">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            <span class="floating-wa-text">Tanya Stok WA</span>
        </a>
    </div>

    <!-- Modern Executive KORPRI Footer -->
    <footer class="public-footer">
        <!-- Desktop Footer Layout -->
        <div class="footer-desktop-wrap">
            <div class="footer-inner">
                <div class="footer-brand-wrap">
                    <div class="footer-logo-mini">
                        <img src="assets/images/korpri-badge.png" alt="Logo KORPRI" width="30" height="30">
                    </div>
                    <div>
                        <div class="footer-brand-title">E-MutZ KORPRI</div>
                        <div class="footer-brand-sub">Dewan Pengurus KORPRI Kabupaten Banjar</div>
                        <div class="footer-address">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <span>Sekretariat: Kantor Bupati Banjar, Jl. A. Yani No. 2 Martapura</span>
                        </div>
                    </div>
                </div>

                <nav class="footer-links-group" aria-label="Navigasi Footer">
                    <a href="#panduan" class="footer-link-pill">Panduan Ukuran</a>
                    <a href="#spesifikasi" class="footer-link-pill">Spesifikasi Eselon II</a>
                    <a href="login.php" class="footer-link-pill">Portal Petugas</a>
                </nav>
            </div>

            <div class="footer-bottom-bar">
                <div>
                    &copy; <?= date('Y') ?> Dewan Pengurus KORPRI Kabupaten Banjar &bull; Jl. A. Yani No. 2 Martapura. Seluruh Hak Cipta Dilindungi.
                </div>
                <div class="footer-status-pill">
                    <span class="footer-status-dot"></span>
                    <span>Layanan Resmi Aktif</span>
                </div>
            </div>
        </div>

        <!-- Mobile-Tailored Executive Footer Layout -->
        <div class="footer-mobile-wrap">
            <div class="footer-mobile-header">
                <div class="footer-mobile-emblem">
                    <img src="assets/images/korpri-badge.png" alt="Lambang KORPRI" width="46" height="46">
                </div>
                <span class="footer-mobile-badge">Dewan Pengurus KORPRI</span>
                <h3 class="footer-mobile-title">Kabupaten Banjar</h3>
                <p class="footer-mobile-desc">Sistem Rekapitulasi Pemesanan &amp; Ketersediaan Mutz ASN</p>
                <div class="footer-location-chip">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span>Jl. A. Yani No. 2 Martapura</span>
                </div>
            </div>

            <nav class="footer-mobile-nav-grid" aria-label="Navigasi Cepat Mobile">
                <a href="#panduan" class="footer-mobile-card">
                    <div class="footer-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M21.3 8.7 8.7 21.3c-1 1-2.5 1-3.4 0l-2.6-2.6c-1-1-1-2.5 0-3.4L15.3 2.7c1-1 2.5-1 3.4 0l2.6 2.6c1 1 1 2.5 0 3.4Z"/><path d="m7.5 10.5 2 2"/><path d="m10.5 7.5 2 2"/><path d="m13.5 4.5 2 2"/><path d="m4.5 13.5 2 2"/></svg>
                    </div>
                    <div class="footer-card-body">
                        <div class="footer-card-title">Panduan Ukuran</div>
                        <div class="footer-card-sub">Cek nomor lingkar kepala (cm)</div>
                    </div>
                    <div class="footer-card-arrow">&rarr;</div>
                </a>

                <a href="#spesifikasi" class="footer-mobile-card">
                    <div class="footer-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--korpri-gold);"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                    </div>
                    <div class="footer-card-body">
                        <div class="footer-card-title">Spesifikasi Eselon II</div>
                        <div class="footer-card-sub">Mutz khusus Kepala SKPD / Pejabat</div>
                    </div>
                    <div class="footer-card-arrow">&rarr;</div>
                </a>

                <a href="login.php" class="footer-mobile-card">
                    <div class="footer-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>
                    <div class="footer-card-body">
                        <div class="footer-card-title">Portal Petugas</div>
                        <div class="footer-card-sub">Login administrator sistem</div>
                    </div>
                    <div class="footer-card-arrow">&rarr;</div>
                </a>
            </nav>

            <div class="footer-mobile-sekretariat">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; color: var(--korpri-gold);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <span><strong>Sekretariat:</strong> Kantor Bupati Banjar, Jl. A. Yani No. 2 Martapura</span>
            </div>

            <div class="footer-mobile-bottom">
                <div class="footer-status-pill">
                    <span class="footer-status-dot"></span>
                    <span>Layanan Resmi Aktif &amp; Terverifikasi</span>
                </div>
                <div>
                    &copy; <?= date('Y') ?> KORPRI Kab. Banjar &bull; Martapura
                </div>
            </div>
        </div>
    </footer>

    <!-- Client-side Interactive Logic -->
    <script>
        const STOK_DATA = <?= json_encode($stok_data) ?>;
        const SIZES_PRIA = <?= json_encode($sizes_pria) ?>;
        const SIZES_WANITA = <?= json_encode($sizes_wanita) ?>;

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
        const themeTogglePublic = document.getElementById('themeTogglePublic');
        function updateThemeIcon() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (themeTogglePublic) {
                themeTogglePublic.innerHTML = isDark 
                    ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>'
                    : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';
            }
        }
        updateThemeIcon();

        if (themeTogglePublic) {
            themeTogglePublic.addEventListener('click', function() {
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

        // Switch Category
        function switchCategory(cat) {
            currentCategory = cat;
            selectedSizeFilter = null; // Reset filter ukuran saat ganti kategori
            
            const tabBiasa = document.getElementById('tabBtnBiasa');
            const tabKepala = document.getElementById('tabBtnKepala');
            const eselonNotice = document.getElementById('eselonNotice');
            const pillPria = document.getElementById('pillPria');
            const pillWanita = document.getElementById('pillWanita');

            if (cat === 'Biasa') {
                tabBiasa.classList.add('active');
                tabKepala.classList.remove('active');
                eselonNotice.style.display = 'none';
                pillPria.classList.remove('active-gold');
                pillWanita.classList.remove('active-gold');
            } else {
                tabKepala.classList.add('active');
                tabBiasa.classList.remove('active');
                eselonNotice.style.display = 'flex';
                if (currentGender === 'Laki-laki') pillPria.classList.add('active-gold');
                if (currentGender === 'Perempuan') pillWanita.classList.add('active-gold');
            }

            renderQuickSizeChips();
            renderStockGrid();
            updateGenderCounts();
        }

        // Switch Gender
        function switchGender(gender) {
            currentGender = gender;
            selectedSizeFilter = null; // Reset filter ukuran saat ganti gender

            const pillPria = document.getElementById('pillPria');
            const pillWanita = document.getElementById('pillWanita');

            if (gender === 'Laki-laki') {
                pillPria.classList.add('active');
                pillWanita.classList.remove('active');
                if (currentCategory === 'Kepala SKPD') {
                    pillPria.classList.add('active-gold');
                    pillWanita.classList.remove('active-gold');
                }
            } else {
                pillWanita.classList.add('active');
                pillPria.classList.remove('active');
                if (currentCategory === 'Kepala SKPD') {
                    pillWanita.classList.add('active-gold');
                    pillPria.classList.remove('active-gold');
                }
            }

            renderQuickSizeChips();
            renderStockGrid();
        }

        function updateGenderCounts() {
            const dataCat = STOK_DATA[currentCategory] || {};
            
            let totalPria = 0;
            const priaObj = dataCat['Laki-laki'] || {};
            for (let sz in priaObj) { totalPria += (priaObj[sz] || 0); }
            
            let totalWanita = 0;
            const wanitaObj = dataCat['Perempuan'] || {};
            for (let sz in wanitaObj) { totalWanita += (wanitaObj[sz] || 0); }

            document.getElementById('badgeCountPria').textContent = totalPria > 0 ? (totalPria + ' ready') : 'Pre-Order';
            document.getElementById('badgeCountWanita').textContent = totalWanita > 0 ? (totalWanita + ' ready') : 'Pre-Order';
        }

        // Render Quick Interactive Size Filter Chips
        function renderQuickSizeChips() {
            const track = document.getElementById('quickSizeChips');
            if (!track) return;
            track.innerHTML = '';

            const allSizes = (currentGender === 'Laki-laki') ? SIZES_PRIA : SIZES_WANITA;
            const stockObj = (STOK_DATA[currentCategory] && STOK_DATA[currentCategory][currentGender]) 
                ? STOK_DATA[currentCategory][currentGender] 
                : {};

            // Chip 'Semua'
            const btnAll = document.createElement('button');
            btnAll.type = 'button';
            btnAll.className = 'size-chip-btn' + (selectedSizeFilter === null ? ' active' : '');
            btnAll.textContent = 'Semua (' + allSizes.length + ')';
            btnAll.onclick = function() {
                selectedSizeFilter = null;
                renderQuickSizeChips();
                renderStockGrid();
            };
            track.appendChild(btnAll);

            // Chips per nomor ukuran
            allSizes.forEach(sz => {
                const qty = stockObj[sz] !== undefined ? stockObj[sz] : 0;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'size-chip-btn' + (selectedSizeFilter === sz ? ' active' : '');
                btn.textContent = `No. ${sz} (${qty})`;
                btn.onclick = function() {
                    selectedSizeFilter = (selectedSizeFilter === sz) ? null : sz;
                    renderQuickSizeChips();
                    renderStockGrid();
                };
                track.appendChild(btn);
            });
        }

        function renderStockGrid() {
            const container = document.getElementById('stockGridContainer');
            container.innerHTML = '';

            const allSizes = (currentGender === 'Laki-laki') ? SIZES_PRIA : SIZES_WANITA;
            const sizes = (selectedSizeFilter !== null && allSizes.includes(selectedSizeFilter))
                ? [selectedSizeFilter]
                : allSizes;

            const stockObj = (STOK_DATA[currentCategory] && STOK_DATA[currentCategory][currentGender]) 
                ? STOK_DATA[currentCategory][currentGender] 
                : {};

            const maxStockBaseline = 60;

            sizes.forEach((sz, idx) => {
                const qty = stockObj[sz] !== undefined ? stockObj[sz] : 0;
                
                let statusBadge = '';
                let statusText = '';
                let barColor = '#10B981';
                let btnHtml = '';

                if (qty > 5) {
                    statusBadge = '<span class="status-badge badge-ready"><span class="status-dot green"></span> Ready Stock</span>';
                    statusText = 'Tersedia';
                    barColor = 'linear-gradient(90deg, #10B981, #059669)';
                    btnHtml = `
                        <button type="button" class="btn-order-wa ready" onclick="openOrderModal('${currentCategory}', '${currentGender}', ${sz}, ${qty})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                            Pesan Ukuran ${sz} via WA
                        </button>
                    `;
                } else if (qty > 0) {
                    statusBadge = `<span class="status-badge badge-low"><span class="status-dot yellow"></span> Sisa ${qty} Pcs</span>`;
                    statusText = 'Menipis';
                    barColor = 'linear-gradient(90deg, #F59E0B, #D97706)';
                    btnHtml = `
                        <button type="button" class="btn-order-wa ready" onclick="openOrderModal('${currentCategory}', '${currentGender}', ${sz}, ${qty})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                            Ambil Sisa ${qty} Pcs
                        </button>
                    `;
                } else {
                    statusBadge = '<span class="status-badge badge-empty"><span class="status-dot red"></span> Stok Habis</span>';
                    statusText = 'Habis';
                    barColor = 'linear-gradient(90deg, #EF4444, #DC2626)';
                    btnHtml = `
                        <button type="button" class="btn-order-wa preorder" onclick="openOrderModal('${currentCategory}', '${currentGender}', ${sz}, 0)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                            Pre-Order Ukuran ${sz}
                        </button>
                    `;
                }

                const percent = Math.min(100, Math.round((qty / maxStockBaseline) * 100));

                const card = document.createElement('div');
                card.className = 'size-card';
                card.style.animationDelay = (idx * 0.04) + 's';
                card.innerHTML = `
                    <div class="card-header-row">
                        <div>
                            <div class="size-number-badge">Uk. ${sz}</div>
                            <div class="size-sub-info">Lingkar Kepala ~${sz} cm</div>
                        </div>
                        ${statusBadge}
                    </div>

                    <div class="stock-qty-wrap">
                        <div class="qty-number" style="color: ${qty > 0 ? (qty <= 5 ? '#D97706' : '#059669') : '#DC2626'};">${qty} <span style="font-size: 1.1rem; font-weight: 700;">pcs</span></div>
                        <div class="qty-label">${statusText} di Sekretariat KORPRI</div>
                        <div class="stock-progress-bar">
                            <div class="stock-progress-fill" style="width: ${percent}%; background: ${barColor};"></div>
                        </div>
                    </div>

                    <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin-bottom: 14px; line-height: 1.4;">
                        ${currentCategory === 'Kepala SKPD' ? 'Eselon II (Bordir List Emas)' : 'Standar Mutz ASN KORPRI'} &bull; ${currentGender}
                    </div>

                    ${btnHtml}
                `;

                container.appendChild(card);
            });
        }

        // WhatsApp Ordering Modal Helpers
        function openOrderModal(category, gender, size, stock) {
            selectedOrderData = { category, gender, size, stock };

            document.getElementById('modalCat').textContent = category === 'Kepala SKPD' ? 'Kepala SKPD / Eselon II' : 'Mutz Standar ASN (Biasa)';
            document.getElementById('modalGender').textContent = gender;
            document.getElementById('modalSize').textContent = 'Nomor ' + size + ' (Lingkar ~' + size + ' cm)';
            
            const statusElem = document.getElementById('modalStockStatus');
            if (stock > 0) {
                statusElem.textContent = 'Ready (' + stock + ' pcs tersedia)';
                statusElem.style.color = '#059669';
            } else {
                statusElem.textContent = 'Habis (Diteruskan sebagai Pre-Order)';
                statusElem.style.color = '#DC2626';
            }

            document.getElementById('waOrderModal').classList.add('active');
            document.body.style.overflow = 'hidden'; // Lock background scrolling on mobile
        }

        function closeOrderModal(event) {
            if (!event || event.target.id === 'waOrderModal' || event.target.tagName === 'BUTTON' || event.target.closest('button')) {
                document.getElementById('waOrderModal').classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        function sendWhatsAppOrder() {
            const nama = document.getElementById('orderNama').value.trim();
            const skpd = document.getElementById('orderSkpd').value.trim();

            const catText = selectedOrderData.category === 'Kepala SKPD' ? 'Mutz Kepala SKPD (Eselon II)' : 'Mutz Standar ASN (Biasa)';
            const statusPesanan = selectedOrderData.stock > 0 ? 'Pemesanan Ready Stock' : 'Permintaan Pre-Order (Stok Habis)';

            let msg = `Halo Admin E-MutZ KORPRI,\n\n`;
            msg += `Saya ingin melakukan *${statusPesanan}* dengan rincian berikut:\n`;
            msg += `• *Kategori:* ${catText}\n`;
            msg += `• *Peruntukan:* ${selectedOrderData.gender}\n`;
            msg += `• *Ukuran Topi:* No. ${selectedOrderData.size}\n`;
            
            if (nama) msg += `• *Nama Pemesan:* ${nama}\n`;
            if (skpd) msg += `• *Asal SKPD / Instansi:* ${skpd}\n`;
            
            msg += `\nMohon informasi ketersediaan fisik dan prosedur pengambilan/pembayarannya ya. Terima kasih!`;

            const phone = '<?= $admin_wa_default ?>';
            const url = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
            window.open(url, '_blank');
            closeOrderModal();
        }

        function openGeneralWhatsApp() {
            const msg = `Halo Admin E-MutZ KORPRI,\n\nSaya ingin menanyakan informasi ketersediaan stok topi mutz ASN (Standar / Kepala SKPD).`;
            const phone = '<?= $admin_wa_default ?>';
            const url = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
            window.open(url, '_blank');
        }

        function copyKatalogLink() {
            const url = window.location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Tautan Katalog Stok berhasil disalin! Anda dapat membagikannya ke WhatsApp.');
                });
            } else {
                prompt('Salin tautan ini:', url);
            }
        }

        // Ultra-smooth throttled back-to-top scroll listener (Zero jank)
        let isScrollTicking = false;
        window.addEventListener('scroll', function() {
            if (!isScrollTicking) {
                window.requestAnimationFrame(function() {
                    const btn = document.getElementById('btnScrollTop');
                    if (btn) {
                        if (window.scrollY > 300) {
                            btn.classList.add('visible');
                        } else {
                            btn.classList.remove('visible');
                        }
                    }
                    isScrollTicking = false;
                });
                isScrollTicking = true;
            }
        }, { passive: true });

        // Initialize on Load
        document.addEventListener('DOMContentLoaded', function() {
            switchCategory('Biasa');
            updateGenderCounts();
            renderQuickSizeChips();
        });
    </script>
</body>
</html>
