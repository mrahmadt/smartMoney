<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NotificationChannels\WebPush\PushSubscription;
use App\Notifications\WebPush;
use Minishlink\WebPush\WebPush as WebPushLib;
use Minishlink\WebPush\Subscription;

class CleanupPushSubscriptions extends Command
{
    protected $signature = 'app:CleanupPushSubscriptions';

    protected $description = 'Remove stale/expired push subscriptions that return 404 or 410';

    public function handle(): void
    {
        $subscriptions = PushSubscription::all();
        $deleted = 0;

        $auth = [
            'VAPID' => [
                'subject' => config('webpush.vapid.subject'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ];

        $webPush = new WebPushLib($auth);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
                'contentEncoding' => $sub->content_encoding ?? 'aesgcm',
            ]);

            $webPush->queueNotification($subscription, json_encode([
                'title' => 'ping',
                'body' => '',
            ]));
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                $endpoint = $report->getRequest()->getUri()->__toString();
                PushSubscription::where('endpoint', $endpoint)->delete();
                $deleted++;
                $this->line("Deleted stale: {$endpoint}");
            }
        }

        $this->info("Cleaned up {$deleted} stale subscription(s) out of {$subscriptions->count()} total.");
    }
}
