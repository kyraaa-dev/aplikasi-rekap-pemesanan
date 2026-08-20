// E-MutZ KORPRI Service Worker (Disabled for Development)
self.addEventListener('install', (event) => {
    // Force the new service worker to take effect immediately
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    // Clean up all caches immediately
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    return caches.delete(name);
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    // Always fetch from network, do not use cache
    event.respondWith(fetch(event.request));
});
