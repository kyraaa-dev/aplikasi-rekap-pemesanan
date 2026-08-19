<style>
/* Efek Hover Mewah pada Menu */
.nav-links a {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
.nav-links a:hover {
    transform: translateX(6px);
    background-color: rgba(79, 70, 229, 0.08) !important; /* Biru transparan elegan */
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

/* SweetAlert2 Highest Layer Guarantee */
.swal2-container {
    z-index: 100000 !important;
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

<!-- Desktop Floating Theme Toggle -->
<button id="themeToggle" title="Ganti Tema (Terang/Gelap)" class="hide-on-print" type="button" style="position: fixed; top: 1.5rem; right: 2rem; z-index: 1000; background-color: var(--white); color: var(--dark); border: 1px solid var(--gray-light); border-radius: 50%; width: 45px; height: 45px; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); padding: 0; transition: transform 0.2s;">
</button>

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
        Swal.fire({ title: title, text: text, icon: icon, confirmButtonColor: 'var(--primary)', customClass: { popup: 'swal-custom-popup' } });
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
            title: 'Membersihkan Sesi & Cache...',
            html: `
                <div style="padding: 10px 0;">
                    <div id="swal-step-text" style="font-size: 0.9rem; font-weight: 600; color: #4F46E5; margin-bottom: 14px;">
                        🧹 Menghapus cache lokal browser...
                    </div>
                    <div style="width: 100%; height: 8px; background: #E5E7EB; border-radius: 999px; overflow: hidden;">
                        <div id="swal-progress-bar" style="width: 20%; height: 100%; background: linear-gradient(90deg, #4F46E5 0%, #06B6D4 50%, #10B981 100%); border-radius: 999px; transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);"></div>
                    </div>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                const progressBar = document.getElementById('swal-progress-bar');
                const stepText = document.getElementById('swal-step-text');
                
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

                // Step 2: Menghapus token & sesi
                setTimeout(() => {
                    if (progressBar) progressBar.style.width = '65%';
                    if (stepText) stepText.innerHTML = '🔒 Menghapus token autentikasi & sesi...';
                }, 400);

                // Step 3: Penyelesaian
                setTimeout(() => {
                    if (progressBar) progressBar.style.width = '100%';
                    if (stepText) stepText.innerHTML = '✨ Selesai! Mengalihkan ke halaman login...';
                }, 850);

                // Step 4: Redirect
                setTimeout(() => {
                    window.location.href = url;
                }, 1200);
            }
        });
    }

    const logoutButtons = document.querySelectorAll('#btnLogout, a[href="logout.php"], .btn-logout');
    logoutButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href') || 'logout.php';
            Swal.fire({
                title: 'Konfirmasi Keluar',
                html: `
                    <div style="margin-top: 8px; font-size: 0.95rem; color: var(--dark); line-height: 1.5;">
                        Apakah Anda yakin ingin keluar dari sistem <strong>E-MutZ KORPRI</strong>?
                    </div>
                    <div style="margin-top: 14px; padding: 10px 14px; background: #FEF3C7; border: 1px solid #FDE68A; border-radius: 8px; font-size: 0.825rem; color: #92400E; text-align: left; display: flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <span>Sistem akan menutup sesi aman Anda dan membersihkan cache sementara browser.</span>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DC2626',
                cancelButtonColor: '#6B7280',
                confirmButtonText: '<span style="display:inline-flex; align-items:center; gap:6px;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg> Ya, Bersihkan & Keluar</span>',
                cancelButtonText: 'Batal',
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
            title: '<span style="display:inline-flex; align-items:center; gap:8px; color:#059669;"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg> Notifikasi WhatsApp</span>',
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
            confirmButtonText: '<span style="display:inline-flex; align-items:center; gap:6px; font-weight:700;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg> Buka di WhatsApp</span>',
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
        spotlightModal.style.display = 'flex';
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
        spotlightModal.style.display = 'none';
        spotlightModal.classList.remove('active');
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
        selectedIndex = -1;
    }

    window.openSpotlight = openSpotlight;
    window.closeSpotlight = closeSpotlight;

    if (btnSpotlightTriggerMobile) btnSpotlightTriggerMobile.addEventListener('click', openSpotlight);
    if (btnSpotlightClose) btnSpotlightClose.addEventListener('click', closeSpotlight);

    // Global click delegate for any search bar in headers or dashboard
    document.addEventListener('click', (e) => {
        if (e.target.closest('.header-search-bar') || e.target.closest('[data-open-spotlight]')) {
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

    // Default Shortcuts when input is empty
    function renderDefaultShortcuts() {
        if (!spotlightResults) return;
        cachedData = null;
        spotlightResults.innerHTML = `
            <div class="spotlight-group-title">⚡ Pintasan Cepat</div>
            <a href="pesanan.php" class="spotlight-item" data-type="menu">
                <div class="spotlight-item-left">
                    <div class="spotlight-item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div>
                    <div class="spotlight-item-info">
                        <div class="spotlight-item-title">Input Pesanan Baru</div>
                        <div class="spotlight-item-subtitle">Tambah pesanan topi mutz baru per anggota SKPD</div>
                    </div>
                </div>
                <span class="spotlight-item-badge" style="background:#EEF2FF; color:#4F46E5;">Buka</span>
            </a>
            <a href="rekap.php" class="spotlight-item" data-type="menu">
                <div class="spotlight-item-left">
                    <div class="spotlight-item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg></div>
                    <div class="spotlight-item-info">
                        <div class="spotlight-item-title">Rekapitulasi Matriks Ukuran</div>
                        <div class="spotlight-item-subtitle">Tabel rekapitulasi ukuran 55-60 L/P dan cetak invoice</div>
                    </div>
                </div>
                <span class="spotlight-item-badge" style="background:#EEF2FF; color:#4F46E5;">Buka</span>
            </a>
            <a href="stok.php" class="spotlight-item" data-type="menu">
                <div class="spotlight-item-left">
                    <div class="spotlight-item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg></div>
                    <div class="spotlight-item-info">
                        <div class="spotlight-item-title">Manajemen Stok Topi</div>
                        <div class="spotlight-item-subtitle">Kelola stok fisik per ukuran dan peringatan minimum</div>
                    </div>
                </div>
                <span class="spotlight-item-badge" style="background:#EEF2FF; color:#4F46E5;">Buka</span>
            </a>
            <a href="skpd.php" class="spotlight-item" data-type="menu">
                <div class="spotlight-item-left">
                    <div class="spotlight-item-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div>
                    <div class="spotlight-item-info">
                        <div class="spotlight-item-title">Daftar Instansi SKPD & Kontak WA</div>
                        <div class="spotlight-item-subtitle">Kelola kontak WhatsApp narahubung dinas</div>
                    </div>
                </div>
                <span class="spotlight-item-badge" style="background:#EEF2FF; color:#4F46E5;">Buka</span>
            </a>
        `;
    }

    // Perform Live Search
    if (spotlightInput) {
        spotlightInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(searchDebounceTimer);

            if (query.length === 0) {
                renderDefaultShortcuts();
                return;
            }

            spotlightResults.innerHTML = `
                <div class="spotlight-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 0.8s linear infinite; margin-bottom: 8px; color: var(--primary);"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
                    <div>Mencari data secara instan...</div>
                </div>
            `;

            searchDebounceTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`api_search.php?q=${encodeURIComponent(query)}`, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        cachedData = data;
                        renderSearchResults(data);
                    } else {
                        spotlightResults.innerHTML = `
                            <div class="spotlight-empty">
                                <div style="color:var(--danger); font-weight:600;">Gagal memuat data pencarian</div>
                                <div style="font-size:0.8rem; color:var(--gray);">Status respons: ${res.status}. Silakan coba lagi.</div>
                            </div>
                        `;
                    }
                } catch (e) {
                    console.error('Search fetch error:', e);
                    spotlightResults.innerHTML = `
                        <div class="spotlight-empty">
                            <div style="color:var(--danger); font-weight:600;">Terjadi kesalahan koneksi</div>
                            <div style="font-size:0.8rem; color:var(--gray);">Periksa jaringan Anda dan coba lagi.</div>
                        </div>
                    `;
                }
            }, 120);
        });

        // Arrow Key Navigation
        spotlightInput.addEventListener('keydown', function(e) {
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
