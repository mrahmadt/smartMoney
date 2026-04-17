<?php

namespace App\Models;

use App\Ai\Agents\GenerateRegex;
use App\Ai\Agents\parseSMS as parseSMSAgent;
use App\Ai\Agents\SMSCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ParseSMS extends Model
{
    use HasFactory;

    protected $table = false;

    public static function parseSMSviaLLM($sms_message)
    {
        $agent = new parseSMSAgent;
        $model = Setting::get('parsesms_model');
        $response = $agent->prompt($sms_message, model: $model);
        $output = json_decode($response->text, true);

        Log::debug('LLM parseSMS response', ['output' => $output]);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        // Clear currency fields when their amount is empty/zero
        if (isset($output['fees']) && (empty($output['fees']) || $output['fees'] == 0)) {
            if (isset($output['feesCurrency'])) {
                $output['feesCurrency'] = '';
            }
        }
        if (isset($output['totalAmount']) && (empty($output['totalAmount']) || $output['totalAmount'] == 0)) {
            if (isset($output['totalAmountCurrency'])) {
                $output['totalAmountCurrency'] = '';
            }
        }

        return $output;
    }

    /**
     * Generate a PHP regex for the given SMS using AI, based on parsed output as context.
     */
    public static function generateRegex(string $smsMessage, array $parsedOutput): ?string
    {
        $agent = new GenerateRegex;
        $agent->smsText = $smsMessage;
        $agent->parsedFields = $parsedOutput;

        $model = Setting::get('parsesms_regex_model');
        $response = $agent->prompt('Generate regex for this SMS', model: $model);
        $output = json_decode($response->text, true);

        Log::debug('LLM GenerateRegex response', ['output' => $output]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('GenerateRegex: invalid JSON response');

            return null;
        }

        $regex = $output['regularExp'] ?? null;
        if (empty($regex)) {
            return null;
        }

        // Validate required named groups exist
        $hasAmount = str_contains($regex, '(?P<amount>');
        $hasMyAccount = str_contains($regex, '(?P<MyAccountNumber>');
        $hasOther = str_contains($regex, '(?P<OtherAccountName>') || str_contains($regex, '(?P<OtherAccountNumber>');

        if (! $hasAmount || ! $hasMyAccount || ! $hasOther) {
            Log::warning('GenerateRegex: missing required named groups', [
                'regex' => $regex,
                'hasAmount' => $hasAmount,
                'hasMyAccount' => $hasMyAccount,
                'hasOther' => $hasOther,
            ]);

            return null;
        }

        // Validate regex actually matches the SMS and extracts correct values
        try {
            $result = @preg_match($regex, $smsMessage, $matches);

            if ($result === false || $result === 0) {
                Log::warning('GenerateRegex: regex does not match the original SMS', ['regex' => $regex]);

                return null;
            }

            Log::debug('GenerateRegex: regex matches the SMS', ['result' => $result, 'regex' => $regex, 'matches' => array_filter($matches, fn ($k) => ! is_int($k), ARRAY_FILTER_USE_KEY)]);

            // Compare captured values against parsed fields
            $currencyFields = ['currency', 'feesCurrency', 'totalAmountCurrency'];
            $amountFields = ['amount', 'totalAmount', 'fees'];
            $fieldsToCompare = ['amount', 'currency', 'totalAmount', 'totalAmountCurrency', 'fees', 'feesCurrency', 'transactionDateTime', 'MyAccountNumber', 'OtherAccountName', 'OtherAccountNumber'];
            $mismatches = [];
            foreach ($fieldsToCompare as $field) {
                $expected = (string) ($parsedOutput[$field] ?? '');
                $captured = $matches[$field] ?? '';
                // Skip if parsed field is empty — not expected in regex output
                if ($expected === '' || $expected === '0') {
                    continue;
                }
                // Skip if regex didn't capture this field (optional field)
                if ($captured === '') {
                    continue;
                }

                $normalizedExpected = str_replace(',', '', $expected);
                $normalizedCaptured = str_replace(',', '', trim($captured));

                // Normalize amounts: leading dot (".50" → "0.50") and trailing zeros ("0.50" vs "0.5")
                if (in_array($field, $amountFields)) {
                    if (str_starts_with($normalizedCaptured, '.')) {
                        $normalizedCaptured = '0'.$normalizedCaptured;
                    }
                    if (str_starts_with($normalizedExpected, '.')) {
                        $normalizedExpected = '0'.$normalizedExpected;
                    }
                    $normalizedExpected = rtrim(rtrim($normalizedExpected, '0'), '.');
                    $normalizedCaptured = rtrim(rtrim($normalizedCaptured, '0'), '.');
                }

                // Normalize currency fields through CurrencyMap
                if (in_array($field, $currencyFields)) {
                    $resolvedCaptured = CurrencyMap::resolve($normalizedCaptured);
                    if ($resolvedCaptured) {
                        $normalizedCaptured = $resolvedCaptured;
                    }
                }

                // Normalize datetime — compare as timestamps
                if ($field === 'transactionDateTime') {
                    $tsExpected = strtotime($normalizedExpected);
                    $tsCaptured = strtotime($normalizedCaptured);
                    if ($tsExpected !== false && $tsCaptured !== false && $tsExpected === $tsCaptured) {
                        continue;
                    }
                }

                if ($normalizedExpected !== $normalizedCaptured) {
                    $mismatches[$field] = ['expected' => $expected, 'captured' => $captured];
                }
            }

            if (! empty($mismatches)) {
                Log::warning('GenerateRegex: captured values do not match parsed fields', [
                    'regex' => $regex,
                    'mismatches' => $mismatches,
                ]);

                return null;
            }

            Log::debug('GenerateRegex: regex validated successfully', [
                'regex' => $regex,
                'matches' => array_filter($matches, fn ($k) => ! is_int($k), ARRAY_FILTER_USE_KEY),
            ]);
        } catch (\Exception $e) {
            Log::error('GenerateRegex: regex validation error', ['regex' => $regex, 'error' => $e->getMessage()]);

            return null;
        }

        return $regex;
    }

    public static function detectCategory($message, $transactionType, $matches, $categories = false)
    {
        $output = [
            'error' => null,
            'category' => 'Unknown',
        ];

        if (! in_array($transactionType, ['withdrawal', 'deposit', 'payment', 'transfer'])) {
            return false;
        }

        if ($transactionType == 'withdrawal') {
            $output['category'] = 'Cash';

            return $output;
        } elseif ($transactionType == 'transfer') {
            $output['category'] = 'Transfer';

            return $output;
        } elseif (in_array($transactionType, ['deposit', 'payment'])) {
            $account = $matches['OtherAccountNumber'] ?? $matches['OtherAccountName'] ?? false;
            if ($account) {
                $category = CategoryMapping::lookup($account);
                if ($category) {
                    $output['category'] = $category;

                    return $output;
                }
            }
        }
        if (Setting::getBool('parsesms_failback_detect_category_ai', false)) {
            $SMSCategoryAgent = new SMSCategory;
            if ($categories) {
                $SMSCategoryAgent->default_categories = $categories;
            }
            $merchant = $matches['OtherAccountName'] ?? $matches['OtherAccountNumber'] ?? '';
            $prompt = "Merchant: {$merchant}\nTransaction type: {$transactionType}\nSMS: {$message}";
            $model = Setting::get('parsesms_category_model');
            $response = $SMSCategoryAgent->prompt($prompt, model: $model);
            $output = json_decode($response->text, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }
            if (isset($output['category']) && $output['category'] !== 'Unknown' && $merchant !== '') {
                CategoryMapping::storeMapping($merchant, $output['category']);
            }
        }

        return $output;
    }
}
