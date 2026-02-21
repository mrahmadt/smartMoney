<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SMSSender;
use App\Models\SMS;
use App\Jobs\parseSMSJob;
    use Carbon\Carbon;
use App\Models\Transaction;
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
        $source_id = 1;
        $fireflyIII = new fireflyIII();
        $account = $fireflyIII->getAccount($source_id);
        $codes = $fireflyIII->getAccountSMSConfig($account->data->attributes);
        dd($codes);

    exit;
        $sms_id = 1;
        $sms = SMS::find($sms_id);

        dispatch(new parseSMSJob($sms))->onConnection('sync');

        exit;

        $this->generateDummyTransactions(200);
        exit;   

        // dd($sms, $SMS_sender);
        // $sms_message = file_get_contents(storage_path('app/prompts/sample_sms_message.txt'));

    }

public function generateDummyTransactions(int $count = 50): void
{
    $SMS_sender = SMSSender::find(1);

    $categories = [
        'Food',
        'Entertainment',
        'Transportation',
        'Shopping',
        'Bills',
        'Utilities',
        'Healthcare',
        'Education',
        'Travel',
        'Other',
    ];

    $descriptions = [
        'Restaurant payment',
        'Supermarket',
        'Taxi ride',
        'Online purchase',
        'Electricity bill',
        'Water bill',
        'Pharmacy',
        'Movie ticket',
        'Fuel station',
        'Coffee shop',
    ];

    $start = Carbon::parse('2025-12-01T00:00:00+00:00');
    $end   = Carbon::parse('2026-02-28T23:59:59+00:00');

    for ($i = 0; $i < $count; $i++) {

        $randomDate = Carbon::createFromTimestamp(
            rand($start->timestamp, $end->timestamp)
        )->toIso8601String(); // 2026-02-01T00:00:00+00:00 format

        $transaction = new Transaction();

        $transaction->createTransaction(
            [
                'type' => 'withdrawal',
                'amount' => rand(10, 1000),
                'currency' => 'SAR',
                'transactionDateTime' => $randomDate,
                'description' => $descriptions[array_rand($descriptions)],
                'category_name' => $categories[array_rand($categories)],
                'MyAccountNumber' => 'XXX7001',
                'OtherAccountName' => 'Merchant ' . rand(100, 999),
            ],
            $SMS_sender
        );
    }
}
}
