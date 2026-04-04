<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SMSSender;
use App\Models\SMS;
use App\Jobs\parseSMSJob;
use Carbon\Carbon;
use App\Models\Transaction;
use App\Models\Alert;
use App\Models\User;
use App\Ai\Agents\parseSMS;
use App\Notifications\WebPush;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use App\Services\fireflyIII;

class WebPushTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:webpush {action?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test commands (push, push-actions)';

    protected static $fireflyIII;
    protected static $SMS_sender;
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        if ($action === 'push') {
            return $this->testSimplePush();
        }

        if ($action === 'push-actions') {
            return $this->testPushWithActions();
        }

        $this->info('Available actions: push, push-actions');
        $this->info('  php artisan app:webpush push          — Send simple push to user 1');
        $this->info('  php artisan app:webpush push-actions   — Send push with action buttons to user 1');
    }

    protected function testSimplePush(): void
    {
        $user = User::find(1);
        if (!$user) {
            $this->error('User 1 not found');
            return;
        }

        $user->notify(new WebPush(
            title: 'Test Notification',
            body: 'This is a test push notification from SmartMoney.',
            url: '/alerts'
        ));

        $this->info('Simple push notification sent to user 1.');
    }

    protected function testPushWithActions(): void
    {
        $user = User::find(1);
        if (!$user) {
            $this->error('User 1 not found');
            return;
        }

        $user->notify(new \App\Notifications\WebPushWithActions(
            title: 'What is your best food?',
            body: 'Pick one of the options below',
            actions: [
                ['label' => 'Pizza', 'action' => 'pizza'],
                ['label' => 'Burger', 'action' => 'burger'],
            ],
            data: ['question' => 'best_food'],
        ));

        $this->info('Push notification with actions sent to user 1.');
    }
}
