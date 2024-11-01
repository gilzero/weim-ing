/**
 * @file
 * Serviceworker file for Push framework Notifications push notifications.
 */

/**
 * Install service worker.
 *
 * @todo Implement some caching.
 */
self.addEventListener('install', (event) => {
  event.waitUntil(self.skipWaiting());
});

/**
 * Activate service worker.
 */
self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

/**
 * Push event listener.
 */
self.addEventListener('push', (event) => {
  if (!(self.Notification && self.Notification.permission === 'granted')) {
    return;
  }

  if (event.data) {
    const data = event.data.json();
    event.waitUntil(
      self.registration.showNotification(data.title, {
        body: data.body,
        icon: data.icon,
        data: {
          url: data.url
        }
      })
    );
  }
});

/**
 * Notification click event listener.
 *
 * @todo Make a logic/request here to update notification to seen (?)
 */
self.addEventListener('notificationclick', function (event) {
  event.waitUntil(
    self.clients.matchAll({ type: 'window' }).then(function (clientList) {
      const url = event.notification.data.url || '/';
      for (let i = 0; i < clientList.length; i++) {
        const client = clientList[i];
        if (client.url === url && 'focus' in client) {
          return client.focus();
        }
      }
      return self.clients.openWindow(url);
    })
  );
});
