<?php

namespace App\Models;

use App\Services\fireflyIII;
use App\Services\TransactionCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Transaction extends Model
{
    use HasFactory;

    protected $table = false;

    protected $fireflyIII;

    protected $SMS_sender;

    public function createTransaction($transaction, $SMS_sender, bool $dryRun = false)
    {
        $this->fireflyIII = new fireflyIII;
        $this->SMS_sender = $SMS_sender;

        $output = [
            'success' => false,
            'error' => null,
            'transaction_id' => null,
        ];
        $transaction_default = [
            'type' => null,
            'amount' => null,
            'currency' => null,
            'transactionDateTime' => null,
            'description' => null,
            'notes' => null,
            'internal_reference' => null,
            'category_id' => null,
            'category_name' => null,
            'tags' => [],
            'fees' => null,
            'feesCurrency' => null,
            'sourceAccountNumber' => null,
            'sourceAccountName' => null,
            'destinationAccountNumber' => null,
            'destinationAccountName' => null,
            'totalAmount' => null,
            'totalAmountCurrency' => null,
        ];
        $transaction = array_merge($transaction_default, $transaction);
        $transaction['date'] = self::normalizeTransactionDateTime(value: $transaction['transactionDateTime'], tz: config('app.timezone', 'UTC'));
        unset($transaction['transactionDateTime']);

        if (! in_array($transaction['type'], ['withdrawal', 'deposit', 'payment', 'transfer'])) {
            $output['error'] = 'Invalid transaction type';

            return $output;
        }
        // FireFly III Types are withdrawal, deposit, transfer, reconciliation, opening balance
        if ($transaction['type'] == 'payment') {
            $transaction['type'] = 'withdrawal';
        }

        $transaction['amount'] = str_replace(',', '', $transaction['amount']);
        $transaction['totalAmount'] = str_replace(',', '', $transaction['totalAmount']);

        if (is_numeric($transaction['totalAmount']) && $transaction['totalAmount'] > 0) {
            $transaction['amount'] = $transaction['totalAmount'];
            $transaction['currency'] = $transaction['totalAmountCurrency'];
            $transaction['fees'] = null;
            $transaction['feesCurrency'] = null;
            unset($transaction['totalAmount']);
            unset($transaction['totalAmountCurrency']);
        } else {
            unset($transaction['totalAmount']);
            unset($transaction['totalAmountCurrency']);
        }

        if ($transaction['amount'] == '' || ! is_numeric($transaction['amount']) || $transaction['amount'] <= 0) {
            $output['error'] = 'Invalid amount';

            return $output;
        }
        if (strlen($transaction['currency']) > 3) {
            $output['error'] = 'Invalid currency';

            return $output;
        }

        if ($transaction['currency'] == '') {
            unset($transaction['currency']);
        }
        if ($transaction['description'] == '') {
            unset($transaction['description']);
        }
        if ($transaction['notes'] == '') {
            unset($transaction['notes']);
        }
        if ($transaction['internal_reference'] == '') {
            unset($transaction['internal_reference']);
        }

        $transaction['fees'] = str_replace(',', '', $transaction['fees']);

        if (($transaction['fees'] != '' && ! is_numeric($transaction['fees']))) {
            $output['error'] = 'Invalid fees' . ' ' . $transaction['fees'];

            return $output;
        } elseif ($transaction['fees'] <= 0 || $transaction['fees'] == '') {
            unset($transaction['fees']);
            unset($transaction['feesCurrency']);
        } else {
            if (strlen($transaction['feesCurrency']) > 3) {
                $output['error'] = 'Invalid fees currency';

                return $output;
            }
        }

        // Normalize source/destination fields
        $transaction['sourceAccountNumber'] = trim((string) $transaction['sourceAccountNumber']);
        $transaction['sourceAccountName'] = trim(self::cleanName((string) $transaction['sourceAccountName']));
        $transaction['destinationAccountNumber'] = trim((string) $transaction['destinationAccountNumber']);
        $transaction['destinationAccountName'] = trim(self::cleanName((string) $transaction['destinationAccountName']));

        Log::debug('Source/Destination fields', [
            'type' => $transaction['type'],
            'sourceAccountNumber' => $transaction['sourceAccountNumber'],
            'sourceAccountName' => $transaction['sourceAccountName'],
            'destinationAccountNumber' => $transaction['destinationAccountNumber'],
            'destinationAccountName' => $transaction['destinationAccountName'],
        ]);

        // Determine which side ("source" or "destination") represents the user's own account ("my account").
        // - deposit: money comes IN, so my account is the destination
        // - withdrawal/payment: money goes OUT, so my account is the source
        // - transfer: try source first (outgoing), then destination (incoming)
        $myShortcode = '';
        $mySide = null;
        if ($transaction['type'] === 'deposit') {
            $myShortcode = $transaction['destinationAccountNumber'];
            $mySide = 'destination';
        } elseif ($transaction['type'] === 'transfer') {
            if ($transaction['sourceAccountNumber'] !== '') {
                $myShortcode = $transaction['sourceAccountNumber'];
                $mySide = 'source';
            } elseif ($transaction['destinationAccountNumber'] !== '') {
                $myShortcode = $transaction['destinationAccountNumber'];
                $mySide = 'destination';
            }
        } else {
            // withdrawal (FireFly type after payment->withdrawal mapping)
            $myShortcode = $transaction['sourceAccountNumber'];
            $mySide = 'source';
        }

        if ($myShortcode === '') {
            $output['error'] = 'My account number cannot be empty';

            return $output;
        }

        if ($transaction['category_name'] == '' || $transaction['category_name'] == null) {
            unset($transaction['category_name']);
        }
        if ($transaction['category_id'] == '' || $transaction['category_id'] == null) {
            unset($transaction['category_id']);
        }

        $accountResult = Account::findBySenderAndShortcode(senderName: $this->SMS_sender->sender, shortcode: $myShortcode);

        // Transfer fallback: if source side didn't resolve, try the destination side as my account (incoming transfer)
        if (! $accountResult && $transaction['type'] === 'transfer' && $mySide === 'source' && $transaction['destinationAccountNumber'] !== '') {
            $accountResult = Account::findBySenderAndShortcode(senderName: $this->SMS_sender->sender, shortcode: $transaction['destinationAccountNumber']);
            if ($accountResult) {
                $myShortcode = $transaction['destinationAccountNumber'];
                $mySide = 'destination';
            }
        }

        if (! $accountResult) {
            $output['error'] = 'My account number does not match any account';

            return $output;
        }

        $myaccount = $accountResult['account'];
        $matchMethod = $accountResult['match'];

        if ($matchMethod === 'guess') {
            $transaction['tags'][] = 'shortcode:guess';
        } elseif ($matchMethod === 'sender_fallback') {
            $transaction['tags'][] = 'shortcode:sender_fallback';
        }

        // Resolve the "other party" fields based on which side is mine.
        if ($mySide === 'source') {
            $otherName = $transaction['destinationAccountName'];
            $otherNumber = $transaction['destinationAccountNumber'];
        } else {
            $otherName = $transaction['sourceAccountName'];
            $otherNumber = $transaction['sourceAccountNumber'];
        }

        if ($otherName === '' && $otherNumber === '') {
            $output['error'] = 'Other party name and number cannot both be empty';

            return $output;
        }

        $budgetId = $myaccount->getBudgetForShortcode($myShortcode);
        if ($budgetId) {
            $transaction['budget_id'] = $budgetId;
        }

        if (empty($transaction['currency'])) {
            $transaction['currency'] = $myaccount->currency_code ?? '';
        }

        if (empty($transaction['currency'])) {
            $output['error'] = 'Missing transaction currency';

            return $output;
        }
        if (empty($transaction['feesCurrency']) && isset($transaction['fees'])) {
            $transaction['feesCurrency'] = $myaccount->currency_code ?? '';
        }

        $fees = 0.0;

        if (
            isset($transaction['fees'], $transaction['feesCurrency']) &&
            is_numeric($transaction['fees']) &&
            (float) $transaction['fees'] > 0 &&
            ! empty($transaction['feesCurrency']) &&
            strtoupper($transaction['feesCurrency']) !== strtoupper($transaction['currency'])
        ) {
            $convertedFees = Currency::exchangeRate(
                amount: (float) $transaction['fees'],
                from: strtoupper($transaction['feesCurrency']),
                to: strtoupper($transaction['currency'])
            );

            if ($convertedFees === false) {
                $output['error'] = 'Failed to convert fees to transaction currency';

                return $output;
            }

            $fees = (float) $convertedFees;
        }

        $amount = (float) $transaction['amount'] + $fees;
        $transaction['currency_code'] = strtoupper($myaccount->currency_code);

        if (strtoupper($transaction['currency']) !== strtoupper($myaccount->currency_code)) {
            $convertedAmount = Currency::exchangeRate(
                amount: $amount,
                from: strtoupper($transaction['currency']),
                to: strtoupper($myaccount->currency_code)
            );
            if ($convertedAmount === false) {
                $output['error'] = 'Failed to convert amount to account currency';

                return $output;
            }
            $transaction['foreign_currency_code'] = strtoupper($transaction['currency']);
            $transaction['foreign_amount'] = (float) $transaction['amount'];
            $transaction['amount'] = $convertedAmount;
        } else {
            $transaction['amount'] = $amount;
        }

        $otherDisplayName = $otherName !== '' ? $otherName : ($otherNumber !== '' ? $otherNumber : 'Unknown');

        if ($transaction['type'] === 'transfer') {
            // Try to resolve the other side to a known asset account (any sender) by shortcode/IBAN suffix.
            $otherAccountResult = $otherNumber !== ''
                ? Account::findBySenderAndShortcode(
                    senderName: $this->SMS_sender->sender,
                    shortcode: $otherNumber,
                    alwaysGuessByIban: true,
                )
                : null;
            $otherAccount = $otherAccountResult['account'] ?? null;

            if ($mySide === 'source') {
                $transaction['source_id'] = $myaccount->firefly_account_id;
                if ($otherAccount) {
                    $transaction['destination_id'] = $otherAccount->firefly_account_id;
                } else {
                    $transaction['destination_name'] = $otherDisplayName;
                }
            } else {
                $transaction['destination_id'] = $myaccount->firefly_account_id;
                if ($otherAccount) {
                    $transaction['source_id'] = $otherAccount->firefly_account_id;
                } else {
                    $transaction['source_name'] = $otherDisplayName;
                }
            }
        } elseif ($transaction['type'] === 'deposit') {
            // Deposit: source = other party (revenue), destination = my asset account
            $transaction['destination_id'] = $myaccount->firefly_account_id;
            $transaction['source_name'] = $otherDisplayName;
        } else {
            // Withdrawal: source = my asset account, destination = other party (expense)
            $transaction['source_id'] = $myaccount->firefly_account_id;
            $transaction['destination_name'] = $otherDisplayName;
        }

        $transaction['tags'][] = $myShortcode;
        unset($transaction['currency']);
        unset($transaction['feesCurrency']);
        unset($transaction['sourceAccountNumber']);
        unset($transaction['sourceAccountName']);
        unset($transaction['destinationAccountNumber']);
        unset($transaction['destinationAccountName']);
        if (! $transaction['tags']) {
            unset($transaction['tags']);
        }

        if ($dryRun) {
            $output['success'] = true;
            $output['transaction'] = $transaction;

            return $output;
        }

        $result = self::callFireflyAPI($transaction);
        $result['budget_id'] = $transaction['budget_id'] ?? null;

        return $result;
    }

    /**
     * Submit a reviewed/edited transaction to Firefly III.
     * Handles account resolution (ID vs name), currency conversion, and API call.
     *
     * @param  array  $data  Form data with keys: type, amount, currency, date, description, notes,
     *                       category_name, tags, budget_id, source_account, destination_account
     * @return array{success: bool, error: ?string, transaction_id: ?int, attributes: ?object}
     */
    public static function submitToFirefly(array $data): array
    {
        $output = [
            'success' => false,
            'error' => null,
            'transaction_id' => null,
            'attributes' => null,
        ];

        $accounts = Account::pluck('firefly_account_name', 'firefly_account_id')->toArray();

        $fireflyTransaction = [
            'type' => $data['type'],
            'amount' => (float) $data['amount'],
            'currency_code' => strtoupper($data['currency']),
            'date' => $data['date'] instanceof \DateTimeInterface ? $data['date']->toIso8601String() : $data['date'],
        ];

        if (! empty($data['description'])) {
            $fireflyTransaction['description'] = $data['description'];
        }
        if (! empty($data['notes'])) {
            $fireflyTransaction['notes'] = $data['notes'];
        }
        if (! empty($data['category_name'])) {
            $fireflyTransaction['category_name'] = $data['category_name'];
        }
        if (! empty($data['tags'])) {
            $fireflyTransaction['tags'] = $data['tags'];
        }
        if (! empty($data['budget_id'])) {
            $fireflyTransaction['budget_id'] = (int) $data['budget_id'];
        }

        // Resolve source: numeric = account ID, string = account name
        $sourceValue = $data['source_account'] ?? null;
        $sourceAccountModel = null;
        if ($sourceValue && is_numeric($sourceValue) && isset($accounts[(int) $sourceValue])) {
            $fireflyTransaction['source_id'] = (int) $sourceValue;
            $sourceAccountModel = Account::where('firefly_account_id', (int) $sourceValue)->first();
        } elseif (! empty($sourceValue)) {
            $fireflyTransaction['source_name'] = $sourceValue;
        }

        // Resolve destination: numeric = account ID, string = account name
        $destValue = $data['destination_account'] ?? null;
        if ($destValue && is_numeric($destValue) && isset($accounts[(int) $destValue])) {
            $fireflyTransaction['destination_id'] = (int) $destValue;
        } elseif (! empty($destValue)) {
            $fireflyTransaction['destination_name'] = $destValue;
        }
        $txCurrency = strtoupper($data['currency']);
        unset($fireflyTransaction['currency']);

        // Currency conversion: if transaction currency differs from source account currency
        if ($sourceAccountModel && ! empty($sourceAccountModel->currency_code)) {
            $accountCurrency = strtoupper($sourceAccountModel->currency_code);
            $fireflyTransaction['currency_code'] = $accountCurrency;

            if ($txCurrency !== $accountCurrency) {
                $convertedAmount = Currency::exchangeRate(
                    amount: (float) $data['amount'],
                    from: $txCurrency,
                    to: $accountCurrency,
                );
                if ($convertedAmount === false) {
                    $output['error'] = 'Failed to convert amount to account currency';

                    return $output;
                }
                $fireflyTransaction['amount'] = $convertedAmount;
                $fireflyTransaction['foreign_currency_code'] = $txCurrency;
                $fireflyTransaction['foreign_amount'] = (float) $data['amount'];
            }
        }
        return self::callFireflyAPI($fireflyTransaction);
    }

    /**
     * Call Firefly III API to create a new transaction.
     * Shared by createTransaction() and submitToFirefly().
     *
     * @param  array  $transaction  Ready-to-send Firefly transaction payload
     * @return array{success: bool, error: ?string, transaction_id: ?int, attributes: ?object}
     */
    private static function callFireflyAPI(array $transaction): array
    {
        $output = [
            'success' => false,
            'error' => null,
            'transaction_id' => null,
            'attributes' => null,
        ];

        try {
            $firefly = new fireflyIII;
            $result = $firefly->newTransaction($transaction);

            if (isset($result->exception) || isset($result->errors) || isset($result->message)) {
                $errorParts = [];
                if (isset($result->message)) {
                    $errorParts[] = $result->message;
                }
                if (isset($result->errors)) {
                    foreach ((array) $result->errors as $field => $messages) {
                        foreach ((array) $messages as $msg) {
                            $errorParts[] = "[{$field}] {$msg}";
                        }
                    }
                }
                $output['error'] = implode(' | ', $errorParts) ?: 'Unknown error';

                return $output;
            }

            if (isset($result->data->id)) {
                $output['success'] = true;
                $output['transaction_id'] = $result->data->id;
                $output['attributes'] = $result->data->attributes->transactions[0];
            } else {
                $output['error'] = 'Unknown error from Firefly';
            }
        } catch (\Exception $e) {
            $output['error'] = $e->getMessage();
        }

        return $output;
    }

    /**
     * Run post-creation actions after a transaction is successfully submitted to Firefly.
     * Shared between parseSMSJob and ReviewTransactions page.
     *
     * @param  object  $attributes  Firefly transaction attributes
     * @param  array  $transaction  Parsed transaction data
     * @param  int|null  $smsId  The SMS record ID (to update is_processed/transaction_id)
     */
    public static function postCreationActions(object $attributes, array $transaction, ?int $smsId = null): void
    {
        $localAccount = Account::where('firefly_account_id', $attributes->source_id)->first();
        $user_id = $localAccount?->user_id ?? 1;
        $user = User::find($user_id);

        TransactionCache::clear($user_id);
        if ($user_id !== 1) {
            TransactionCache::clear(1);
        }

        Alert::newTransaction(transaction: $attributes, user: $user);

        $budget_id = $attributes->budget_id ?? null;

        // Create pending category review if mapping has alternatives
        if (in_array($transaction['type'] ?? null, ['withdrawal', 'payment', 'transfer'], true)) {
            $merchantName = $transaction['destinationAccountName'] ?? $transaction['destination_name'] ?? null;
        } else {
            $merchantName = $transaction['sourceAccountName'] ?? $transaction['source_name'] ?? null;
        }
        if ($merchantName) {
            try {
                $mapping = CategoryMapping::lookupMapping($merchantName);
                if ($mapping && $mapping->hasAlternatives()) {
                    PendingCategoryReview::create([
                        'firefly_transaction_id' => $attributes->transaction_journal_id ?? null,
                        'firefly_journal_id' => $attributes->transaction_journal_id ?? null,
                        'account_name' => $merchantName,
                        'category_mapping_id' => $mapping->id,
                        'current_category_id' => $mapping->category_id,
                        'alternative_category_ids' => $mapping->alternative_category_ids,
                        'user_id' => $localAccount?->user_id,
                        'budget_id' => $localAccount?->budget_id ?? $budget_id,
                        'transaction_amount' => $attributes->amount ?? $transaction['amount'] ?? 0,
                        'currency_code' => $attributes->currency_code ?? $transaction['currency'] ?? null,
                        'transaction_date' => $attributes->date ?? now(),
                        'transaction_description' => $attributes->description ?? $transaction['description'] ?? '',
                        'status' => 'pending',
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to create pending category review', ['error' => $e->getMessage(), 'merchant' => $merchantName]);
            }
        }

        $destination_id = $attributes->destination_id ?? null;

        if ($destination_id) {
            $destAbnormal = self::abnormalDestinationAmount(
                amount: $attributes->amount,
                destination_id: $destination_id,
                transaction_journal_id: $attributes->transaction_journal_id,
                budget_id: $budget_id,
            );
            if ($destAbnormal) {
                $destName = $attributes->destination_name ?? 'Unknown';
                app()->setLocale($user->language ?? 'en');
                Alert::createAlertWithAdminCopy(
                    title: __('alert.abnormal_destination_title', ['destination' => $destName]),
                    message: __('alert.abnormal_destination_message', [
                        'multiplier' => $destAbnormal['multiplier'],
                        'destination' => $destName,
                        'amount' => str_replace('.00', '', number_format($destAbnormal['amount'], 2)),
                        'average_amount' => str_replace('.00', '', number_format($destAbnormal['average_amount'], 2)),
                    ]),
                    user_id: $user_id,
                    transaction_journal_id: $attributes->transaction_journal_id,
                    data: $destAbnormal,
                    pin: true,
                    topic: 'abnormal',
                );
            }
        }

        $categoryName = $attributes->category_name ?? null;
        if ($categoryName) {
            $date = isset($attributes->date) ? Carbon::parse($attributes->date)->format('Y-m-d') : null;
            $freqResult = self::unusualCategoryFrequency(
                categoryName: $categoryName,
                date: $date,
                budget_id: $budget_id,
            );
            if ($freqResult) {
                app()->setLocale($user->language ?? 'en');
                Alert::createAlertWithAdminCopy(
                    title: __('alert.unusual_category_frequency_title', ['category' => $categoryName]),
                    message: __('alert.unusual_category_frequency_message', [
                        'count' => $freqResult['today_count'],
                        'category' => $categoryName,
                        'average' => str_replace('.00', '', $freqResult['average_daily_count']),
                    ]),
                    user_id: $user_id,
                    data: $freqResult,
                    pin: true,
                    topic: 'abnormal',
                );
            }
        }
    }

    public static function abnormalTransaction($amount, $type, $abnormal_threshold_percentage, $transaction_journal_id, $source_id = null, $destination_id = null, $category_id = null, $budget_id = null)
    {

        if ($abnormal_threshold_percentage == 0) {
            return false;
        } // if threshold is 0, disable abnormal transaction detection

        $filter = [];

        if ($source_id) {
            $filter['source_id'] = $source_id;
        }
        if ($destination_id) {
            $filter['destination_id'] = $destination_id;
        }
        if ($category_id) {
            $filter['category_id'] = $category_id;
        }
        if ($budget_id) {
            $filter['budget_id'] = $budget_id;
        }

        $total_pages = 10;
        $today = date('Y-m-d');
        $maxMonths = strtotime('-' . Setting::getInt('average_transactions_months', 3) . ' months', strtotime($today));
        $xMonthsAgo = date('Y-m-d', $maxMonths);

        $fireflyIII = new fireflyIII;
        $transactions = [];
        for ($page = 1; $page <= $total_pages; $page++) {
            $response = $fireflyIII->getTransactions(end: $today, start: $xMonthsAgo, filter: $filter, limit: 500, page: $page, type: $type);
            if ($response == false) {
                break;
            }
            foreach ($response as $item) {
                if (! isset($item->amount)) {
                    continue;
                }
                if ($item->transaction_journal_id == $transaction_journal_id) {
                    continue;
                }
                $transactions[] = [
                    'amount' => $item->amount,
                ];
            }
        }
        // calculate average amount from $transactions[*]['amount']
        $total_transactions = count($transactions);

        // if($total_transactions == 0) {
        //     echo "No transactions found for the given criteria.\n";
        // }
        if ($total_transactions == 0) {
            return false;
        }
        $average_amount = array_sum(array_column($transactions, 'amount')) / $total_transactions;
        // $difference_percentage = abs($amount - $average_amount) / $average_amount * 100;
        $difference_percentage = ($amount - $average_amount) / $average_amount * 100;

        if ($difference_percentage >= $abnormal_threshold_percentage) {

            return [
                'amount' => $amount,
                'average_amount' => $average_amount,
                'difference_percentage' => $difference_percentage,
            ];
        }

        return false;
    }

    /**
     * Check if a single transaction amount is abnormal for a destination.
     * Requires both % threshold AND minimum SAR diff to be exceeded.
     *
     * This is 40x your normal spend at Aldrewes. Amount: 30,000, Average: 750
     */
    public static function abnormalDestinationAmount($amount, $destination_id, $transaction_journal_id, $budget_id = null)
    {
        $threshold = Setting::getInt('abnormal_threshold_percentage_destination', 20);
        $minDiff = Setting::getInt('abnormal_threshold_percentage_destination_min', 50);

        if ($threshold == 0) {
            return false;
        }

        $result = self::abnormalTransaction(
            amount: $amount,
            type: 'withdrawal',
            abnormal_threshold_percentage: $threshold,
            transaction_journal_id: $transaction_journal_id,
            destination_id: $destination_id,
            budget_id: $budget_id,
        );

        if (! $result) {
            return false;
        }

        $diffAmount = abs($result['amount'] - $result['average_amount']);
        if ($diffAmount < $minDiff) {
            return false;
        }

        $result['difference_amount'] = $diffAmount;
        $result['multiplier'] = $result['average_amount'] > 0
            ? round($result['amount'] / $result['average_amount'], 1)
            : 0;

        return $result;
    }

    /**
     * Check if today's transaction count for a category is unusually high.
     * Compares today's count to average daily count over N months.
     * 2 Transportation transactions today, which is unusual (average: 0.4 per day)
     */
    public static function unusualCategoryFrequency($categoryName, $date = null, $budget_id = null)
    {
        $date = $date ?? date('Y-m-d');
        $multiplier = Setting::getInt('abnormal_frequency_multiplier', 2);
        $months = Setting::getInt('average_transactions_months', 3);

        $firefly = new fireflyIII;

        $filter = ['category_name' => $categoryName];
        if ($budget_id) {
            $filter['budget_id'] = $budget_id;
        }

        // Count today's transactions for this category
        $todayTransactions = $firefly->getTransactions(
            start: $date,
            end: $date,
            type: 'withdrawal',
            filter: $filter,
            limit: 200
        );
        $todayCount = is_array($todayTransactions) ? count($todayTransactions) : 0;
        if ($todayCount < 2) {
            return false;
        }
        // Count total transactions for this category over N months
        $startDate = date('Y-m-d', strtotime("-{$months} months", strtotime($date)));
        $totalCount = 0;
        for ($page = 1; $page <= 10; $page++) {
            $response = $firefly->getTransactions(
                start: $date,
                end: $startDate,
                type: 'withdrawal',
                filter: $filter,
                limit: 500,
                page: $page
            );
            if (! $response) {
                break;
            }
            $totalCount += count($response);
        }

        $totalDays = max(1, (strtotime($date) - strtotime($startDate)) / 86400);
        $averageDaily = $totalCount / $totalDays;

        if ($averageDaily <= 0) {
            return false;
        }
        if ($todayCount >= $multiplier * $averageDaily) {
            return [
                'today_count' => $todayCount,
                'average_daily_count' => round($averageDaily, 1),
            ];
        }

        return false;
    }

    /**
     * Check if today's transaction count for a destination is unusually high.
     */
    public static function unusualDestinationFrequency($destinationName, $date = null, $budget_id = null)
    {
        $date = $date ?? date('Y-m-d');
        $multiplier = Setting::getInt('abnormal_frequency_multiplier', 2);
        $months = Setting::getInt('average_transactions_months', 3);

        $firefly = new fireflyIII;

        $filter = ['destination_name' => $destinationName];
        if ($budget_id) {
            $filter['budget_id'] = $budget_id;
        }

        $todayTransactions = $firefly->getTransactions(
            start: $date,
            end: $date,
            type: 'withdrawal',
            filter: $filter,
            limit: 200
        );
        $todayCount = is_array($todayTransactions) ? count($todayTransactions) : 0;
        if ($todayCount < 2) {
            return false;
        }

        $startDate = date('Y-m-d', strtotime("-{$months} months", strtotime($date)));
        $totalCount = 0;
        for ($page = 1; $page <= 10; $page++) {
            $response = $firefly->getTransactions(
                start: $date,
                end: $startDate,
                type: 'withdrawal',
                filter: $filter,
                limit: 500,
                page: $page
            );
            if (! $response) {
                break;
            }
            $totalCount += count($response);
        }

        $totalDays = max(1, (strtotime($date) - strtotime($startDate)) / 86400);
        $averageDaily = $totalCount / $totalDays;

        if ($averageDaily <= 0) {
            return false;
        }
        if ($todayCount >= $multiplier * $averageDaily) {
            return [
                'today_count' => $todayCount,
                'average_daily_count' => round($averageDaily, 1),
            ];
        }

        return false;
    }

    /**
     * Compare spending for a period against average of past periods.
     * Returns result if both % and min amount thresholds are exceeded.
     *
     * @param  array  $filter  e.g. ['category_name' => 'Dining'] or ['destination_name' => 'Starbucks']
     * @param  string  $currentStart  Start of current period (Y-m-d)
     * @param  string  $currentEnd  End of current period (Y-m-d)
     * @param  int  $periodsBack  How many past periods to average
     * @param  int  $periodDays  Length of each period in days (1=daily, 7=weekly, 30=monthly)
     * @param  int  $thresholdPercent  Minimum % above average
     * @param  int  $thresholdMin  Minimum amount above average
     */
    public static function periodSpendingComparison(array $filter, string $currentStart, string $currentEnd, int $periodsBack, int $periodDays, int $thresholdPercent, int $thresholdMin, $budget_id = null)
    {
        if ($thresholdPercent == 0) {
            return false;
        }

        if ($budget_id) {
            $filter['budget_id'] = $budget_id;
        }

        $firefly = new fireflyIII;

        // Current period total
        $currentTotal = 0;
        for ($page = 1; $page <= 10; $page++) {
            $response = $firefly->getTransactions(
                start: $currentEnd,
                end: $currentStart,
                type: 'withdrawal',
                filter: $filter,
                limit: 500,
                page: $page
            );
            if (! $response) {
                break;
            }
            foreach ($response as $t) {
                $currentTotal += abs((float) $t->amount);
            }
        }

        if ($currentTotal == 0) {
            return false;
        }

        // Past periods totals
        $pastTotals = [];
        for ($i = 1; $i <= $periodsBack; $i++) {
            $pEnd = date('Y-m-d', strtotime('-' . ($i * $periodDays) . ' days', strtotime($currentEnd)));
            $pStart = date('Y-m-d', strtotime('-' . $periodDays . ' days', strtotime($pEnd)));

            $periodTotal = 0;
            for ($page = 1; $page <= 10; $page++) {
                $response = $firefly->getTransactions(
                    start: $pEnd,
                    end: $pStart,
                    type: 'withdrawal',
                    filter: $filter,
                    limit: 500,
                    page: $page
                );
                if (! $response) {
                    break;
                }
                foreach ($response as $t) {
                    $periodTotal += abs((float) $t->amount);
                }
            }
            $pastTotals[] = $periodTotal;
        }

        $pastTotals = array_filter($pastTotals, fn($t) => $t > 0);
        if (count($pastTotals) == 0) {
            return false;
        }

        $averageTotal = array_sum($pastTotals) / count($pastTotals);
        if ($averageTotal == 0) {
            return false;
        }

        $diffAmount = $currentTotal - $averageTotal;
        $diffPercent = ($diffAmount / $averageTotal) * 100;

        if ($diffPercent >= $thresholdPercent && $diffAmount >= $thresholdMin) {
            return [
                'current_total' => round($currentTotal, 2),
                'average_total' => round($averageTotal, 2),
                'difference_percentage' => round($diffPercent, 1),
                'difference_amount' => round($diffAmount, 2),
                'multiplier' => round($currentTotal / $averageTotal, 1),
            ];
        }

        return false;
    }

    public static $cleanName = [
        // BURGER KING 214 Q07
        ['/(.*) \d{3,}?\s?\w{1,}\d{1,}$/m', '$1'],

        // DUNKIN DONUTS 10236
        // DUNKIN DONUTS T10236
        ['/(.{3,}) \w?(\d{1,})$/m', '$1'],

        // S150 Tamimi Markets
        ['/^\w?(\d{1,})\s(.{3,})/m', '$2'],

        // GAB*CINEMA
        ['/(\*)/m', ' '],

        // ALDRDEES RB
        ['/(.{3,}) \w{1,2}$/m', '$1'],

        // Nintendo CA1160093092
        ['/(.{6,}) ([a-zA-Z]{1,3})?(\d{4,})$/m', '$1'],

        // UNIVERSAL COLD STORE .
        ['/\.$/m', ''],

        // PAYPAL something
        ['/paypal (.{3,})/mi', '$1'],

        // BURGER KING #5878
        ['/(.*) \#\d{1,}$/m', '$1'],

        // KUDU W0059
        ['/(.*) [A-Z]\d{3,4}$/m', '$1'],

        // starbucks-S942
        ['/(.*)-\w?\d{1,}$/m', '$1'],

        // NAHDI MEDICAL CO
        ['/(.*) CO$/im', '$1'],

        // sleep house est
        ['/(.*) est$/im', '$1'],

        // CENTERPOINT 21481 RIY
        ['/(.{3,}) \d{4,} [A-Za-z]{3,}$/m', '$1'],
    ];

    public static function cleanName($name)
    {
        $name = strtolower($name);
        $regex_patterns = array_map(function ($item) {
            return $item[0];
        }, static::$cleanName);
        $replacement = array_map(function ($item) {
            return $item[1];
        }, static::$cleanName);
        $clean = preg_replace($regex_patterns, $replacement, $name);
        // $clean = preg_replace($regex_patterns, $replacement, $clean);

        $clean = trim(preg_replace('/\s{2,}/m', ' ', $clean));
        if (strlen($clean) == 0) {
            return ucwords($name);
        }

        return ucwords($clean);
    }

    public static function generateDescription($message)
    {
        $description = static::extractFirstLine($message);
        $description = str_replace([':', '-', '/', ',', '\\', '?', '!'], ' ', $description);
        $description = preg_replace('/\.$/m', '', $description);

        return $description;
    }

    private static function extractFirstLine($text)
    {
        $limit_chars = 50;

        // Split the text into lines
        $lines = explode("\n", trim($text));

        if (empty($lines)) {
            return ''; // Return empty if there are no lines
        }

        // Get the first line
        $first_line = $lines[0];

        // Limit the first line to 25 characters without breaking words
        if (strlen($first_line) > $limit_chars) {
            // Find the last space within the first 25 characters
            $trimmed_line = substr($first_line, 0, $limit_chars);
            $last_space = strrpos($trimmed_line, ' ');

            if ($last_space !== false) {
                $first_line = substr($trimmed_line, 0, $last_space); // Trim at the last space
            } else {
                $first_line = $trimmed_line; // No space found, keep it as is
            }
        }

        return trim($first_line); // Return the extracted first line
    }

    public static function normalizeTransactionDateTime(?string $value, string $tz = 'Asia/Riyadh'): string
    {
        $value = trim((string) $value);
        if ($value == '') {
            return now($tz)->toIso8601String();
        }
        // Strict ISO 8601 with timezone: 2026-02-01T00:00:00+00:00 OR Z
        $isoRegex = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|[+\-]\d{2}:\d{2})$/';

        // If already correct, keep it
        if ($value !== '' && preg_match($isoRegex, $value)) {
            return $value;
        }

        // Try to parse other known formats and convert
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'd-m-Y',
            'm-d H:i',
            'm-d H:i:s',
        ];

        if ($value !== '') {
            foreach ($formats as $format) {
                try {
                    $dt = Carbon::createFromFormat($format, $value, $tz);

                    // If format has no year, assume current year
                    if (! str_contains($format, 'Y') && ! str_contains($format, 'y')) {
                        $dt->year(now($tz)->year);
                    }

                    return $dt->toIso8601String();
                } catch (\Throwable $e) {
                    // try next
                }
            }

            // Final fallback: let Carbon try to parse it
            try {
                return Carbon::parse($value, $tz)->toIso8601String();
            } catch (\Throwable $e) {
                // fall through to now()
            }
        }

        // If cannot parse/convert, return current time in required format
        return now($tz)->toIso8601String();
    }
}
