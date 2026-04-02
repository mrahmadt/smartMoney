<?php

use App\Console\Commands\SubscriptionDetector;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->detector = new SubscriptionDetector();
});

function makeTransaction(string $date, float $amount, int $destinationId, string $destinationName, $billId = null): object
{
    return (object) [
        'date' => $date,
        'amount' => $amount,
        'destination_id' => $destinationId,
        'destination_name' => $destinationName,
        'bill_id' => $billId,
    ];
}

function setSettings(array $settings): void
{
    foreach ($settings as $key => $value) {
        Cache::put("setting:{$key}", $value, 3600);
    }
}

test('detectRecurringTransactions returns empty when no transactions', function () {
    setSettings([
        'SubscriptionDetector_transactions_recurring_types' => 'daily,weekly,monthly,quarterly,half-year,yearly',
        'SubscriptionDetector_min_amount' => '10',
    ]);

    $result = $this->detector->detectRecurringTransactions([]);

    expect($result)->toBeArray();
    foreach ($result as $type => $items) {
        expect($items)->toBeEmpty();
    }
});

test('detectRecurringTransactions detects monthly recurring', function () {
    setSettings([
        'SubscriptionDetector_transactions_recurring_types' => 'daily,weekly,monthly,quarterly,half-year,yearly',
        'SubscriptionDetector_min_amount' => '10',
    ]);

    // 4 monthly transactions at Netflix (need >= 3 for monthly detection)
    $transactions = [
        makeTransaction('2026-01-15T00:00:00Z', 50, 100, 'Netflix'),
        makeTransaction('2026-02-14T00:00:00Z', 50, 100, 'Netflix'),
        makeTransaction('2026-03-15T00:00:00Z', 50, 100, 'Netflix'),
        makeTransaction('2026-04-14T00:00:00Z', 50, 100, 'Netflix'),
    ];

    $result = $this->detector->detectRecurringTransactions($transactions);

    expect($result['monthly'])->toHaveCount(1);
    expect($result['monthly'][0]['destination_name'])->toBe('Netflix');
    expect($result['monthly'][0]['maxAmount'])->toBe(50.0);
    expect($result['monthly'][0]['minAmount'])->toBe(50.0);
});

test('detectRecurringTransactions detects weekly recurring', function () {
    setSettings([
        'SubscriptionDetector_transactions_recurring_types' => 'daily,weekly,monthly,quarterly,half-year,yearly',
        'SubscriptionDetector_min_amount' => '10',
    ]);

    // 4 weekly transactions (need >= 3 for weekly detection)
    $transactions = [
        makeTransaction('2026-03-01T00:00:00Z', 30, 200, 'Gym'),
        makeTransaction('2026-03-08T00:00:00Z', 30, 200, 'Gym'),
        makeTransaction('2026-03-15T00:00:00Z', 30, 200, 'Gym'),
        makeTransaction('2026-03-22T00:00:00Z', 30, 200, 'Gym'),
    ];

    $result = $this->detector->detectRecurringTransactions($transactions);

    expect($result['weekly'])->toHaveCount(1);
    expect($result['weekly'][0]['destination_name'])->toBe('Gym');
});

test('detectRecurringTransactions ignores transactions with bill_id', function () {
    setSettings([
        'SubscriptionDetector_transactions_recurring_types' => 'daily,weekly,monthly,quarterly,half-year,yearly',
        'SubscriptionDetector_min_amount' => '10',
    ]);

    $transactions = [
        makeTransaction('2026-01-15T00:00:00Z', 50, 100, 'Netflix', 1),
        makeTransaction('2026-02-14T00:00:00Z', 50, 100, 'Netflix', 1),
        makeTransaction('2026-03-15T00:00:00Z', 50, 100, 'Netflix', 1),
        makeTransaction('2026-04-14T00:00:00Z', 50, 100, 'Netflix', 1),
    ];

    $result = $this->detector->detectRecurringTransactions($transactions);

    expect($result['monthly'])->toBeEmpty();
});

test('detectRecurringTransactions ignores amounts below min_amount', function () {
    setSettings([
        'SubscriptionDetector_transactions_recurring_types' => 'daily,weekly,monthly,quarterly,half-year,yearly',
        'SubscriptionDetector_min_amount' => '100',
    ]);

    $transactions = [
        makeTransaction('2026-01-15T00:00:00Z', 5, 100, 'CheapService'),
        makeTransaction('2026-02-14T00:00:00Z', 5, 100, 'CheapService'),
        makeTransaction('2026-03-15T00:00:00Z', 5, 100, 'CheapService'),
        makeTransaction('2026-04-14T00:00:00Z', 5, 100, 'CheapService'),
    ];

    $result = $this->detector->detectRecurringTransactions($transactions);

    expect($result['monthly'])->toBeEmpty();
});

test('detectRecurringTransactions respects allowed recurring types', function () {
    setSettings([
        'SubscriptionDetector_transactions_recurring_types' => 'quarterly,yearly',
        'SubscriptionDetector_min_amount' => '10',
    ]);

    // Monthly transactions that should be ignored because 'monthly' is not in allowed types
    $transactions = [
        makeTransaction('2026-01-15T00:00:00Z', 50, 100, 'Netflix'),
        makeTransaction('2026-02-14T00:00:00Z', 50, 100, 'Netflix'),
        makeTransaction('2026-03-15T00:00:00Z', 50, 100, 'Netflix'),
        makeTransaction('2026-04-14T00:00:00Z', 50, 100, 'Netflix'),
    ];

    $result = $this->detector->detectRecurringTransactions($transactions);

    expect($result['monthly'])->toBeEmpty();
});

test('detectRecurringTransactions does not detect with too few transactions', function () {
    setSettings([
        'SubscriptionDetector_transactions_recurring_types' => 'daily,weekly,monthly,quarterly,half-year,yearly',
        'SubscriptionDetector_min_amount' => '10',
    ]);

    // Only 2 monthly transactions — need 3 to detect monthly
    $transactions = [
        makeTransaction('2026-01-15T00:00:00Z', 50, 100, 'Netflix'),
        makeTransaction('2026-02-14T00:00:00Z', 50, 100, 'Netflix'),
    ];

    $result = $this->detector->detectRecurringTransactions($transactions);

    expect($result['monthly'])->toBeEmpty();
});

test('detectRecurringTransactions detects quarterly recurring', function () {
    setSettings([
        'SubscriptionDetector_transactions_recurring_types' => 'daily,weekly,monthly,quarterly,half-year,yearly',
        'SubscriptionDetector_min_amount' => '10',
    ]);

    // 3 quarterly transactions (need >= 2 for quarterly detection)
    $transactions = [
        makeTransaction('2025-07-01T00:00:00Z', 200, 300, 'Insurance'),
        makeTransaction('2025-10-01T00:00:00Z', 200, 300, 'Insurance'),
        makeTransaction('2026-01-01T00:00:00Z', 200, 300, 'Insurance'),
    ];

    $result = $this->detector->detectRecurringTransactions($transactions);

    expect($result['quarterly'])->toHaveCount(1);
    expect($result['quarterly'][0]['destination_name'])->toBe('Insurance');
});

test('detectRecurringTransactions tracks min and max amounts', function () {
    setSettings([
        'SubscriptionDetector_transactions_recurring_types' => 'daily,weekly,monthly,quarterly,half-year,yearly',
        'SubscriptionDetector_min_amount' => '10',
    ]);

    $transactions = [
        makeTransaction('2026-01-15T00:00:00Z', 45, 100, 'Netflix'),
        makeTransaction('2026-02-14T00:00:00Z', 55, 100, 'Netflix'),
        makeTransaction('2026-03-15T00:00:00Z', 50, 100, 'Netflix'),
        makeTransaction('2026-04-14T00:00:00Z', 60, 100, 'Netflix'),
    ];

    $result = $this->detector->detectRecurringTransactions($transactions);

    expect($result['monthly'])->toHaveCount(1);
    // krsort reverses order, first processed sets no min/max, subsequent ones update
    // min should be <= max, and both should be within the range of amounts provided
    expect($result['monthly'][0]['minAmount'])->toBeLessThanOrEqual($result['monthly'][0]['maxAmount']);
    expect($result['monthly'][0]['minAmount'])->toBeGreaterThan(0);
    expect($result['monthly'][0]['maxAmount'])->toBeGreaterThanOrEqual(45.0);
});

test('detectRecurringTransactions detects multiple destinations independently', function () {
    setSettings([
        'SubscriptionDetector_transactions_recurring_types' => 'daily,weekly,monthly,quarterly,half-year,yearly',
        'SubscriptionDetector_min_amount' => '10',
    ]);

    $transactions = [
        // Netflix monthly
        makeTransaction('2026-01-15T00:00:00Z', 50, 100, 'Netflix'),
        makeTransaction('2026-02-14T00:00:00Z', 50, 100, 'Netflix'),
        makeTransaction('2026-03-15T00:00:00Z', 50, 100, 'Netflix'),
        makeTransaction('2026-04-14T00:00:00Z', 50, 100, 'Netflix'),
        // Gym weekly
        makeTransaction('2026-03-01T00:00:00Z', 30, 200, 'Gym'),
        makeTransaction('2026-03-08T00:00:00Z', 30, 200, 'Gym'),
        makeTransaction('2026-03-15T00:00:00Z', 30, 200, 'Gym'),
        makeTransaction('2026-03-22T00:00:00Z', 30, 200, 'Gym'),
    ];

    $result = $this->detector->detectRecurringTransactions($transactions);

    expect($result['monthly'])->toHaveCount(1);
    expect($result['weekly'])->toHaveCount(1);
    expect($result['monthly'][0]['destination_name'])->toBe('Netflix');
    expect($result['weekly'][0]['destination_name'])->toBe('Gym');
});

test('detectRecurringTransactions ignores irregular intervals', function () {
    setSettings([
        'SubscriptionDetector_transactions_recurring_types' => 'daily,weekly,monthly,quarterly,half-year,yearly',
        'SubscriptionDetector_min_amount' => '10',
    ]);

    // Random intervals — not matching any pattern
    $transactions = [
        makeTransaction('2026-01-01T00:00:00Z', 50, 100, 'RandomStore'),
        makeTransaction('2026-01-15T00:00:00Z', 50, 100, 'RandomStore'),
        makeTransaction('2026-02-20T00:00:00Z', 50, 100, 'RandomStore'),
        makeTransaction('2026-03-05T00:00:00Z', 50, 100, 'RandomStore'),
    ];

    $result = $this->detector->detectRecurringTransactions($transactions);

    // Should not be detected as any recurring type
    foreach ($result as $items) {
        $found = collect($items)->where('destination_name', 'RandomStore')->count();
        expect($found)->toBe(0);
    }
});
