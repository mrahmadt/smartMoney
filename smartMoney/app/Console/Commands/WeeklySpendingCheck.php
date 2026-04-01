<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Alert;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\fireflyIII;

class WeeklySpendingCheck extends Command
{
    protected $signature = 'app:WeeklySpendingCheck';

    protected $description = 'Check weekly spending anomalies by category and destination';

    public function handle()
    {
        $firefly = new fireflyIII();
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($today)));
        $thresholdCategory = Setting::getInt('abnormal_threshold_percentage_category', 20);
        $thresholdCategoryMin = Setting::getInt('abnormal_threshold_percentage_category_min', 100);
        $thresholdDestination = Setting::getInt('abnormal_threshold_percentage_destination', 20);
        $thresholdDestinationMin = Setting::getInt('abnormal_threshold_percentage_destination_min', 50);
        $months = Setting::getInt('average_transactions_months', 3);
        $weeksBack = (int)($months * 4.3);

        // Fetch this week's withdrawals
        $transactions = [];
        for ($page = 1; $page <= 10; $page++) {
            $response = $firefly->getTransactions(start: $today, end: $weekStart, type: 'withdrawal', limit: 500, page: $page);
            if (!$response) break;
            $transactions = array_merge($transactions, $response);
        }

        if (empty($transactions)) {
            $this->info('No transactions this week.');
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

        // 1. Category weekly total vs average
        foreach ($byCategory as $category => $total) {
            if ($category === 'Uncategorized') continue;
            $result = Transaction::periodSpendingComparison(
                filter: ['category_name' => $category],
                currentStart: $weekStart,
                currentEnd: $today,
                periodsBack: $weeksBack,
                periodDays: 7,
                thresholdPercent: $thresholdCategory,
                thresholdMin: $thresholdCategoryMin,
            );
            if ($result) {
                app()->setLocale($admin->language ?? 'en');
                Alert::createAlertWithAdminCopy(
                    title: __('alert.weekly_category_overspend_title', ['category' => $category]),
                    message: __('alert.weekly_category_overspend_message', [
                        'category' => $category,
                        'percentage' => $result['difference_percentage'],
                        'amount' => number_format($result['difference_amount'], 0),
                    ]),
                    user_id: 1,
                    data: $result,
                    pin: true,
                );
                $this->info("Weekly category alert: {$category} up {$result['difference_percentage']}%");
            }
        }

        // 2. Destination weekly total vs average
        foreach ($byDestination as $destination => $total) {
            $result = Transaction::periodSpendingComparison(
                filter: ['destination_name' => $destination],
                currentStart: $weekStart,
                currentEnd: $today,
                periodsBack: $weeksBack,
                periodDays: 7,
                thresholdPercent: $thresholdDestination,
                thresholdMin: $thresholdDestinationMin,
            );
            if ($result) {
                app()->setLocale($admin->language ?? 'en');
                Alert::createAlertWithAdminCopy(
                    title: __('alert.weekly_destination_overspend_title', ['destination' => $destination]),
                    message: __('alert.weekly_destination_overspend_message', [
                        'multiplier' => $result['multiplier'],
                        'destination' => $destination,
                        'amount' => number_format($result['current_total'], 0),
                        'average_amount' => number_format($result['average_total'], 0),
                    ]),
                    user_id: 1,
                    data: $result,
                    pin: true,
                );
                $this->info("Weekly destination alert: {$destination} {$result['multiplier']}x");
            }
        }

        $this->info('Weekly spending check completed.');
    }
}
