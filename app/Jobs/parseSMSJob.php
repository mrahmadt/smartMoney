<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Alert;
use App\Models\CategoryMapping;
use App\Models\CurrencyMap;
use App\Models\ParseSMS;
use App\Models\PendingCategoryReview;
use App\Models\Setting;
use App\Models\SMS;
use App\Models\SMSRegularExp;
use App\Models\SMSSender;
use App\Models\Transaction;
use App\Models\User;
use App\Services\fireflyIII;
use App\Services\TransactionCache;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class parseSMSJob implements ShouldQueue
{
    use Queueable;

    protected $sms;

    protected $fireflyIII;

    protected $SMS_sender;

    public bool $dryRun = false;

    public array $dryRunOutput = [];

    /**
     * Create a new job instance.
     */
    public function __construct($sms, bool $dryRun = false)
    {
        $this->sms = $sms;
        $this->dryRun = $dryRun;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->fireflyIII = new fireflyIII;

        $SMS_sender = SMSSender::where('is_active', true)->where('sender', $this->sms->sender)->first();
        if (! $SMS_sender) {
            $this->dryRunLog('error', 'No active sender found for this SMS');
            if (! $this->dryRun) {
                SMS::processInvalidSMS(sms: $this->sms, errors: 'No active sender found for this SMS', keep: true);
            }

            return;
        }
        $this->SMS_sender = $SMS_sender;

        $isValid = SMS::isValidBankTransaction(message: $this->sms->message, cleanSMS: false);

        if (! $isValid) {
            $this->dryRunLog('error', 'Not a valid bank transaction');
            if (! $this->dryRun) {
                SMS::processInvalidSMS(sms: $this->sms, errors: 'Not a valid bank transaction');
            }

            return;
        }
        $this->dryRunLog('info', 'SMS is a valid bank transaction');

        $SMSRegularExp = SMSRegularExp::findInvalidRegExp(sender_id: $this->SMS_sender->id, message: $this->sms->message);
        if ($SMSRegularExp) {
            $this->dryRunLog('error', 'Invalid Transaction - regex matched id:'.$SMSRegularExp['id']);
            if (! $this->dryRun) {
                SMS::processInvalidSMS(sms: $this->sms, errors: 'Invalid Transaction - regex matched id:'.$SMSRegularExp['id'], keep: true);
            }

            return;
        }

        // $categories = $this->fireflyIII->getCategories();
        $SMSRegularExp = false;
        if (Setting::getBool('parsesms_regex_enabled', true)) {
            $SMSRegularExp = SMSRegularExp::findValidRegExp(sender_id: $this->SMS_sender->id, message: $this->sms->message);
        }
        $transaction = [];
        $sms_date = $this->sms->content['query']['date'] ?? null;

        $newTransaction = false;
        if ($SMSRegularExp) {
            $this->dryRunLog('info', 'Matched regex id: '.$SMSRegularExp['id'], ['transactionType' => $SMSRegularExp['transactionType'], 'matches' => $SMSRegularExp['matches']]);
            // $detectedCategory = ParseSMS::detectCategory(message: $this->sms->message, transactionType: $SMSRegularExp['transactionType'], matches: $SMSRegularExp['matches'], categories: $categories);
            $detectedCategory = ParseSMS::detectCategory(message: $this->sms->message, transactionType: $SMSRegularExp['transactionType'], matches: $SMSRegularExp['matches']);
            $transaction['type'] = $SMSRegularExp['transactionType'];
            $transaction['amount'] = $SMSRegularExp['matches']['amount'] ?? null;
            $transaction['currency'] = $SMSRegularExp['matches']['currency'] ?? null;
            $transaction['totalAmount'] = $SMSRegularExp['matches']['totalAmount'] ?? null;
            $transaction['totalAmountCurrency'] = $SMSRegularExp['matches']['totalAmountCurrency'] ?? null;

            $transaction['transactionDateTime'] = $sms_date ?? $SMSRegularExp['matches']['transactionDateTime'] ?? null;

            $transaction['sourceAccountNumber'] = $SMSRegularExp['matches']['sourceAccountNumber'] ?? null;
            $transaction['sourceAccountName'] = $SMSRegularExp['matches']['sourceAccountName'] ?? null;
            $transaction['destinationAccountNumber'] = $SMSRegularExp['matches']['destinationAccountNumber'] ?? null;
            $transaction['destinationAccountName'] = $SMSRegularExp['matches']['destinationAccountName'] ?? null;

            $transaction['fees'] = $SMSRegularExp['matches']['fees'] ?? null;
            $transaction['feesCurrency'] = $SMSRegularExp['matches']['feesCurrency'] ?? null;

            // Normalize currency fields through CurrencyMap
            foreach (['currency', 'feesCurrency', 'totalAmountCurrency'] as $currencyField) {
                if (! empty($transaction[$currencyField])) {
                    $resolved = CurrencyMap::resolve($transaction[$currencyField]);
                    if ($resolved) {
                        $transaction[$currencyField] = $resolved;
                    }
                }
            }

            $transaction['category_name'] = $detectedCategory['category'] ?? null;
            $transaction['description'] = Transaction::generateDescription($this->sms->message);
            $transaction['notes'] = $this->SMS_sender->sender."\n".$this->sms->message;
            $transaction['tags'] = ['regex:'.$SMSRegularExp['id']];
            $newTransaction = true;
        } elseif (Setting::getBool('parsesms_failback_ai', false)) { // Not found. now we need LLM support
            try {
                $output = ParseSMS::parseSMSviaLLM($this->sms->message);
                if ($output === false) {
                    $this->dryRunLog('error', 'LLM returned invalid JSON output');
                    if (! $this->dryRun) {
                        SMS::processInvalidSMS(sms: $this->sms, errors: 'LLM returned invalid JSON output', keep: true);
                    }

                    return;
                }

                if (isset($output['error']) && $output['error'] !== '') {
                    $this->dryRunLog('error', 'LLM error: '.$output['error']);
                    if (! $this->dryRun) {
                        SMS::processInvalidSMS(sms: $this->sms, message: 'LLM error', errors: 'LLM error: '.$output['error'], keep: true);
                    }

                    return;
                }
                if (! isset($output['transactionType']) || ! in_array($output['transactionType'], ['withdrawal', 'deposit', 'payment', 'transfer'])) {
                    $this->dryRunLog('error', 'Invalid Transaction Type: '.($output['transactionType'] ?? 'null'));
                    if (! $this->dryRun) {
                        SMS::processInvalidSMS(sms: $this->sms, errors: 'Invalid Transaction Type: '.($output['transactionType'] ?? 'null'), keep: true);
                    }

                    return;
                }
                $this->dryRunLog('info', 'LLM parsed successfully', $output);
                if (Setting::getBool('parsesms_regex_enabled', true) && ! $this->dryRun) {
                    try {
                        $generatedRegex = ParseSMS::generateRegex($this->sms->message, $output);
                        if ($generatedRegex) {
                            SMSRegularExp::storeRegularExp(
                                message: $this->sms->message,
                                regularExp: $generatedRegex,
                                sender_id: $this->SMS_sender->id,
                                transactionType: $output['transactionType'],
                                ai_output: $output,
                                isValid: true
                            );
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to generate/store regex', ['error' => $e->getMessage()]);
                    }
                }
                $transaction['type'] = $output['transactionType'];
                $transaction['amount'] = $output['amount'] ?? null;
                $transaction['currency'] = $output['currency'] ?? null;
                $transaction['totalAmount'] = $output['totalAmount'] ?? null;
                $transaction['totalAmountCurrency'] = $output['totalAmountCurrency'] ?? null;
                $transaction['transactionDateTime'] = $sms_date ?? $output['transactionDateTime'] ?? null;

                $transaction['sourceAccountNumber'] = $output['sourceAccountNumber'] ?? null;
                $transaction['sourceAccountName'] = $output['sourceAccountName'] ?? null;
                $transaction['destinationAccountNumber'] = $output['destinationAccountNumber'] ?? null;
                $transaction['destinationAccountName'] = $output['destinationAccountName'] ?? null;

                $transaction['fees'] = $output['fees'] ?? null;
                $transaction['feesCurrency'] = $output['feesCurrency'] ?? null;

                $detectedCategory = ParseSMS::detectCategory(message: $this->sms->message, transactionType: $output['transactionType'], matches: $output);
                $transaction['category_name'] = $detectedCategory['category'] ?? null;
                $transaction['description'] = Transaction::generateDescription($this->sms->message);
                $transaction['notes'] = $this->SMS_sender->sender."\n".$this->sms->message;
                $transaction['tags'] = ['by AI'];
                $newTransaction = true;
            } catch (\Exception $e) {
                $this->dryRunLog('error', 'Internal Error: '.$e->getMessage());
                if (! $this->dryRun) {
                    SMS::processInvalidSMS(sms: $this->sms, message: 'Internal Error (generateDescription)', errors: 'Internal Error: '.$e->getMessage(), keep: true);
                }

                return;
            }
        }
        if (! $newTransaction) {
            $this->dryRunLog('error', 'No regex matched and AI fallback is disabled or failed');
            if (! $this->dryRun) {
                SMS::processInvalidSMS(sms: $this->sms, errors: 'No regex matched and AI fallback is disabled or failed', keep: true);
            }

            return;
        }

        $this->dryRunLog('transaction', 'Parsed transaction data', $transaction);

        $transactionModel = new Transaction;
        $status = $transactionModel->createTransaction($transaction, $this->SMS_sender, $this->dryRun);

        if ($this->dryRun) {
            if ($status['success']) {
                $this->dryRunLog('transaction', 'Firefly III transaction payload', $status['transaction'] ?? []);
            } else {
                $this->dryRunLog('error', 'Validation failed: '.($status['error'] ?? 'Unknown'));
            }

            return;
        }

        \Log::debug('Transaction creation status', ['status' => $status, 'transaction' => $transaction]);
        if ($status['success']) {
            echo 'Transaction created successfully with ID: '.$status['transaction_id'];
            $this->sms->update([
                'is_processed' => true,
                'transaction_id' => $status['transaction_id'],
            ]);

            $localAccount = Account::where('firefly_account_id', $status['attributes']->source_id)->first();
            $user_id = $localAccount?->user_id ?? 1;
            $user = User::find($user_id);

            // Clear dashboard transaction cache for affected users
            TransactionCache::clear($user_id);
            if ($user_id !== 1) {
                TransactionCache::clear(1);
            }

            Alert::newTransaction(transaction: $status['attributes'], user: $user);

            $budget_id = $status['attributes']->budget_id ?? null;

            // Create pending category review if mapping has alternatives
            // Pick the merchant/other-party name based on transaction type for category review.
            if (in_array($transaction['type'] ?? null, ['withdrawal', 'payment', 'transfer'], true)) {
                $merchantName = $transaction['destinationAccountName'] ?? $transaction['destinationAccountNumber'] ?? null;
            } else {
                $merchantName = $transaction['sourceAccountName'] ?? $transaction['sourceAccountNumber'] ?? null;
            }
            if ($merchantName) {
                try {
                    $mapping = CategoryMapping::lookupMapping($merchantName);
                    if ($mapping && $mapping->hasAlternatives()) {
                        PendingCategoryReview::create([
                            'firefly_transaction_id' => $status['transaction_id'],
                            'firefly_journal_id' => $status['attributes']->transaction_journal_id ?? $status['transaction_id'],
                            'account_name' => $merchantName,
                            'category_mapping_id' => $mapping->id,
                            'current_category_id' => $mapping->category_id,
                            'alternative_category_ids' => $mapping->alternative_category_ids,
                            'user_id' => $localAccount?->user_id,
                            'budget_id' => $localAccount?->budget_id ?? $budget_id,
                            'transaction_amount' => $status['attributes']->amount ?? $transaction['amount'] ?? 0,
                            'currency_code' => $status['attributes']->currency_code ?? $transaction['currency'] ?? null,
                            'transaction_date' => $status['attributes']->date ?? now(),
                            'transaction_description' => $status['attributes']->description ?? $transaction['description'] ?? '',
                            'status' => 'pending',
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to create pending category review', ['error' => $e->getMessage(), 'merchant' => $merchantName]);
                }
            }
            // $category_id = $status['attributes']->category_id ?? null;

            // $source_id = $status['attributes']->source_id ?? null;
            $destination_id = $status['attributes']->destination_id ?? null;
            // $type = $status['attributes']->type ?? ($transaction['type'] ?? null);

            // $abnormal_threshold_percentage = 0;

            // Real-time check: Destination amount abnormal
            // This is 40x your normal spend at Aldrewes. Amount: 30,000, Average: 750
            if ($destination_id) {
                $destAbnormal = Transaction::abnormalDestinationAmount(
                    amount: $status['attributes']->amount,
                    destination_id: $destination_id,
                    transaction_journal_id: $status['attributes']->transaction_journal_id,
                    budget_id: $budget_id,
                );
                if ($destAbnormal) {
                    $destName = $status['attributes']->destination_name ?? 'Unknown';
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
                        transaction_journal_id: $status['attributes']->transaction_journal_id,
                        data: $destAbnormal,
                        pin: true,
                        topic: 'abnormal',
                    );
                }
            }

            // Real-time check: Unusual category frequency today
            // 2 Transportation transactions today, which is unusual (average: 0.4 per day)
            $categoryName = $status['attributes']->category_name ?? null;
            if ($categoryName) {
                if (isset($status['attributes']->date)) {
                    $date = Carbon::parse($status['attributes']->date)->format('Y-m-d');
                } else {
                    $date = null;
                }
                $freqResult = Transaction::unusualCategoryFrequency(
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
        } else {
            SMS::processInvalidSMS(sms: $this->sms, message: 'Failed to create transaction', errors: 'Failed to create transaction: '.$status['error'].' '.print_r($transaction, true), keep: true);

            return;
        }
    }

    protected function dryRunLog(string $type, string $message, array $data = []): void
    {
        $this->dryRunOutput[] = compact('type', 'message', 'data');
    }
}
