<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* Efek Hover Mewah pada Menu */
.nav-links a {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
.nav-links a:hover {
    transform: translateX(6px);
    background-color: rgba(79, 70, 229, 0.08) !important;
}
.nav-links a svg {
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.nav-links a:hover svg {
    transform: scale(1.2) rotate(8deg);
}

/* Efek Animasi pada Logo */
.brand img {
    transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.brand:hover img {
    transform: scale(1.15) rotate(-6deg);
}

/* SweetAlert2 Layer Guarantee */
.swal2-container {
    z-index: 100000 !important;
}

/* =========================================================
   Standalone Bulletproof Spotlight Modal CSS
   ========================================================= */
.spotlight-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 999999 !important;
    background: rgba(15, 23, 42, 0.65) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    display: none;
    justify-content: center !important;
    align-items: flex-start !important;
    padding: 60px 16px 20px !important;
    box-sizing: border-box !important;
    margin: 0 !important;
}
.spotlight-overlay.active {
    display: flex !important;
}
.spotlight-dialog {
    width: 100% !important;
    max-width: 660px !important;
    background: #FFFFFF !important;
    border-radius: 16px !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    max-height: 80vh !important;
    border: 1px solid #E5E7EB !important;
    animation: spotlightPop 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
@keyframes spotlightPop {
    from { opacity: 0; transform: scale(0.95) translateY(-10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
[data-theme="dark"] .spotlight-dialog {
    background: #1E293B !important;
    border-color: #334155 !important;
}
.spotlight-header {
    display: flex !important;
    align-items: center !important;
    padding: 1rem 1.25rem !important;
    border-bottom: 1px solid #E5E7EB !important;
    gap: 12px !important;
    background: inherit !important;
}
.spotlight-icon {
    color: #4F46E5 !important;
    flex-shrink: 0 !important;
}
.spotlight-input {
    flex: 1 !important;
    border: none !important;
    outline: none !important;
    font-size: 1.05rem !important;
    background: transparent !important;
    color: inherit !important;
    font-family: inherit !important;
}
.spotlight-close-btn {
    background: transparent !important;
    border: none !important;
    color: #6B7280 !important;
    cursor: pointer !important;
    padding: 6px !important;
    border-radius: 6px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.spotlight-close-btn:hover {
    background: rgba(0,0,0,0.05) !important;
    color: #111827 !important;
}
.spotlight-filters {
    display: flex !important;
    gap: 8px !important;
    padding: 10px 1.25rem !important;
    background: #F9FAFB !important;
    border-bottom: 1px solid #E5E7EB !important;
    overflow-x: auto !important;
}
[data-theme="dark"] .spotlight-filters {
    background: #0F172A !important;
    border-color: #334155 !important;
}
.spotlight-filter-btn {
    background: #FFFFFF !important;
    border: 1px solid #E5E7EB !important;
    color: #4B5563 !important;
    padding: 6px 14px !important;
    border-radius: 20px !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    white-space: nowrap !important;
    transition: all 0.2s !important;
}
.spotlight-filter-btn:hover {
    border-color: #4F46E5 !important;
    color: #4F46E5 !important;
}
.spotlight-filter-btn.active {
    background: #4F46E5 !important;
    color: #FFFFFF !important;
    border-color: #4F46E5 !important;
}
.spotlight-results {
    padding: 10px 0 !important;
    overflow-y: auto !important;
    max-height: 52vh !important;
    display: flex !important;
    flex-direction: column !important;
}
.spotlight-group-title {
    font-size: 0.75rem !important;
    font-weight: 700 !important;
    color: #6B7280 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 8px 1.25rem 4px !important;
}
.spotlight-item {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 10px 1.25rem !important;
    text-decoration: none !important;
    color: inherit !important;
    transition: background 0.15s !important;
    border-left: 3px solid transparent !important;
    cursor: pointer !important;
}
.spotlight-item:hover, .spotlight-item.active {
    background: rgba(79, 70, 229, 0.06) !important;
    border-left-color: #4F46E5 !important;
}
.spotlight-item-left {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    flex: 1 !important;
    min-width: 0 !important;
}
.spotlight-item-icon {
    width: 36px !important;
    height: 36px !important;
    border-radius: 8px !important;
    background: #EEF2FF !important;
    color: #4F46E5 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
}
.spotlight-item-info {
    flex: 1 !important;
    min-width: 0 !important;
}
.spotlight-item-title {
    font-weight: 600 !important;
    font-size: 0.925rem !important;
    color: #111827 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}
[data-theme="dark"] .spotlight-item-title {
    color: #F8FAFC !important;
}
.spotlight-item-subtitle {
    font-size: 0.8rem !important;
    color: #6B7280 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}
.spotlight-item-badge {
    font-size: 0.725rem !important;
    font-weight: 600 !important;
    padding: 3px 8px !important;
    border-radius: 6px !important;
    white-space: nowrap !important;
    flex-shrink: 0 !important;
}
.spotlight-footer {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 10px 1.25rem !important;
    background: #F9FAFB !important;
    border-top: 1px solid #E5E7EB !important;
    font-size: 0.75rem !important;
    color: #6B7280 !important;
}
[data-theme="dark"] .spotlight-footer {
    background: #0F172A !important;
    border-color: #334155 !important;
}
.spotlight-shortcuts {
    display: flex !important;
    gap: 12px !important;
}
.spotlight-shortcuts kbd {
    background: #E5E7EB !important;
    color: #374151 !important;
    padding: 1px 5px !important;
    border-radius: 4px !important;
    font-size: 0.7rem !important;
    font-weight: 600 !important;
}
.spotlight-empty {
    padding: 2.5rem 1.5rem !important;
    text-align: center !important;
    color: #6B7280 !important;
}

/* Premium Search Loading Animation */
@keyframes searchSpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes orbitDot {
    0% { transform: rotate(0deg) translateX(18px) rotate(0deg); opacity: 0.3; }
    50% { opacity: 1; }
    100% { transform: rotate(360deg) translateX(18px) rotate(-360deg); opacity: 0.3; }
}
@keyframes pulseRing {
    0% { transform: scale(0.8); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.2; }
    100% { transform: scale(0.8); opacity: 0.5; }
}
@keyframes textShift {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 1; }
}
.search-loading-container {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    padding: 2rem 1.5rem !important;
    gap: 1.25rem !important;
    animation: fadeInUp 0.3s ease !important;
}
.search-orbit-loader {
    position: relative !important;
    width: 48px !important;
    height: 48px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.search-orbit-ring {
    position: absolute !important;
    inset: 0 !important;
    border: 2.5px solid transparent !important;
    border-top-color: #4F46E5 !important;
    border-right-color: #818CF8 !important;
    border-radius: 50% !important;
    animation: searchSpin 0.9s cubic-bezier(0.5, 0, 0.5, 1) infinite !important;
}
.search-orbit-ring-inner {
    position: absolute !important;
    inset: 6px !important;
    border: 2px solid transparent !important;
    border-bottom-color: #C084FC !important;
    border-left-color: #A78BFA !important;
    border-radius: 50% !important;
    animation: searchSpin 0.6s cubic-bezier(0.5, 0, 0.5, 1) infinite reverse !important;
}
.search-orbit-dot {
    position: absolute !important;
    width: 6px !important;
    height: 6px !important;
    background: #4F46E5 !important;
    border-radius: 50% !important;
    animation: orbitDot 1.2s linear infinite !important;
    box-shadow: 0 0 6px rgba(79, 70, 229, 0.5) !important;
}
.search-orbit-dot:nth-child(2) { animation-delay: -0.4s !important; background: #818CF8 !important; }
.search-orbit-dot:nth-child(3) { animation-delay: -0.8s !important; background: #C084FC !important; }
.search-orbit-core {
    width: 10px !important;
    height: 10px !important;
    background: linear-gradient(135deg, #4F46E5, #A78BFA) !important;
    border-radius: 50% !important;
    animation: pulseRing 1.2s ease-in-out infinite !important;
    box-shadow: 0 0 12px rgba(79, 70, 229, 0.4) !important;
}
.search-loading-text {
    font-size: 0.85rem !important;
    font-weight: 600 !important;
    color: #4F46E5 !important;
    animation: textShift 1.5s ease-in-out infinite !important;
    letter-spacing: 0.01em !important;
}
.search-loading-sub {
    font-size: 0.75rem !important;
    color: #9CA3AF !important;
    margin-top: -6px !important;
}
.search-skeleton {
    width: 100% !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 6px !important;
    padding: 0 1.25rem !important;
    box-sizing: border-box !important;
}
.search-skeleton-row {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    padding: 10px 0 !important;
    animation: fadeInUp 0.4s ease both !important;
}
.search-skeleton-row:nth-child(1) { animation-delay: 0.05s !important; }
.search-skeleton-row:nth-child(2) { animation-delay: 0.12s !important; }
.search-skeleton-row:nth-child(3) { animation-delay: 0.19s !important; }
.search-skeleton-avatar {
    width: 36px !important;
    height: 36px !important;
    border-radius: 8px !important;
    background: linear-gradient(90deg, #E5E7EB 25%, #F3F4F6 50%, #E5E7EB 75%) !important;
    background-size: 200% 100% !important;
    animation: shimmer 1.5s ease-in-out infinite !important;
    flex-shrink: 0 !important;
}
.search-skeleton-lines {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 6px !important;
}
.search-skeleton-line {
    height: 10px !important;
    border-radius: 5px !important;
    background: linear-gradient(90deg, #E5E7EB 25%, #F3F4F6 50%, #E5E7EB 75%) !important;
    background-size: 200% 100% !important;
    animation: shimmer 1.5s ease-in-out infinite !important;
}
.search-skeleton-line.w-75 { width: 75% !important; }
.search-skeleton-line.w-50 { width: 50% !important; }
.search-skeleton-line.w-60 { width: 60% !important; }
.search-skeleton-line.w-40 { width: 40% !important; }
.search-skeleton-badge {
    width: 56px !important;
    height: 22px !important;
    border-radius: 6px !important;
    background: linear-gradient(90deg, #E5E7EB 25%, #F3F4F6 50%, #E5E7EB 75%) !important;
    background-size: 200% 100% !important;
    animation: shimmer 1.5s ease-in-out infinite !important;
    flex-shrink: 0 !important;
}
[data-theme="dark"] .search-skeleton-avatar,
[data-theme="dark"] .search-skeleton-line,
[data-theme="dark"] .search-skeleton-badge {
    background: linear-gradient(90deg, #334155 25%, #475569 50%, #334155 75%) !important;
    background-size: 200% 100% !important;
}
[data-theme="dark"] .search-loading-text { color: #A78BFA !important; }

/* =========================================================
   Desktop Floating Controls (Search Bar & Theme Toggle)
   ========================================================= */
.desktop-floating-actions {
    position: fixed !important;
    top: 1.5rem !important;
    right: 2rem !important;
    z-index: 1000 !important;
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    pointer-events: auto !important;
}

.floating-search-btn {
    display: inline-flex !important;
    align-items: center !important;
    gap: 10px !important;
    background: #FFFFFF !important;
    color: #4B5563 !important;
    border: 1px solid #E5E7EB !important;
    border-radius: 30px !important;
    padding: 8px 18px !important;
    font-size: 0.85rem !important;
    font-weight: 500 !important;
    cursor: pointer !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    height: 44px !important;
    box-sizing: border-box !important;
}

.floating-search-btn:hover {
    border-color: #4F46E5 !important;
    color: #4F46E5 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 18px rgba(79, 70, 229, 0.18) !important;
}

.floating-search-btn svg {
    color: #4F46E5 !important;
    flex-shrink: 0 !important;
}

.floating-search-text {
    font-size: 0.85rem !important;
    white-space: nowrap !important;
}

.floating-search-kbd {
    background: #F3F4F6 !important;
    color: #6B7280 !important;
    border: 1px solid #E5E7EB !important;
    border-radius: 6px !important;
    padding: 2px 6px !important;
    font-size: 0.725rem !important;
    font-weight: 700 !important;
}

.floating-theme-btn {
    background: #FFFFFF !important;
    color: #1F2937 !important;
    border: 1px solid #E5E7EB !important;
    border-radius: 50% !important;
    width: 44px !important;
    height: 44px !important;
    min-width: 44px !important;
    min-height: 44px !important;
    cursor: pointer !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    padding: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-sizing: border-box !important;
}

.floating-theme-btn:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12) !important;
    border-color: #4F46E5 !important;
    color: #4F46E5 !important;
}

[data-theme="dark"] .floating-search-btn {
    background: #1E293B !important;
    color: #E2E8F0 !important;
    border-color: #334155 !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.4) !important;
}
[data-theme="dark"] .floating-search-kbd {
    background: #0F172A !important;
    color: #94A3B8 !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .floating-theme-btn {
    background: #1E293B !important;
    color: #F8FAFC !important;
    border-color: #334155 !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.4) !important;
}

@media (max-width: 991px) {
    .desktop-floating-actions {
        display: none !important;
    }
}
</style>
<script>
    if(localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark');

    document.addEventListener('DOMContentLoaded', function() {
        const themeToggle = document.getElementById('themeToggle');
        const themeToggleMobile = document.getElementById('themeToggleMobile');
        const svgDark = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block; margin:auto;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';
        const svgLight = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block; margin:auto;"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>';

        function updateThemeIcons() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (themeToggle) themeToggle.innerHTML = isDark ? svgLight : svgDark;
            if (themeToggleMobile) themeToggleMobile.innerHTML = isDark ? svgLight : svgDark;
        }

        updateThemeIcons();

        function toggleTheme() {
            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
            updateThemeIcons();
        }

        if (themeToggle) themeToggle.addEventListener('click', toggleTheme);
        if (themeToggleMobile) themeToggleMobile.addEventListener('click', toggleTheme);

        // Mobile Sidebar Drawer Logic
        const sidebar = document.querySelector('.sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            if (sidebar) sidebar.classList.add('open');
            if (sidebarOverlay) sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('open');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (sidebarToggle) sidebarToggle.addEventListener('click', openSidebar);
        if (sidebarCloseBtn) sidebarCloseBtn.addEventListener('click', closeSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

        // Close on link click on mobile
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 991) {
                    closeSidebar();
                }
            });
        });
    });
</script>

<!-- Mobile Top Navbar -->
<aside class="sidebar">
    <div class="brand">
        <div style="display: flex; align-items: center; gap: 10px;">
            <img src="assets/images/logo.png?v=2" alt="Logo E-MutZ" style="width: 48px; height: 48px; object-fit: contain; border-radius: 8px;">
            <span>E-MutZ KORPRI</span>
        </div>
        <button class="sidebar-close-btn" id="sidebarCloseBtn" title="Tutup Menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <ul class="nav-links">
        <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Dashboard</a>
        </li>
        <li><a href="skpd.php" class="<?= basename($_SERVER['PHP_SELF']) == 'skpd.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            Data SKPD</a>
        </li>
        <li><a href="pesanan.php" class="<?= basename($_SERVER['PHP_SELF']) == 'pesanan.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Input Pesanan</a>
        </li>
        <li><a href="rekap.php" class="<?= basename($_SERVER['PHP_SELF']) == 'rekap.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
            Rekapitulasi</a>
        </li>
        <li><a href="retur.php" class="<?= basename($_SERVER['PHP_SELF']) == 'retur.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path><path d="M3 22v-6h6"></path><path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path></svg>
            Riwayat Retur</a>
        </li>
        <li><a href="stok.php" class="<?= basename($_SERVER['PHP_SELF']) == 'stok.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
            Manajemen Stok</a>
        </li>
        <li><a href="pengaturan.php" class="<?= basename($_SERVER['PHP_SELF']) == 'pengaturan.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            Pengaturan</a>
        </li>
        <li id="pwaInstallItem" style="margin-top: 0.75rem;">
            <a href="#" id="btnPwaInstall" style="background: rgba(79, 70, 229, 0.08); color: var(--primary); font-weight: 600; border-radius: 8px; border: 1px dashed rgba(79, 70, 229, 0.4);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line><polyline points="9 9 12 12 15 9"></polyline><line x1="12" y1="6" x2="12" y2="12"></line></svg>
                📲 Pasang Aplikasi</a>
        </li>
        <li style="margin-top: 1.5rem;"><a href="logout.php" id="btnLogout" style="color: var(--danger);">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Keluar (Logout)</a>
        </li>
    </ul>
</aside>

<!-- Mobile Top Navbar (Fixed/Sticky on mobile) -->
<header class="mobile-topbar hide-on-print">
    <button id="sidebarToggle" class="btn-hamburger" title="Buka Menu" aria-label="Buka Menu" type="button">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
    </button>
    <a href="index.php" class="mobile-topbar-brand">
        <img src="assets/images/logo.png?v=2" alt="Logo" style="width: 32px; height: 32px; object-fit: contain;">
        <span>E-MutZ KORPRI</span>
    </a>
    <div class="mobile-topbar-actions" style="display: flex; align-items: center; gap: 6px;">
        <button id="btnSpotlightTriggerMobile" class="btn-hamburger" title="Pencarian Cepat" aria-label="Pencarian Cepat" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </button>
        <button id="themeToggleMobile" class="btn-hamburger" title="Ganti Tema" aria-label="Ganti Tema" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
        </button>
    </div>
</header>

<!-- Backdrop Overlay for Mobile Drawer -->
<div class="sidebar-overlay hide-on-print" id="sidebarOverlay"></div>

<!-- Desktop Floating Controls (Search Bar & Theme Toggle - Fixed on Top-Right on All Pages) -->
<div class="desktop-floating-actions hide-on-print">
    <button id="btnSpotlightTrigger" class="floating-search-btn" title="Pencarian Cepat (Ctrl + K / ⌘K)" type="button">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <span class="floating-search-text">Cari Pesanan / SKPD...</span>
        <kbd class="floating-search-kbd">⌘K</kbd>
    </button>
    <button id="themeToggle" class="floating-theme-btn" title="Ganti Tema (Terang/Gelap)" type="button">
    </button>
</div>

<!-- Spotlight Quick Search Modal (Command Palette) -->
<div id="spotlightModal" class="spotlight-overlay hide-on-print" style="display: none;">
    <div class="spotlight-dialog" role="dialog" aria-modal="true">
        <!-- Search Header -->
        <div class="spotlight-header">
            <svg class="spotlight-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="spotlightInput" class="spotlight-input" placeholder="Ketik nama pemesan, SKPD, nomor WA, ukuran, atau menu..." autocomplete="off" spellcheck="false">
            <button id="btnSpotlightClose" class="spotlight-close-btn" title="Tutup Pencarian (Esc)" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <!-- Filter Tags -->
        <div class="spotlight-filters">
            <button class="spotlight-filter-btn active" data-filter="all" type="button">Semua</button>
            <button class="spotlight-filter-btn" data-filter="pesanan" type="button">👤 Pemesan</button>
            <button class="spotlight-filter-btn" data-filter="skpd" type="button">🏛️ SKPD</button>
            <button class="spotlight-filter-btn" data-filter="menus" type="button">⚡ Menu Cepat</button>
        </div>

        <!-- Results List -->
        <div id="spotlightResults" class="spotlight-results">
            <!-- Rendered dynamically via JS -->
        </div>

        <!-- Footer -->
        <div class="spotlight-footer">
            <div class="spotlight-shortcuts">
                <span><kbd>↑</kbd><kbd>↓</kbd> Pilih</span>
                <span><kbd>↵</kbd> Buka</span>
                <span><kbd>Esc</kbd> Tutup</span>
            </div>
            <span>E-MutZ Spotlight</span>
        </div>
    </div>
</div>

<!-- SweetAlert2 & Instant Navigation Engine -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/instant-nav.js?v=<?= time() ?>"></script>
<script>
// Global Toast Configuration for Premium UI
window.Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // 1. URL Notification Parsing
    const urlParams = new URLSearchParams(window.location.search);
    const notif = urlParams.get('notif');
    if (notif) {
        let title = 'Berhasil';
        let text = 'Aksi berhasil dilakukan';
        let icon = 'success';
        
        switch(notif) {
            case 'bayar_sukses': text = 'Pembayaran berhasil dikonfirmasi!'; break;
            case 'ambil_sukses': text = 'Status pengambilan berhasil diubah!'; break;
            case 'ambil_semua_sukses': text = 'Semua pesanan SKPD berhasil ditandai SUDAH DIAMBIL!'; break;
            case 'bayar_semua_sukses': text = 'Semua pembayaran SKPD berhasil dikonfirmasi LUNAS!'; break;
            case 'hapus_sukses': text = 'Data berhasil dihapus!'; break;
            case 'simpan_sukses': text = 'Data berhasil disimpan!'; break;
            case 'edit_sukses': text = 'Data berhasil diperbarui!'; break;
            case 'pengaturan_sukses': text = 'Pengaturan berhasil disimpan!'; break;
        }
        window.Toast.fire({ icon: icon, title: text });
        // Clean URL
        window.history.replaceState(null, null, window.location.pathname);
    }
    
    const errorMsg = urlParams.get('error_msg');
    if (errorMsg) {
        Swal.fire({ title: 'Gagal', text: decodeURIComponent(errorMsg), icon: 'error', confirmButtonColor: '#EF4444' });
        window.history.replaceState(null, null, window.location.pathname);
    }

    // 2. Global Confirm Action (replaces native confirm())
    document.querySelectorAll('.btn-confirm').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            const title = this.getAttribute('data-confirm-title') || 'Konfirmasi';
            const text = this.getAttribute('data-confirm-text') || 'Apakah Anda yakin?';
            const confirmText = this.getAttribute('data-confirm-btn') || 'Ya, Lanjutkan';
            const confirmColor = this.getAttribute('data-confirm-color') || '#EF4444';
            
            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#6B7280',
                confirmButtonText: confirmText,
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });

    // 3. Animated Logout & Cache Clearing Handler
    function performAnimatedLogout(url) {
        Swal.fire({
            title: '',
            html: `
                <style>
                @keyframes spinFast { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                @keyframes gradientGlow { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
                @keyframes popIn { 0% { opacity: 0; transform: scale(0.9) translateY(10px); } 100% { opacity: 1; transform: scale(1) translateY(0); } }
                .swal-logout-container { padding: 10px; display: flex; flex-direction: column; align-items: center; animation: popIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
                .swal-spinner-ring { width: 64px; height: 64px; border-radius: 50%; border: 4px solid var(--gray-light); border-top-color: var(--primary); animation: spinFast 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite; margin-bottom: 24px; position: relative; }
                .swal-spinner-ring::after { content: ''; position: absolute; top: -4px; left: -4px; right: -4px; bottom: -4px; border-radius: 50%; border: 4px solid transparent; border-bottom-color: #EC4899; animation: spinFast 1.2s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite reverse; }
                .swal-step-title { font-size: 1.25rem; font-weight: 800; color: var(--dark); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
                .swal-step-desc { font-size: 0.95rem; font-weight: 500; color: var(--gray); margin-bottom: 24px; height: 22px; transition: color 0.3s; }
                .swal-progress-track { width: 100%; height: 8px; background: var(--gray-light); border-radius: 999px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); }
                .swal-progress-fill { width: 0%; height: 100%; background: linear-gradient(90deg, #4F46E5, #06B6D4, #EC4899, #4F46E5); background-size: 300% 100%; border-radius: 999px; transition: width 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); animation: gradientGlow 2s linear infinite; }
                </style>
                <div class="swal-logout-container">
                    <div class="swal-spinner-ring" id="swal-spinner"></div>
                    <div class="swal-step-title" id="swal-step-title">MEMUTUS KONEKSI</div>
                    <div class="swal-step-desc" id="swal-step-text">Menginisialisasi penutupan sesi...</div>
                    <div class="swal-progress-track">
                        <div id="swal-progress-bar" class="swal-progress-fill"></div>
                    </div>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            background: 'var(--white)',
            color: 'var(--dark)',
            backdrop: `rgba(15, 23, 42, 0.85) backdrop-filter: blur(8px)`,
            didOpen: () => {
                const progressBar = document.getElementById('swal-progress-bar');
                const stepText = document.getElementById('swal-step-text');
                const stepTitle = document.getElementById('swal-step-title');
                const spinner = document.getElementById('swal-spinner');
                
                // Bersihkan client storage
                try {
                    sessionStorage.clear();
                    const theme = localStorage.getItem('theme');
                    localStorage.clear();
                    if (theme) localStorage.setItem('theme', theme);
                    
                    if ('caches' in window) {
                        caches.keys().then(names => {
                            names.forEach(name => caches.delete(name));
                        });
                    }
                } catch(err) {
                    console.error(err);
                }

                setTimeout(() => {
                    if (progressBar) progressBar.style.width = '35%';
                    if (stepText) stepText.innerHTML = '🧹 Menghapus cache & rekam jejak lokal...';
                    if (stepTitle) stepTitle.innerHTML = 'MEMBERSIHKAN CACHE';
                }, 300);

                setTimeout(() => {
                    if (progressBar) progressBar.style.width = '75%';
                    if (stepText) stepText.innerHTML = '🔒 Mencabut token autentikasi & mengunci sesi...';
                    if (stepTitle) stepTitle.innerHTML = 'MENGUNCI AKSES';
                }, 800);

                setTimeout(() => {
                    if (progressBar) progressBar.style.width = '100%';
                    if (stepText) { stepText.innerHTML = '✨ Sesi Anda telah sepenuhnya ditutup.'; stepText.style.color = '#10B981'; }
                    if (stepTitle) { stepTitle.innerHTML = 'LOGOUT BERHASIL'; stepTitle.style.color = '#10B981'; }
                    if (spinner) {
                        spinner.style.animation = 'none';
                        spinner.style.border = 'none';
                        spinner.innerHTML = `<svg style="width:100%; height:100%; color:#10B981; animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
                    }
                }, 1400);

                setTimeout(() => {
                    window.location.href = url;
                }, 2200);
            }
        });
    }

    const logoutButtons = document.querySelectorAll('#btnLogout, a[href="logout.php"], .btn-logout');
    logoutButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href') || 'logout.php';
            Swal.fire({
                title: '',
                html: `
                    <div style="display:flex; flex-direction:column; align-items:center; margin-bottom: 20px;">
                        <div style="width: 72px; height: 72px; border-radius: 24px; background: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; border: 1px solid rgba(239, 68, 68, 0.2); box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.3);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        </div>
                        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--dark); margin: 0 0 8px 0;">Konfirmasi Keluar</h2>
                        <div style="font-size: 0.95rem; color: var(--gray); line-height: 1.5; text-align: center; padding: 0 10px;">
                            Apakah Anda yakin ingin keluar dari sistem <strong>E-MutZ KORPRI</strong>?
                        </div>
                    </div>
                    <div style="padding: 12px 16px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; font-size: 0.85rem; color: #D97706; text-align: left; display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <span style="font-weight: 500; line-height: 1.4;">Sistem akan memutus koneksi aman Anda dan membersihkan memori sementara browser.</span>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: 'var(--gray-light)',
                background: 'var(--white)',
                backdrop: `rgba(15, 23, 42, 0.65) backdrop-filter: blur(4px)`,
                customClass: {
                    cancelButton: 'swal2-cancel-custom'
                },
                confirmButtonText: '<span style="font-weight: 700; letter-spacing: 0.5px;">YA, KELUAR SISTEM</span>',
                cancelButtonText: '<span style="font-weight: 600; color: var(--dark);">Batal</span>',
                reverseButtons: true,
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    performAnimatedLogout(url);
                }
            });
        });
    });

    // 4. Global WhatsApp Direct Notification Handler
    window.openWhatsAppSender = function({ skpdName, targetWa, totalQty, totalRp, statusBayar, statusAmbil }) {
        // Normalisasi nomor telepon
        let cleanWa = (targetWa || '').replace(/[^0-9]/g, '');
        if (cleanWa.startsWith('0')) {
            cleanWa = '62' + cleanWa.substring(1);
        }

        const tplReady = `Yth. Pengurus / Bendahara *${skpdName}*,\n\nKami informasikan dari *Sekretariat KORPRI* bahwa pemesanan Topi MutZ KORPRI sebanyak *${totalQty} pcs* (Total: ${totalRp}) saat ini statusnya: *SIAP DIAMBIL* 📦.\n\nStatus Pembayaran: *${statusBayar}*\n\nMohon dapat diambil pada jam kerja di Sekretariat KORPRI. Terima kasih banyak. 🙏`;

        const tplTagihan = `Yth. Bendahara / Kontak *${skpdName}*,\n\nBerikut kami sampaikan rincian tagihan pemesanan Topi MutZ KORPRI untuk *${skpdName}*:\n• Jumlah Pesanan: *${totalQty} pcs*\n• Total Tagihan: *${totalRp}*\n• Status Bayar: *${statusBayar}*\n• Status Barang: *${statusAmbil}*\n\nMohon untuk dapat segera melakukan konfirmasi pembayaran. Terima kasih atas perhatian dan kerjasamanya. 🙏`;

        const tplSelesai = `Yth. Pengurus / Perwakilan *${skpdName}*,\n\nTerima kasih, kami konfirmasikan bahwa pemesanan Topi MutZ KORPRI sebanyak *${totalQty} pcs* untuk *${skpdName}* telah *SELESAI & DITERIMA* dengan status pembayaran: *LUNAS* ✅.\n\nTerima kasih atas kerjasamanya! 🙏✨`;

        const tplCustom = `Yth. Pengurus *${skpdName}*,\n\n`;

        Swal.fire({
            title: '<span style="display:inline-flex; align-items:center; gap:8px; color:#059669;"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" /><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" /></svg> Notifikasi WhatsApp</span>',
            html: `
                <div style="text-align: left; font-size: 0.9rem;">
                    <div style="margin-bottom: 12px; background: #ECFDF5; border: 1px solid #A7F3D0; padding: 10px 14px; border-radius: 8px; color: #065F46;">
                        <strong style="font-size: 1rem;">${skpdName}</strong><br>
                        <span style="font-size: 0.825rem; color: #047857;">Pesanan: <b>${totalQty} pcs</b> | Total: <b>${totalRp}</b> | Bayar: <b>${statusBayar}</b></span>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="font-weight: 600; display: block; margin-bottom: 4px; color: var(--dark); font-size: 0.85rem;">Nomor WhatsApp Tujuan:</label>
                        <input type="text" id="swal-wa-phone" class="swal2-input" style="width: 100%; margin: 0; padding: 8px 12px; height: 38px; font-size: 0.9rem; box-sizing: border-box;" placeholder="Contoh: 08123456789 (Kosongkan jika ingin memilih kontak manual di WA)" value="${cleanWa}">
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="font-weight: 600; display: block; margin-bottom: 4px; color: var(--dark); font-size: 0.85rem;">Pilih Format Template Pesan:</label>
                        <select id="swal-wa-tpl" class="swal2-select" style="width: 100%; margin: 0; padding: 8px 12px; height: 38px; font-size: 0.85rem; border-radius: 6px; box-sizing: border-box;">
                            <option value="ready">📦 1. Pemberitahuan Siap Diambil</option>
                            <option value="tagihan">💳 2. Pemberitahuan Tagihan Pembayaran</option>
                            <option value="selesai">✅ 3. Konfirmasi Pesanan Selesai & Lunas</option>
                            <option value="custom">✏️ 4. Tulis Pesan Bebas (Kustom)</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 4px;">
                        <label style="font-weight: 600; display: block; margin-bottom: 4px; color: var(--dark); font-size: 0.85rem;">Pratinjau Pesan WhatsApp (Dapat Diedit):</label>
                        <textarea id="swal-wa-text" class="swal2-textarea" style="width: 100%; margin: 0; padding: 10px; height: 130px; font-size: 0.825rem; font-family: monospace; line-height: 1.4; resize: vertical; border-radius: 6px; box-sizing: border-box;"></textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: '#25D366',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<span style="display:inline-flex; align-items:center; gap:6px; font-weight:700;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" /><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" /></svg> Buka di WhatsApp</span>',
            cancelButtonText: 'Batal',
            didOpen: () => {
                const selectTpl = document.getElementById('swal-wa-tpl');
                const txtArea = document.getElementById('swal-wa-text');

                // Template default berdasarkan status
                if (statusBayar !== 'Lunas') {
                    selectTpl.value = 'tagihan';
                    txtArea.value = tplTagihan;
                } else if (statusAmbil === 'Sudah Diambil') {
                    selectTpl.value = 'selesai';
                    txtArea.value = tplSelesai;
                } else {
                    selectTpl.value = 'ready';
                    txtArea.value = tplReady;
                }

                selectTpl.addEventListener('change', function() {
                    switch(this.value) {
                        case 'ready': txtArea.value = tplReady; break;
                        case 'tagihan': txtArea.value = tplTagihan; break;
                        case 'selesai': txtArea.value = tplSelesai; break;
                        case 'custom': txtArea.value = tplCustom; break;
                    }
                });
            }
        }).then((res) => {
            if (res.isConfirmed) {
                const phoneInput = (document.getElementById('swal-wa-phone').value || '').trim();
                const message = (document.getElementById('swal-wa-text').value || '').trim();

                let targetPhone = phoneInput.replace(/[^0-9]/g, '');
                if (targetPhone.startsWith('0')) {
                    targetPhone = '62' + targetPhone.substring(1);
                }

                let waUrl = '';
                if (targetPhone) {
                    waUrl = `https://api.whatsapp.com/send?phone=${targetPhone}&text=${encodeURIComponent(message)}`;
                } else {
                    waUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`;
                }

                window.open(waUrl, '_blank');
            }
        });
    };

    // Global Event Listener for all .btn-wa-notify
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-wa-notify');
        if (btn) {
            e.preventDefault();
            const skpdName = btn.getAttribute('data-skpd') || '';
            const targetWa = btn.getAttribute('data-wa') || '';
            const totalQty = btn.getAttribute('data-total-qty') || '0';
            const totalRp = btn.getAttribute('data-total-rp') || 'Rp 0';
            const statusBayar = btn.getAttribute('data-status-bayar') || 'Belum Lunas';
            const statusAmbil = btn.getAttribute('data-status-ambil') || 'Belum Diambil';

            window.openWhatsAppSender({ skpdName, targetWa, totalQty, totalRp, statusBayar, statusAmbil });
        }
    });

    // 5. Progressive Web App (PWA) Handler & Service Worker Registration
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js')
            .then(reg => console.log('E-MutZ PWA Service Worker Registered! Scope:', reg.scope))
            .catch(err => console.warn('E-MutZ PWA SW Registration failed:', err));
    }

    let deferredPwaPrompt = null;
    const btnPwaInstall = document.getElementById('btnPwaInstall');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPwaPrompt = e;
        // Tampilkan tombol pasang aplikasi dengan badge menarik
        if (btnPwaInstall) {
            btnPwaInstall.style.animation = 'pulse 2s infinite';
        }
    });

    if (btnPwaInstall) {
        btnPwaInstall.addEventListener('click', async (e) => {
            e.preventDefault();
            if (deferredPwaPrompt) {
                deferredPwaPrompt.prompt();
                const { outcome } = await deferredPwaPrompt.userChoice;
                if (outcome === 'accepted') {
                    const pwaItem = document.getElementById('pwaInstallItem');
                    if (pwaItem) pwaItem.style.display = 'none';
                }
                deferredPwaPrompt = null;
            } else {
                // Panduan interaktif jika dibuka di browser yang tidak trigger beforeinstallprompt (seperti Safari iOS)
                Swal.fire({
                    title: '<span style="display:inline-flex; align-items:center; gap:8px; color:var(--primary);"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line><polyline points="9 9 12 12 15 9"></polyline><line x1="12" y1="6" x2="12" y2="12"></line></svg> Pasang Aplikasi di HP</span>',
                    html: `
                        <div style="text-align:left; font-size:0.875rem;">
                            <p style="margin-bottom:12px; color:var(--dark);">Pasang <b>E-MutZ KORPRI</b> di layar utama HP / Tablet Anda agar dapat dibuka seperti aplikasi resmi tanpa perlu ketik link:</p>
                            
                            <div style="background:#F0FDF4; border:1px solid #BBF7D0; padding:12px; border-radius:8px; margin-bottom:10px; color:#166534;">
                                <strong style="display:flex; align-items:center; gap:5px; margin-bottom:4px; font-size:0.9rem;">
                                    🤖 Pengguna Android (Chrome / Edge):
                                </strong>
                                1. Ketuk menu titik tiga (<b>⋮</b>) di kanan atas browser.<br>
                                2. Pilih menu <b>"Install app"</b> atau <b>"Tambahkan ke Layar Utama"</b>.
                            </div>

                            <div style="background:#EFF6FF; border:1px solid #BFDBFE; padding:12px; border-radius:8px; color:#1E40AF;">
                                <strong style="display:flex; align-items:center; gap:5px; margin-bottom:4px; font-size:0.9rem;">
                                    🍎 Pengguna iPhone / iPad (Safari):
                                </strong>
                                1. Ketuk ikon <b>Bagikan (Share / Kotak Panah Atas)</b> di bilah bawah Safari.<br>
                                2. Gulir ke bawah dan pilih <b>"Add to Home Screen"</b> (Tambah ke Layar Utama).
                            </div>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonColor: 'var(--primary)',
                    confirmButtonText: 'Saya Mengerti 👍'
                });
            }
        });
    }

    window.addEventListener('appinstalled', () => {
        const pwaItem = document.getElementById('pwaInstallItem');
        if (pwaItem) pwaItem.style.display = 'none';
        deferredPwaPrompt = null;
        Swal.fire({
            title: 'Aplikasi Terpasang! 🎉',
            text: 'E-MutZ KORPRI sekarang dapat dibuka langsung dari layar utama HP atau Komputer Anda.',
            icon: 'success',
            confirmButtonColor: '#10B981'
        });
    });

    // 6. Global Spotlight Search Controller (Ctrl + K / ⌘K)
    const spotlightModal = document.getElementById('spotlightModal');
    const spotlightInput = document.getElementById('spotlightInput');
    const spotlightResults = document.getElementById('spotlightResults');
    const btnSpotlightClose = document.getElementById('btnSpotlightClose');
    const btnSpotlightTrigger = document.getElementById('btnSpotlightTrigger');
    const btnSpotlightTriggerMobile = document.getElementById('btnSpotlightTriggerMobile');
    const filterBtns = document.querySelectorAll('.spotlight-filter-btn');

    let currentFilter = 'all';
    let searchDebounceTimer = null;
    let cachedData = null;
    let selectedIndex = -1;

    function openSpotlight() {
        if (!spotlightModal) return;
        if (spotlightModal.parentElement !== document.body) {
            document.body.appendChild(spotlightModal);
        }
        spotlightModal.style.setProperty('display', 'flex', 'important');
        spotlightModal.classList.add('active');
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
        if (spotlightInput) {
            spotlightInput.value = '';
            setTimeout(() => {
                spotlightInput.focus();
                spotlightInput.select();
            }, 60);
            renderDefaultShortcuts();
        }
    }

    function closeSpotlight() {
        if (!spotlightModal) return;
        spotlightModal.style.setProperty('display', 'none', 'important');
        spotlightModal.classList.remove('active');
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
        selectedIndex = -1;
    }

    window.openSpotlight = openSpotlight;
    window.closeSpotlight = closeSpotlight;

    // Immediately detach modal to body root to prevent flex column displacement
    if (spotlightModal && spotlightModal.parentElement !== document.body) {
        document.body.appendChild(spotlightModal);
    }

    if (btnSpotlightTrigger) btnSpotlightTrigger.addEventListener('click', openSpotlight);
    if (btnSpotlightTriggerMobile) btnSpotlightTriggerMobile.addEventListener('click', openSpotlight);
    if (btnSpotlightClose) btnSpotlightClose.addEventListener('click', closeSpotlight);

    // Global click delegate for floating search button or any header search bar
    document.addEventListener('click', (e) => {
        if (e.target.closest('.floating-search-btn') || e.target.closest('.header-search-bar') || e.target.closest('[data-open-spotlight]')) {
            e.preventDefault();
            openSpotlight();
        }
    });

    if (spotlightResults) {
        spotlightResults.addEventListener('click', (e) => {
            if (e.target.closest('a')) closeSpotlight();
        });
    }

    if (spotlightModal) {
        spotlightModal.addEventListener('click', (e) => {
            if (e.target === spotlightModal) closeSpotlight();
        });
    }

    // Keyboard Shortcuts (Ctrl + K / ⌘K & Esc)
    document.addEventListener('keydown', (e) => {
        const isK = e.key === 'k' || e.key === 'K' || e.code === 'KeyK';
        const isCmdOrCtrl = e.metaKey || e.ctrlKey;
        if (isCmdOrCtrl && isK) {
            e.preventDefault();
            e.stopPropagation();
            if (spotlightModal && (spotlightModal.classList.contains('active') || spotlightModal.style.display === 'flex')) {
                closeSpotlight();
            } else {
                openSpotlight();
            }
        } else if (e.key === 'Escape' && spotlightModal && (spotlightModal.classList.contains('active') || spotlightModal.style.display === 'flex')) {
            e.preventDefault();
            closeSpotlight();
        }
    });

    // Filter Buttons Click
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.getAttribute('data-filter') || 'all';
            if (cachedData) {
                renderSearchResults(cachedData);
            } else {
                renderDefaultShortcuts();
            }
        });
    });

    // Default Shortcuts when input is empty (pre-cached HTML)
    const defaultShortcutsHtml = `
        <div class="spotlight-group-title">⚡ Pintasan Cepat</div>
        <a href="pesanan.php" class="spotlight-item" data-type="menu"><div class="spotlight-item-left"><div class="spotlight-item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div><div class="spotlight-item-info"><div class="spotlight-item-title">Input Pesanan Baru</div><div class="spotlight-item-subtitle">Tambah pesanan topi mutz baru per anggota SKPD</div></div></div><span class="spotlight-item-badge" style="background:#EEF2FF; color:#4F46E5;">Buka</span></a>
        <a href="rekap.php" class="spotlight-item" data-type="menu"><div class="spotlight-item-left"><div class="spotlight-item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg></div><div class="spotlight-item-info"><div class="spotlight-item-title">Rekapitulasi Matriks Ukuran</div><div class="spotlight-item-subtitle">Tabel rekapitulasi ukuran 55-60 L/P dan cetak invoice</div></div></div><span class="spotlight-item-badge" style="background:#EEF2FF; color:#4F46E5;">Buka</span></a>
        <a href="stok.php" class="spotlight-item" data-type="menu"><div class="spotlight-item-left"><div class="spotlight-item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg></div><div class="spotlight-item-info"><div class="spotlight-item-title">Manajemen Stok Topi</div><div class="spotlight-item-subtitle">Kelola stok fisik per ukuran dan peringatan minimum</div></div></div><span class="spotlight-item-badge" style="background:#EEF2FF; color:#4F46E5;">Buka</span></a>
        <a href="skpd.php" class="spotlight-item" data-type="menu"><div class="spotlight-item-left"><div class="spotlight-item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><div class="spotlight-item-info"><div class="spotlight-item-title">Daftar Instansi SKPD & Kontak WA</div><div class="spotlight-item-subtitle">Kelola kontak WhatsApp narahubung dinas</div></div></div><span class="spotlight-item-badge" style="background:#EEF2FF; color:#4F46E5;">Buka</span></a>
    `;

    function renderDefaultShortcuts() {
        if (!spotlightResults) return;
        cachedData = null;
        spotlightResults.innerHTML = defaultShortcutsHtml;
    }

    // Pre-build loading skeleton template once
    const loadingSkeletonTpl = '<div class="search-skeleton"><div class="search-skeleton-row"><div class="search-skeleton-avatar"></div><div class="search-skeleton-lines"><div class="search-skeleton-line w-75"></div><div class="search-skeleton-line w-50"></div></div><div class="search-skeleton-badge"></div></div><div class="search-skeleton-row"><div class="search-skeleton-avatar"></div><div class="search-skeleton-lines"><div class="search-skeleton-line w-60"></div><div class="search-skeleton-line w-40"></div></div><div class="search-skeleton-badge"></div></div><div class="search-skeleton-row"><div class="search-skeleton-avatar"></div><div class="search-skeleton-lines"><div class="search-skeleton-line w-75"></div><div class="search-skeleton-line w-50"></div></div><div class="search-skeleton-badge"></div></div></div>';

    // Perform Live Search (INP-optimized: all DOM writes deferred via scheduler)
    if (spotlightInput) {
        let pendingRAF = null;
        let lastQuery = '';

        spotlightInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(searchDebounceTimer);

            // Skip if query hasn't changed
            if (query === lastQuery) return;
            lastQuery = query;

            if (query.length === 0) {
                if (pendingRAF) { cancelAnimationFrame(pendingRAF); pendingRAF = null; }
                // Defer even the default shortcuts render
                requestAnimationFrame(() => renderDefaultShortcuts());
                return;
            }

            // Defer loading UI to next frame — lets browser paint input immediately
            if (pendingRAF) cancelAnimationFrame(pendingRAF);
            pendingRAF = requestAnimationFrame(() => {
                pendingRAF = null;
                spotlightResults.innerHTML =
                    '<div class="search-loading-container">' +
                        '<div class="search-orbit-loader">' +
                            '<div class="search-orbit-ring"></div>' +
                            '<div class="search-orbit-ring-inner"></div>' +
                            '<div class="search-orbit-dot"></div>' +
                            '<div class="search-orbit-dot"></div>' +
                            '<div class="search-orbit-dot"></div>' +
                            '<div class="search-orbit-core"></div>' +
                        '</div>' +
                        '<div class="search-loading-text">Mencari data instan...</div>' +
                        '<div class="search-loading-sub">Mencocokkan "<b>' + query + '</b>" ke seluruh database</div>' +
                    '</div>' + loadingSkeletonTpl;
            });

            searchDebounceTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`api_search.php?q=${encodeURIComponent(query)}`, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        cachedData = data;
                        // Defer result rendering to next frame
                        requestAnimationFrame(() => renderSearchResults(data));
                    } else {
                        spotlightResults.innerHTML = '<div class="spotlight-empty"><div style="color:var(--danger);font-weight:600;">Gagal memuat data</div><div style="font-size:0.8rem;color:var(--gray);">Status: ' + res.status + '</div></div>';
                    }
                } catch (e) {
                    console.error('Search error:', e);
                    spotlightResults.innerHTML = '<div class="spotlight-empty"><div style="color:var(--danger);font-weight:600;">Kesalahan koneksi</div><div style="font-size:0.8rem;color:var(--gray);">Periksa jaringan Anda.</div></div>';
                }
            }, 150);
        });

        // Arrow Key Navigation (only handle navigation keys, skip for typing)
        spotlightInput.addEventListener('keydown', function(e) {
            if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp' && e.key !== 'Enter') return;

            const items = spotlightResults.querySelectorAll('.spotlight-item');
            if (items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                updateSelectedItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                updateSelectedItem(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    items[selectedIndex].click();
                } else if (items.length > 0) {
                    items[0].click();
                }
            }
        });
    }

    function updateSelectedItem(items) {
        items.forEach((item, idx) => {
            if (idx === selectedIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('active');
            }
        });
    }

    // Render Search Results
    function renderSearchResults(data) {
        if (!spotlightResults) return;
        selectedIndex = -1;

        let html = '';
        let totalMatches = 0;

        const showPesanan = (currentFilter === 'all' || currentFilter === 'pesanan') && data.pesanan && data.pesanan.length > 0;
        const showSkpd = (currentFilter === 'all' || currentFilter === 'skpd') && data.skpd && data.skpd.length > 0;
        const showMenus = (currentFilter === 'all' || currentFilter === 'menus') && data.menus && data.menus.length > 0;

        // 1. Pesanan Items
        if (showPesanan) {
            html += `<div class="spotlight-group-title">👤 Hasil Pemesan (${data.pesanan.length})</div>`;
            data.pesanan.forEach(item => {
                totalMatches++;
                const bayarBg = item.status_bayar === 'Lunas' ? '#DCFCE7' : '#FEE2E2';
                const bayarColor = item.status_bayar === 'Lunas' ? '#166534' : '#991B1B';
                const ambilBg = item.status_pengambilan === 'Sudah Diambil' ? '#DCFCE7' : '#FEF3C7';
                const ambilColor = item.status_pengambilan === 'Sudah Diambil' ? '#166534' : '#92400E';

                html += `
                    <a href="pesanan.php?filter_skpd=${item.skpd_id}" class="spotlight-item" data-type="pesanan" onclick="document.getElementById('spotlightModal').classList.remove('active');">
                        <div class="spotlight-item-left">
                            <div class="spotlight-item-icon" style="background:#EEF2FF; color:#4F46E5;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </div>
                            <div class="spotlight-item-info">
                                <div class="spotlight-item-title">${escapeHtml(item.nama_pemesan)} <span style="font-weight:400; color:var(--gray); font-size:0.8rem;">(${escapeHtml(item.nama_skpd)})</span></div>
                                <div class="spotlight-item-subtitle">${item.jenis_kelamin} Uk. <b>${item.ukuran}</b> (${item.jumlah} pcs) &bull; ${item.subtotal_formatted}</div>
                            </div>
                        </div>
                        <div style="display:flex; gap:6px; align-items:center;">
                            <span class="spotlight-item-badge" style="background:${bayarBg}; color:${bayarColor};">${item.status_bayar}</span>
                            <span class="spotlight-item-badge" style="background:${ambilBg}; color:${ambilColor};">${item.status_pengambilan}</span>
                        </div>
                    </a>
                `;
            });
        }

        // 2. SKPD Items
        if (showSkpd) {
            html += `<div class="spotlight-group-title">🏛️ Hasil Instansi SKPD (${data.skpd.length})</div>`;
            data.skpd.forEach(skpd => {
                totalMatches++;
                const statusBadge = skpd.total_belum_lunas === 0 ? 
                    '<span class="spotlight-item-badge" style="background:#DCFCE7; color:#166534;">Semua Lunas</span>' : 
                    `<span class="spotlight-item-badge" style="background:#FEE2E2; color:#991B1B;">${skpd.total_belum_lunas} Belum Lunas</span>`;

                html += `
                    <a href="pesanan.php?filter_skpd=${skpd.id}" class="spotlight-item" data-type="skpd" onclick="document.getElementById('spotlightModal').classList.remove('active');">
                        <div class="spotlight-item-left">
                            <div class="spotlight-item-icon" style="background:#F0FDF4; color:#10B981;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            </div>
                            <div class="spotlight-item-info">
                                <div class="spotlight-item-title">${escapeHtml(skpd.nama_skpd)}</div>
                                <div class="spotlight-item-subtitle">Total: <b>${skpd.total_qty} pcs</b> (${skpd.total_rp_formatted}) ${skpd.no_wa ? ' &bull; WA: ' + escapeHtml(skpd.no_wa) : ''}</div>
                            </div>
                        </div>
                        ${statusBadge}
                    </a>
                `;
            });
        }

        // 3. Navigation Menus
        if (showMenus) {
            html += `<div class="spotlight-group-title">⚡ Navigasi Menu</div>`;
            data.menus.forEach(menu => {
                totalMatches++;
                html += `
                    <a href="${menu.url}" class="spotlight-item" data-type="menu" onclick="document.getElementById('spotlightModal').classList.remove('active');">
                        <div class="spotlight-item-left">
                            <div class="spotlight-item-icon" style="background:#EEF2FF; color:#4F46E5;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </div>
                            <div class="spotlight-item-info">
                                <div class="spotlight-item-title">${escapeHtml(menu.title)}</div>
                                <div class="spotlight-item-subtitle">${escapeHtml(menu.desc)}</div>
                            </div>
                        </div>
                        <span class="spotlight-item-badge" style="background:#F1F5F9; color:#475569;">Buka</span>
                    </a>
                `;
            });
        }

        if (totalMatches === 0) {
            html = `
                <div class="spotlight-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--gray); margin-bottom:10px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <div style="font-weight:600; color:var(--dark); margin-bottom:4px;">Tidak ada hasil ditemukan</div>
                    <div style="font-size:0.825rem;">Coba cari dengan kata kunci lain seperti nama SKPD, nama anggota, nomor WA, atau ukuran.</div>
                </div>
            `;
        }

        spotlightResults.innerHTML = html;
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
</script>
