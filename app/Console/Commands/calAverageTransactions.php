<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\fireflyIII;
use App\Models\AverageTransaction;

class calAverageTransactions extends Command
{
    protected $signature = 'app:cal-average-transactions {--type=withdrawal}'; //deposit, withdrawal

    protected $description = 'calculates the average transaction amount for a given period of time.';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $type = $this->option('type');

        if($type != 'withdrawal' && $type != 'deposit'){
            $this->error('Invalid type. Use either withdrawal or deposit');
            return;

        }elseif($type == 'withdrawal'){
            if(!config('calAverageTransactions.withdrawal_enabled')){
                $this->error('Withdrawal is not enabled');
                return;
            }
            $this->info('Calculating average withdrawal amount');

        }elseif($type == 'deposit'){
            if(!config('calAverageTransactions.deposit_enabled')){
                $this->error('Deposit is not enabled');
                return;
            }
            $this->info('Calculating average weposit amount');

        }

        $today = date('Y-m-d');
        $fireflyIII = new fireflyIII();
        $filter = [];
        $total_pages = 1;

        $this->info('Calculating average purchase amount for the last '.config('calAverageTransactions.months').' months');

        $xMonthsAgo = date('Y-m-d', strtotime('-'.config('calAverageTransactions.months').' months', strtotime($today)));

        $averageTransactions = [];

        for($page=1; $page<=$total_pages; $page++){
            $this->info('Page: '.$page);
            $transactions = $fireflyIII->getTransactions($today, $xMonthsAgo, $filter, 50, $page, $type);
            if($transactions == false) break;
            
            $attrs = [];

            if(config('calAverageTransactions.all_min')) $attrs['all'] = config('calAverageTransactions.all_min');
            if(config('calAverageTransactions.destination_min')) $attrs['destination_name'] = config('calAverageTransactions.destination_min');
            if(config('calAverageTransactions.source_min')) $attrs['source_name'] = config('calAverageTransactions.source_min');
            if(config('calAverageTransactions.category_min')) $attrs['category_name'] = config('calAverageTransactions.category_min');

            foreach($attrs as $attr => $limit){
                $averageTransactions_current_page = $this->calculateSummary($transactions, $attr);
                foreach ($averageTransactions_current_page as $key => $value) {
                    if($key == '') $key = 'Unknown';
                    if(!isset($averageTransactions[$attr][$key]['total'])){
                        $averageTransactions[$attr][$key]['total'] = 0;
                        $averageTransactions[$attr][$key]['total_amount'] = 0;
                    }
                    $averageTransactions[$attr][$key]['total'] += $value['total'];
                    $averageTransactions[$attr][$key]['total_amount'] += $value['total_amount'];
                }
            }
            if($transactions->meta->pagination->current_page == 1){
                $total_pages = $transactions->meta->pagination->total_pages;
            }
        }

        foreach($attrs as $attr => $limit){
            if(!isset($averageTransactions[$attr])) continue;
            foreach ($averageTransactions[$attr] as $key => $value) {
                if ($value['total'] >= $limit) {
                    $value['average_amount'] = $value['total_amount'] / $value['total'];
                    $averageTransactions[$attr][$key] = $value;
                }else{
                    unset($averageTransactions[$attr][$key]);
                }
            }
        }

        AverageTransaction::where('type', $type)->delete();

        // type attribute key total total_amount average_amount
        foreach($averageTransactions as $attribute => $averageTransaction){
            foreach($averageTransaction as $key => $value){
                $averageTransaction = new AverageTransaction();
                $averageTransaction->type = $type;
                $averageTransaction->attribute = $attribute;
                $averageTransaction->key = $key;
                $averageTransaction->total = $value['total'];
                $averageTransaction->total_amount = $value['total_amount'];
                $averageTransaction->average_amount = $value['average_amount'];
                $averageTransaction->save();
            }
        }
    }

    function calculateSummary($transactions, $groupBy = 'all') {
        $summary = [
            'total' => 0,
            'total_amount' => 0,
        ];
        $summaryByGroup = [];
    
        // Loop through all transaction objects
        foreach ($transactions->data as $transactionGroup) {
            foreach ($transactionGroup->attributes->transactions as $transaction) {
                if ($groupBy != 'all') {
                    $group = $transaction->$groupBy;
                }else{
                    $group = 'all';
                }
                    if(!isset($summaryByGroup[$group])) $summaryByGroup[$group] = $summary;
                    $summaryByGroup[$group]['total']++;
                    $summaryByGroup[$group]['total_amount'] += (float) $transaction->amount;
            }
        }
        return $summaryByGroup;
    }
}
