<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Ai\Agents\SMSCategory;
use App\Ai\Agents\parseSMS as parseSMSAgent;

class ParseSMS extends Model
{
    use HasFactory;
    protected $table = false;

    public static function parseSMSviaLLM($sms_message)
    {
        $agent = new parseSMSAgent();
        $model = Setting::get('parsesms_model');
        $response = $agent->prompt($sms_message, model: $model);
        $output = json_decode($response->text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        return $output;
    }

    public static function detectCategory($message, $transactionType, $matches, $categories = false)
    {
        $output = [
            'error' => null,
            'category' => 'Unknown',
        ];

        if (!in_array($transactionType, ['withdrawal', 'deposit', 'payment', 'transfer'])) return false;

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
            $SMSCategoryAgent = new SMSCategory();
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
