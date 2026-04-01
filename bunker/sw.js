/* =========================================
   BUNKER OS - SERVICE WORKER (DOOMSDAY UPGRADE)
   ========================================= */

const CACHE_NAME = 'bunker-os-v2.0.0'; // Naikkan versinya agar browser membuang cache lama

// Aset statis yang WAJIB ada meski offline
const staticAssets = [
  './',
  './index.php',
  './manifest.json',
  '../assets/npc-icon.svg',
  '../assets/terminal.css',
  '../assets/terminal.js',
  'https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap'
];

// 1. Proses Install: Masukkan semua aset statis ke brankas
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(staticAssets))
      .then(() => self.skipWaiting())
  );
});

// 2. Proses Fetch: Logika Jaringan
self.addEventListener('fetch', event => {
  const request = event.request;

  // STRATEGI 1: Untuk halaman HTML/PHP (Seperti Vault & Auth) -> NETWORK FIRST
  if (request.mode === 'navigate' || request.destination === 'document') {
    event.respondWith(
      fetch(request)
        .then(response => {
          // Jika internet nyala, simpan fotokopi halaman (yang sudah melewati login PHP) ke Cache
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(request, responseClone));
          return response;
        })
        .catch(() => {
          // JIKA OFFLINE: Berikan fotokopi halaman terakhir dari cache! (Bypass PHP Login)
          return caches.match(request);
        })
    );
  } 
  // STRATEGI 2: Untuk aset statis (CSS, JS, Gambar) -> CACHE FIRST
  else {
    event.respondWith(
      caches.match(request).then(cachedResponse => {
        if (cachedResponse) {
          return cachedResponse; // Gunakan cache jika ada
        }
        // Jika tidak ada di cache, coba cari di internet lalu simpan
        return fetch(request).then(response => {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(request, responseClone));
          return response;
        }).catch(() => {
          // Abaikan jika aset gagal dimuat saat offline
        });
      })
    );
  }
});

// 3. Proses Aktivasi: Bersihkan fotokopi/cache versi lama
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
    }).then(() => self.clients.claim())
  );
});