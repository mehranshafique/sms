const CACHE_NAME = 'digitex-pwa-cache-v4';
const OFFLINE_URL = '/offline.html';

const urlsToCache = [
    OFFLINE_URL,
    '/images/favicon.png',
    '/images/icon-192.png',
    '/images/icon-512.png',
    'https://e-digitex.com/public/images/smsslogonew.png' // Pre-cache the logo for the offline page
];

// Install the service worker and cache core assets
self.addEventListener('install', event => {
    self.skipWaiting(); 
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache and saving offline UI');
                return cache.addAll(urlsToCache);
            })
    );
});

// Cache and return requests
self.addEventListener('fetch', event => {
    // We only want to intercept page navigations (HTML requests)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(error => {
                // If the network request fails (user is offline), serve the static offline UI
                console.log('Network failed, serving offline page.');
                return caches.match(OFFLINE_URL);
            })
        );
    } else {
        // For other requests (CSS, JS), try network first, fallback to cache
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