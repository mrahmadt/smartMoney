<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\fireflyIII;

class Transaction extends Model
{
    use HasFactory;
    protected $table  = false;

    public static function transactionsStatistics($transactions){
        $stats = [
            'total' => 0,
            'total_deposits' => 0,
            'total_withdrawals' => 0,
            'total_transfers' => 0,
            'total_income' => 0,
            'total_expense' => 0,
            'destinations' => [],
            'sources' => [],
            'categories' => [],
            'topTransactions' => [],
        ];
        

        // Sort the transactions in descending order by amount
        usort($transactions, function($a, $b) {
            return $b->amount - $a->amount;
        });

        // Get the top 10 transactions
        $topTransactions = array_slice($transactions, 0, 10);

        // Store the top 10 transactions in $stats['topTransactions']
        $stats['topTransactions'] = $topTransactions;

        foreach($transactions as $transaction){
            $stats['total']++;

            if($transaction->category_name){
                $category_name = $transaction->category_name;
            }else{
                $category_name = 'Uncategorized';
            }

            if(!isset($stats['categories'][$category_name])) {
                $stats['categories'][$category_name] = [
                    'count' => 0,
                    'amount' => 0,
                    'withdrawalOnly' => 0,
                ];
            }
            $stats['categories'][$category_name]['count']++;

            if($transaction->type == 'deposit'){
                $stats['total_deposits']++;
                $stats['total_income'] += $transaction->amount;

                if(!isset($stats['sources'][$transaction->source_name])) $stats['sources'][$transaction->source_name] = 0;
                $stats['sources'][$transaction->source_name]++;

                $stats['categories'][$category_name]['amount'] = $stats['categories'][$category_name]['amount'] + $transaction->amount;
            
    

            }elseif($transaction->type == 'withdrawal'){
                $stats['total_withdrawals']++;
                $stats['total_expense'] += $transaction->amount;
                
                if(!isset($stats['destinations'][$transaction->destination_name])) $stats['destinations'][$transaction->destination_name] = 0;
                $stats['destinations'][$transaction->destination_name]++;

                $stats['categories'][$category_name]['amount'] = $stats['categories'][$category_name]['amount'] - $transaction->amount;

                $stats['categories'][$category_name]['withdrawalOnly'] += $transaction->amount;

            }elseif($transaction->type == 'transfer'){
                $stats['total_transfer']++;
            }
        }

        return $stats;
    }
    public static function abnormalTransaction($transaction, $fireflyIII = null){
        if(!$fireflyIII) $fireflyIII = new fireflyIII();
        $type = $transaction['type'];
        if(config('alert.abnormal_transactions_'.$type.'_enabled')) {
            $order = config('alert.abnormal_transactions_'.$type.'_order');
            foreach($order as $item){
                $percentage = config('alert.abnormal_transactions_'.$type.'_'.$item.'_percentage');
                /*
                    type: withdrawal diposit
                    attribute: all source destination category
                    key: all "Visa SAIB CC"
                */
                $key = null;
                $attribute = $item;
                if( $item == 'source') {
                    $attribute = 'source_name';
                }elseif($item == 'destination'){
                    $attribute = 'destination_name';
                }elseif($item == 'all'){
                    $attribute = 'all';
                    $key = 'all';
                }elseif($item == 'category'){
                    $attribute = 'category_name';
                }
                if($key == null) {
                    $key = $transaction[$item];
                    if($key == '') $key = 'Unknown';
                }
                $averageTransaction = AverageTransaction::where('type', $type)
                ->where('attribute', $attribute)
                ->where('key', $key)->first();
                if($averageTransaction){
                    $average_amount = $averageTransaction->average_amount;
                    if($transaction['amount'] >= ($average_amount + ($average_amount * ($percentage / 100)))){
                        $user = 1;
                        $users = User::where('alertAbnormalTransaction', 1)->get();
                        foreach($users as $user){
                            Alert::abnormalTransaction($item, $transaction, $percentage,$averageTransaction, $user);
                        }
                        break;
                    }
                }
            }
        }
    }
    public static function checkBillOverMaxAmount($transaction, $fireflyIII = null){
        if(!$fireflyIII) $fireflyIII = new fireflyIII();
        if(config('billDetector.enabled') && $transaction['type'] == 'withdrawal') {
            $bill = $fireflyIII->findBill($transaction['destination_name']);
            if(!$bill) return false; //return response()->json(['error' => 'Bill not found'], 404);
            $billPercentage = Account::billOverAmountPercentage($bill);
            if(!$billPercentage){
                $billPercentage = config('alert.bill_over_amount_percentage');
            }
            $maxAmount = $bill->attributes->amount_max;
            if($transaction['amount'] >= ($maxAmount + ($maxAmount * ($billPercentage / 100)))){
                $user = 1;
                $users = User::where('alertBillOverAmountPercentage', 1)->get();
                foreach($users as $user){
                    Alert::billOverMaxAmount($bill, $transaction, $billPercentage, $user);
                }
            }
        }
    }
}
