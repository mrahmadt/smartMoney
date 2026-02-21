<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\fireflyIII;
use Illuminate\Support\Facades\Auth;

class SpendingChart extends ChartWidget
{
    protected ?string $heading = 'Spending';

    protected function getData(): array
    {
        
        $data = $this->getFFData(1);
        $labels = $data['labels'];
        $spending = $data['spending'];

$labels = array_map(function($date) {
    return date('m-d', strtotime($date));
}, $labels);
        // dd($labels, $spending);

    return [
        'datasets' => [
            [
                'label' => 'Spending',
                'fill' => true,
                'data' => $spending,
                'backgroundColor' => 'rgba(235, 54, 114, 0.2)' ,
                'borderColor' => 'red',
            ],
        ],
        'labels' => $labels,
    ];

    }
protected function getOptions(): array
{
    return [
        'plugins' => [
            'legend' => [
                'display' => false,
            ],
        ],
    ];
}
    protected function getType(): string
    {
        return 'line';
    }


    public function getFFData($budget_id, $start = null, $end = null)
    {
        $cacheKey = 'top_spendingChart_' . Auth::id();
        $cachedData = cache()->get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }


        if($start == null) $start = date('Y-m-01');
        if($end == null) $end = date('Y-m-t');

        // if $end > today, set $end to today
        if(strtotime($end) > strtotime(date('Y-m-d'))) {
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

        $filter = [];
        $budget_id = Auth::user()->budget_id;
        if ($budget_id != null) {
            $filter['budget_id'] = $budget_id;
        }
        $transactions = [];

        for($page = 1; $page <= 10; $page++){
            $output = $firefly->getTransactions(start: $start, end:$end, filter: $filter, limit: 200, page: $page, type: 'withdrawal');
            if(empty($output)){ break; }
            if(isset($output->data)){
                foreach($output->data as $transaction){
                    if(isset($transaction->attributes->transactions)){
                        $transactions = array_merge($transactions, $transaction->attributes->transactions);
                    }else{
                        $transactions[] = $transaction;
                    }
                }
            }else{
                $transactions = array_merge($transactions, $output);
            }

        }
                    foreach ($transactions as $transaction) {
                // Extract the date part (up to 'T' character)
                $date = substr($transaction->date, 0, 10);
                // Add the transaction amount to the corresponding date
                $spending[$date] += (float)$transaction->amount;
            }
        $data =  [
            'labels' => $spendingLabels,
            'spending' => array_values($spending),
        ];
        cache()->put($cacheKey, $data, now()->addHour());
        return $data;

    }
}
