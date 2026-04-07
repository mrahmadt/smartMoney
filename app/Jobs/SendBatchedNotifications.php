<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\User;
use App\Notifications\AlertEmail;
use App\Notifications\ApnPush;
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
        if (! $user) {
            return;
        }

        // Grab all un-notified alerts for this user
        $alerts = Alert::where('user_id', $this->userId)
            ->whereNull('notified_at')
            ->orderBy('created_at')
            ->get();

        if ($alerts->isEmpty()) {
            return;
        }

        // Mark as notified immediately to prevent duplicate sends
        Alert::where('user_id', $this->userId)
            ->whereIn('id', $alerts->pluck('id'))
            ->update(['notified_at' => now()]);

        app()->setLocale($user->language ?? 'en');

        if ($alerts->count() === 1) {
            $alert = $alerts->first();
            $title = $alert->title;
            $webPushBody = $alert->message;
            $emailBody = $alert->message;
            $url = $alert->transaction_journal_id
                ? '/transactions/'.$alert->transaction_journal_id.'/edit'
                : '/alerts/'.$alert->id;
        } else {
            $title = __('alert.batched_title', ['count' => $alerts->count()]);
            $emailBody = $this->buildFullBody($alerts);
            $webPushBody = $this->buildWebPushBody($alerts);
            $url = '/alerts';
        }

        $tag = 'alert-batch-'.$this->userId.'-'.now()->timestamp;
        $user->notify(new WebPush($title, $webPushBody, $url, $tag));
        if ($user->alert_via_email) {
            $user->notify(new AlertEmail($title, $emailBody));
        }

        // Send to iOS devices via APNs (if configured)
        $this->sendToDeviceTokens($user, $title, $webPushBody);
    }

    protected function sendToDeviceTokens($user, string $title, string $body): void
    {
        $tokens = $user->routeNotificationForApn();
        if (empty($tokens)) {
            return;
        }

        try {
            $user->notify(new ApnPush($title, $body));
            \Log::debug('APNs push sent', ['user_id' => $user->id, 'device_count' => count($tokens)]);
        } catch (\Exception $e) {
            \Log::warning('APNs push failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
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
        return mb_substr($titlesOnly, 0, $maxLength - 3).'...';
    }
}
