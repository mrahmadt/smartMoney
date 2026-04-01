# Alert Notification Batching — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Batch multiple alerts for the same user into a single WebPush + email notification using a delayed job.

**Architecture:** Save alerts to DB immediately, dispatch delayed `SendBatchedNotifications` job per user. Job collects un-notified alerts and sends combined notification.

**Tech Stack:** Laravel 12, Queue (database driver), WebPush, Mail

**Spec:** `docs/superpowers/specs/2026-04-01-alert-batching-design.md`

---

## File Structure

### New Files
| File | Responsibility |
|------|---------------|
| `database/migrations/2026_04_01_210000_add_notified_at_to_alerts_table.php` | Add nullable timestamp column |
| `app/Jobs/SendBatchedNotifications.php` | Delayed job: collect + combine + send notifications |

### Modified Files
| File | Change |
|------|--------|
| `app/Models/Alert.php` | Remove `notify()`, update `createAlert()` to save-then-dispatch, update `newTransaction()` to use `createAlert()` |
| `lang/en/alert.php` | Add `batched_title` key |
| `lang/ar/alert.php` | Add `batched_title` key |

---

## Task 1: Migration — add `notified_at` to alerts

**Files:**
- Create: `database/migrations/2026_04_01_210000_add_notified_at_to_alerts_table.php`

- [ ] **Step 1: Create migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('is_pinned');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`
Expected: Migration completes successfully.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_04_01_210000_add_notified_at_to_alerts_table.php
git commit -m "feat: add notified_at column to alerts table for batching"
```

---

## Task 2: SendBatchedNotifications Job

**Files:**
- Create: `app/Jobs/SendBatchedNotifications.php`

- [ ] **Step 1: Create the job**

```php
<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\User;
use App\Notifications\AlertEmail;
use App\Notifications\WebPush;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBatchedNotifications implements ShouldQueue
{
    use Queueable;

    protected int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user) return;

        // Grab all un-notified alerts for this user
        $alerts = Alert::where('user_id', $this->userId)
            ->whereNull('notified_at')
            ->orderBy('created_at')
            ->get();

        if ($alerts->isEmpty()) return;

        // Mark as notified immediately to prevent duplicate sends
        Alert::where('user_id', $this->userId)
            ->whereIn('id', $alerts->pluck('id'))
            ->update(['notified_at' => now()]);

        app()->setLocale($user->language ?? 'en');

        if ($alerts->count() === 1) {
            $title = $alerts->first()->title;
            $webPushBody = $alerts->first()->message;
            $emailBody = $alerts->first()->message;
        } else {
            $title = __('alert.batched_title', ['count' => $alerts->count()]);
            $emailBody = $this->buildFullBody($alerts);
            $webPushBody = $this->buildWebPushBody($alerts);
        }

        $user->notify(new WebPush($title, $webPushBody));
        if ($user->alert_via_email) {
            $user->notify(new AlertEmail($title, $emailBody));
        }
    }

    protected function buildFullBody($alerts): string
    {
        return $alerts->map(function ($alert) {
            return "• {$alert->title}\n{$alert->message}";
        })->implode("\n\n");
    }

    protected function buildWebPushBody($alerts): string
    {
        $maxLength = 200;

        // Try full body (titles + messages)
        $full = $this->buildFullBody($alerts);
        if (mb_strlen($full) <= $maxLength) {
            return $full;
        }

        // Fallback: titles only
        $titlesOnly = $alerts->map(fn ($a) => "• {$a->title}")->implode("\n");
        if (mb_strlen($titlesOnly) <= $maxLength) {
            return $titlesOnly;
        }

        // Hard truncate
        return mb_substr($titlesOnly, 0, $maxLength - 3) . '...';
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Jobs/SendBatchedNotifications.php
git commit -m "feat: add SendBatchedNotifications job for alert batching"
```

---

## Task 3: Update Alert Model

**Files:**
- Modify: `app/Models/Alert.php`

- [ ] **Step 1: Add `notified_at` to fillable and casts**

Add `'notified_at'` to `$fillable` array.
Add `'notified_at' => 'datetime'` to `$casts` array.

- [ ] **Step 2: Update `newTransaction()` — switch from `notify()` to `createAlert()`**

Replace:
```php
Alert::notify(
    title: $title,
    message: $message,
    user: $user,
    data: [
        'transaction_id' => $transaction->transaction_journal_id
    ]
);
```

With:
```php
Alert::createAlert(
    title: $title,
    message: $message,
    user: $user,
    transaction_journal_id: $transaction->transaction_journal_id ?? null,
    data: [
        'transaction_id' => $transaction->transaction_journal_id
    ]
);
```

- [ ] **Step 3: Update `createAlert()` — save then dispatch instead of send inline**

Replace the entire method with:
```php
public static function createAlert($title, $message, $user, $transaction_journal_id = null, $data = [], $pin = false)
{
    $alert = new Alert();
    $alert->title = $title;
    $alert->transaction_journal_id = $transaction_journal_id;
    $alert->user_id = $user->id;
    $alert->message = $message;
    if ($data) $alert->data = $data;
    if ($pin) $alert->is_pinned = true;
    $alert->save();

    $delay = (int) Setting::get('alert_batch_delay', 5);
    if (config('queue.default') === 'sync') {
        SendBatchedNotifications::dispatchSync($user->id);
    } else {
        SendBatchedNotifications::dispatch($user->id)->delay(now()->addSeconds($delay));
    }
}
```

- [ ] **Step 4: Remove `notify()` method**

Delete the `notify()` static method entirely.

- [ ] **Step 5: Add imports**

Add to the top:
```php
use App\Jobs\SendBatchedNotifications;
use App\Models\Setting;
```

- [ ] **Step 6: Commit**

```bash
git add app/Models/Alert.php
git commit -m "feat: update Alert model to use batched notifications"
```

---

## Task 4: Translations

**Files:**
- Modify: `lang/en/alert.php`
- Modify: `lang/ar/alert.php`

- [ ] **Step 1: Add English translation**

Add to `lang/en/alert.php`:
```php
'batched_title' => ':count new alerts',
```

- [ ] **Step 2: Add Arabic translation**

Add to `lang/ar/alert.php`:
```php
'batched_title' => ':count تنبيهات جديدة',
```

- [ ] **Step 3: Commit**

```bash
git add lang/en/alert.php lang/ar/alert.php
git commit -m "feat: add batched alert title translations (en + ar)"
```

---

## Task 5: Verification

- [ ] **Step 1: Run migrations** — `php artisan migrate` (exit 0)
- [ ] **Step 2: Run tests** — `php artisan test` (155 pass, same pre-existing failures)
- [ ] **Step 3: Verify app boots** — `php artisan about` (no errors)
- [ ] **Step 4: Verify column exists** — `php artisan tinker` check `notified_at` column
