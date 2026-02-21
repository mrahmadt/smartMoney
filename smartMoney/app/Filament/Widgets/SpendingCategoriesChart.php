<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\fireflyIII;
use Illuminate\Support\Facades\Auth;

class SpendingCategoriesChart extends ChartWidget
{
    protected ?string $heading = 'Categories';

    protected function getData(): array
    {
        $categories = $this->getFFData(1);

        // get only $categories items, only top 20 categories, and group the rest as "Other"
        // sort $categories by value in descending order
        arsort($categories);
        // dd($categories);
        $limit = 14;
        if (count($categories) > $limit) {
            $otherCategories = array_slice($categories, $limit, null, true);
            $categories = array_slice($categories, 0, $limit, true);
            $categories['Other'] = array_sum($otherCategories);
            // $categories = array_slice($categories, 0, $limit + 1, true);
        }

        $colorPalette = [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 205, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(255, 159, 64, 1)',
            'rgba(199, 199, 199, 1)',
            'rgba(83, 102, 255, 1)',
            'rgba(255, 99, 255, 1)',
            'rgba(99, 255, 132, 1)',
            'rgba(255, 180, 99, 1)',
            'rgba(120, 120, 255, 1)',
            'rgba(255, 120, 120, 1)',
            'rgba(120, 255, 120, 1)',
            'rgba(200, 100, 255, 1)',
        ];

        $backgroundColor = [];

        $count = count($categories);

        for ($i = 0; $i < $count; $i++) {
            $backgroundColor[] = $colorPalette[$i % 15];
        }

        return [
            'datasets' => [
                [
                    'data' => array_values($categories),
                    'backgroundColor' => $backgroundColor,
                ],
            ],
            'labels' => array_keys($categories),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    public function getFFData($budget_id, $start = null, $end = null)
    {
        $cacheKey = 'top_spendingCategoriesChart_' . Auth::id();
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

        $filter = [];
        $budget_id = Auth::user()->budget_id;
        if ($budget_id != null) {
            $filter['budget_id'] = $budget_id;
        }
        $categories = [];
        $transactions = [];

        for ($page = 1; $page <= 10; $page++) {
            $output = $firefly->getTransactions(start: $start, end: $end, filter: $filter, limit: 200, page: $page);
            if (empty($output)) {
                break;
            }
            if (isset($output->data)) {
                foreach ($output->data as $transaction) {
                    if (isset($transaction->attributes->transactions)) {
                        $transactions = array_merge($transactions, $transaction->attributes->transactions);
                    } else {
                        $transactions[] = $transaction;
                    }
                }
            } else {
                $transactions = array_merge($transactions, $output);
            }
        }
        foreach ($transactions as $transaction) {
            if ($transaction->category_name) {
                $category_name = $transaction->category_name;
            } else {
                $category_name = 'Uncategorized';
            }

            if (!isset($categories[$category_name])) {
                $categories[$category_name] = 0;;
            }


            if ($transaction->type == 'deposit') {
                // $categories[$category_name]= $categories[$category_name] + $transaction->amount;
            } elseif ($transaction->type == 'withdrawal' || $transaction->type == 'transfer') {
                $categories[$category_name] = $categories[$category_name] - $transaction->amount;
            }
        }
        // Convert $categories values to absolute values
        foreach ($categories as $category => $amount) {
            $categories[$category] = abs($amount);
        }
        cache()->put($cacheKey, $categories, now()->addHour());

        return $categories;
    }
}
