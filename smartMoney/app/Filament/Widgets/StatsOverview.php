<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Support\Enums\IconPosition;
use App\Services\fireflyIII;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends StatsOverviewWidget
{


    protected function getStats(): array
    {
        $budget_id = Auth::user()->budget_id;
        if($budget_id == null){
            return [];
        }

        $budget = $this->getFFData(1);
        $color = 'danger';

        $budget['budget_percentage_used'] = 80;
        if($budget['budget_percentage_used'] < 50){
            $color = 'success';
        }elseif($budget['budget_percentage_used'] <= 70){
            $color = 'warning';
        }
        
        $stat = Stat::make('Remaining', $budget['remaining'])
                ->description($budget['spentSum'] . ' (' . $budget['budget_percentage_used'] . '%)')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($budget['spending'])
                // ->icon('heroicon-m-currency-dollar')
                ->color($color);
        
        return [
            $stat,
        ];
    }


    public function getFFData($budget_id, $start = null, $end = null)
    {
        $cacheKey = 'top_stats1_' . Auth::id();
        $cachedData = cache()->get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }


        if ($start == null) $start = date('Y-m-01');
        if ($end == null) $end = date('Y-m-t');

        // if $end > today, set $end to today
        if (strtotime($end) > strtotime(date('Y-m-d'))) {
            $end = date('Y-m-d');
        }

        $spendingLabels = [];
        // $spendingLabels should be an array of dates in 'MM-DD' format from $start to $end
        $period = new \DatePeriod(
            new \DateTime($start),
            new \DateInterval('P1D'),
            new \DateTime($end . ' +1 day')
        );

        $spending = [];
        foreach ($period as $date) {
            $label = $date->format('Y-m-d');
            $spendingLabels[] = $label;
            $spending[$label] = 0;
        }

        $firefly = new fireflyIII();
        $budget_id = Auth::user()->budget_id;
        if($budget_id == null){
            $budget_id = 1;
        }
        $budget = $firefly->getBudget($budget_id, $start, $end);
        $budget = $budget->data;
        if (isset($budget->attributes->spent[0])) {
            $remaining = number_format($budget->attributes->auto_budget_amount + $budget->attributes->spent[0]->sum, 0);
            $budget_percentage_used = number_format(abs(($budget->attributes->spent[0]->sum / $budget->attributes->auto_budget_amount) * 100), 0);
            $spentSum = number_format($budget->attributes->spent[0]->sum, 0);
        } else {
            $remaining = number_format($budget->attributes->auto_budget_amount, 0);
            $budget_percentage_used = 0;
            $spentSum = 0;
        }

        $spending = [];
        $index = 0;
        for($page = 1; $page <= 10; $page++){
            $transactions = $firefly->getTransactions(start: $start, end:$end, filter: ['budget_id' => $budget_id], limit: 200, page: $page, type: 'withdrawal');
            if(empty($transactions)){
                break;
            }
            
            foreach ($transactions as $transaction) {
                $amount = (float)$transaction->amount;
                if($index > 0){
                    
                    if($spending[$index-1] <= $amount){
                        $spending[] = $amount;
                        $index++;
                    }
                }else{
                    $spending[] = $amount;
                    $index++;
                }
                
            }
        }

        $stat = [
            'remaining' => $remaining,
            'budget_percentage_used' => $budget_percentage_used,
            'spentSum' => $spentSum,
            'spending' => array_values($spending),
        ];
        cache()->put($cacheKey, $stat, now()->addHour());

        return $stat;

    }
}
