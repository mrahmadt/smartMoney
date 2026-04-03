<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Alert;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\fireflyIII;

class MonthlySpendingCheck extends Command
{
    protected $signature = 'app:MonthlySpendingCheck';

    protected $description = 'Check monthly spending anomalies by category and destination';

    public function handle()
    {
        $firefly = new fireflyIII();
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $thresholdCategory = Setting::getInt('abnormal_threshold_percentage_category', 20);
        $thresholdCategoryMin = Setting::getInt('abnormal_threshold_percentage_category_min', 100);
        $thresholdDestination = Setting::getInt('abnormal_threshold_percentage_destination', 20);
        $thresholdDestinationMin = Setting::getInt('abnormal_threshold_percentage_destination_min', 50);
        $months = Setting::getInt('average_transactions_months', 3);

        // Fetch this month's withdrawals
        $transactions = [];
        for ($page = 1; $page <= 10; $page++) {
            $response = $firefly->getTransactions(start: $today, end: $monthStart, type: 'withdrawal', limit: 500, page: $page);
            if (!$response) break;
            $transactions = array_merge($transactions, $response);
        }

        if (empty($transactions)) {
            $this->info('No transactions this month.');
            return;
        }

        // Group by category
        $byCategory = [];
        foreach ($transactions as $t) {
            $cat = $t->category_name ?? 'Uncategorized';
            $byCategory[$cat] = ($byCategory[$cat] ?? 0) + abs((float)$t->amount);
        }

        // Group by destination
        $byDestination = [];
        foreach ($transactions as $t) {
            $dest = $t->destination_name ?? 'Unknown';
            $byDestination[$dest] = ($byDestination[$dest] ?? 0) + abs((float)$t->amount);
        }

        $admin = User::find(1);
        if (!$admin) return;

        // 1. Category monthly total vs average
        foreach ($byCategory as $category => $total) {
            if ($category === 'Uncategorized') continue;
            $result = Transaction::periodSpendingComparison(
                filter: ['category_name' => $category],
                currentStart: $monthStart,
                currentEnd: $today,
                periodsBack: $months,
                periodDays: 30,
                thresholdPercent: $thresholdCategory,
                thresholdMin: $thresholdCategoryMin,
            );
            if ($result) {
                app()->setLocale($admin->language ?? 'en');
                Alert::createAlertWithAdminCopy(
                    title: __('alert.monthly_category_overspend_title', ['category' => $category]),
                    message: __('alert.monthly_category_overspend_message', [
                        'category' => $category,
                        'percentage' => $result['difference_percentage'],
                        'amount' => number_format($result['difference_amount'], 0),
                    ]),
                    user_id: 1,
                    data: $result,
                    pin: true,
                    topic: 'report',
                );
                $this->info("Monthly category alert: {$category} up {$result['difference_percentage']}%");
            }
        }

        // 2. Destination monthly total vs average
        foreach ($byDestination as $destination => $total) {
            $result = Transaction::periodSpendingComparison(
                filter: ['destination_name' => $destination],
                currentStart: $monthStart,
                currentEnd: $today,
                periodsBack: $months,
                periodDays: 30,
                thresholdPercent: $thresholdDestination,
                thresholdMin: $thresholdDestinationMin,
            );
            if ($result) {
                app()->setLocale($admin->language ?? 'en');
                Alert::createAlertWithAdminCopy(
                    title: __('alert.monthly_destination_overspend_title', ['destination' => $destination]),
                    message: __('alert.monthly_destination_overspend_message', [
                        'multiplier' => $result['multiplier'],
                        'destination' => $destination,
                        'amount' => number_format($result['current_total'], 0),
                        'average_amount' => number_format($result['average_total'], 0),
                    ]),
                    user_id: 1,
                    data: $result,
                    pin: true,
                    topic: 'report',
                );
                $this->info("Monthly destination alert: {$destination} {$result['multiplier']}x");
            }
        }

        $this->info('Monthly spending check completed.');
    }
}
