<?php

namespace App\Console\Commands;
use App\Helpers\fireflyIII;

use Illuminate\Console\Command;
use App\Models\Alert;
use App\Models\User;

class billDetector extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:billDetector';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect recurring transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if(!config('billDetector.enabled')) {
            $this->info('Bill Detector is disabled');
            return;
        }
        $fireflyIII = new fireflyIII();

        $filter = [];
        $total_pages = 1;
        $today = date('Y-m-d');
        $transactions = [];
        $xMonthsAgo = date('Y-m-d', strtotime('-'.config('billDetector.go_back_days').' days', strtotime($today)));
        for($page=1; $page<=$total_pages; $page++){
            // $this->info('Page: '.$page);
            // dd($today, $xMonthsAgo);
            $more_transactions = $fireflyIII->getTransactions($today, $xMonthsAgo, $filter, 50, $page, 'withdrawal');
            if($more_transactions == false)  break;
            if($more_transactions->meta->pagination->current_page == 1){
                $total_pages = $more_transactions->meta->pagination->total_pages;
            }
            $transactions = array_merge($transactions, $more_transactions->data);
        }

        $recurringDetected = $this->detectRecurringTransactions($transactions);

        $fireflyIII->createRuleGroup(['title'=>'Bills']);

        foreach($recurringDetected as $repeat_freq => $items){
            foreach($items as $item){
                $billTitle =  $item['destination_name'];
                $RoleTitle =  $item['destination_name'] . ' bills';
                $bill = $fireflyIII->createBill([
                    'name' => $billTitle,
                    'amount_min' => $item['minAmount'],
                    'amount_max' => $item['maxAmount'],
                    'repeat_freq' => $repeat_freq,
                    'date' => $item['date'],
                ]);
                if($bill){
                    
                    $users = User::where('alertNewBillCreation', true)->get();
                    foreach($users as $user){
                        Alert::createAlert('Bill Created', 'New bill has been created (' . $billTitle . ')', $user);
                    }
                    $this->info('Bill created for '.$item['destination_name']);
                    $role = $fireflyIII->createRole([
                        'title' => $RoleTitle,
                        'description' => 'Flag ' . $item['destination_name'] . ' transaction as bill',
                        'rule_group_title' => 'Bills',
                        'trigger' => 'store-journal',
                        'triggers' => [
                            [
                                'type' => 'destination_account_id',
                                'value' => $item['destination_id'],
                            ],
                            [
                                'type' => 'transaction_type',
                                'value' => 'withdrawal',
                            ]
                        ],
                        'actions' => [
                            [
                                'type' => 'link_to_bill',
                                'value' => $bill->data->attributes->name,
                            ]
                        ],
                    ]);
                    if($role){
                        $this->info('role created for '.$item['destination_name']);
                       
                        $triggerRole = $fireflyIII->triggerRole(['id'=>$role->data->id]);
                        if($triggerRole){
                            $this->info('trigger role created for '.$item['destination_name']);
                        }else{
                            $this->error('Failed to create trigger role for '.$item['destination_name']);
                        }
                    }else{
                        $this->error('Failed to create role for '.$item['destination_name']);
                    }
                }else{
                    $this->error('Failed to create bill for '.$item['destination_name']);
                }
            }

        }
    }

    public function detectRecurringTransactions($transactions) {
        $recurringTransactions = [
            'daily' => [],
            'weekly' => [],
            'monthly' => [],
            'quarterly' => [],
            'half-year' => [],
            'yearly' => [],
        ];
        krsort($transactions);
        $previousTransactions = [];

        foreach ($transactions as $transaction) {
            $trans = $transaction->attributes->transactions[0];
                // Check if the transaction has a bill_id
                if (!empty($trans->bill_id)) {
                    continue; // Skip if it has a bill_id
                }
                
                // Get the transaction details
                $date = new \DateTime($trans->date);
                $amount = $trans->amount;
                $destination_id = $trans->destination_id;

                // print explode('T',$trans->date)[0] . ' ' . $trans->destination_name . "\n";
                if(!isset($previousTransactions[$destination_id]['date'])){
                    $previousTransactions[$destination_id] = [
                        'date' => $date->format('c'),
                        'maxAmount' => 0,
                        'minAmount' => 0,
                        'count' => 1,
                        'type' => null,
                        'likelyType' => null,
                        'destination_name' => $trans->destination_name,
                        'destination_id' => $trans->destination_id,
                        // 'dateDiff' => [],
                        // 'transactions' => []
                    ];
                }else{
                    $prevDate = new \DateTime($previousTransactions[$destination_id]['date']);
                    $dateDiff = abs($date->diff($prevDate)->days);

                    // $amountDiff = abs($amount - $previousTransactions[$destination_id]['amount']);
                    // $amountDiff_OK =  $amountDiff <= ($previousTransactions[$destination_id]['amount'] * 0.05);
                    // $recurringTransactions[$destination_id]['amount'] = $amount;
                    $previousTransactions[$destination_id]['date'] = $date->format('c');
                    
                    if($amount > $previousTransactions[$destination_id]['maxAmount']){
                        $previousTransactions[$destination_id]['maxAmount'] = $amount;
                    }
                    if($amount < $previousTransactions[$destination_id]['minAmount'] || $previousTransactions[$destination_id]['minAmount'] == 0){
                        $previousTransactions[$destination_id]['minAmount'] = $amount;
                    }

                    if($dateDiff == 0) continue;

                    if($dateDiff <= 4 && ($previousTransactions[$destination_id]['likelyType'] == null || $previousTransactions[$destination_id]['likelyType'] == 'daily')){
                        $previousTransactions[$destination_id]['count']++;
                        $previousTransactions[$destination_id]['likelyType'] = 'daily';
                        if($previousTransactions[$destination_id]['count'] >= 7){
                            $previousTransactions[$destination_id]['type'] = 'daily';
                        }

                    }elseif( $dateDiff >= 5 && $dateDiff <= 8 && ($previousTransactions[$destination_id]['likelyType'] == null || $previousTransactions[$destination_id]['likelyType'] == 'weekly')){
                        $previousTransactions[$destination_id]['count']++;
                        $previousTransactions[$destination_id]['likelyType'] = 'weekly';
                        if($previousTransactions[$destination_id]['count'] >= 3){
                            $previousTransactions[$destination_id]['type'] = 'weekly';
                        }

                    }elseif($dateDiff >= 28 && $dateDiff <= 34 && ($previousTransactions[$destination_id]['likelyType'] == null || $previousTransactions[$destination_id]['likelyType'] == 'monthly')){
                        $previousTransactions[$destination_id]['count']++;
                        $previousTransactions[$destination_id]['likelyType'] = 'monthly';
                        if($previousTransactions[$destination_id]['count'] >= 3){
                            $previousTransactions[$destination_id]['type'] = 'monthly';
                        }

                    }elseif($dateDiff >= 80 && $dateDiff <= 100 && ($previousTransactions[$destination_id]['likelyType'] == null || $previousTransactions[$destination_id]['likelyType'] == 'quarterly')){
                        $previousTransactions[$destination_id]['count']++;
                        $previousTransactions[$destination_id]['likelyType'] = 'quarterly';
                        if($previousTransactions[$destination_id]['count'] >= 2){
                            $previousTransactions[$destination_id]['type'] = 'quarterly';
                        }
                    }elseif($dateDiff >= 170 && $dateDiff <= 200 && ($previousTransactions[$destination_id]['likelyType'] == null || $previousTransactions[$destination_id]['likelyType'] == 'half-year')){
                        $previousTransactions[$destination_id]['count']++;
                        $previousTransactions[$destination_id]['likelyType'] = 'half-year';
                        if($previousTransactions[$destination_id]['count'] >= 2){
                            $previousTransactions[$destination_id]['type'] = 'half-year';
                        }
                    }elseif($dateDiff >= 350 && $dateDiff <= 390 && ($previousTransactions[$destination_id]['likelyType'] == null || $previousTransactions[$destination_id]['likelyType'] == 'yearly')){
                        $previousTransactions[$destination_id]['count']++;
                        $previousTransactions[$destination_id]['likelyType'] = 'yearly';
                        if($previousTransactions[$destination_id]['count'] >= 2){
                            $previousTransactions[$destination_id]['type'] = 'yearly';
                        }
                    }
                }
        }
        $recurringTypes = explode(',', config('billDetector.transactions_recurring_types'));

        foreach ($previousTransactions as $destination_id => $previousTransaction) {
            if($previousTransaction['type'] == null) continue;
            if(!in_array($previousTransaction['type'], $recurringTypes)) continue;
            unset($previousTransaction['likelyType']);
            if($previousTransaction['maxAmount'] < config('billDetector.min_amount')) continue;
            $recurringTransactions[$previousTransaction['type']][] = $previousTransaction;
        }

        return $recurringTransactions;
    }

}
