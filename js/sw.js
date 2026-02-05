self.addEventListener('message', (e) => {
    const data = e.data;
    self.registration.showNotification(data.title, {
        body: data.body,
        icon: data.icon,
        data: data.data.url,
    });
});

// Notification click event listener
self.addEventListener('notificationclick', e => {
    // Close the notification popout
    e.notification.close();
    // Get all the Window clients
    e.waitUntil(clients.matchAll({type: 'window'}).then(clientsArr => {
        clients.openWindow(e.notification.data);
    }));
});