<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\CurrencyMap;
use App\Models\ParseSMS;
use App\Models\PendingTransaction;
use App\Models\Setting;
use App\Models\SMS;
use App\Models\SMSRegularExp;
use App\Models\SMSSender;
use App\Models\Transaction;
use App\Models\User;
use App\Services\fireflyIII;
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

        // Check if auto-create is disabled — if so, validate via dry run and queue for review
        $autoCreate = Setting::getBool('auto_create_transaction', true);

        if (! $autoCreate && ! $this->dryRun) {
            $transactionModel = new Transaction;
            $status = $transactionModel->createTransaction($transaction, $this->SMS_sender, true);

            if ($status['success']) {
                $resolvedTransaction = $status['transaction'] ?? [];
                $transaction['source_id'] = $resolvedTransaction['source_id'] ?? null;
                $transaction['source_name'] = $resolvedTransaction['source_name'] ?? null;
                $transaction['destination_id'] = $resolvedTransaction['destination_id'] ?? null;
                $transaction['destination_name'] = $resolvedTransaction['destination_name'] ?? null;
                $transaction['budget_id'] = $resolvedTransaction['budget_id'] ?? null;
                $transaction['currency'] = $resolvedTransaction['currency_code'] ?? $transaction['currency'] ?? '';
                $transaction['date'] = $resolvedTransaction['date'] ?? $transaction['date'] ?? now();
                $transaction['amount'] = $resolvedTransaction['amount'] ?? $transaction['amount'];

                $this->createPendingTransaction($transaction, 'manual_review');
                $this->sms->update(['is_processed' => true]);
            } else {
                SMS::processInvalidSMS(sms: $this->sms, message: 'Failed validation for review', errors: 'Validation failed: '.($status['error'] ?? 'Unknown'), keep: true);
            }

            return;
        }

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

            Transaction::postCreationActions(
                attributes: $status['attributes'],
                transaction: $transaction,
                smsId: $this->sms->id,
            );
        } else {
            // Create a pending transaction for review so the user can fix and retry
            $this->createPendingTransaction($transaction, 'error', $status['error'] ?? 'Unknown error');

            SMS::processInvalidSMS(sms: $this->sms, message: 'Failed to create transaction', errors: 'Failed to create transaction: '.$status['error'].' '.print_r($transaction, true), keep: true);

            return;
        }
    }

    protected function dryRunLog(string $type, string $message, array $data = []): void
    {
        $this->dryRunOutput[] = compact('type', 'message', 'data');
    }

    protected function createPendingTransaction(array $transaction, string $reason, ?string $errorMessage = null): void
    {
        try {
            // Compute the final amount: totalAmount > amount+fees > amount
            $amount = (float) ($transaction['amount'] ?? 0);
            $currency = $transaction['currency'] ?? '';

            $totalAmount = isset($transaction['totalAmount']) ? str_replace(',', '', $transaction['totalAmount']) : null;
            if (is_numeric($totalAmount) && (float) $totalAmount > 0) {
                $amount = (float) $totalAmount;
                $currency = $transaction['totalAmountCurrency'] ?? $currency;
            } else {
                $fees = isset($transaction['fees']) ? str_replace(',', '', $transaction['fees']) : null;
                if (is_numeric($fees) && (float) $fees > 0) {
                    $feesCurrency = $transaction['feesCurrency'] ?? '';
                    if ($feesCurrency === '' || strtoupper($feesCurrency) === strtoupper($currency)) {
                        $amount += (float) $fees;
                    }
                }
            }

            // Resolve budget_id from account if not already set
            $budgetId = $transaction['budget_id'] ?? null;
            if (! $budgetId && isset($transaction['source_id'])) {
                $account = Account::where('firefly_account_id', $transaction['source_id'])->first();
                if ($account) {
                    $budgetId = $account->budget_id;
                }
            }

            PendingTransaction::create([
                'sms_id' => $this->sms->id,
                'reason' => $reason,
                'error_message' => $errorMessage,
                'type' => $transaction['type'],
                'amount' => $amount,
                'currency' => $currency,
                'date' => $transaction['date'] ?? $transaction['transactionDateTime'] ?? now(),
                'description' => $transaction['description'] ?? null,
                'notes' => $transaction['notes'] ?? null,
                'category_name' => $transaction['category_name'] ?? null,
                'source_account_id' => $transaction['source_id'] ?? null,
                'source_account_name' => $transaction['source_name'] ?? $transaction['sourceAccountName'] ?? null,
                'destination_account_id' => $transaction['destination_id'] ?? null,
                'destination_account_name' => $transaction['destination_name'] ?? $transaction['destinationAccountName'] ?? null,
                'tags' => $transaction['tags'] ?? null,
                'budget_id' => $budgetId,
                'user_id' => isset($transaction['source_id'])
                    ? (Account::where('firefly_account_id', $transaction['source_id'])->first()?->user_id ?? 1)
                    : 1,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create pending transaction', ['error' => $e->getMessage()]);
        }
    }
}
