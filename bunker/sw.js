const CACHE_NAME = 'bunker-cache-v2'; // Versi diubah ke v2
const urlsToCache = [
    './index.php',
    '../assets/style.css',
    '../assets/npc-icon.svg' // Ikon diubah ke SVG
];

// Install Service Worker dan Cache aset penting
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                return cache.addAll(urlsToCache);
            })
    );
});

// Membersihkan cache versi lama saat update
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Intersep request jaringan
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                return response || fetch(event.request);
            })
    );
});