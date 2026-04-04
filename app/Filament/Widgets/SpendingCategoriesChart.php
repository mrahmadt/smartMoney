<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\fireflyIII;
use App\Services\TransactionCache;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;

class SpendingCategoriesChart extends ChartWidget
{
    protected ?string $heading = null;

    public function getHeading(): ?string
    {
        app()->setLocale(Auth::user()->language ?? 'en');
        return __('widget.categories');
    }

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
            $categories[__('widget.other')] = array_sum($otherCategories);
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
        $transactions = TransactionCache::getMonthlyTransactions();

        $categories = [];
        foreach ($transactions as $transaction) {
            if ($transaction->category_name) {
                $category_name = $transaction->category_name;
            } else {
                $category_name = __('widget.uncategorized');
            }

            if (!isset($categories[$category_name])) {
                $categories[$category_name] = 0;
            }

            if ($transaction->type == 'withdrawal' || $transaction->type == 'transfer') {
                $categories[$category_name] = $categories[$category_name] - $transaction->amount;
            }
        }
        foreach ($categories as $category => $amount) {
            $categories[$category] = abs($amount);
        }

        return $categories;
    }
}
