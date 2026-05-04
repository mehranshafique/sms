const CACHE_NAME = 'digitex-pwa-cache-v2';
const urlsToCache = [
    '/',
    '/dashboard',
    '/css/style.css',
    '/images/favicon.png'
];

// Install the service worker and cache core assets
self.addEventListener('install', event => {
    // Force the waiting service worker to become the active service worker.
    self.skipWaiting(); 
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

// Cache and return requests
self.addEventListener('fetch', event => {
    // Basic network-first strategy for dynamic content
    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request);
        })
    );
});

// Update service worker and delete old caches
self.addEventListener('activate', event => {
    // Claim control of all open client pages immediately
    event.waitUntil(clients.claim()); 
    
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});