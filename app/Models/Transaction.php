<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
