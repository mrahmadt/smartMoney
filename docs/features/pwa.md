# PWA Support

SmartMoney is a Progressive Web App (PWA) that can be installed on mobile and desktop devices with push notification support.

## Features

- **Installable:** Users can add SmartMoney to their home screen on mobile or install it as a desktop app
- **Push Notifications:** Real-time browser notifications for transactions and alerts (even when the app is in the background)
- **Standalone Mode:** Runs without browser chrome when installed

## Web App Manifest

File: `public/manifest.json`

```json
{
    "name": "Budget",
    "short_name": "Budget",
    "display": "standalone",
    "theme_color": "#3b43ff"
}
```

Includes icons at 128px, 192px, and 512px for various device sizes.

## Service Worker

File: `public/sw.js`

Handles:
- **Push events:** Receives and displays Web Push notifications
- **Notification clicks:** Opens the app when a notification is tapped
- **Background sync:** Maintains push subscription while app is closed

## Web Push Notifications

### VAPID Authentication

Uses the Web Push protocol with VAPID keys:

```env
VAPID_PUBLIC_KEY=your-public-key
VAPID_PRIVATE_KEY=your-private-key
VAPID_SUBJECT=mailto:your@email.com
```

Generate keys with: `php artisan webpush:vapid`

### Subscription Management

- **Subscribe:** `POST /webpush/subscribe` — registers the browser's push subscription
- **Unsubscribe:** `DELETE /webpush/unsubscribe` — removes the subscription
- **Settings page:** Users can manage their push notification preferences in the Web Push Settings page

### Push Prompt

The `WebPushPrompt` dashboard widget automatically appears for users who haven't subscribed to push notifications, encouraging them to enable real-time alerts.

## Key Files

| File | Purpose |
|------|---------|
| `public/manifest.json` | PWA manifest |
| `public/sw.js` | Service Worker |
| `app/Http/Controllers/WebPushSubscriptionController.php` | Subscribe/unsubscribe endpoints |
| `app/Notifications/WebPush.php` | Push notification class |
| `app/Filament/Pages/WebPushSettings.php` | Push preference page |
| `app/Filament/Widgets/WebPushPrompt.php` | Dashboard prompt widget |
| `config/webpush.php` | VAPID configuration |
