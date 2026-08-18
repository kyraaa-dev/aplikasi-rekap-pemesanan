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
</style>
<script>
    if(localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark');

    document.addEventListener('DOMContentLoaded', function() {
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            const svgDark = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block; margin:auto;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';
            const svgLight = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block; margin:auto;"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>';

            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                themeToggle.innerHTML = svgLight;
            } else {
                themeToggle.innerHTML = svgDark;
            }

            themeToggle.addEventListener('click', function() {
                if (document.documentElement.getAttribute('data-theme') === 'dark') {
                    document.documentElement.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                    themeToggle.innerHTML = svgDark;
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                    themeToggle.innerHTML = svgLight;
                }
            });
        }
    });
</script>

<button id="themeToggle" title="Ganti Tema (Terang/Gelap)" class="hide-on-print" style="position: fixed; top: 1.5rem; right: 2rem; z-index: 1000; background-color: var(--white); color: var(--dark); border: 1px solid var(--gray-light); border-radius: 50%; width: 45px; height: 45px; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); padding: 0; transition: transform 0.2s;">
</button>

<aside class="sidebar">
    <div class="brand" style="display: flex; align-items: center; gap: 10px;">
        <img src="assets/images/logo.png?v=2" alt="Logo E-MutZ" style="width: 56px; height: 56px; object-fit: contain; border-radius: 8px;">
        E-MutZ KORPRI
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
        <li style="margin-top: 2rem;"><a href="logout.php" id="btnLogout" style="color: var(--danger);">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Keluar (Logout)</a>
        </li>
    </ul>
</aside>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    // 3. Logout handler (still specific but uses similar logic)
    const btnLogout = document.getElementById('btnLogout');
    if (btnLogout) {
        btnLogout.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            Swal.fire({
                title: 'Konfirmasi Keluar',
                text: "Apakah Anda yakin ingin keluar dari aplikasi?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    }
});
</script>
