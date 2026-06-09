/* ============================================
   MIEREA POFTA — Service Worker v2.0
   Cache-first pentru assets, network-first
   pentru date dinamice. Push notifications.
   ============================================ */

const CACHE_NAME    = 'miereavpofta-v3';
const CACHE_STATIC  = 'miereavpofta-static-v3';
const CACHE_DYNAMIC = 'miereavpofta-dynamic-v3';

/* Assets care se cacheaza la instalare */
const STATIC_ASSETS = [
  '/',
  '/index.php',
  '/assets/app.js',
  '/assets/style.css',
  '/manifest.json',
  '/icon-192.png',
  '/icon-512.png',
  'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Nunito:wght@400;600;700;800;900&family=Roboto+Mono:wght@500;700&display=swap',
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
];

/* ── INSTALL: pre-cache assets statice ── */
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_STATIC).then(cache => {
      return cache.addAll(STATIC_ASSETS).catch(err => {
        console.warn('[SW] Unele assets nu s-au putut cacha:', err);
      });
    })
  );
  self.skipWaiting();
});

/* ── ACTIVATE: curăță cache-urile vechi ── */
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(k => k !== CACHE_STATIC && k !== CACHE_DYNAMIC)
          .map(k => {
            console.log('[SW] Șterg cache vechi:', k);
            return caches.delete(k);
          })
      )
    )
  );
  self.clients.claim();
});

/* ── FETCH: strategie per tip de request ── */
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignoră requests non-GET și cele din alte origini (afară de CDN)
  if (request.method !== 'GET') return;

  // NICIODATĂ nu pune în cache logout sau requests cu nocache
  if (
    url.search.includes('logout') ||
    url.search.includes('nocache') ||
    url.search.includes('no-cache')
  ) {
    event.respondWith(fetch(request));
    return;
  }

  // Date dinamice (backend.php, ?get_data, ?fetch) — Network-first
  if (
    url.pathname.includes('backend.php') ||
    url.search.includes('get_data') ||
    url.search.includes('fetch') ||
    url.search.includes('get_permissions')
  ) {
    event.respondWith(networkFirst(request));
    return;
  }

  // index.php — întotdeauna Network-first (conține sesiune PHP)
  if (url.pathname.endsWith('index.php') || url.pathname === '/' || url.pathname.endsWith('/')) {
    event.respondWith(networkFirst(request));
    return;
  }

  // Cod aplicatie (app.js, style.css) — Network-first ca update-urile sa se aplice imediat
  if (url.pathname.endsWith('app.js') || url.pathname.endsWith('style.css')) {
    event.respondWith(networkFirst(request));
    return;
  }

  // Alte assets statice (imagini, fonturi) — Cache-first
  if (
    STATIC_ASSETS.some(a => request.url.includes(a)) ||
    url.pathname.match(/\.(png|jpg|jpeg|gif|webp|svg|woff2?)$/)
  ) {
    event.respondWith(cacheFirst(request));
    return;
  }

  // Toate celelalte — Stale-while-revalidate
  event.respondWith(staleWhileRevalidate(request));
});

/* ── STRATEGIE: Cache-first ── */
async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(CACHE_STATIC);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    return new Response('Offline — asset indisponibil', { status: 503 });
  }
}

/* ── STRATEGIE: Network-first ── */
async function networkFirst(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(CACHE_DYNAMIC);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await caches.match(request);
    return cached || new Response(JSON.stringify({ offline: true, data: [] }), {
      headers: { 'Content-Type': 'application/json' },
      status: 200,
    });
  }
}

/* ── STRATEGIE: Stale-while-revalidate ── */
async function staleWhileRevalidate(request) {
  const cache    = await caches.open(CACHE_DYNAMIC);
  const cached   = await cache.match(request);
  const fetchPromise = fetch(request).then(response => {
    if (response.ok) cache.put(request, response.clone());
    return response;
  }).catch(() => null);
  return cached || fetchPromise || new Response('Offline', { status: 503 });
}

/* ── PUSH NOTIFICATIONS ── */
self.addEventListener('push', event => {
  let data = { title: '🐝 Mierea Pofta', body: 'Ai o notificare nouă!', icon: '/icon-192.png', badge: '/icon-192.png' };

  if (event.data) {
    try {
      data = { ...data, ...event.data.json() };
    } catch {
      data.body = event.data.text();
    }
  }

  event.waitUntil(
    self.registration.showNotification(data.title, {
      body:    data.body,
      icon:    data.icon   || '/icon-192.png',
      badge:   data.badge  || '/icon-192.png',
      tag:     data.tag    || 'miereavpofta',
      vibrate: [200, 100, 200],
      data:    { url: data.url || '/' },
      actions: [
        { action: 'view',    title: '👁️ Vezi',    icon: '/icon-192.png' },
        { action: 'dismiss', title: '✖️ Închide' },
      ],
    })
  );
});

/* ── CLICK PE NOTIFICARE ── */
self.addEventListener('notificationclick', event => {
  event.notification.close();
  if (event.action === 'dismiss') return;

  const targetUrl = event.notification.data?.url || '/';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
      for (const client of clientList) {
        if (client.url.includes(self.location.origin) && 'focus' in client) {
          client.focus();
          return client.navigate(targetUrl);
        }
      }
      if (clients.openWindow) return clients.openWindow(targetUrl);
    })
  );
});

/* ── SYNC (offline queue) ── */
self.addEventListener('sync', event => {
  if (event.tag === 'sync-offline-queue') {
    event.waitUntil(syncOfflineData());
  }
});

async function syncOfflineData() {
  const allClients = await clients.matchAll();
  for (const client of allClients) {
    client.postMessage({ type: 'SW_SYNC_REQUEST' });
  }
}

/* ── MESSAGE (de la pagină) ── */
self.addEventListener('message', event => {
  if (event.data?.type === 'SKIP_WAITING') self.skipWaiting();
  if (event.data?.type === 'CACHE_URLS') {
    caches.open(CACHE_STATIC).then(cache => cache.addAll(event.data.urls || []));
  }
});