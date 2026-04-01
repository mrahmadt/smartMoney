<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Alert;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\fireflyIII;

class DailySpendingCheck extends Command
{
    protected $signature = 'app:DailySpendingCheck';

    protected $description = 'Check daily spending anomalies by category and destination';

    public function handle()
    {
        $firefly = new fireflyIII();
        $today = date('Y-m-d');
        $thresholdCategory = Setting::getInt('abnormal_threshold_percentage_category', 20);
        $thresholdCategoryMin = Setting::getInt('abnormal_threshold_percentage_category_min', 100);
        $months = Setting::getInt('average_transactions_months', 3);

        // Fetch today's withdrawals
        $transactions = [];
        for ($page = 1; $page <= 10; $page++) {
            $response = $firefly->getTransactions(start: $today, end: $today, type: 'withdrawal', limit: 500, page: $page);
            if (!$response) break;
            $transactions = array_merge($transactions, $response);
        }

        if (empty($transactions)) {
            $this->info('No transactions today.');
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
        $destCounts = [];
        foreach ($transactions as $t) {
            $dest = $t->destination_name ?? 'Unknown';
            $byDestination[$dest] = ($byDestination[$dest] ?? 0) + abs((float)$t->amount);
            $destCounts[$dest] = ($destCounts[$dest] ?? 0) + 1;
        }

        $admin = User::find(1);
        if (!$admin) return;

        // 1. Category daily total vs average
        foreach ($byCategory as $category => $total) {
            if ($category === 'Uncategorized') continue;
            $result = Transaction::periodSpendingComparison(
                filter: ['category_name' => $category],
                currentStart: $today,
                currentEnd: $today,
                periodsBack: $months * 30,
                periodDays: 1,
                thresholdPercent: $thresholdCategory,
                thresholdMin: $thresholdCategoryMin,
            );
            if ($result) {
                app()->setLocale($admin->language ?? 'en');
                Alert::createAlertWithAdminCopy(
                    title: __('alert.daily_category_overspend_title', ['category' => $category]),
                    message: __('alert.daily_category_overspend_message', [
                        'category' => $category,
                        'percentage' => $result['difference_percentage'],
                        'amount' => number_format($result['difference_amount'], 0),
                    ]),
                    user_id: 1,
                    data: $result,
                    pin: true,
                );
                $this->info("Category alert: {$category} up {$result['difference_percentage']}%");
            }
        }

        // 2. Unusual category frequency
        $catCounts = [];
        foreach ($transactions as $t) {
            $cat = $t->category_name ?? null;
            if (!$cat) continue;
            $catCounts[$cat] = ($catCounts[$cat] ?? 0) + 1;
        }
        foreach ($catCounts as $category => $count) {
            $freqResult = Transaction::unusualCategoryFrequency($category, $today);
            if ($freqResult) {
                app()->setLocale($admin->language ?? 'en');
                Alert::createAlertWithAdminCopy(
                    title: __('alert.unusual_category_frequency_title', ['category' => $category]),
                    message: __('alert.unusual_category_frequency_message', [
                        'count' => $freqResult['today_count'],
                        'category' => $category,
                        'average' => $freqResult['average_daily_count'],
                    ]),
                    user_id: 1,
                    data: $freqResult,
                    pin: true,
                );
                $this->info("Frequency alert: {$category} x{$freqResult['today_count']}");
            }
        }

        // 3. Unusual destination frequency (repeated same-day)
        foreach ($destCounts as $destination => $count) {
            $freqResult = Transaction::unusualDestinationFrequency($destination, $today);
            if ($freqResult) {
                app()->setLocale($admin->language ?? 'en');
                Alert::createAlertWithAdminCopy(
                    title: __('alert.unusual_destination_frequency_title'),
                    message: __('alert.unusual_destination_frequency_message', [
                        'count' => $freqResult['today_count'],
                        'destination' => $destination,
                        'average' => $freqResult['average_daily_count'],
                    ]),
                    user_id: 1,
                    data: $freqResult,
                    pin: true,
                );
                $this->info("Destination frequency alert: {$destination} x{$freqResult['today_count']}");
            }
        }

        $this->info('Daily spending check completed.');
    }
}
