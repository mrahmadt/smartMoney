<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\fireflyIII;
use App\Services\OpenAI;
use App\Ai\Agents\SMSCategory;
use App\Ai\Agents\parseSMS as parseSMSAgent;

class ParseSMS extends Model
{
    use HasFactory;
    protected $table = false;

    private static function prepareCategories($fireflyIII_categories = false)
    {
        if ($fireflyIII_categories === false) {
            $fireflyIII = new fireflyIII();
            $fireflyIII_categories = $fireflyIII->getCategories();
        }
        $default_categories = ["Pharmacy", "Grocery", "Restaurant", "Shopping", "Entertainment", "Transportation", "Utilities", "Healthcare", "Education", "Travel"];
        $categories = array_merge($default_categories, $fireflyIII_categories);
        $categories = array_unique($categories);
        $categories = implode(',', $categories);
        return $categories;
    }

    public static function parseSMSviaLLM($sms_message, $categories = false)
    {
        // $openai = new OpenAI();
        // $categoriesStr = self::prepareCategories($categories);

        // $timezone = config('app.timezone');
        // $prompt = file_get_contents(storage_path('app/prompts/parseSMS-prompt.txt'));
        // $schema = file_get_contents(storage_path('app/prompts/parseSMS-schema.json'));


        // $prompt = str_replace('%%timezone%%', $timezone, $prompt);
        // $prompt = str_replace('%%categories%%', $categoriesStr, $prompt);
        // $query = str_replace('%%sms_message%%', $sms_message, $prompt);

        // $schema = str_replace('%%timezone%%', $timezone, $schema);
        // $schema = str_replace('%%categories%%', $categoriesStr, $schema);

        // $llm_options = [
        //     'schema' => $schema
        // ];
        // $options = [
        //     'model' => config('openai.default_model'),
        //     'query' => $query,
        //     'llm_options' => $llm_options,
        // ];
        // $output = $openai->runSync($options);
        // $output = json_decode($output, true);

        $SMSCategoryAgent = new parseSMSAgent();
        if ($categories) {
            $SMSCategoryAgent->default_categories = $categories;
        }
        $response = $SMSCategoryAgent->prompt($sms_message);
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

        $fireflyIII = new fireflyIII();

        if ($transactionType == 'withdrawal') {
            $output['category'] = 'Cash';
            return $output;
        } elseif ($transactionType == 'transfer') {
            $output['category'] = 'Transfer';
            return $output;
        } elseif (in_array($transactionType, ['deposit', 'payment'])) {
            // $account = $matches['OtherAccountNumber'] ?? $matches['OtherAccountName'] ?? $matches['MyAccountNumber'] ?? false;
            $account = $matches['OtherAccountNumber'] ?? $matches['OtherAccountName'] ?? false;
            if ($account) {
                $category = $fireflyIII->lookupCategory($account);
                if ($category) {
                    $output['category'] = $category;
                    return $output;
                }
            }
        }
        if (config('parseSMS.failback_detect_category_AI')) {
            // $openai = new OpenAI();
            // $categoriesStr = self::prepareCategories($categories);
            // $prompt = file_get_contents(storage_path('app/prompts/parseSMS-category-prompt.txt'));
            // $schema = file_get_contents(storage_path('app/prompts/parseSMS-category-schema.json'));
            // $prompt = str_replace('%%categories%%', $categoriesStr, $prompt);
            // $query = str_replace('%%sms_message%%', $message, $prompt);
            // $schema = str_replace('%%categories%%', $categoriesStr, $schema);

            // $llm_options = [
            //     'schema' => $schema
            // ];
            // $options = [
            //     'model' => config('openai.default_model'),
            //     'query' => $query,
            //     'llm_options' => $llm_options,
            // ];
            // $output = $openai->runSync($options);
            $SMSCategoryAgent = new SMSCategory();
            if ($categories) {
                $SMSCategoryAgent->default_categories = $categories;
            }
            $response = $SMSCategoryAgent->prompt($message);
            $output = json_decode($response->text, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }
        }
        return $output;
    }
}
