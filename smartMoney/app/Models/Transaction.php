<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\fireflyIII;
use Carbon\Carbon;
use App\Models\Currency;

class Transaction extends Model
{
    use HasFactory;
    protected $table = false;
    protected $fireflyIII;
    protected $SMS_sender;


public function createTransaction($transaction, $SMS_sender)
    {
        $this->fireflyIII = new fireflyIII();
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
            'MyAccountNumber' => null,
            'OtherAccountNumber' => null,
            'OtherAccountName' => null,
            'totalAmount' => null,
            'totalAmountCurrency' => null,
        ];
        $transaction = array_merge($transaction_default, $transaction);
        $transaction['date'] = self::normalizeTransactionDateTime($transaction['transactionDateTime']);
        unset($transaction['transactionDateTime']);

        if (!in_array($transaction['type'], ['withdrawal', 'deposit', 'payment', 'transfer'])) {
            $output['error'] = 'Invalid transaction type';
            return $output;
        }
        // FireFly III Types are withdrawal, deposit, transfer, reconciliation, opening balance
        if ($transaction['type'] == 'payment') {
            $transaction['type'] = 'withdrawal';
        }

        $transaction['amount'] = str_replace(',', '', $transaction['amount']);
        $transaction['totalAmount'] = str_replace(',', '', $transaction['totalAmount']);


        if (is_numeric($transaction['totalAmount']) || $transaction['totalAmount'] > 0) {
            $transaction['amount'] = $transaction['totalAmount'];
            $transaction['currency'] = $transaction['totalAmountCurrency'];
            $transaction['fees'] = null;
            $transaction['feesCurrency'] = null;
            unset($transaction['totalAmount']);
            unset($transaction['totalAmountCurrency']);
        }else{
            unset($transaction['totalAmount']);
            unset($transaction['totalAmountCurrency']);
        }

        if ($transaction['amount'] == '' || !is_numeric($transaction['amount']) || $transaction['amount'] <= 0) {
            $output['error'] = 'Invalid amount';
            return $output;
        }

        if (strlen($transaction['currency']) > 3) {
            $output['error'] = 'Invalid currency';
            return $output;
        }

        if ($transaction['currency'] == '') unset($transaction['currency']);
        if ($transaction['description'] == '') unset($transaction['description']);
        if ($transaction['notes'] == '') unset($transaction['notes']);
        if ($transaction['internal_reference'] == '') unset($transaction['internal_reference']);

        $transaction['fees'] = str_replace(',', '', $transaction['fees']);

        if (($transaction['fees'] != '' && !is_numeric($transaction['fees']))) {
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

        $transaction['MyAccountNumber'] = trim($transaction['MyAccountNumber']);
        if ($transaction['MyAccountNumber'] == '') {
            $output['error'] = 'MyAccountNumber cannot be empty';
            return $output;
        }

        $transaction['OtherAccountNumber'] = trim($transaction['OtherAccountNumber']);
        $transaction['OtherAccountName'] = trim(self::cleanName($transaction['OtherAccountName']));
        if ($transaction['OtherAccountName'] == '' && $transaction['OtherAccountNumber'] == '') {
            $output['error'] = 'OtherAccountName and OtherAccountNumber cannot both be empty';
            return $output;
        }
        

        if ($transaction['category_name'] == '' || $transaction['category_name'] == null) {
            unset($transaction['category_name']);
        }
        if ($transaction['category_id'] == '' || $transaction['category_id'] == null) {
            unset($transaction['category_id']);
        }

        $myaccount = $this->fireflyIII->getAccountBySMSAcctCode(sender: $this->SMS_sender->sender, accountCode: $transaction['MyAccountNumber'], failIfAccountCodeNotFound: true);

        if (!$myaccount) {
            $output['error'] = 'MyAccountNumber does not match any account in FireFly III';
            return $output;
        }

        $transaction['source_id'] = $myaccount->id;

        if (empty($transaction['currency'])) {
            $transaction['currency'] = $myaccount->attributes->currency_code ?? '';
        }

        if (empty($transaction['currency'])) {
            $output['error'] = 'Missing transaction currency';
            return $output;
        }
        if (empty($transaction['feesCurrency']) && isset($transaction['fees'])) {
            $transaction['feesCurrency'] = $myaccount->attributes->currency_code ?? '';
        }

        $fees = 0.0;

        if (
            isset($transaction['fees'], $transaction['feesCurrency']) &&
            is_numeric($transaction['fees']) &&
            (float)$transaction['fees'] > 0 &&
            !empty($transaction['feesCurrency']) &&
            strtoupper($transaction['feesCurrency']) !== strtoupper($transaction['currency'])
        ) {
            $convertedFees = Currency::exchangeRate(
                amount: (float)$transaction['fees'],
                from: strtoupper($transaction['feesCurrency']),
                to: strtoupper($transaction['currency'])
            );

            if ($convertedFees === false) {
                $output['error'] = 'Failed to convert fees to transaction currency';
                return $output;
            }

            $fees = (float)$convertedFees;
        }


        $amount = (float)$transaction['amount'] + $fees;
        $transaction['currency_code'] = strtoupper($myaccount->attributes->currency_code);

        if (strtoupper($transaction['currency']) !== strtoupper($myaccount->attributes->currency_code)) {
            $convertedAmount = Currency::exchangeRate(
                amount: $amount,
                from: strtoupper($transaction['currency']),
                to: strtoupper($myaccount->attributes->currency_code)
            );
            if ($convertedAmount === false) {
                $output['error'] = 'Failed to convert amount to account currency';
                return $output;
            }
            $transaction['foreign_currency_code'] = strtoupper($transaction['currency']);
            $transaction['foreign_amount'] = (float)$transaction['amount'];
            $transaction['amount'] = $convertedAmount;
        } else {
            $transaction['amount'] = $amount;
        }

        $transaction['destination_name'] = $transaction['OtherAccountName'] ?? $transaction['OtherAccountNumber'] ?? 'Unknown';

        $transaction['tags'][] = $transaction['MyAccountNumber'];
        if($myaccount->_failback) $transaction['tags'][] = '_failback';
        unset($transaction['currency']);
        unset($transaction['feesCurrency']);
        unset($transaction['MyAccountNumber']);
        unset($transaction['OtherAccountName']);
        unset($transaction['OtherAccountNumber']);
        if (!$transaction['tags']) unset($transaction['tags']);
        $result = $this->fireflyIII->newTransaction($transaction);

        if(isset($result->exception) || isset($result->errors) || isset($result->message)){
                $output['error'] = ($result->message ?? json_encode($result->errors) ?? 'Unknown error');
                return $output;
        }elseif(isset($result->data->id)){
            $output['success'] = true;
            $output['transaction_id'] = $result->data->id;
            $output['attributes'] = $result->data->attributes->transactions[0];
        }else{
            $output['error'] = 'Unknown error';
        }
        return $output;
    }
    public static $cleanName = [
        //BURGER KING 214 Q07
       ['/(.*) \d{3,}?\s?\w{1,}\d{1,}$/m','$1'],

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
       ['/\.$/m',''],

       // PAYPAL something
       ['/PAYPAL (.{3,})/m','$1'],

        //BURGER KING #5878
       ['/(.*) \#\d{1,}$/m','$1'],

        //KUDU W0059
       ['/(.*) [A-Z]\d{3,4}$/m','$1'],

       // starbucks-S942
       ['/(.*)-\w?\d{1,}$/m','$1'],

       // NAHDI MEDICAL CO
       ['/(.*) CO$/im','$1'],

       // sleep house est
       ['/(.*) est$/im','$1'],

       // CENTERPOINT 21481 RIY
       ['/(.{3,}) \d{4,} [A-Za-z]{3,}$/m','$1'],
   ];

    public static function cleanName($name){
        $name = strtolower($name);
        $regex_patterns = array_map(function($item) {
            return $item[0];
        }, static::$cleanName);
        $replacement = array_map(function($item) {
            return $item[1];
        }, static::$cleanName);
        $clean = preg_replace($regex_patterns, $replacement, $name);
        // $clean = preg_replace($regex_patterns, $replacement, $clean);

        $clean = trim(preg_replace('/\s{2,}/m', ' ' , $clean));
        if(strlen($clean) == 0) return ucwords($name);
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
            return ""; // Return empty if there are no lines
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
                    if (!str_contains($format, 'Y') && !str_contains($format, 'y')) {
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
