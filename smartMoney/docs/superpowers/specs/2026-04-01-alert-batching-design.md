# Alert Notification Batching

## Overview

Batch multiple alerts for the same user into a single WebPush and a single email notification. Instead of sending notifications immediately when alerts are created, save the alert to the database and dispatch a delayed job. The job collects all un-notified alerts for the user and sends one combined notification.

## Decisions

- **Architecture**: Delayed job per user (`SendBatchedNotifications`), dispatched from `Alert::createAlert()`
- **Batch window**: 5 seconds default, configurable via `Setting::get('alert_batch_delay', 5)`
- **Tracking**: New `notified_at` timestamp on `alerts` table (null = pending)
- **Unified path**: `Alert::notify()` removed; `newTransaction()` now uses `createAlert()` (all alerts get DB records)
- **Format (batched)**: Summary header + bullet list of titles and messages
- **WebPush truncation**: Full body first → titles-only fallback → hard truncate at 197+`...`
- **Email**: Always full body (no truncation)
- **Sync driver**: If queue driver is `sync`, send immediately without delay (avoid blocking)

---

## 1. Schema Change

### `alerts` table — new column

```
notified_at: timestamp, nullable, default null
```

- `null` = notification not yet sent
- Set to `now()` when the batched job sends the notification
- Add to `$fillable` and `$casts` on Alert model

---

## 2. SendBatchedNotifications Job

New job: `app/Jobs/SendBatchedNotifications.php`

**Input**: `user_id` (int)

**Flow**:
1. Load user, set locale
2. Query: `Alert::where('user_id', $userId)->whereNull('notified_at')->get()`
3. If empty, return (another job instance already processed them)
4. Mark all as notified: `->update(['notified_at' => now()])` (prevents duplicate sends)
5. Build notification content:

**Single alert (count = 1)**:
- Title: `alert.title`
- Body: `alert.message`

**Batched alerts (count > 1)**:
- Title: `"{count} new alerts"` (localized via `alert.batched_title`)
- Full body (for email):
  ```
  • {alert1.title}
  {alert1.message}

  • {alert2.title}
  {alert2.message}
  ```
- WebPush body: build full body first. If > 200 chars, try titles-only:
  ```
  • {alert1.title}
  • {alert2.title}
  ```
  If still > 200 chars, truncate at 197 chars + `...`

6. Send one `WebPush` notification to user
7. If `user.alert_via_email`, send one `AlertEmail` notification to user

---

## 3. Alert Model Changes

### Remove `notify()` method

No longer needed. All paths go through `createAlert()`.

### Update `createAlert()`

**Before** (current):
```php
public static function createAlert($title, $message, $user, ...) {
    $user->notify(new WebPush($title, $message));
    // email commented out
    $alert = new Alert();
    // ... save
}
```

**After**:
```php
public static function createAlert($title, $message, $user, ...) {
    $alert = new Alert();
    // ... save (with notified_at = null)

    // Dispatch batched notification job
    $delay = (int) Setting::get('alert_batch_delay', 5);
    if (config('queue.default') === 'sync') {
        // Sync driver: send immediately, no batching
        SendBatchedNotifications::dispatchSync($user->id);
    } else {
        SendBatchedNotifications::dispatch($user->id)->delay(now()->addSeconds($delay));
    }
}
```

### Update `newTransaction()`

Change `Alert::notify(...)` to `Alert::createAlert(...)`, passing `transaction_journal_id` and `data`:

```php
Alert::createAlert(
    title: $title,
    message: $message,
    user: $user,
    transaction_journal_id: $transaction->transaction_journal_id ?? null,
    data: ['transaction_id' => $transaction->transaction_journal_id]
);
```

This means transaction alerts now get DB records (side benefit: visible in alerts UI).

### `createAlertWithAdminCopy()` — no changes

Already calls `createAlert()` internally. Each user (including admin) gets their own delayed job dispatch, so batching is per-user.

---

## 4. Error Handling

- **Job failure**: Alerts stay in DB with `notified_at = null`. Laravel queue retry handles re-attempts. Alerts are never lost.
- **Idempotency**: Step 4 (marking `notified_at`) acts as a lock. Concurrent job instances for the same user — second one finds zero un-notified alerts and exits.
- **WebPush service down**: Exception logged, job retries. Email may still succeed (separate try).
- **Sync queue driver**: Skip delay, dispatch synchronously. No batching in sync mode to avoid blocking requests.

---

## 5. Files to Create/Modify

### New files:
- `database/migrations/XXXX_add_notified_at_to_alerts_table.php`
- `app/Jobs/SendBatchedNotifications.php`

### Modified files:
- `app/Models/Alert.php` — remove `notify()`, update `createAlert()`, update `newTransaction()`
- `lang/en/alert.php` — add `batched_title`
- `lang/ar/alert.php` — add `batched_title`

### Unchanged:
- `app/Notifications/WebPush.php`
- `app/Notifications/AlertEmail.php`
- `app/Jobs/parseSMSJob.php`
- `app/Console/Commands/DailySpendingCheck.php`
