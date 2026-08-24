/**
 * E-MutZ KORPRI - Instant Navigation & Prefetch Engine (SPA Mode)
 * Memberikan pengalaman navigasi instan tanpa jeda, tanpa reload layar putih, dan ultra responsif.
 */
(function() {
    const pageCache = new Map();
    let currentPath = window.location.pathname;

    // Brutalism Loading Screen
    let brutalLoader = null;
    function createBrutalLoader() {
        if (!brutalLoader) {
            brutalLoader = document.createElement('div');
            brutalLoader.id = 'brutal-loader';
            brutalLoader.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;background:#FDE047;z-index:9999999;transition:opacity 0.2s ease, visibility 0.2s ease;opacity:0;visibility:hidden;display:flex;align-items:center;justify-content:center;flex-direction:column;pointer-events:none;';
            
            brutalLoader.innerHTML = `
                <div style="background:#fff;border:4px solid #000;box-shadow:8px 8px 0px #000;padding:20px 40px;font-size:2rem;font-weight:900;color:#000;text-transform:uppercase;letter-spacing:2px;animation:brutalPulse 0.4s infinite alternate;">
                    MEMUAT...
                </div>
                <style>
                    @keyframes brutalPulse {
                        0% { transform: scale(1) translate(0, 0); box-shadow: 8px 8px 0px #000; }
                        100% { transform: scale(1.05) translate(-4px, -4px); box-shadow: 12px 12px 0px #000; }
                    }
                </style>
            `;
            document.body.appendChild(brutalLoader);
        }
    }

    function startProgress() {
        createBrutalLoader();
        brutalLoader.style.opacity = '1';
        brutalLoader.style.visibility = 'visible';
    }

    function endProgress() {
        if (!brutalLoader) return;
        setTimeout(() => {
            brutalLoader.style.opacity = '0';
            setTimeout(() => {
                brutalLoader.style.visibility = 'hidden';
            }, 200);
        }, 300); // Give it a slight delay so the brutalist animation is seen
    }

    // Expose to global window so other scripts (like AJAX forms) can use it
    window.startBrutalLoader = startProgress;
    window.endBrutalLoader = endProgress;

    // Prefetch a URL into memory cache
    async function prefetchUrl(url) {
        if (pageCache.has(url)) return pageCache.get(url);
        try {
            const response = await fetch(url, { 
                headers: { 'X-Requested-With': 'InstantNav' },
                credentials: 'same-origin'
            });
            if (response.ok) {
                const html = await response.text();
                pageCache.set(url, html);
                return html;
            }
        } catch (e) {
            console.warn('Prefetch error:', e);
        }
        return null;
    }

    // Check if link is eligible for instant navigation
    function isEligibleLink(anchor) {
        if (!anchor || !anchor.href) return false;
        if (anchor.target && anchor.target !== '_self') return false;
        if (anchor.hasAttribute('download')) return false;
        if (anchor.classList.contains('btn-confirm')) return false;
        if (anchor.getAttribute('href').startsWith('#') || anchor.getAttribute('href').startsWith('javascript:')) return false;

        const url = new URL(anchor.href, window.location.origin);
        // Same origin check
        if (url.origin !== window.location.origin) return false;

        // Skip non-HTML files or special action endpoints
        const path = url.pathname.toLowerCase();
        if (path.endsWith('logout.php') || 
            path.endsWith('backup.php') || 
            path.endsWith('export_excel.php') || 
            url.searchParams.has('export') || 
            url.searchParams.has('cetak')) {
            return false;
        }

        return true;
    }

    // Initialize/Re-run page-specific scripts
    function reinitPageScripts(container) {
        // 1. Re-run input gender to size options
        const jkSelect = container.querySelector('#jenis_kelamin');
        const ukuranSelect = container.querySelector('#ukuran');
        if (jkSelect && ukuranSelect) {
            function updateUkuranOptions() {
                const jk = jkSelect.value;
                ukuranSelect.innerHTML = '';
                let options = [];
                if (jk === 'Laki-laki') {
                    options = [55, 56, 57, 58, 59, 60];
                } else if (jk === 'Perempuan') {
                    options = [58, 59, 60];
                } else {
                    ukuranSelect.innerHTML = '<option value="">-- Pilih Jenis Kelamin Dahulu --</option>';
                    return;
                }
                options.forEach(size => {
                    const opt = document.createElement('option');
                    opt.value = size;
                    opt.textContent = size;
                    ukuranSelect.appendChild(opt);
                });
            }
            updateUkuranOptions();
            jkSelect.addEventListener('change', updateUkuranOptions);
        }

        // 2. Re-run confirm buttons (SweetAlert confirm)
        container.querySelectorAll('.btn-confirm').forEach(btn => {
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

        // 3. Re-run execute inline script tags inside new page safely
        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            try {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                if (oldScript.innerHTML) {
                    newScript.text = oldScript.innerHTML;
                }
                if (oldScript.parentNode) {
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                }
            } catch (err) {
                console.warn('Script execution error:', err);
            }
        });

        // 4. Animate fade-up elements
        container.querySelectorAll('.fade-up').forEach(el => {
            el.classList.add('visible');
        });

        // 5. Close mobile sidebar if open
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        if (sidebar && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
        }

        // 6. Guarantee unlock scroll & dismiss lingering modals
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
        document.querySelectorAll('.modal-skpd-overlay, .spotlight-overlay, .sidebar-overlay').forEach(el => {
            el.classList.remove('active');
            el.style.display = 'none';
        });

        // 7. Trigger global custom event for any listeners
        document.dispatchEvent(new CustomEvent('page:loaded', { detail: { url: window.location.href } }));
    }

    // Perform Instant Navigation
    async function navigateTo(url, pushState = true) {
        startProgress();
        // Immediately restore body scroll and dismiss any active modals before swap
        document.body.style.overflow = '';
        document.body.classList.remove('swal2-shown', 'swal2-height-auto'); // In case SweetAlert got stuck
        document.documentElement.style.overflow = '';
        document.querySelectorAll('.modal-skpd-overlay, .spotlight-overlay, .sidebar-overlay').forEach(el => {
            el.classList.remove('active');
            el.style.display = 'none';
        });

        const mainContent = document.querySelector('main.main-content');
        if (mainContent) {
            mainContent.classList.add('page-loading', 'skeleton-shimmer');
        }

        try {
            // Always fetch fresh HTML from server to ensure live database state
            const response = await fetch(url, { 
                headers: { 'X-Requested-With': 'InstantNav' },
                credentials: 'same-origin',
                cache: 'no-cache'
            });
            if (!response.ok) {
                window.location.href = url; // Fallback
                return;
            }
            const html = await response.text();

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newMain = doc.querySelector('main.main-content');
            if (!newMain || !mainContent) {
                window.location.href = url; // Fallback if structure differs
                return;
            }

            // Update Title
            document.title = doc.title;

            // Swap Main Content with micro-animation
            mainContent.innerHTML = newMain.innerHTML;
            mainContent.className = newMain.className;
            mainContent.classList.remove('page-loading');
            mainContent.classList.add('page-enter');
            setTimeout(() => {
                mainContent.classList.remove('page-enter');
            }, 250);

            // Update Navigation Active State
            const urlObj = new URL(url, window.location.origin);
            const currentFileName = urlObj.pathname.split('/').pop() || 'index.php';

            document.querySelectorAll('.nav-links a').forEach(navLink => {
                const linkHref = navLink.getAttribute('href');
                if (linkHref && linkHref.split('?')[0] === currentFileName) {
                    navLink.classList.add('active');
                } else {
                    navLink.classList.remove('active');
                }
            });

            // Update History State
            if (pushState) {
                window.history.pushState({ url }, doc.title, url);
            }

            // Restore scroll and scroll to top
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
            window.scrollTo({ top: 0, behavior: 'instant' });

            // Reinitialize dynamic elements
            reinitPageScripts(mainContent);

            endProgress();
        } catch (error) {
            console.error('Instant navigation failed, falling back:', error);
            window.location.href = url;
        }
    }

    // Event Listeners
    document.addEventListener('DOMContentLoaded', () => {
        // Cache current page immediately
        pageCache.set(window.location.href, document.documentElement.outerHTML);

        // Preload on mouse hover or touch start
        document.addEventListener('mouseover', (e) => {
            const anchor = e.target.closest('a');
            if (anchor && isEligibleLink(anchor)) {
                prefetchUrl(anchor.href);
            }
        }, { passive: true });

        document.addEventListener('touchstart', (e) => {
            const anchor = e.target.closest('a');
            if (anchor && isEligibleLink(anchor)) {
                prefetchUrl(anchor.href);
            }
        }, { passive: true });

        // Intercept link clicks
        document.addEventListener('click', (e) => {
            const anchor = e.target.closest('a');
            if (!anchor || !isEligibleLink(anchor)) return;

            // Don't intercept modifier keys (Cmd/Ctrl + click to open in new tab)
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            // If already on the same page and no query change, just scroll top
            if (anchor.href === window.location.href) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            e.preventDefault();
            navigateTo(anchor.href, true);
        });

        // Handle Browser Back / Forward buttons
        window.addEventListener('popstate', (e) => {
            navigateTo(window.location.href, false);
        });
    });
})();
