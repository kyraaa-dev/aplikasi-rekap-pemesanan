/**
 * E-MutZ KORPRI - Instant Navigation & Prefetch Engine (SPA Mode)
 * Memberikan pengalaman navigasi instan tanpa jeda, tanpa reload layar putih, dan ultra responsif.
 */
(function() {
    const pageCache = new Map();
    let currentPath = window.location.pathname;

    // Progress Bar Element
    let progressBar = null;
    function createProgressBar() {
        if (!progressBar) {
            progressBar = document.createElement('div');
            progressBar.id = 'nprogress-bar';
            progressBar.style.cssText = 'position:fixed;top:0;left:0;height:3px;background:linear-gradient(90deg,#4F46E5,#06B6D4,#10B981);z-index:9999999;transition:width 0.2s ease, opacity 0.2s ease;width:0%;opacity:0;pointer-events:none;box-shadow:0 0 10px rgba(79,70,229,0.7);';
            document.body.appendChild(progressBar);
        }
    }

    function startProgress() {
        createProgressBar();
        progressBar.style.opacity = '1';
        progressBar.style.width = '35%';
        setTimeout(() => {
            if (progressBar && progressBar.style.opacity === '1') {
                progressBar.style.width = '75%';
            }
        }, 80);
    }

    function endProgress() {
        if (!progressBar) return;
        progressBar.style.width = '100%';
        setTimeout(() => {
            progressBar.style.opacity = '0';
            setTimeout(() => {
                progressBar.style.width = '0%';
            }, 200);
        }, 150);
    }

    // Prefetch a URL into memory cache
    async function prefetchUrl(url) {
        if (pageCache.has(url)) return pageCache.get(url);
        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'InstantNav' } });
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

        // 3. Re-run execute inline script tags inside new page
        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
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

        // 6. Trigger global custom event for any listeners
        document.dispatchEvent(new CustomEvent('page:loaded', { detail: { url: window.location.href } }));
    }

    // Perform Instant Navigation
    async function navigateTo(url, pushState = true) {
        startProgress();
        const mainContent = document.querySelector('main.main-content');
        if (mainContent) {
            mainContent.classList.add('page-loading');
        }

        try {
            let html = pageCache.get(url);
            if (!html) {
                const response = await fetch(url, { headers: { 'X-Requested-With': 'InstantNav' } });
                if (!response.ok) {
                    window.location.href = url; // Fallback
                    return;
                }
                html = await response.text();
                pageCache.set(url, html);
            }

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

            // Scroll to top
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
