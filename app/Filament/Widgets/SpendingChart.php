<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\fireflyIII;
use App\Services\TransactionCache;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;

class SpendingChart extends ChartWidget
{
    protected ?string $heading = null;

    public function getHeading(): ?string
    {
        app()->setLocale(Auth::user()->language ?? 'en');
        return __('widget.spending');
    }

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
                'label' => __('widget.spending'),
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
        if ($start == null) $start = date('Y-m-01');
        if ($end == null) $end = date('Y-m-t');

        if (strtotime($end) > strtotime(date('Y-m-d'))) {
            $end = date('Y-m-d');
        }

        $spendingLabels = [];
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

        $transactions = TransactionCache::getMonthlyTransactions();

        foreach ($transactions as $transaction) {
            if (($transaction->type ?? '') !== 'withdrawal') {
                continue;
            }
            $date = substr($transaction->date, 0, 10);
            if (isset($spending[$date])) {
                $spending[$date] += (float) $transaction->amount;
            }
        }

        return [
            'labels' => $spendingLabels,
            'spending' => array_values($spending),
        ];
    }
}
