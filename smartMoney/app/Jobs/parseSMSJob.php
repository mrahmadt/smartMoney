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
use App\Models\Alert;

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

        $categories = $this->fireflyIII->getCategories();
        $SMSRegularExp = SMSRegularExp::findValidRegExp(sender_id: $this->SMS_sender->id, message: $this->sms->message);
        $transaction = [];


        if ($SMSRegularExp) {
            $detectedCategory = ParseSMS::detectCategory(message: $this->sms->message, transactionType: $SMSRegularExp['transactionType'], matches: $SMSRegularExp['matches'], categories: $categories);
            $transaction['type'] = $SMSRegularExp['transactionType'];
            $transaction['amount'] = $SMSRegularExp['matches']['amount'] ?? null;
            $transaction['currency'] = $SMSRegularExp['matches']['currency'] ?? null;
            $transaction['totalAmount'] = $SMSRegularExp['matches']['totalAmount'] ?? null;
            $transaction['totalAmountCurrency'] = $SMSRegularExp['matches']['totalAmountCurrency'] ?? null;

            $transaction['transactionDateTime'] = $SMSRegularExp['matches']['transactionDateTime'] ?? null;

            $transaction['MyAccountNumber'] = $SMSRegularExp['matches']['MyAccountNumber'] ?? null;
            $transaction['OtherAccountNumber'] = $SMSRegularExp['matches']['OtherAccountNumber'] ?? null;
            $transaction['OtherAccountName'] = $SMSRegularExp['matches']['OtherAccountName'] ?? null;

            $transaction['fees'] = $SMSRegularExp['matches']['fees'] ?? null;
            $transaction['feesCurrency'] = $SMSRegularExp['matches']['feesCurrency'] ?? null;

            $transaction['category_name'] = $detectedCategory['category'] ?? null;
            $transaction['description'] = Transaction::generateDescription($this->sms->message);
            $transaction['notes'] = $this->SMS_sender->sender . "\n" . $this->sms->message;
            $transaction['tags'] = ['regex:' . $SMSRegularExp['id']];
        } elseif (config('parseSMS.failback_AI')) { // Not found. now we need LLM support
            try {
                $output = ParseSMS::parseSMSviaLLM($this->sms->message, $categories);

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
                $transaction['transactionDateTime'] = $output['transactionDateTime'] ?? null;

                $transaction['MyAccountNumber'] = $output['MyAccountNumber'] ?? null;
                $transaction['OtherAccountNumber'] = $output['OtherAccountNumber'] ?? null;
                $transaction['OtherAccountName'] = $output['OtherAccountName'] ?? null;

                $transaction['fees'] = $output['fees'] ?? null;
                $transaction['feesCurrency'] = $output['feesCurrency'] ?? null;

                $transaction['category_name'] = $output['category'] ?? null;
                $transaction['description'] = Transaction::generateDescription($this->sms->message);
                $transaction['notes'] = $this->SMS_sender->sender . "\n" . $this->sms->message;
                $transaction['tags'] = ['by AI'];
            } catch (\Exception $e) {
                SMS::processInvalidSMS(sms: $this->sms, errors: 'Internal Error: ' . $e->getMessage(), keep: true);
                return;
            }
        }
        $transactionModel = new Transaction();
        $status = $transactionModel->createTransaction($transaction, $this->SMS_sender);
        dd($status);
        if ($status['success']) {
            print('Transaction created successfully with ID: ' . $status['transaction_id']);
            $this->sms->update(['is_processed' => true]);
            Alert::newTransaction($status['attributes']);
        } else {
            SMS::processInvalidSMS(sms: $this->sms, errors: 'Failed to create transaction: ' . $status['error'] . ' ' . print_r($transaction, true), keep: true);
            return;
        }
    }
}
