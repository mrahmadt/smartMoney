<?php

namespace App\Console\Commands;
use App\Services\fireflyIII;

use Illuminate\Console\Command;
use App\Models\Alert;
use App\Models\Setting;
use App\Models\User;

class SubscriptionDetector extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:SubscriptionDetector';

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
        if(!Setting::getBool('SubscriptionDetector_enabled', true)) {
            $this->info('Bill Detector is disabled');
            return;
        }
        $fireflyIII = new fireflyIII();

        // $subscription = $fireflyIII->findSubscription('Merchant 997');

        // $role = $fireflyIII->createRule([
        //                 'title' => 'Merchant 997 subscription',
        //                 'description' => 'Flag Merchant 997 - subscription',
        //                 'rule_group_title' => 'Subscriptions',
        //                 'trigger' => 'store-journal',
        //                 'triggers' => [
        //                     [
        //                         'type' => 'destination_account_is',
        //                         'value' => 276,
        //                     ],
        //                     [
        //                         'type' => 'transaction_type',
        //                         'value' => 'withdrawal',
        //                     ]
        //                 ],
        //                 'actions' => [
        //                     [
        //                         'type' => 'link_to_bill',
        //                         'value' => $subscription->attributes->name,
        //                     ]
        //                 ],
        //             ]);

        // dd($subscription, $role);

        $total_pages = 1;
        $today = date('Y-m-d');
        $transactions = [];
        $xMonthsAgo = date('Y-m-d', strtotime('-'.Setting::getInt('SubscriptionDetector_go_back_days', 120).' days', strtotime($today)));
        for($page=1; $page<=$total_pages; $page++){
            // $this->info('Page: '.$page);
            // dd($today, $xMonthsAgo);
            $more_transactions = $fireflyIII->getTransactions(
                start: $today,
                end: $xMonthsAgo,
                limit: 200,
                page: $page,
                type: 'withdrawal',
            );
            if($more_transactions == false)  break;
            if($fireflyIII->transactionsMeta->pagination->current_page == 1){
                $total_pages = $fireflyIII->transactionsMeta->pagination->total_pages;
            }
            $transactions = array_merge($transactions, $more_transactions);
        }

        $recurringDetected = $this->detectRecurringTransactions($transactions);
        $fireflyIII->createRuleGroup(['title'=>'Subscriptions']);

        foreach($recurringDetected as $repeat_freq => $items){
            foreach($items as $item){
                $subscriptionTitle =  $item['destination_name'];
                $RoleTitle =  $item['destination_name'] . ' subscription';
                $subscription = $fireflyIII->createSubscription([
                    'name' => $subscriptionTitle,
                    'amount_min' => $item['minAmount'],
                    'amount_max' => $item['maxAmount'],
                    'repeat_freq' => $repeat_freq,
                    'date' => $item['date'],
                ]);
                if($subscription){
                    $user = User::find(1);
                    if($user){
                        app()->setLocale($user->language ?? 'en');
                        Alert::createAlert(
                            __('alert.subscription_created_title'),
                            __('alert.subscription_created_message', ['name' => $subscriptionTitle]),
                            $user
                        );
                    }

                    $role = $fireflyIII->createRule([
                        'title' => $RoleTitle,
                        'description' => $item['destination_name'] . ' - subscription',
                        'rule_group_title' => 'Subscriptions',
                        'trigger' => 'store-journal',
                        'triggers' => [
                            [
                                'type' => 'destination_account_is',
                                'value' => $item['destination_name'],
                            ],
                            [
                                'type' => 'transaction_type',
                                'value' => 'withdrawal',
                            ]
                        ],
                        'actions' => [
                            [
                                'type' => 'link_to_bill',
                                'value' => $subscription->data->attributes->name,
                            ]
                        ],
                    ]);
                    if($role){
                        $this->info('role created for '.$item['destination_name']);
                        $triggerRule = $fireflyIII->triggerRule(['id'=>$role->data->id]);
                        if($triggerRule){
                            $this->info('trigger rule created for '.$item['destination_name']);
                        }else{
                            $this->error('Failed to create trigger rule for '.$item['destination_name']);
                        }
                    }else{
                        $this->error('Failed to create rule for '.$item['destination_name']);
                    }
                }else{
                    $this->error('Failed to create subscription for '.$item['destination_name']);
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
                // Check if the transaction has a bill_id
                if (!empty($transaction->bill_id)) {
                    continue; // Skip if it has a bill_id
                }
                
                // Get the transaction details
                $date = new \DateTime($transaction->date);
                $amount = $transaction->amount;
                $destination_id = $transaction->destination_id;

                // print explode('T',$transaction->date)[0] . ' ' . $transaction->destination_name . "\n";
                if(!isset($previousTransactions[$destination_id]['date'])){
                    $previousTransactions[$destination_id] = [
                        'date' => $date->format('c'),
                        'maxAmount' => 0,
                        'minAmount' => 0,
                        'count' => 1,
                        'type' => null,
                        'likelyType' => null,
                        'destination_name' => $transaction->destination_name,
                        'destination_id' => $transaction->destination_id,
                        // 'dateDiff' => [],
                        // 'transactions' => []
                    ];
                }else{
                    $prevDate = new \DateTime($previousTransactions[$destination_id]['date']);
                    $dateDiff = abs($date->diff($prevDate)->days);
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
        $recurringTypes = explode(',', Setting::get('SubscriptionDetector_transactions_recurring_types', 'daily,weekly,monthly,quarterly,half-year,yearly'));

        foreach ($previousTransactions as $destination_id => $previousTransaction) {
            if($previousTransaction['type'] == null) continue;
            if(!in_array($previousTransaction['type'], $recurringTypes)) continue;
            unset($previousTransaction['likelyType']);
            if($previousTransaction['maxAmount'] < Setting::getInt('SubscriptionDetector_min_amount', 10)) continue;
            $recurringTransactions[$previousTransaction['type']][] = $previousTransaction;
        }
        return $recurringTransactions;
    }

}
