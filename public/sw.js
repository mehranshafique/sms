const CACHE_NAME = 'digitex-pwa-cache-v3';

// Add the exact paths to your login page and its essential CSS/JS/Images here
const urlsToCache = [
    '/',
    '/login',
    '/css/style.css',
    '/vendor/global/global.min.css',
    '/images/favicon.png'
];

// Install the service worker and cache core assets
self.addEventListener('install', event => {
    self.skipWaiting(); // Force active immediately
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache and saving core assets');
                return cache.addAll(urlsToCache);
            })
    );
});

// Cache and return requests
self.addEventListener('fetch', event => {
    // For page navigations (e.g., user types digitex.com/dashboard while offline)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => {
                // If the network fails (offline), serve the cached login page
                return caches.match('/login').then(response => {
                    return response || caches.match('/');
                });
            })
        );
    } else {
        // For other assets (CSS, images), try network first, then cache
        event.respondWith(
            fetch(event.request).catch(() => {
                return caches.match(event.request);
            })
        );
    }
});

// Update service worker and delete old caches
self.addEventListener('activate', event => {
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