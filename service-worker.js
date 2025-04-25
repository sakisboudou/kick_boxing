// service-worker.js

self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installed');
});

self.addEventListener('fetch', (event) => {
  // Default network-first strategy
  event.respondWith(fetch(event.request));
});
