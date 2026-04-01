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
