const CACHE_NAME = 'smartmoney-v1';
const OFFLINE_URL = '/offline.html';

// Cache app shell on install
self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return cache.addAll([
        '/',
        '/offline.html',
        '/favicon.ico',
      ]);
    })
  );
  self.skipWaiting();
});

// Clean old caches on activate and check push subscription
self.addEventListener('activate', function (event) {
  event.waitUntil(
    Promise.all([
      // Clean old caches
      caches.keys().then(function (names) {
        return Promise.all(
          names.filter(function (name) { return name !== CACHE_NAME; })
               .map(function (name) { return caches.delete(name); })
        );
      }),
      // Check if push subscription is still valid after SW update
      self.registration.pushManager.getSubscription().then(function (sub) {
        if (!sub) {
          // Notify clients that re-subscription may be needed
          self.clients.matchAll({ type: 'window' }).then(function (clients) {
            clients.forEach(function (client) {
              client.postMessage({ type: 'push-subscription-lost' });
            });
          });
        }
      }),
    ])
  );
  self.clients.claim();
});

// Network-first with offline fallback
self.addEventListener('fetch', function (event) {
  if (event.request.method !== 'GET') return;
  event.respondWith(
    fetch(event.request).catch(function () {
      return caches.match(event.request).then(function (response) {
        return response || caches.match(OFFLINE_URL);
      });
    })
  );
});

// Push notifications
self.addEventListener('push', function (event) {
  const data = event.data ? event.data.json() : {};

  // Skip silent ping from subscription cleanup
  if (data.title === 'ping') return;

  const title = data.title || 'Notification';
  const options = {
    body: data.body || '',
    icon: data.icon || '/favicon.ico',
    data: data.data || {},
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

// Notification click — focus existing tab or open new one
self.addEventListener('notificationclick', function (event) {
  event.notification.close();

  // Handle action button clicks
  if (event.action) {
    console.log('Notification action clicked:', event.action, event.notification.data);
    // Could post to server or handle client-side
  }

  const url = (event.notification.data && event.notification.data.url) || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
      for (var i = 0; i < windowClients.length; i++) {
        var client = windowClients[i];
        if (client.url.includes(self.location.origin) && 'focus' in client) {
          client.focus();
          client.navigate(url);
          return;
        }
      }
      return clients.openWindow(url);
    })
  );
});