// Rende Omnia Control installabile su Android.
// Non intercetta le richieste: login, sessione e Livewire restano quelli del server.
self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', () => {});
