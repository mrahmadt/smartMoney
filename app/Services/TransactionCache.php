<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TransactionCache
{
    /**
     * Get all transactions for the current month, cached and shared across widgets.
     * Single API call, reused by all dashboard widgets.
     *
     * @return array<object>
     */
    public static function getMonthlyTransactions(): array
    {
        $userId = Auth::id();
        $cacheKey = 'monthly_transactions_' . $userId;

        // Fast path: cache hit
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Lock so only one request fetches from Firefly III
        $lock = Cache::lock($cacheKey . '_lock', 120);

        try {
            $lock->block(120);

            // Double-check: another caller may have filled the cache while we waited
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            $firefly = new fireflyIII();
            $start = date('Y-m-01');
            $end = date('Y-m-t');
            $filter = Account::getTransactionFilter();

            $allTransactions = [];
            for ($page = 1; $page <= 10; $page++) {
                $output = $firefly->getTransactions(start: $start, end: $end, filter: $filter, limit: 200, page: $page);
                if (empty($output)) {
                    break;
                }
                $allTransactions = array_merge($allTransactions, $output);
            }

            Cache::put($cacheKey, $allTransactions, now()->addHours(6));
            return $allTransactions;
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Clear the cached transactions for the current user.
     */
    public static function clear(?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        Cache::forget('monthly_transactions_' . $userId);
    }
}
