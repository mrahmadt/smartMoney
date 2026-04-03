# Alert Notification System

SmartMoney uses a batched notification system that groups multiple alerts into a single Web Push and/or email notification per user.

## How It Works

### Alert Creation

All alerts flow through `Alert::createAlert()`:
1. Saves the alert to the `alerts` table (with `notified_at = null`)
2. Dispatches a `SendBatchedNotifications` job with a configurable delay (default: 5 seconds)

### Batching

The `SendBatchedNotifications` job:
1. Waits the configured delay (allowing multiple alerts to accumulate)
2. Queries all un-notified alerts for the user (`notified_at IS NULL`)
3. Marks them as notified (`notified_at = now()`) to prevent duplicate sends
4. Builds the notification:
   - **Single alert:** Sends title and message as-is
   - **Multiple alerts:** Combines into a summary notification

### Combined Format

For multiple alerts:

**Title:** "{count} new alerts" (localized)

**Email body (full):**
```
• Spent 45 SAR
Spent 45 SAR - STARBUCKS (Cafe) - My Account

• Unusual Spend at ALDREES
This is 4x your normal spend at ALDREES. Amount: 3,000, Average: 750
```

**Web Push body (truncated for 200-char limit):**
- First tries full body with titles + messages
- Falls back to titles only if too long
- Hard truncates at 197 chars + "..." as last resort

### Delivery Channels

- **Web Push:** Always sent (uses VAPID protocol via `NotificationChannels\WebPush`)
- **Email:** Only sent if `user.alert_via_email` is `true` (uses Laravel's `MailMessage`)

### Sync Driver Handling

If the queue driver is `sync` (no queue worker), the job runs synchronously without delay to avoid blocking the request.

## Alert Topics

Each alert has a `topic` field for classification:

| Topic | Source | Visible by default |
|-------|--------|---------------------|
| `transaction` | New transaction created | Hidden (cleaned after 5 days) |
| `abnormal` | Real-time anomaly detection | Yes |
| `report` | Scheduled spending checks | Yes |
| `subscription` | Subscription detector | Yes |

### Topic Filtering

The alerts table in the UI shows a topic filter dropdown:
- Default view hides `transaction` topic alerts
- Users can filter by specific topic or show all

### Cleanup

The `app:CleanupAlerts` command:
- Deletes **read** alerts older than `cleanup_alerts_days` (default: 30)
- Deletes **transaction** topic alerts (read or unread) older than `cleanup_transaction_alerts_days` (default: 5)

## Alert Methods

| Method | DB Record | Notification | Use Case |
|--------|-----------|-------------|----------|
| `Alert::createAlert()` | Yes | Yes (batched) | Standard alerts |
| `Alert::createAlertWithAdminCopy()` | Yes (user + admin) | Yes (batched, both users) | Anomaly alerts needing admin visibility |
| `Alert::newTransaction()` | Yes | Yes (batched) | New transaction notifications |

## Pinned Alerts

Alerts can be pinned (`is_pinned = true`), which:
- Shows them in the Dashboard's **Pinned Alerts** banner
- Keeps them visible until manually dismissed
- Used for anomaly alerts that need attention

## Settings

| Key | Default | Description |
|-----|---------|-------------|
| `alert_batch_delay` | `5` | Seconds to wait before sending batched notifications |
| `cleanup_alerts_days` | `30` | Days to keep read alerts |
| `cleanup_transaction_alerts_days` | `5` | Days to keep transaction alerts |

## Key Files

| File | Purpose |
|------|---------|
| `app/Models/Alert.php` | Alert model with `createAlert()`, `createAlertWithAdminCopy()`, `newTransaction()` |
| `app/Jobs/SendBatchedNotifications.php` | Batching logic, message combining, truncation |
| `app/Notifications/WebPush.php` | Web Push notification |
| `app/Notifications/AlertEmail.php` | Email notification |
| `app/Console/Commands/CleanupAlerts.php` | Alert cleanup command |
| `app/Filament/Resources/Alerts/` | Alert UI (list, view, filters) |
| `app/Filament/Widgets/PinnedAlertsBanner.php` | Dashboard pinned alerts widget |
