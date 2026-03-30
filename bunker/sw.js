/* =========================================
   BUNKER OS - SERVICE WORKER
   ========================================= */

const CACHE_NAME = 'bunker-os-v1.2.1';
const urlsToCache = [
  './',
  './index.php',
  './manifest.json',
  '../assets/npc-icon.svg',
  'https://cdn.jsdelivr.net/gh/jeannesbryan/terminal/terminal.css',
  'https://cdn.jsdelivr.net/gh/jeannesbryan/terminal/terminal.js',
  'https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap'
];

// Install SW
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

// Cache and return requests
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) return response;
        return fetch(event.request);
      }
    )
  );
});

// Update SW
self.addEventListener('activate', event => {
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