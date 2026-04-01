<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Transaction;
use App\Services\fireflyIII;
use Illuminate\Support\Facades\Cache;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AbnormalTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        // Load the fake BEFORE the real class is autoloaded
        require_once __DIR__ . '/../Doubles/FakeFireflyIII.php';
        parent::setUp();
        fireflyIII::resetMock();
    }

    protected function setSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            Cache::put("setting:{$key}", $value, 3600);
        }
    }

    // --- abnormalDestinationAmount ---

    public function test_abnormal_destination_amount_returns_false_when_threshold_is_zero(): void
    {
        $this->setSettings([
            'abnormal_threshold_percentage_destination' => '0',
        ]);

        $result = Transaction::abnormalDestinationAmount(
            amount: 100,
            destination_id: 1,
            transaction_journal_id: 999,
        );

        $this->assertFalse($result);
    }

    public function test_abnormal_destination_amount_returns_false_when_no_historical_transactions(): void
    {
        $this->setSettings([
            'abnormal_threshold_percentage_destination' => '20',
            'abnormal_threshold_percentage_destination_min' => '50',
            'average_transactions_months' => '3',
        ]);

        fireflyIII::setResponses([false]);

        $result = Transaction::abnormalDestinationAmount(
            amount: 100,
            destination_id: 1,
            transaction_journal_id: 999,
        );

        $this->assertFalse($result);
    }

    public function test_abnormal_destination_amount_detects_abnormal_amount(): void
    {
        $this->setSettings([
            'abnormal_threshold_percentage_destination' => '20',
            'abnormal_threshold_percentage_destination_min' => '50',
            'average_transactions_months' => '3',
        ]);

        $historicalTransactions = array_map(fn($i) => (object)[
            'amount' => 100,
            'transaction_journal_id' => $i,
        ], range(1, 10));

        fireflyIII::setResponses([$historicalTransactions, false]);

        $result = Transaction::abnormalDestinationAmount(
            amount: 500,
            destination_id: 1,
            transaction_journal_id: 999,
        );

        $this->assertIsArray($result);
        $this->assertEquals(500, $result['amount']);
        $this->assertEquals(100.0, $result['average_amount']);
        $this->assertEquals(400.0, $result['difference_amount']);
        $this->assertEquals(5.0, $result['multiplier']);
    }

    public function test_abnormal_destination_amount_returns_false_when_diff_below_minimum(): void
    {
        $this->setSettings([
            'abnormal_threshold_percentage_destination' => '20',
            'abnormal_threshold_percentage_destination_min' => '50',
            'average_transactions_months' => '3',
        ]);

        $historicalTransactions = array_map(fn($i) => (object)[
            'amount' => 100,
            'transaction_journal_id' => $i,
        ], range(1, 10));

        fireflyIII::setResponses([$historicalTransactions, false]);

        // Amount 130 => 30% above avg but only 30 SAR diff (below 50 min)
        $result = Transaction::abnormalDestinationAmount(
            amount: 130,
            destination_id: 1,
            transaction_journal_id: 999,
        );

        $this->assertFalse($result);
    }

    public function test_abnormal_destination_amount_passes_budget_id_filter(): void
    {
        $this->setSettings([
            'abnormal_threshold_percentage_destination' => '20',
            'abnormal_threshold_percentage_destination_min' => '50',
            'average_transactions_months' => '3',
        ]);

        $historicalTransactions = array_map(fn($i) => (object)[
            'amount' => 100,
            'transaction_journal_id' => $i,
        ], range(1, 10));

        fireflyIII::setResponses([$historicalTransactions, false]);

        $result = Transaction::abnormalDestinationAmount(
            amount: 500,
            destination_id: 1,
            transaction_journal_id: 999,
            budget_id: 5,
        );

        $this->assertIsArray($result);

        $calls = fireflyIII::getCallLog();
        $this->assertNotEmpty($calls);
        $this->assertEquals(5, $calls[0]['filter']['budget_id']);
    }

    // --- unusualCategoryFrequency ---

    public function test_unusual_category_frequency_returns_false_when_today_count_less_than_2(): void
    {
        $this->setSettings([
            'abnormal_frequency_multiplier' => '2',
            'average_transactions_months' => '3',
        ]);

        fireflyIII::setResponses([
            [(object)['amount' => 50]], // today: 1 transaction
        ]);

        $result = Transaction::unusualCategoryFrequency('Dining');

        $this->assertFalse($result);
    }

    public function test_unusual_category_frequency_detects_unusual_frequency(): void
    {
        $this->setSettings([
            'abnormal_frequency_multiplier' => '2',
            'average_transactions_months' => '3',
        ]);

        $todayTransactions = array_map(fn($i) => (object)['amount' => 50], range(1, 5));
        // ~90 days, 10 total => avg ~0.11/day. Today = 5 >> 2 * 0.11
        $historicalTransactions = array_map(fn($i) => (object)['amount' => 50], range(1, 10));

        fireflyIII::setResponses([
            $todayTransactions,       // today count
            $historicalTransactions,  // historical page 1
            false,                    // historical page 2 (end)
        ]);

        $result = Transaction::unusualCategoryFrequency('Dining', '2026-04-01');

        $this->assertIsArray($result);
        $this->assertEquals(5, $result['today_count']);
        $this->assertArrayHasKey('average_daily_count', $result);
    }

    public function test_unusual_category_frequency_returns_false_when_normal(): void
    {
        $this->setSettings([
            'abnormal_frequency_multiplier' => '2',
            'average_transactions_months' => '3',
        ]);

        $todayTransactions = array_map(fn($i) => (object)['amount' => 50], range(1, 2));
        // ~90 days, 180 total => avg 2/day. Today = 2, threshold = 2*2 = 4
        $historicalTransactions = array_map(fn($i) => (object)['amount' => 50], range(1, 180));

        fireflyIII::setResponses([
            $todayTransactions,       // today count
            $historicalTransactions,  // historical page 1
            false,                    // historical page 2 (end)
        ]);

        $result = Transaction::unusualCategoryFrequency('Dining', '2026-04-01');

        $this->assertFalse($result);
    }

    public function test_unusual_category_frequency_includes_budget_id_when_provided(): void
    {
        $this->setSettings([
            'abnormal_frequency_multiplier' => '2',
            'average_transactions_months' => '3',
        ]);

        fireflyIII::setResponses([
            [(object)['amount' => 50]], // today: 1 (returns false early)
        ]);

        $result = Transaction::unusualCategoryFrequency('Dining', '2026-04-01', budget_id: 7);

        $this->assertFalse($result);

        $calls = fireflyIII::getCallLog();
        $this->assertNotEmpty($calls);
        $this->assertEquals(7, $calls[0]['filter']['budget_id']);
        $this->assertEquals('Dining', $calls[0]['filter']['category_name']);
    }

    public function test_unusual_category_frequency_excludes_budget_id_when_null(): void
    {
        $this->setSettings([
            'abnormal_frequency_multiplier' => '2',
            'average_transactions_months' => '3',
        ]);

        fireflyIII::setResponses([
            [(object)['amount' => 50]], // today: 1 (returns false early)
        ]);

        $result = Transaction::unusualCategoryFrequency('Dining', '2026-04-01');

        $this->assertFalse($result);

        $calls = fireflyIII::getCallLog();
        $this->assertNotEmpty($calls);
        $this->assertArrayNotHasKey('budget_id', $calls[0]['filter']);
    }

    // --- unusualDestinationFrequency ---

    public function test_unusual_destination_frequency_returns_false_when_today_count_less_than_2(): void
    {
        $this->setSettings([
            'abnormal_frequency_multiplier' => '2',
            'average_transactions_months' => '3',
        ]);

        fireflyIII::setResponses([
            [(object)['amount' => 50]], // today: 1 transaction
        ]);

        $result = Transaction::unusualDestinationFrequency('Starbucks');

        $this->assertFalse($result);
    }

    public function test_unusual_destination_frequency_detects_unusual_frequency(): void
    {
        $this->setSettings([
            'abnormal_frequency_multiplier' => '2',
            'average_transactions_months' => '3',
        ]);

        $todayTransactions = array_map(fn($i) => (object)['amount' => 30], range(1, 4));
        // ~90 days, 5 total => avg ~0.056/day. Today = 4 >> 2 * 0.056
        $historicalTransactions = array_map(fn($i) => (object)['amount' => 30], range(1, 5));

        fireflyIII::setResponses([
            $todayTransactions,
            $historicalTransactions,
            false,
        ]);

        $result = Transaction::unusualDestinationFrequency('Starbucks', '2026-04-01');

        $this->assertIsArray($result);
        $this->assertEquals(4, $result['today_count']);
        $this->assertArrayHasKey('average_daily_count', $result);
    }

    public function test_unusual_destination_frequency_returns_false_when_normal(): void
    {
        $this->setSettings([
            'abnormal_frequency_multiplier' => '2',
            'average_transactions_months' => '3',
        ]);

        $todayTransactions = array_map(fn($i) => (object)['amount' => 30], range(1, 2));
        // ~90 days, 180 total => avg 2/day. Today = 2, threshold = 4
        $historicalTransactions = array_map(fn($i) => (object)['amount' => 30], range(1, 180));

        fireflyIII::setResponses([
            $todayTransactions,
            $historicalTransactions,
            false,
        ]);

        $result = Transaction::unusualDestinationFrequency('Starbucks', '2026-04-01');

        $this->assertFalse($result);
    }

    public function test_unusual_destination_frequency_includes_budget_id_when_provided(): void
    {
        $this->setSettings([
            'abnormal_frequency_multiplier' => '2',
            'average_transactions_months' => '3',
        ]);

        fireflyIII::setResponses([
            [(object)['amount' => 50]],
        ]);

        $result = Transaction::unusualDestinationFrequency('Starbucks', '2026-04-01', budget_id: 3);

        $this->assertFalse($result);

        $calls = fireflyIII::getCallLog();
        $this->assertNotEmpty($calls);
        $this->assertEquals(3, $calls[0]['filter']['budget_id']);
        $this->assertEquals('Starbucks', $calls[0]['filter']['destination_name']);
    }

    public function test_unusual_destination_frequency_excludes_budget_id_when_null(): void
    {
        $this->setSettings([
            'abnormal_frequency_multiplier' => '2',
            'average_transactions_months' => '3',
        ]);

        fireflyIII::setResponses([
            [(object)['amount' => 50]],
        ]);

        $result = Transaction::unusualDestinationFrequency('Starbucks', '2026-04-01');

        $this->assertFalse($result);

        $calls = fireflyIII::getCallLog();
        $this->assertNotEmpty($calls);
        $this->assertArrayNotHasKey('budget_id', $calls[0]['filter']);
    }

    // --- periodSpendingComparison ---

    public function test_period_spending_returns_false_when_threshold_is_zero(): void
    {
        $result = Transaction::periodSpendingComparison(
            filter: ['category_name' => 'Dining'],
            currentStart: '2026-03-01',
            currentEnd: '2026-03-31',
            periodsBack: 3,
            periodDays: 30,
            thresholdPercent: 0,
            thresholdMin: 50,
        );

        $this->assertFalse($result);
    }

    public function test_period_spending_returns_false_when_current_total_is_zero(): void
    {
        fireflyIII::setResponses([false]); // no current period transactions

        $result = Transaction::periodSpendingComparison(
            filter: ['category_name' => 'Dining'],
            currentStart: '2026-03-01',
            currentEnd: '2026-03-31',
            periodsBack: 3,
            periodDays: 30,
            thresholdPercent: 20,
            thresholdMin: 50,
        );

        $this->assertFalse($result);
    }

    public function test_period_spending_detects_overspending(): void
    {
        // Current period: 5 transactions of 100 = 500
        $currentTransactions = array_map(fn($i) => (object)['amount' => -100], range(1, 5));
        // Past period 1: 2 transactions of 100 = 200
        $pastPeriod1 = array_map(fn($i) => (object)['amount' => -100], range(1, 2));
        // Past period 2: 2 transactions of 100 = 200
        $pastPeriod2 = array_map(fn($i) => (object)['amount' => -100], range(1, 2));

        fireflyIII::setResponses([
            $currentTransactions, false,    // current period pages
            $pastPeriod1, false,            // past period 1 pages
            $pastPeriod2, false,            // past period 2 pages
        ]);

        $result = Transaction::periodSpendingComparison(
            filter: ['category_name' => 'Dining'],
            currentStart: '2026-03-01',
            currentEnd: '2026-03-31',
            periodsBack: 2,
            periodDays: 30,
            thresholdPercent: 20,
            thresholdMin: 50,
        );

        $this->assertIsArray($result);
        $this->assertEquals(500.0, $result['current_total']);
        $this->assertEquals(200.0, $result['average_total']);
        $this->assertEquals(150.0, $result['difference_percentage']);
        $this->assertEquals(300.0, $result['difference_amount']);
        $this->assertEquals(2.5, $result['multiplier']);
    }

    public function test_period_spending_returns_false_when_below_threshold(): void
    {
        // Current period: 2 transactions of 100 = 200
        $currentTransactions = array_map(fn($i) => (object)['amount' => -100], range(1, 2));
        // Past period 1: 2 transactions of 100 = 200 (same as current)
        $pastPeriod1 = array_map(fn($i) => (object)['amount' => -100], range(1, 2));

        fireflyIII::setResponses([
            $currentTransactions, false,
            $pastPeriod1, false,
        ]);

        $result = Transaction::periodSpendingComparison(
            filter: ['category_name' => 'Dining'],
            currentStart: '2026-03-01',
            currentEnd: '2026-03-31',
            periodsBack: 1,
            periodDays: 30,
            thresholdPercent: 20,
            thresholdMin: 50,
        );

        $this->assertFalse($result);
    }

    public function test_period_spending_returns_false_when_no_past_data(): void
    {
        $currentTransactions = array_map(fn($i) => (object)['amount' => -100], range(1, 5));

        fireflyIII::setResponses([
            $currentTransactions, false, // current period
            false,                       // past period 1 (empty)
            false,                       // past period 2 (empty)
        ]);

        $result = Transaction::periodSpendingComparison(
            filter: ['category_name' => 'Dining'],
            currentStart: '2026-03-01',
            currentEnd: '2026-03-31',
            periodsBack: 2,
            periodDays: 30,
            thresholdPercent: 20,
            thresholdMin: 50,
        );

        $this->assertFalse($result);
    }

    public function test_period_spending_returns_false_when_diff_amount_below_min(): void
    {
        // Current: 220, Past avg: 200 => 10% above, diff = 20 (below min 50)
        $currentTransactions = [(object)['amount' => -220]];
        $pastPeriod1 = [(object)['amount' => -200]];

        fireflyIII::setResponses([
            $currentTransactions, false,
            $pastPeriod1, false,
        ]);

        $result = Transaction::periodSpendingComparison(
            filter: ['destination_name' => 'Starbucks'],
            currentStart: '2026-03-01',
            currentEnd: '2026-03-31',
            periodsBack: 1,
            periodDays: 30,
            thresholdPercent: 5,
            thresholdMin: 50,
        );

        $this->assertFalse($result);
    }

    public function test_period_spending_uses_provided_filter(): void
    {
        fireflyIII::setResponses([false]); // returns false immediately

        Transaction::periodSpendingComparison(
            filter: ['destination_name' => 'Starbucks', 'budget_id' => 9],
            currentStart: '2026-03-01',
            currentEnd: '2026-03-31',
            periodsBack: 1,
            periodDays: 30,
            thresholdPercent: 20,
            thresholdMin: 50,
        );

        $calls = fireflyIII::getCallLog();
        $this->assertNotEmpty($calls);
        $this->assertEquals('Starbucks', $calls[0]['filter']['destination_name']);
        $this->assertEquals(9, $calls[0]['filter']['budget_id']);
    }
}