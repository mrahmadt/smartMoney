"use strict";

self.addEventListener("install", function(event) {
    self.skipWaiting();
});

self.addEventListener("activate", function(event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener("push", function(event) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    const payload = event.data ? event.data.json() : {};
    event.waitUntil(self.registration.showNotification(payload.title, payload));
});

// self.addEventListener("notificationclick", (e) => {
//     alert("notification clicked");
//     console.log(e);
// });