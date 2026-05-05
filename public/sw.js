const CACHE_NAME = 'digitex-pwa-cache-v5';
const OFFLINE_URL = '/offline.html';

// Keep this list local and minimal! If one file fails, the cache aborts.
const urlsToCache = [
    OFFLINE_URL,
    '/images/favicon.png'
];

// Install the service worker and cache core assets
self.addEventListener('install', event => {
    self.skipWaiting(); 
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Caching offline fallback...');
                return cache.addAll(urlsToCache);
            })
            .catch(error => console.error('Cache install error:', error))
    );
});

// Intercept requests
self.addEventListener('fetch', event => {
    // Only intercept HTML page requests (Navigations)
    if (event.request.mode === 'navigate' || (event.request.method === 'GET' && event.request.headers.get('accept').includes('text/html'))) {
        event.respondWith(
            fetch(event.request).catch(() => {
                // Network failed, return the offline page from cache
                console.log('Serving offline.html fallback');
                return caches.match(OFFLINE_URL);
            })
        );
    }
});

// Update service worker and delete old caches
self.addEventListener('activate', event => {
    event.waitUntil(clients.claim()); 
    
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});