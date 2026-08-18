// E-MutZ KORPRI Service Worker
const CACHE_NAME = 'emutz-korpri-v1.0';
const STATIC_ASSETS = [
    './manifest.json',
    './assets/css/style.css',
    './assets/js/script.js',
    './assets/images/logo.png',
    './assets/images/icon-192.png',
    './assets/images/icon-512.png',
    './assets/images/apple-touch-icon.png',
    './assets/images/icon-maskable-512.png'
];

// Install Event - Pre-cache essential static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('PWA Pre-cache failed for some assets:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate Event - Clean up outdated caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== CACHE_NAME) {
                        return caches.delete(name);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event - Network first for PHP data, Cache first for static assets
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Only handle GET requests from the same origin
    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Static assets: Cache First, fallback to Network
    if (request.destination === 'style' || request.destination === 'script' || request.destination === 'image' || request.destination === 'font') {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                });
            })
        );
        return;
    }

    // PHP Pages & Dynamic Navigation: Network First, fallback to Cache
    event.respondWith(
        fetch(request).then((networkResponse) => {
            if (networkResponse && networkResponse.status === 200) {
                const responseClone = networkResponse.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(request, responseClone);
                });
            }
            return networkResponse;
        }).catch(() => {
            return caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return caches.match('./index.php');
            });
        })
    );
});
