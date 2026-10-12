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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog & Stok Mutz ASN KORPRI | Biasa & Kepala SKPD (Eselon II)</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="stylesheet" href="assets/css/app.css?v=<?= filemtime("assets/css/app.css") ?>">
    
    <style>
        /* Override body flex from app.css for public landing page */
        body.public-catalog-page {
            display: block !important;
            width: 100% !important;
            max-width: 100vw !important;
            margin: 0 !important;
            padding: 0 !important;
            min-height: 100vh !important;
            overflow-x: hidden !important;
            background-color: var(--brutal-bg);
            color: var(--dark);
        }

        /* Top Navbar */
        .public-navbar {
            width: 100%;
            background: #FFFFFF;
            border-bottom: 3px solid #000000;
            padding: 0.85rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }
        [data-theme="dark"] .public-navbar {
            background: #18181B;
            border-bottom: 3px solid #FAFAFA;
        }

        .brand-public {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
            min-width: 0;
        }
        .brand-public img {
            width: 44px;
            height: 44px;
            object-fit: contain;
            border-radius: 6px;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        .brand-public:hover img {
            transform: scale(1.05);
        }
        .brand-text-title {
            font-size: 1.15rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            line-height: 1.1;
            margin: 0;
            color: inherit;
            white-space: nowrap;
        }
        .brand-text-sub {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--gray);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .nav-actions .btn {
            border-radius: 6px;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.1s;
        }
        .nav-actions .btn:hover {
            transform: translate(-1px, -1px);
        }
        .nav-actions .btn:active {
            transform: translate(1px, 1px);
        }
        .nav-btn-short {
            display: none;
        }

        /* Container Layout */
        .catalog-container {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 1.25rem;
            box-sizing: border-box;
        }

        /* Hero Section */
        .hero-section {
            padding: 2rem 0 1.5rem 0;
            text-align: center;
        }

        .hero-card-banner {
            background: #FFFFFF;
            border: 3px solid #000000;
            box-shadow: 6px 6px 0px #000000;
            border-radius: 12px;
            padding: 2.25rem 1.75rem 2rem 1.75rem;
            margin-bottom: 2rem;
            text-align: center;
            transition: box-shadow 0.2s ease;
        }
        [data-theme="dark"] .hero-card-banner {
            background: #18181B;
            border-color: #FAFAFA;
            box-shadow: 6px 6px 0px #FAFAFA;
        }

        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #DCFCE7;
            color: #166534;
            border: 2px solid #000000;
            box-shadow: 2px 2px 0px #000000;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 800;
            margin-bottom: 1.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        [data-theme="dark"] .live-badge {
            background: #064E3B;
            color: #A7F3D0;
            border-color: #34D399;
            box-shadow: 2px 2px 0px #34D399;
        }
        .live-pulse {
            width: 9px;
            height: 9px;
            background: #22C55E;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            animation: pulse-ring 1.8s infinite;
            flex-shrink: 0;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        .hero-title {
            font-size: 2.3rem;
            font-weight: 900;
            line-height: 1.15;
            letter-spacing: -0.8px;
            margin: 0 auto 0.85rem auto;
            max-width: 850px;
            text-transform: uppercase;
            color: #000000;
        }
        [data-theme="dark"] .hero-title {
            color: #FFFFFF !important;
        }

        .hero-desc {
            font-size: 1.05rem;
            color: #374151;
            max-width: 720px;
            margin: 0 auto;
            line-height: 1.6;
            font-weight: 600;
        }
        [data-theme="dark"] .hero-desc {
            color: #E4E4E7 !important;
        }

        /* Stat Banner Highlights */
        .stat-banner-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 2rem;
        }
        .stat-banner-box {
            background: #FFFFFF;
            border: 2px solid #000;
            box-shadow: 4px 4px 0px #000;
            border-radius: 8px;
            padding: 16px 18px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 14px;
            color: #000;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        [data-theme="dark"] .stat-banner-box {
            background: #18181B;
            border-color: #FAFAFA;
            box-shadow: 4px 4px 0px #FAFAFA;
            color: #FAFAFA;
        }
        .stat-banner-box:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0px #000;
        }
        [data-theme="dark"] .stat-banner-box:hover {
            box-shadow: 6px 6px 0px #FAFAFA;
        }
        .stat-banner-icon {
            width: 46px;
            height: 46px;
            border-radius: 8px;
            border: 2px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }
        [data-theme="dark"] .stat-banner-icon {
            border-color: #FAFAFA;
        }

        /* Category Switcher Tabs & Filters */
        .category-switcher-wrap {
            margin-bottom: 1.5rem;
            position: sticky;
            top: 70px;
            z-index: 85;
            background: var(--brutal-bg);
            padding: 6px 0 10px 0;
            transition: all 0.2s ease;
        }
        .category-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            background: #FFFFFF;
            border: 3px solid #000;
            box-shadow: 6px 6px 0px #000;
            border-radius: 10px;
            padding: 8px;
        }
        [data-theme="dark"] .category-tabs {
            background: #18181B;
            border-color: #FAFAFA;
            box-shadow: 6px 6px 0px #FAFAFA;
        }

        .tab-btn {
            background: transparent !important;
            border: 2px solid transparent !important;
            border-radius: 8px;
            padding: 14px 18px;
            cursor: pointer;
            text-align: left;
            transition: all 0.15s ease;
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: inherit !important;
            box-shadow: none !important;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        .tab-btn:hover {
            background: rgba(0, 0, 0, 0.05) !important;
        }
        [data-theme="dark"] .tab-btn:hover {
            background: rgba(255, 255, 255, 0.08) !important;
        }

        .tab-btn.active.tab-biasa {
            background: #00FFCC !important;
            color: #000000 !important;
            border: 2px solid #000000 !important;
            box-shadow: 3px 3px 0px #000000 !important;
        }
        .tab-btn.active.tab-kepala {
            background: #FBBF24 !important;
            color: #000000 !important;
            border: 2px solid #000000 !important;
            box-shadow: 3px 3px 0px #000000 !important;
        }

        .tab-title {
            font-size: 1.05rem;
            font-weight: 900;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 2px;
            color: inherit;
        }
        .tab-title-short {
            display: none;
        }
        .tab-sub {
            font-size: 0.8rem;
            font-weight: 600;
            opacity: 0.85;
            color: inherit;
        }
        .tab-price-tag {
            background: #000000;
            color: #FFFFFF;
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: 900;
            font-size: 0.85rem;
            white-space: nowrap;
            letter-spacing: -0.2px;
        }

        /* Eselon Notice Banner */
        .eselon-banner {
            background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
            border: 3px solid #000000;
            box-shadow: 5px 5px 0px #000000;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 16px;
            color: #000000;
            text-align: left;
        }
        [data-theme="dark"] .eselon-banner {
            background: linear-gradient(135deg, #2A1F02 0%, #3D2B00 100%);
            border-color: #F59E0B;
            box-shadow: 5px 5px 0px #F59E0B;
            color: #FAFAFA;
        }
        .eselon-badge-icon {
            width: 48px;
            height: 48px;
            background: #F59E0B;
            color: #000;
            border: 2px solid #000;
            box-shadow: 2.5px 2.5px 0px #000;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            flex-shrink: 0;
        }

        /* Gender Subfilter Pills */
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
            background: #FFFFFF !important;
            color: #000000 !important;
            border: 2px solid #000000 !important;
            box-shadow: 3px 3px 0px #000000 !important;
            padding: 9px 20px;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex !important;
            align-items: center;
            gap: 8px;
            transition: transform 0.1s, box-shadow 0.1s;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        [data-theme="dark"] .gender-pill {
            background: #18181B !important;
            color: #FAFAFA !important;
            border-color: #FAFAFA !important;
            box-shadow: 3px 3px 0px #FAFAFA !important;
        }
        .gender-pill:hover {
            transform: translate(-1px, -1px);
        }
        .gender-pill.active {
            background: #00FFCC !important;
            color: #000000 !important;
            border-color: #000000 !important;
            box-shadow: 2px 2px 0px #000000 !important;
            transform: translate(1px, 1px);
        }
        .gender-pill.active-gold {
            background: #FBBF24 !important;
            color: #000000 !important;
            border-color: #000000 !important;
            box-shadow: 2px 2px 0px #000000 !important;
        }
        .pill-badge {
            background: rgba(0,0,0,0.12);
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 0.78rem;
            font-weight: 800;
        }
        [data-theme="dark"] .pill-badge {
            background: rgba(255,255,255,0.18);
        }
        .pill-label-short {
            display: none;
        }

        /* Interactive Quick Size Chips */
        .quick-size-track {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            overflow-x: auto;
            padding: 4px 2px 6px 2px;
            margin-bottom: 0.5rem;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .quick-size-track::-webkit-scrollbar {
            display: none;
        }
        .size-chip-btn {
            background: #FFFFFF !important;
            color: #000000 !important;
            border: 2px solid #000000 !important;
            box-shadow: 2px 2px 0px #000000 !important;
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 0.82rem;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all 0.15s ease;
            user-select: none;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            -webkit-tap-highlight-color: transparent;
        }
        [data-theme="dark"] .size-chip-btn {
            background: #18181B !important;
            color: #FAFAFA !important;
            border-color: #FAFAFA !important;
            box-shadow: 2px 2px 0px #FAFAFA !important;
        }
        .size-chip-btn:hover {
            transform: translate(-1px, -1px);
        }
        .size-chip-btn.active {
            background: #000000 !important;
            color: #FFFFFF !important;
            border-color: #000000 !important;
            transform: translate(1px, 1px);
            box-shadow: 1px 1px 0px #000000 !important;
        }
        [data-theme="dark"] .size-chip-btn.active {
            background: #00FFCC !important;
            color: #000000 !important;
            border-color: #00FFCC !important;
            box-shadow: 1px 1px 0px #00FFCC !important;
        }

        /* Stock Cards Grid */
        .stock-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 3.5rem;
        }

        @keyframes cardPopIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .size-card {
            background: #FFFFFF;
            border: 3px solid #000000;
            box-shadow: 5px 5px 0px #000000;
            border-radius: 8px;
            padding: 22px 20px;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            color: #000000;
            animation: cardPopIn 0.22s ease-out backwards;
        }
        [data-theme="dark"] .size-card {
            background: #18181B;
            border-color: #FAFAFA;
            box-shadow: 5px 5px 0px #FAFAFA;
            color: #FAFAFA;
        }
        .size-card:hover {
            transform: translate(-3px, -3px);
            box-shadow: 8px 8px 0px #000000;
        }
        [data-theme="dark"] .size-card:hover {
            box-shadow: 8px 8px 0px #FAFAFA;
        }

        .card-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
        }
        .size-number-badge {
            font-size: 2.2rem;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -1px;
            color: inherit;
        }
        .size-sub-info {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray);
            margin-top: 4px;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
            border: 1.5px solid #000;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
        }
        .badge-ready {
            background: #86EFAC;
            color: #064E3B;
        }
        .badge-low {
            background: #FDE047;
            color: #713F12;
        }
        .badge-empty {
            background: #FCA5A5;
            color: #7F1D1D;
        }

        /* Stock Quantity Meter */
        .stock-qty-wrap {
            margin: 12px 0 16px 0;
            padding: 14px;
            background: #F9FAFB;
            border: 2px dashed #000000;
            border-radius: 6px;
            text-align: center;
        }
        [data-theme="dark"] .stock-qty-wrap {
            background: #09090B;
            border-color: #4B5563;
        }
        .qty-number {
            font-size: 2.1rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 2px;
        }
        .qty-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stock-progress-bar {
            height: 9px;
            background: #E5E7EB;
            border: 1.5px solid #000;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }
        [data-theme="dark"] .stock-progress-bar {
            border-color: #FAFAFA;
            background: #27272A;
        }
        .stock-progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }

        .btn-order-wa {
            margin-top: auto;
            width: 100%;
            padding: 11px 14px;
            border-radius: 6px;
            border: 2px solid #000;
            box-shadow: 2px 2px 0px #000;
            font-weight: 800;
            font-size: 0.9rem;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.1s;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        .btn-order-wa:hover {
            transform: translate(-1px, -1px);
            box-shadow: 4px 4px 0px #000;
        }
        .btn-order-wa:active {
            transform: translate(1px, 1px);
            box-shadow: 1px 1px 0px #000;
        }
        .btn-order-wa.ready {
            background: #25D366 !important;
            color: #000000 !important;
        }
        .btn-order-wa.preorder {
            background: #F3F4F6 !important;
            color: #111827 !important;
            border-color: #000000 !important;
        }
        [data-theme="dark"] .btn-order-wa.preorder {
            background: #27272A !important;
            color: #FAFAFA !important;
            border-color: #FAFAFA !important;
            box-shadow: 2px 2px 0px #FAFAFA !important;
        }

        /* Size Guide Section */
        .guide-box {
            background: #FFFFFF;
            border: 3px solid #000000;
            box-shadow: 6px 6px 0px #000000;
            border-radius: 10px;
            padding: 28px;
            margin-bottom: 3.5rem;
            color: #000000;
        }
        [data-theme="dark"] .guide-box {
            background: #18181B;
            border-color: #FAFAFA;
            box-shadow: 6px 6px 0px #FAFAFA;
            color: #FAFAFA;
        }

        .table-scroll-hint {
            display: none;
        }

        .guide-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .guide-table th, .guide-table td {
            border: 2px solid #000000;
            padding: 12px 14px;
            text-align: center;
            font-weight: 700;
            font-size: 0.9rem;
        }
        [data-theme="dark"] .guide-table th, 
        [data-theme="dark"] .guide-table td {
            border-color: #FAFAFA;
        }
        .guide-table th {
            background: #00FFCC;
            color: #000000;
            text-transform: uppercase;
        }
        .guide-table td.subhead {
            background: #F3F4F6;
            color: #000000;
        }
        [data-theme="dark"] .guide-table td.subhead {
            background: #27272A;
            color: #FAFAFA;
        }

        /* Comparison Section */
        .comparison-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-top: 1.5rem;
        }
        .comparison-card {
            border: 2px solid #000000;
            border-radius: 8px;
            padding: 20px;
            background: #F9FAFB;
            color: #000000;
        }
        [data-theme="dark"] .comparison-card {
            background: #09090B;
            border-color: #FAFAFA;
            color: #FAFAFA;
        }

        /* Modal Dialog */
        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            box-sizing: border-box;
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
        }
        .modal-backdrop.active {
            display: flex;
        }
        .modal-drag-handle {
            display: none;
        }
        .modal-card {
            background: #FFFFFF;
            border: 3px solid #000000;
            box-shadow: 8px 8px 0px #000000;
            border-radius: 10px;
            max-width: 480px;
            width: 100%;
            padding: 24px;
            position: relative;
            box-sizing: border-box;
            color: #000000;
        }
        [data-theme="dark"] .modal-card {
            background: #18181B;
            border-color: #FAFAFA;
            box-shadow: 8px 8px 0px #FAFAFA;
            color: #FAFAFA;
        }

        /* Public Footer */
        .public-footer {
            border-top: 3px solid #000000;
            background: #FFFFFF;
            padding: 2.5rem 1.5rem;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
            margin-top: 2rem;
            color: #000000;
        }
        [data-theme="dark"] .public-footer {
            background: #18181B;
            border-top: 3px solid #FAFAFA;
            color: #FAFAFA;
        }

        /* Floating Actions Group */
        .floating-actions-wrap {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
            z-index: 95;
            pointer-events: none;
        }
        .floating-actions-wrap > * {
            pointer-events: auto;
        }
        .floating-wa-btn {
            background: #25D366;
            color: #000000;
            border: 2px solid #000000;
            box-shadow: 4px 4px 0px #000000;
            border-radius: 50px;
            padding: 12px 20px;
            font-weight: 900;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.15s;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        .floating-wa-btn:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0px #000000;
        }
        .floating-wa-btn:active {
            transform: translate(1px, 1px);
            box-shadow: 2px 2px 0px #000000;
        }
        .btn-scroll-top {
            display: none !important;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #FFFFFF !important;
            color: #000000 !important;
            border: 2px solid #000000 !important;
            box-shadow: 3px 3px 0px #000000 !important;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        [data-theme="dark"] .btn-scroll-top {
            background: #18181B !important;
            color: #FAFAFA !important;
            border-color: #FAFAFA !important;
            box-shadow: 3px 3px 0px #FAFAFA !important;
        }
        .btn-scroll-top.visible {
            display: inline-flex !important;
        }
        .btn-scroll-top:hover {
            transform: translate(-1px, -1px);
            box-shadow: 4px 4px 0px #000000 !important;
        }
        .btn-scroll-top:active {
            transform: translate(1px, 1px);
            box-shadow: 1px 1px 0px #000000 !important;
        }

        /* ==========================================================================
           COMPREHENSIVE MOBILE & TABLET RESPONSIVE RULES (Max Width: 640px)
           ========================================================================== */
        @media (max-width: 640px) {
            /* Top Navbar */
            .public-navbar {
                padding: 0.55rem 0.85rem;
                gap: 0.5rem;
            }
            .brand-public {
                gap: 8px;
                min-width: 0;
            }
            .brand-public img {
                width: 36px;
                height: 36px;
            }
            .brand-text-title {
                font-size: 0.95rem;
            }
            .brand-text-sub {
                display: none !important;
            }
            .nav-actions {
                gap: 5px;
            }
            .nav-actions .btn {
                padding: 6px 9px !important;
                font-size: 0.78rem !important;
                min-height: 34px !important;
            }

            /* Container */
            .catalog-container {
                padding: 0 0.85rem;
            }

            /* Hero */
            .hero-section {
                padding: 1.25rem 0 1rem 0;
            }
            .hero-card-banner {
                padding: 1.25rem 1rem;
                margin-bottom: 1.25rem;
                border-radius: 10px;
                box-shadow: 4px 4px 0px #000000;
            }
            [data-theme="dark"] .hero-card-banner {
                box-shadow: 4px 4px 0px #FAFAFA;
            }
            .hero-title {
                font-size: clamp(1.35rem, 5.5vw, 1.75rem);
                margin-bottom: 0.65rem;
                letter-spacing: -0.5px;
            }
            .hero-desc {
                font-size: 0.88rem;
                line-height: 1.5;
            }
            .live-badge {
                font-size: 0.72rem;
                padding: 5px 12px;
                margin-bottom: 0.9rem;
                max-width: 100%;
                white-space: normal;
                line-height: 1.3;
            }

            /* 2-Tier Compact Mobile Stat Banners */
            .stat-banner-row {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                margin-bottom: 1.5rem;
            }
            .stat-banner-box {
                padding: 10px 12px;
                gap: 10px;
                border-radius: 8px;
                box-shadow: 3px 3px 0px #000;
            }
            [data-theme="dark"] .stat-banner-box {
                box-shadow: 3px 3px 0px #FAFAFA;
            }
            .stat-banner-box:first-child {
                grid-column: 1 / -1;
            }
            .stat-banner-icon {
                width: 38px;
                height: 38px;
                font-size: 1.15rem;
                border-radius: 6px;
            }
            .stat-val {
                font-size: 1.25rem !important;
            }
            .stat-lbl {
                font-size: 0.72rem !important;
                line-height: 1.2;
            }

            /* Sticky Category & Filter Controls on Mobile */
            .category-switcher-wrap {
                top: 50px;
                padding: 6px 0 8px 0;
                margin-left: -0.85rem;
                margin-right: -0.85rem;
                padding-left: 0.85rem;
                padding-right: 0.85rem;
                background: rgba(253, 251, 247, 0.96);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            }
            [data-theme="dark"] .category-switcher-wrap {
                background: rgba(18, 18, 18, 0.96);
            }
            .category-tabs {
                grid-template-columns: 1fr 1fr;
                gap: 6px;
                padding: 5px;
                box-shadow: 4px 4px 0px #000;
                border-radius: 8px;
            }
            [data-theme="dark"] .category-tabs {
                box-shadow: 4px 4px 0px #FAFAFA;
            }
            .tab-btn {
                padding: 8px 10px !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 4px !important;
                border-radius: 6px !important;
                min-height: 54px !important;
            }
            .tab-title-full {
                display: none !important;
            }
            .tab-title-short {
                display: inline !important;
            }
            .tab-title {
                font-size: 0.82rem;
                gap: 4px;
                line-height: 1.2;
            }
            .tab-sub {
                display: none !important;
            }
            .tab-price-tag {
                font-size: 0.72rem;
                padding: 2px 7px;
                border-radius: 4px;
            }

            /* Segmented 1-Row Gender Pills */
            .gender-filter-bar {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                margin-top: 8px;
                margin-bottom: 8px;
                width: 100%;
            }
            .pill-label-full {
                display: none !important;
            }
            .pill-label-short {
                display: inline !important;
            }
            .gender-pill {
                width: 100%;
                justify-content: center;
                padding: 8px 8px !important;
                font-size: 0.85rem !important;
                min-height: 42px !important;
                box-shadow: 2px 2px 0px #000 !important;
                border-radius: 6px;
                white-space: nowrap;
                gap: 6px;
            }
            [data-theme="dark"] .gender-pill {
                box-shadow: 2px 2px 0px #FAFAFA !important;
            }
            .gender-pill .pill-badge {
                font-size: 0.72rem;
                padding: 2px 5px;
            }

            /* Quick Size Chips on Mobile */
            .quick-size-track {
                justify-content: flex-start;
                padding: 2px 2px 4px 2px;
                gap: 6px;
            }
            .size-chip-btn {
                padding: 5px 11px !important;
                font-size: 0.78rem !important;
                box-shadow: 2px 2px 0px #000 !important;
                min-height: 32px !important;
            }

            /* Eselon Banner Compact on Mobile */
            .eselon-banner {
                flex-direction: row !important;
                text-align: left !important;
                padding: 12px 14px !important;
                gap: 12px !important;
                margin-bottom: 1.25rem !important;
                border-radius: 8px !important;
                box-shadow: 3px 3px 0px #000 !important;
                align-items: flex-start !important;
            }
            .eselon-badge-icon {
                width: 38px !important;
                height: 38px !important;
                font-size: 1.25rem !important;
                border-radius: 6px !important;
            }
            .eselon-banner h3 {
                font-size: 0.95rem !important;
                line-height: 1.25 !important;
            }
            .eselon-banner p {
                font-size: 0.78rem !important;
                line-height: 1.35 !important;
            }

            /* Stock Cards */
            .stock-grid {
                grid-template-columns: 1fr;
                gap: 14px;
                margin-bottom: 2rem;
            }
            .card-header-row {
                gap: 8px;
            }
            .status-badge {
                padding: 4px 8px !important;
                font-size: 0.72rem !important;
            }
            .size-card {
                padding: 16px 14px;
                box-shadow: 4px 4px 0px #000;
                border-radius: 8px;
            }
            [data-theme="dark"] .size-card {
                box-shadow: 4px 4px 0px #FAFAFA;
            }
            .size-card:active {
                transform: scale(0.985);
            }
            .size-number-badge {
                font-size: 1.85rem;
            }
            .size-sub-info {
                font-size: 0.8rem;
            }
            .stock-qty-wrap {
                padding: 10px 12px;
                margin: 10px 0 12px 0;
            }
            .qty-number {
                font-size: 1.85rem;
            }
            .btn-order-wa {
                padding: 12px 14px;
                font-size: 0.92rem;
                min-height: 46px;
            }
            .btn-order-wa:active {
                transform: scale(0.97);
            }

            /* Guide Box & Table */
            .guide-box {
                padding: 18px 14px;
                margin-bottom: 2rem;
                border-radius: 8px;
                box-shadow: 4px 4px 0px #000;
            }
            [data-theme="dark"] .guide-box {
                box-shadow: 4px 4px 0px #FAFAFA;
            }
            .table-scroll-hint {
                display: flex !important;
                align-items: center;
                gap: 6px;
                font-size: 0.75rem;
                font-weight: 700;
                color: var(--gray);
                margin-bottom: 8px;
            }
            .guide-table th, .guide-table td {
                padding: 8px 10px;
                font-size: 0.8rem;
            }

            /* Comparison Cards */
            .comparison-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .comparison-card {
                padding: 14px;
            }

            /* Bottom Sheet WhatsApp Order Modal */
            .modal-backdrop {
                align-items: flex-end;
                padding: 0;
            }
            .modal-drag-handle {
                display: block;
                width: 44px;
                height: 5px;
                background: #D1D5DB;
                border-radius: 999px;
                margin: 0 auto 14px auto;
            }
            [data-theme="dark"] .modal-drag-handle {
                background: #4B5563;
            }
            .modal-card {
                max-width: 100%;
                border-radius: 20px 20px 0 0;
                border-bottom: none;
                max-height: 85vh;
                overflow-y: auto;
                padding: 16px 18px calc(24px + env(safe-area-inset-bottom, 0px)) 18px;
                box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.35);
                animation: slideUpSheet 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .modal-card input {
                font-size: 16px !important;
                padding: 11px 12px !important;
            }

            /* Floating Actions on Mobile */
            .floating-actions-wrap {
                bottom: calc(16px + env(safe-area-inset-bottom, 0px));
                right: 14px;
                gap: 8px;
            }
            .floating-wa-btn {
                padding: 10px 15px;
                font-size: 0.84rem;
                box-shadow: 3px 3px 0px #000;
            }
            .btn-scroll-top {
                width: 40px;
                height: 40px;
                box-shadow: 2.5px 2.5px 0px #000 !important;
            }
        }

        @keyframes slideUpSheet {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }

        @media (max-width: 440px) {
            .nav-btn-text {
                display: none !important;
            }
            .nav-btn-short {
                display: inline !important;
            }
            .floating-wa-text {
                display: none !important;
            }
            .floating-wa-btn {
                padding: 10px 12px;
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

    <!-- Top Public Navbar -->
    <header class="public-navbar">
        <a href="cek_stok.php" class="brand-public">
            <img src="assets/images/logo.png?v=2" alt="Logo KORPRI" width="44" height="44">
            <div>
                <h1 class="brand-text-title">E-MutZ KORPRI</h1>
                <p class="brand-text-sub">Katalog &amp; Informasi Stok Resmi</p>
            </div>
        </a>

        <div class="nav-actions">
            <!-- Theme Toggle -->
            <button id="themeTogglePublic" class="btn" style="padding: 7px 11px; font-size: 0.85rem; background: var(--white); color: var(--dark); border: 2px solid var(--dark);" title="Ganti Tema">
                <svg id="themeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </button>

            <!-- Quick Share Link Button -->
            <button onclick="copyKatalogLink()" class="btn" style="padding: 7px 12px; font-size: 0.85rem; background: #00FFCC; color: #000; border: 2px solid #000;" title="Salin Tautan Katalog">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                <span class="nav-btn-text" style="font-weight: 800;">Bagikan</span>
            </button>

            <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
                <a href="index.php" class="btn" style="padding: 7px 14px; font-size: 0.85rem; background: #FFE600; color: #000; border: 2px solid #000; font-weight: 800; text-decoration: none;">
                    <span class="nav-btn-text">Dashboard &rarr;</span>
                    <span class="nav-btn-short">Admin</span>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn" style="padding: 7px 14px; font-size: 0.85rem; background: #FFFFFF; color: #000; border: 2px solid #000; font-weight: 800; text-decoration: none;">
                    <span class="nav-btn-text">Login Petugas</span>
                    <span class="nav-btn-short">Login</span>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <main class="catalog-container">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-card-banner">
                <div class="live-badge">
                    <span class="live-pulse"></span>
                    <span>Update Stok Real-Time dari Sekretariat KORPRI</span>
                </div>

                <h2 class="hero-title">
                    Ketersediaan Stok Topi Mutz ASN
                </h2>
                <p class="hero-desc">
                    Informasi ukuran yang tersedia untuk <strong>Mutz Standar ASN</strong> dan <strong>Mutz Khusus Kepala SKPD / Pejabat Eselon II</strong>. Cek nomor lingkar kepala Anda sebelum melakukan pemesanan.
                </p>
            </div>

            <!-- Stat Highlights -->
            <div class="stat-banner-row">
                <div class="stat-banner-box">
                    <div class="stat-banner-icon" style="background: #00FFCC; color: #000;">📦</div>
                    <div>
                        <div class="stat-val" style="font-size: 1.5rem; font-weight: 900; line-height: 1.1;"><?= number_format($total_stok_tersedia, 0, ',', '.') ?> Pcs</div>
                        <div class="stat-lbl" style="font-size: 0.8rem; font-weight: 700; color: var(--gray);">Total Stok di Sekretariat KORPRI</div>
                    </div>
                </div>

                <div class="stat-banner-box">
                    <div class="stat-banner-icon" style="background: #60A5FA; color: #000;">🏷️</div>
                    <div>
                        <div class="stat-val" style="font-size: 1.5rem; font-weight: 900; line-height: 1.1;">Rp <?= number_format(HARGA_BIASA, 0, ',', '.') ?></div>
                        <div class="stat-lbl" style="font-size: 0.8rem; font-weight: 700; color: var(--gray);">Mutz Standar ASN</div>
                    </div>
                </div>

                <div class="stat-banner-box">
                    <div class="stat-banner-icon" style="background: #FBBF24; color: #000;">👑</div>
                    <div>
                        <div class="stat-val" style="font-size: 1.5rem; font-weight: 900; line-height: 1.1;">Rp <?= number_format(HARGA_KEPALA, 0, ',', '.') ?></div>
                        <div class="stat-lbl" style="font-size: 0.8rem; font-weight: 700; color: var(--gray);">Mutz Kepala SKPD (Eselon II)</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Category Switcher (Mutz Biasa vs Kepala SKPD) & Mobile Sticky Filter Bar -->
        <div class="category-switcher-wrap" id="categorySwitcher">
            <div class="category-tabs">
                <button type="button" class="tab-btn tab-biasa active" id="tabBtnBiasa" onclick="switchCategory('Biasa')">
                    <div>
                        <div class="tab-title">
                            <span class="tab-title-full">🏷️ Standar ASN</span>
                            <span class="tab-title-short">🏷️ Standar ASN</span>
                        </div>
                        <div class="tab-sub">Diperuntukkan bagi seluruh ASN (PNS &amp; PPPK)</div>
                    </div>
                    <div class="tab-price-tag">Rp <?= number_format(HARGA_BIASA, 0, ',', '.') ?></div>
                </button>

                <button type="button" class="tab-btn tab-kepala" id="tabBtnKepala" onclick="switchCategory('Kepala SKPD')">
                    <div>
                        <div class="tab-title">
                            <span class="tab-title-full">👑 Kepala SKPD (Eselon II)</span>
                            <span class="tab-title-short">👑 Kepala SKPD</span>
                        </div>
                        <div class="tab-sub">Pejabat Pimpinan Tinggi Pratama</div>
                    </div>
                    <div class="tab-price-tag">Rp <?= number_format(HARGA_KEPALA, 0, ',', '.') ?></div>
                </button>
            </div>

            <!-- Gender Selector Pills -->
            <div class="gender-filter-bar">
                <button type="button" class="gender-pill active" id="pillPria" onclick="switchGender('Laki-laki')">
                    <span class="pill-label-full">👔 Pria (Laki-laki)</span>
                    <span class="pill-label-short">👔 Pria</span>
                    <span id="badgeCountPria" class="pill-badge">...</span>
                </button>

                <button type="button" class="gender-pill" id="pillWanita" onclick="switchGender('Perempuan')">
                    <span class="pill-label-full">🧕 Wanita (Perempuan)</span>
                    <span class="pill-label-short">🧕 Wanita</span>
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
            <div class="eselon-badge-icon">👑</div>
            <div style="flex: 1; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                    <span style="font-size: 0.72rem; font-weight: 800; background: #FEF3C7; color: #B45309; padding: 2px 7px; border-radius: 4px; border: 1px solid #D97706; text-transform: uppercase; letter-spacing: 0.4px;">Eksklusif Eselon II</span>
                    <span style="font-size: 0.75rem; font-weight: 800; color: #92400E;">Bordir List Benang Emas</span>
                </div>
                <h3 style="margin: 0 0 4px 0; font-size: 1.05rem; font-weight: 900; color: inherit; line-height: 1.3;">
                    Spesifikasi Mutz Kepala Dinas / Badan / Asisten
                </h3>
                <p style="margin: 0; font-size: 0.85rem; font-weight: 600; opacity: 0.88; line-height: 1.45;">
                    Dirancang khusus dengan bordir list benang emas tebal resmi KORPRI bagi Pejabat Pimpinan Tinggi Pratama. Tersedia ukuran Pria &amp; Wanita.
                </p>
            </div>
        </div>

        <!-- Cards Grid Container -->
        <div class="stock-grid" id="stockGridContainer">
            <!-- Rendered dynamically by JavaScript -->
        </div>

        <!-- Size Guide & How to Measure Section -->
        <section class="guide-box" id="panduan">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 1.35rem; font-weight: 900; text-transform: uppercase; color: inherit;">
                    📏 Panduan Ukuran &amp; Cara Mengukur Lingkar Kepala
                </h3>
                <span style="font-size: 0.8rem; font-weight: 800; background: #FEF3C7; color: #B45309; padding: 4px 10px; border-radius: 4px; border: 1.5px solid #000;">
                    Tips Akurasi
                </span>
            </div>

            <p style="font-size: 0.95rem; line-height: 1.5; color: var(--gray); font-weight: 500;">
                Nomor ukuran topi mutz sesuai dengan <strong>lingkar kepala dalam satuan sentimeter (cm)</strong>. Gunakan pita meteran jahit melingkari kepala kira-kira 1 cm di atas telinga dan tepat di atas alis.
            </p>

            <div class="table-scroll-hint">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                <span>Geser tabel ke samping untuk melihat seluruh nomor (55 - 60)</span>
            </div>
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

            <div style="margin-top: 1.25rem; background: #EFF6FF; border: 2px solid #000; border-radius: 6px; padding: 14px 16px; color: #1E3A8A;">
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <div style="font-size: 1.25rem;">💡</div>
                    <div style="font-size: 0.875rem; line-height: 1.45; font-weight: 600;">
                        <strong>Catatan untuk ASN Wanita (Berhijab/Sanggul):</strong> Disarankan memilih 1 hingga 2 nomor lebih besar (biasanya No. 58 s/d 60) untuk memberikan ruang hijab agar topi tidak menekan kepala dan tetap nyaman serta tegak rapi saat dikenakan.
                    </div>
                </div>
            </div>

            <!-- Comparison Table: Biasa vs Kepala SKPD -->
            <div style="margin-top: 2rem;" id="spesifikasi">
                <h4 style="margin: 0 0 10px 0; font-size: 1.15rem; font-weight: 900; text-transform: uppercase; color: inherit;">
                    ⚖️ Perbedaan Mutz Biasa vs Kepala SKPD (Eselon II)
                </h4>
                
                <div class="comparison-grid">
                    <div class="comparison-card" style="border-left: 6px solid #00FFCC;">
                        <div style="font-size: 0.8rem; font-weight: 800; color: #059669; text-transform: uppercase;">Standar ASN</div>
                        <h5 style="margin: 4px 0 8px 0; font-size: 1.1rem; font-weight: 900; color: inherit;">Mutz Reguler (Biasa)</h5>
                        <ul style="padding-left: 18px; margin: 0; font-size: 0.875rem; line-height: 1.6; font-weight: 600;">
                            <li>Diperuntukkan bagi seluruh ASN (PNS dan PPPK).</li>
                            <li>Harga resmi: <strong>Rp <?= number_format(HARGA_BIASA, 0, ',', '.') ?></strong>.</li>
                        </ul>
                    </div>

                    <div class="comparison-card" style="border-left: 6px solid #F59E0B;">
                        <div style="font-size: 0.8rem; font-weight: 800; color: #D97706; text-transform: uppercase;">Pejabat Eselon II</div>
                        <h5 style="margin: 4px 0 8px 0; font-size: 1.1rem; font-weight: 900; color: inherit;">Mutz Kepala SKPD</h5>
                        <ul style="padding-left: 18px; margin: 0; font-size: 0.875rem; line-height: 1.6; font-weight: 600;">
                            <li>Khusus Kepala Dinas, Kepala Badan, Asisten Sekda, Staf Ahli &amp; Sekda.</li>
                            <li>Bahan eksklusif premium dengan ketahanan bentuk lebih kokoh.</li>
                            <li>Aksen <strong>Bordir List Benang Emas Tebal</strong> di sekeliling mutz.</li>
                            <li>Harga resmi: <strong>Rp <?= number_format(HARGA_KEPALA, 0, ',', '.') ?></strong>.</li>
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 2px solid var(--dark); padding-bottom: 10px;">
                <h3 style="margin: 0; font-size: 1.2rem; font-weight: 900; color: inherit;" id="modalTitle">Konfirmasi Pemesanan</h3>
                <button type="button" onclick="closeOrderModal()" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; font-weight: 900; color: inherit; padding: 0 4px;">&times;</button>
            </div>

            <p style="font-size: 0.875rem; color: var(--gray); margin-top: 0; font-weight: 600;" id="modalSubtitle">
                Pesanan akan langsung diteruskan ke WhatsApp Pengurus/Admin Rekap KORPRI.
            </p>

            <div style="background: #F3F4F6; border: 2px solid #000; border-radius: 6px; padding: 12px; margin-bottom: 14px; color: #000;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="font-size: 0.8rem; font-weight: 700; color: #4B5563;">Kategori:</span>
                    <strong style="font-size: 0.85rem;" id="modalCat">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="font-size: 0.8rem; font-weight: 700; color: #4B5563;">Peruntukan:</span>
                    <strong style="font-size: 0.85rem;" id="modalGender">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="font-size: 0.8rem; font-weight: 700; color: #4B5563;">Nomor Ukuran:</span>
                    <strong style="font-size: 1rem; color: #2563EB;" id="modalSize">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 1px dashed #CBD5E1; padding-top: 4px; margin-top: 4px;">
                    <span style="font-size: 0.8rem; font-weight: 700; color: #4B5563;">Status Stok:</span>
                    <strong style="font-size: 0.85rem;" id="modalStockStatus">-</strong>
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 800; margin-bottom: 4px;">Nama Pemesan (Opsional):</label>
                <input type="text" id="orderNama" placeholder="Contoh: Budi Santoso, S.IP" style="width: 100%; border: 2px solid var(--dark); border-radius: 4px; font-weight: 600; box-sizing: border-box; background: var(--white); color: var(--dark);">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 800; margin-bottom: 4px;">Asal Dinas / SKPD (Opsional):</label>
                <input type="text" id="orderSkpd" placeholder="Contoh: Dinas Pendidikan" style="width: 100%; border: 2px solid var(--dark); border-radius: 4px; font-weight: 600; box-sizing: border-box; background: var(--white); color: var(--dark);">
            </div>

            <button type="button" onclick="sendWhatsAppOrder()" class="btn" style="width: 100%; background: #25D366; color: #000; font-size: 1rem; justify-content: center; padding: 12px; border: 2px solid #000; box-shadow: 3px 3px 0px #000; font-weight: 800; cursor: pointer;">
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

    <!-- Footer -->
    <footer class="public-footer">
        <div style="max-width: 600px; margin: 0 auto;">
            <img src="assets/images/logo.png?v=2" alt="Logo KORPRI" width="48" height="48" style="margin-bottom: 10px;">
            <div style="font-weight: 900; font-size: 1.1rem; text-transform: uppercase;">E-MutZ KORPRI</div>
            <p style="color: var(--gray); font-size: 0.85rem; margin: 6px 0 18px 0; font-weight: 600;">
                Sistem Informasi Rekapitulasi Pemesanan &amp; Manajemen Stok Mutz ASN.
            </p>
            <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;">
                <a href="#panduan" style="font-weight: 700; color: inherit; font-size: 0.85rem;">Panduan Ukuran</a>
                <span>&bull;</span>
                <a href="#spesifikasi" style="font-weight: 700; color: inherit; font-size: 0.85rem;">Spesifikasi Eselon II</a>
                <span>&bull;</span>
                <a href="login.php" style="font-weight: 700; color: inherit; font-size: 0.85rem;">Portal Admin</a>
            </div>
            <div style="margin-top: 1.5rem; font-size: 0.75rem; color: var(--gray); font-weight: 700;">
                &copy; <?= date('Y') ?> Dewan Pengurus KORPRI. Seluruh Hak Cipta Dilindungi.
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
            btnAll.textContent = '✨ Semua (' + allSizes.length + ')';
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
                let barColor = '#22C55E';
                let btnHtml = '';

                if (qty > 5) {
                    statusBadge = '<span class="status-badge badge-ready">🟢 Ready Stock</span>';
                    statusText = 'Tersedia';
                    barColor = '#22C55E';
                    btnHtml = `
                        <button type="button" class="btn-order-wa ready" onclick="openOrderModal('${currentCategory}', '${currentGender}', ${sz}, ${qty})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                            Pesan Ukuran ${sz} via WA
                        </button>
                    `;
                } else if (qty > 0) {
                    statusBadge = `<span class="status-badge badge-low">🟡 Sisa ${qty} Pcs</span>`;
                    statusText = 'Menipis';
                    barColor = '#F59E0B';
                    btnHtml = `
                        <button type="button" class="btn-order-wa ready" onclick="openOrderModal('${currentCategory}', '${currentGender}', ${sz}, ${qty})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                            Ambil Sisa ${qty} Pcs
                        </button>
                    `;
                } else {
                    statusBadge = '<span class="status-badge badge-empty">🔴 Stok Habis</span>';
                    statusText = 'Habis';
                    barColor = '#EF4444';
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

                    <div style="font-size: 0.8rem; font-weight: 600; color: var(--gray); margin-bottom: 14px; line-height: 1.4;">
                        ${currentCategory === 'Kepala SKPD' ? '👑 Eselon II (Bordir List Emas)' : '🏷️ Standar Mutz ASN KORPRI'} &bull; ${currentGender}
                    </div>

                    ${btnHtml}
                `;

                container.appendChild(card);
            });
        }

        // WhatsApp Ordering Modal Helpers
        function openOrderModal(category, gender, size, stock) {
            selectedOrderData = { category, gender, size, stock };

            document.getElementById('modalCat').textContent = category === 'Kepala SKPD' ? '👑 Kepala SKPD / Eselon II' : '🏷️ Mutz Standar ASN (Biasa)';
            document.getElementById('modalGender').textContent = gender;
            document.getElementById('modalSize').textContent = 'Nomor ' + size + ' (Lingkar ~' + size + ' cm)';
            
            const statusElem = document.getElementById('modalStockStatus');
            if (stock > 0) {
                statusElem.textContent = '🟢 Ready (' + stock + ' pcs tersedia)';
                statusElem.style.color = '#059669';
            } else {
                statusElem.textContent = '🔴 Habis (Diteruskan sebagai Pre-Order)';
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
                    alert('✅ Tautan Katalog Stok berhasil disalin! Anda dapat membagikannya ke WhatsApp.');
                });
            } else {
                prompt('Salin tautan ini:', url);
            }
        }

        // Back to top scroll listener
        window.addEventListener('scroll', function() {
            const btn = document.getElementById('btnScrollTop');
            if (btn) {
                if (window.scrollY > 300) {
                    btn.classList.add('visible');
                } else {
                    btn.classList.remove('visible');
                }
            }
        });

        // Initialize on Load
        document.addEventListener('DOMContentLoaded', function() {
            switchCategory('Biasa');
            updateGenderCounts();
            renderQuickSizeChips();
        });
    </script>
</body>
</html>
