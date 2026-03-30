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
use App\Services\fireflyIII;

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected static $fireflyIII;
    protected static $SMS_sender;
    /**
     * Execute the console command.
     */
    public function handle()
    {

    $user = User::find(1);
    Alert::createAlert(
        title: 'Test Alert 2',
        message: 'This is a test alert.',
        user: $user,
        data: ['foo' => 'bar']
    );

    exit;

    $date = '2026-0' . mt_rand(1,3) . '-' . mt_rand(1,28) . ' ' . mt_rand(10,23) . ':' . mt_rand(10,59) . ':' . mt_rand(10,59); 

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
