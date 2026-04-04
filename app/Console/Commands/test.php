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

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test {action?}';

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
        $this->info('  php artisan app:test push          — Send simple push to user 1');
        $this->info('  php artisan app:test push-actions   — Send push with action buttons to user 1');
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

        $user->notify(new class('What is your best food?', 'Pick one of the options below') extends \Illuminate\Notifications\Notification implements \Illuminate\Contracts\Queue\ShouldQueue {
            use \Illuminate\Bus\Queueable;

            public function __construct(public string $title, public string $body) {}

            public function via($notifiable): array
            {
                return [WebPushChannel::class];
            }

            public function toWebPush($notifiable, $notification): WebPushMessage
            {
                return (new WebPushMessage)
                    ->title($this->title)
                    ->body($this->body)
                    ->icon('/img/icon-192x192.png')
                    ->vibrate([200, 100, 200])
                    ->action('Pizza', 'pizza')
                    ->action('Burger', 'burger')
                    ->data(['question' => 'best_food'])
                    ->options(['TTL' => 1000]);
            }
        });

        $this->info('Push notification with actions sent to user 1.');
    }
    $data = Transaction::cleanName('ALDREwES');
    dd($data);
    exit;
        $fireflyIII = new fireflyIII();
        $transaction = $fireflyIII->getTransaction(322);
        $status = [
            'attributes' => $transaction,
        ];
        $date = Carbon::parse($status['attributes']->date)->format('Y-m-d');
        $amount = 30000;
        $destination_id = 13;
        $budget_id = null;
        $transaction_journal_id = 322;
        $categoryName = 'Transportation';
        $user_id = 1;


        $destAbnormal = Transaction::abnormalDestinationAmount(
            amount: $amount,
            destination_id: $destination_id,
            transaction_journal_id: $transaction_journal_id,
            budget_id: $budget_id,
        );
        if ($destAbnormal) {
            $destName = $status['attributes']->destination_name ?? 'Unknown';
            app()->setLocale($user->language ?? 'en');
            dd(
                title: __('alert.abnormal_destination_title', ['destination' => $destName]),
                message: __('alert.abnormal_destination_message', [
                    'multiplier' => $destAbnormal['multiplier'],
                    'destination' => $destName,
                    'amount' => str_replace('.00', '', number_format($destAbnormal['amount'], 2)),
                    'average_amount' => str_replace('.00', '', number_format($destAbnormal['average_amount'], 2)),
                ]),
                user_id: 1,
                transaction_journal_id: 1,
                data: $destAbnormal,
                pin: true,
            );
        }
        dd($destAbnormal);

        exit;
        $user = User::find(1);
        Alert::createAlert(
            title: 'Test Alert 2',
            message: 'This is a test alert.',
            user: $user,
            data: ['foo' => 'bar']
        );

        exit;

        $date = '2026-0' . mt_rand(1, 3) . '-' . mt_rand(1, 28) . ' ' . mt_rand(10, 23) . ':' . mt_rand(10, 59) . ':' . mt_rand(10, 59);

        $message = "PoS Purchase
By: ***7632;Credit Card
Amount: USD 100.00
At: ALDREwES 239
Balance: USD 122238785.99
Date: ' . $date . '";

        $content = [
            'app' => [
                'version' => '1.1',
            ],
            'query' => [
                'sender' => 'saib',
                'message' => [
                    'text' => "PoS Purchase\nBy: ***7632;Credit Card\nAmount: USD 100.00\nAt: ALDREwES 239\nBalance: USD 122238785.99\nDate: $date",
                ],
            ],
            '_version' => 1,
        ];

        $sms = new SMS();
        $sms->sender = 'saib';
        $sms->content = $content;
        $sms->message = $message;
        $sms->is_processed = false;
        $sms->save();

        (new parseSMSJob($sms))->handle();
    }
}
