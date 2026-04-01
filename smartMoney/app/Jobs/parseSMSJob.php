<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\fireflyIII;
use App\Models\SMSRegularExp;
use App\Models\SMS;
use App\Models\ParseSMS;
use App\Models\Transaction;
use App\Models\SMSSender;
use App\Models\Account;
use App\Models\Alert;
use App\Models\User;
use App\Models\Setting;
use Carbon\Carbon;

class parseSMSJob implements ShouldQueue
{
    use Queueable;

    protected $sms;
    protected $fireflyIII;
    protected $SMS_sender;

    /**
     * Create a new job instance.
     */
    public function __construct($sms)
    {
        $this->sms = $sms;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->fireflyIII = new fireflyIII();

        $SMS_sender = SMSSender::where('is_active', true)->where('sender', $this->sms->sender)->first();
        if (!$SMS_sender) {
            SMS::processInvalidSMS(sms: $this->sms, errors: 'No active sender found for this SMS', keep: true);
            return;
        }
        $this->SMS_sender = $SMS_sender;


        $isValid = SMS::isValidBankTransaction(message: $this->sms->message, cleanSMS: false);

        if (!$isValid) {
            SMS::processInvalidSMS(sms: $this->sms, errors: 'Not a valid bank transaction');
            return;
        }


        $SMSRegularExp = SMSRegularExp::findInvalidRegExp(sender_id: $this->SMS_sender->id, message: $this->sms->message);
        if ($SMSRegularExp) {
            SMS::processInvalidSMS(sms: $this->sms, errors: 'Invalid Transaction - regex matched id:' . $SMSRegularExp['id'], keep: true);
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
            // $detectedCategory = ParseSMS::detectCategory(message: $this->sms->message, transactionType: $SMSRegularExp['transactionType'], matches: $SMSRegularExp['matches'], categories: $categories);
            $detectedCategory = ParseSMS::detectCategory(message: $this->sms->message, transactionType: $SMSRegularExp['transactionType'], matches: $SMSRegularExp['matches']);
            $transaction['type'] = $SMSRegularExp['transactionType'];
            $transaction['amount'] = $SMSRegularExp['matches']['amount'] ?? null;
            $transaction['currency'] = $SMSRegularExp['matches']['currency'] ?? null;
            $transaction['totalAmount'] = $SMSRegularExp['matches']['totalAmount'] ?? null;
            $transaction['totalAmountCurrency'] = $SMSRegularExp['matches']['totalAmountCurrency'] ?? null;

            $transaction['transactionDateTime'] = $sms_date ?? $SMSRegularExp['matches']['transactionDateTime'] ?? null;

            $transaction['MyAccountNumber'] = $SMSRegularExp['matches']['MyAccountNumber'] ?? null;
            $transaction['OtherAccountNumber'] = $SMSRegularExp['matches']['OtherAccountNumber'] ?? null;
            $transaction['OtherAccountName'] = $SMSRegularExp['matches']['OtherAccountName'] ?? null;

            $transaction['fees'] = $SMSRegularExp['matches']['fees'] ?? null;
            $transaction['feesCurrency'] = $SMSRegularExp['matches']['feesCurrency'] ?? null;

            $transaction['category_name'] = $detectedCategory['category'] ?? null;
            $transaction['description'] = Transaction::generateDescription($this->sms->message);
            $transaction['notes'] = $this->SMS_sender->sender . "\n" . $this->sms->message;
            $transaction['tags'] = ['regex:' . $SMSRegularExp['id']];
            $newTransaction = true;
        } elseif (Setting::getBool('parsesms_failback_ai', false)) { // Not found. now we need LLM support
            try {
                $output = ParseSMS::parseSMSviaLLM($this->sms->message);
                if ($output === false) {
                    SMS::processInvalidSMS(sms: $this->sms, errors: 'LLM returned invalid JSON output', keep: true);
                    return;
                }

                if (isset($output['error']) && $output['error'] !== '') {
                    SMS::processInvalidSMS(sms: $this->sms, errors: 'LLM error: ' . $output['error'], keep: true);
                    return;
                }
                if (!isset($output['transactionType']) || !in_array($output['transactionType'], ['withdrawal', 'deposit', 'payment', 'transfer'])) {
                    SMS::processInvalidSMS(sms: $this->sms, errors: 'Invalid Transaction Type: ' . ($output['transactionType'] ?? 'null'), keep: true);
                    return;
                }

                if (isset($output['regularExp']) && $output['regularExp'] !== '') {
                    SMSRegularExp::storeRegularExp(
                        message: $this->sms->message,
                        regularExp: $output['regularExp'],
                        sender_id: $this->SMS_sender->id,
                        transactionType: $output['transactionType'],
                        ai_output: $output,
                        isValid: true
                    );
                }
                $transaction['type'] = $output['transactionType'];
                $transaction['amount'] = $output['amount'] ?? null;
                $transaction['currency'] = $output['currency'] ?? null;
                $transaction['totalAmount'] = $output['totalAmount'] ?? null;
                $transaction['totalAmountCurrency'] = $output['totalAmountCurrency'] ?? null;
                $transaction['transactionDateTime'] = $sms_date ?? $output['transactionDateTime'] ?? null;

                $transaction['MyAccountNumber'] = $output['MyAccountNumber'] ?? null;
                $transaction['OtherAccountNumber'] = $output['OtherAccountNumber'] ?? null;
                $transaction['OtherAccountName'] = $output['OtherAccountName'] ?? null;

                $transaction['fees'] = $output['fees'] ?? null;
                $transaction['feesCurrency'] = $output['feesCurrency'] ?? null;

                $detectedCategory = ParseSMS::detectCategory(message: $this->sms->message, transactionType: $output['transactionType'], matches: $output);
                $transaction['category_name'] = $detectedCategory['category'] ?? null;
                $transaction['description'] = Transaction::generateDescription($this->sms->message);
                $transaction['notes'] = $this->SMS_sender->sender . "\n" . $this->sms->message;
                $transaction['tags'] = ['by AI'];
                $newTransaction = true;
            } catch (\Exception $e) {
                SMS::processInvalidSMS(sms: $this->sms, errors: 'Internal Error: ' . $e->getMessage(), keep: true);
                return;
            }
        }
        if (!$newTransaction) {
            SMS::processInvalidSMS(sms: $this->sms, errors: 'No regex matched and AI fallback is disabled or failed', keep: true);
            return;
        }
        $transactionModel = new Transaction();
        $status = $transactionModel->createTransaction($transaction, $this->SMS_sender);

        if ($status['success']) {
            print('Transaction created successfully with ID: ' . $status['transaction_id']);
            $this->sms->update(['is_processed' => true]);

            $localAccount = Account::where('firefly_account_id', $status['attributes']->source_id)->first();
            $user_id = $localAccount?->user_id ?? 1;
            $user = User::find($user_id);
            Alert::newTransaction(transaction: $status['attributes'], user: $user);

            $budget_id = $status['attributes']->budget_id ?? null;
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
                            'amount' => str_replace('.00','',number_format($destAbnormal['amount'], 2)),
                            'average_amount' => str_replace('.00','',number_format($destAbnormal['average_amount'], 2)),
                        ]),
                        user_id: $user_id,
                        transaction_journal_id: $status['attributes']->transaction_journal_id,
                        data: $destAbnormal,
                        pin: true,
                    );
                }
            }

            // Real-time check: Unusual category frequency today
            // 2 Transportation transactions today, which is unusual (average: 0.4 per day)
            $categoryName = $status['attributes']->category_name ?? null;
            if ($categoryName) {
                if(isset($status['attributes']->date)){
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
                            'average' => str_replace('.00','',$freqResult['average_daily_count']),
                        ]),
                        user_id: $user_id,
                        data: $freqResult,
                        pin: true,
                    );
                }
            }

        } else {
            SMS::processInvalidSMS(sms: $this->sms, errors: 'Failed to create transaction: ' . $status['error'] . ' ' . print_r($transaction, true), keep: true);
            return;
        }
    }
}
