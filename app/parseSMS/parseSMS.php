<?php

namespace App\parseSMS;

use App\Models\SMS\ValueLookup;
use App\Helpers\fireflyIII;
use App\Helpers\OpenAI;

class parseSMS {
    // Order is important! The first matching will be used
    public static $transactionTypes = [];
    public static $smsKeywords = [];
    public static $failback_openAI = false;
    public static $fireflyIII;
    public static $options;


    public static function execute($sms, $options = []){
        static::$options = array_merge([
            'dryRun' => false,
        ], $options);

        
        static::$failback_openAI = config('parseSMS.failback_openAI');
        static::$fireflyIII = new fireflyIII();
        $data = static::parse($sms->message, $sms->sender);
        if(isset($data['transactionType']) && $data['transactionType'] == 'ignore'){
            return false;
        }
        return $data;
    }

    private static function lookupCategory($data){
        $category = null;
        if($data['transactionType'] == 'withdrawal'){
            $category = static::$fireflyIII->lookupCategory($data['destination']);
        }elseif($data['transactionType'] == 'deposit'){
            $category = static::$fireflyIII->lookupCategory($data['source']);
        }
        return $category;
    }

    

    public static function parse($message, $sender = null)
    {
        $data = [
            'parsed' => false,
            'tags' => [],
            'config' => [],
        ];
        if(static::$options['dryRun']){
            print "Dry Run: Sender: $sender\n";
            print "Dry Run: Message: $message\n";
        }
        foreach (static::$transactionTypes as $transactionType) {
            foreach ($transactionType['keywords'] as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    if(isset($transactionType['values'])){
                        foreach ($transactionType['values'] as $key => $value) {
                            if($key == '' || $value == '') continue;
                            $data[$key] = $value;
                        }
                    }
                    if(isset($transactionType['config'])){
                        foreach ($transactionType['config'] as $key => $value) {
                            $data['config'][$key] = $value;
                        }
                    }

                }
            }
        }
        foreach (static::$smsKeywords as $smsKeyword) {
            // Go over all regex
            foreach ($smsKeyword['regex']as $regex) {
                if (!preg_match($regex, $message, $matches)) continue;

                // Check if we have any named groups, put them in data array
                $found_data = false;
                foreach ($matches as $key => $value) {
                    if (!is_numeric($key) && trim($value) != ''){
                        $data[$key] = trim($value);
                        $found_data = true;
                        // Check if we have a lookup value
                        $lookup = ValueLookup::where('key', $key)->where('value', $value)->first();
                        if($lookup){
                            $data[$key . '_lookup'] = $lookup->replaceWith;
                        }
                    }
                }
                if(!$found_data) continue;

                // set default value if not set
                if(isset($smsKeyword['values'])){
                    foreach ($smsKeyword['values'] as $key => $value) {
                        if(isset($data[$key])) continue;
                        $data[$key] = $value;
                    }
                }

                // Get the config values
                if(isset($smsKeyword['config'])){
                    foreach ($smsKeyword['config'] as $key => $value) {
                        $data['config'][$key] = $value;
                    }
                }

                $data['parsed'] = true;
                break;
            }
        }
        if(isset($data['transactionType'])) $data['transactionType'] = strtolower($data['transactionType']);

        if(isset($data['transactionType']) && $data['transactionType'] == 'ignore'){
            $data['parsed'] = false;
            return $data;
        }

        if(isset($data['amount']) && $data['amount'] != ''){
            $data['amount'] = str_replace(',', '', $data['amount']);
        }
        
        $data['date'] = static::extractDateTime($message,'c');
        
        $data['internal_reference'] = md5($message);

        if(!isset($data['description'])){
            $data['description'] = static::generateDescription($message);
            
        }

        if(!isset($data['destination']) && isset($data['destinationNum'])){
            $data['destination'] = $data['destinationNum'];
        }

        if(!isset($data['source']) && isset($data['sourceNum'])){
            $data['source'] = $data['sourceNum'];
        }

        // swap between source and destination
        if(isset($data['config']['swapSourceDestination']) && $data['config']['swapSourceDestination']){
            $source = $data['source'] ?? null;
            $sourceNum = $data['sourceNum'] ?? null;
            $destination = $data['destination'] ?? null;
            $destinationNum = $data['destinationNum'] ?? null;
            $data['source'] = $destination;
            $data['destination'] = $source;
            $data['sourceNum'] = $destinationNum;
            $data['destinationNum'] = $sourceNum;
        }

        // clean up the account name
        if(isset($data['transactionType'])){
            if($data['transactionType'] == 'withdrawal' && isset($data['destination'])){
                $data['destination'] = static::cleanName($data['destination']);
            }elseif($data['transactionType'] == 'deposit' && isset($data['source'])){
                $data['source'] = static::cleanName($data['source']);
            }
        }

        // replace values
        if(isset($data['config']['replace'])){
            foreach ($data['config']['replace'] as $field => $fieldValues) {
                // if field value is set and field in $fieldValues then replace it value
                if(isset($fieldValues[$data[$field]]) && in_array($fieldValues[$data[$field]], $fieldValues)){
                    $value = $fieldValues[$data[$field]];
                    // if $value is a variable (starts with $) then replace it with the value of the variable
                    if(substr($value, 0, 1) == '$'){
                        $value = substr($value, 1);
                        if(isset($data[$value])){
                            $data[$field] = $data[$value];
                        }
                    }else{
                        $data[$field] = $value;
                    }
                    break;
                }
            }
        }

        // overwrite values
        if(isset($data['config']['overwrite_value'])){
            foreach ($data['config']['overwrite_value'] as $key => $value) {
                // if $value is a variable (starts with $) then replace it with the value of the variable
                if(substr($value, 0, 1) == '$'){
                    $value = substr($value, 1);
                    if(isset($data[$value])){
                        $data[$key] = $data[$value];
                    }
                }else{
                    $data[$key] = $value;
                }
            }
        }

        // remove duplicate spaces
        if(isset($data['config'])) unset($data['config']);

        foreach($data as $key => $value){
            if($data[$key] && !is_array($data[$key])) $data[$key] = trim(preg_replace('/\s{2,}/m', ' ' , $data[$key]));
        }

        // currency must be 3 characters english
        if(isset($data['currency'])){
            if(strlen($data['currency']) != 3|| !ctype_alpha($data['currency'])){
                $data['parsed'] = false;
            }
            // Upper case
            $data['currency'] = strtoupper($data['currency']);
        }

        

        if(isset($data['transactionType']) && $data['transactionType'] == 'ignore'){
            $data['parsed'] = false;
        } else {
            // Check if the message is parsed
            $data['parsed'] = static::validateData($data);



            if($data['parsed'] == false){
                if(static::$options['dryRun']){
                    print "\nDry Run: parse returned false\n";
                }
            }else{
                if(static::$options['dryRun']){
                    print "\nDry Run: parse returned true\n";
                }
            }

            $parsed_via_chatgpt = false;


            if($data['parsed'] == false && static::$failback_openAI == true){
                if(static::$options['dryRun']) print "\nDray Run: askChatGPT\n";
                $chatGPT_data = static::askChatGPT($message);
                if($chatGPT_data && $chatGPT_data['parsed']){
                    if(isset($chatGPT_data['transactionType'])){
                        if($chatGPT_data['transactionType'] == 'withdrawal' && isset($chatGPT_data['destination'])){
                            $chatGPT_data['destination'] = static::cleanName($chatGPT_data['destination']);
                        }elseif($chatGPT_data['transactionType'] == 'deposit' && isset($dachatGPT_datata['source'])){
                            $chatGPT_data['source'] = static::cleanName($chatGPT_data['source']);
                        }
                    }
                    $data['tags'] = array_merge($data['tags'], ['ChatGPT']);
                    // $category = static::lookupCategory($chatGPT_data);
                    
                    // if($category){
                    //     $category_lookup = false;
                    //     $chatGPT_data['category'] = $category;
                    //     $data['tags'] = array_merge($data['tags'], ['Category By FFIII']);
                    // }
                    $parsed_via_chatgpt = true;
                    $data = $chatGPT_data;
                }
            }
            
            if($data['parsed'] == true && ($parsed_via_chatgpt == true || !isset($data['category']))){
                $category = static::lookupCategory($data);
                if($category){
                    $data['category'] = $category;
                    $data['tags'] = array_merge($data['tags'], ['Category By FFIII']);
                }else{
                    if(static::$options['dryRun']) print "Dry Run: askChatGPTCategory\n";
                    $category = static::askChatGPTCategory($message);
                    if($category){
                        $data['category'] = $category;
                        $data['tags'] = array_merge($data['tags'], ['Category By ChatGPT']);
                    }
                }
            }
        }
        return $data;
    }

    public static function askChatGPTCategory($message){
        if(config('parseSMS.detect_category_openai') != true) return null;
$openai_prompt = 'I received below SMS from my bank, its possible it is a bank transaction, I need you to identify the transaction category. 
1. Do your best to provide it and guess it from the "destination" if transaction type is withdrawal or from the source if transaction type is deposit.
2. if the message is not clear, not a bank transaction or cannot be categorized, return {"error":"cannot be categorized"}
3. Categories are: %%categories%%. if the transaction category does not fit in any of them, then suggest another category.
4. Response must always be json schema {"category": "Pharmacy"} or {"error":"cannot be categorized"}
Examples:
SMS:
    Payment through POS 11.20 USD
    Account Number: 0444xxx321111
    From: AlDaysam Pharmacy 7632
Return {"category": "Pharmacy"}

SMS:
    Credit Card XXX3221
    At: SAUDI ELECTRICITY COMPANY
    Amount: 229.67 USD
    Reason: Online Payment
Return {"category": "Utilities"}

ٍSMS:
    Salary
    Amount: USD 1,720
    To: XXX7222
Return {"category": "Salary"}

';
        $categories = static::prepareCategories();
        $prompt = str_replace('%%categories%%', $categories, $openai_prompt);
        $response = new OpenAI();
        $response = $response->askChatGPT($prompt . $message);
        if($response == false || $response == null || isset($response->error)){
            return null;
        }
        return $response->category ?? null;
    }


    private static function prepareCategories(){
        $default_categories = ["Pharmacy", "Grocery", "Restaurant", "Shopping", "Entertainment", "Transportation", "Utilities", "Healthcare", "Education", "Travel"];
        $fireflyIII_categories = static::$fireflyIII->getCategories();
        $categories = array_merge($default_categories, $fireflyIII_categories);
        $categories = array_unique($categories);
        $categories = implode(',', $categories);
        return $categories;
    }

    public static function generateDescription($message){
        $description = static::extractFirstLine($message);
        $description = str_replace([':','-','/',',','\\','?','!'], ' ', $description);
        $description = preg_replace('/\.$/m', '' , $description);
        return $description;
    }

    public static function validateData($data){
        if(
            !isset($data['source']) ||
            !isset($data['destination']) ||
            !isset($data['description']) ||
            !isset($data['amount']) ||
            !isset($data['transactionType']) ||
            !isset($data['date']) ||
            $data['source'] == null || 
            $data['destination'] == null || 
            $data['description'] == null || 
            $data['amount'] == null || 
            $data['transactionType'] == null ||
            $data['date'] == null ||
            is_numeric($data['amount']) == false

        ){
            return false;
        }
        return true;
    }

    public static function askChatGPT($message){
$openai_prompt = 'I received below SMS from my bank, its possible the message about a bank transaction, I need you to identify the transaction details.
Below SMS is an example:
Payment through POS 113.20 SAR
Account Number: 0114xxx324121
Mada: Card Number xxx822
From: AlDaysam Pharmacy 7632
Fee: 5.00 SAR
At 22:40 on 2017/12/06

You should return a JSON object like this:
{
    "transactionType": "withdrawal",
    "amount": 113.20,
    "currency": "SAR",
    "source": "0148xxx957001",
    "cardNum": "xxx812",
    "destination": "AlDaysam Pharmacy 7632",
    "fees": 5.00,
    "feesCurrency": "SAR",
    "category": "Pharmacy"
}

1. Always return JSON, and must include "transactionType", "amount", "currency", "source", and "destination" keys.
2. The possible values for the "transactionType" are: "withdrawal", "deposit"
3. The "amount" is always a float and currency is a string 3 characters long.
4. The "source" is a string (Only return the sender name, account number or card number, not the bank name).
5. the "destination" is a string (Only return the receiver name, account number or card number, not the bank name).
6. "category" is an optional, do your best to provide it and guess it from the "destination" if type is withdrawal or from the source if type is deposit.
7. if the message is not clear, not a bank transaction or can not be parsed, return {"error":"Cannot parse the transaction"}
8. Categories are: %%categories%%. if the transaction category does not fit in any of them, then suggest another category.
9. The "fees" and feesCurrency are optional, if not available, then do not return it.
10. if "source" or "destination" not available, return "Unknown" instead.


';
                $categories = static::prepareCategories();
                $prompt = str_replace('%%categories%%', $categories, $openai_prompt);
                $response = new OpenAI();
                $response = $response->askChatGPT($prompt . $message);
                if($response == false || $response == null || isset($response->error)){
                    return false;
                }
                $data = [
                    'parsed' => true,
                    'transactionType' => $response->transactionType ?? null,
                    'source' => $response->source ?? null,
                    'destination' => $response->destination ?? null,
                    'amount' => $response->amount ?? null,
                    'currency' => $response->currency ?? null,
                    'description' => static::generateDescription($message),
                    'fees' => $response->fees ?? null,
                    'currencyFees' => $response->currencyFees ?? null,
                    'category' => $response->category ?? null,
                    'date' => date('c'),
                    'tags' => ['ChatGPT'],
                    'internal_reference' => md5($message),
                ];
                $data['parsed'] = static::validateData($data);
                return $data;
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

    private static function extractFirstLine($text) {
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
    public static function extractDateTime($message, $returnFormat = 'c'){
        $date_patterns = [
            // '/\b\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d{3,8}Z\b/' => 'Y-m-d\TH:i:s.u\Z', // format DATE ISO 8601
            '/\b\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'Y-m-d',
            '/\b\d{4}-(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])\b/' => 'Y-d-m',
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-\d{4}\b/' => 'd-m-Y',
            '/\b(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])-\d{4}\b/' => 'm-d-Y',
    
            '/\b(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'm-d',
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])\b/' => 'd-m',
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])\b/' => 'd-m',
            '/\b(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'm-d',
    
    
            '/\b\d{4}\/(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\b/' => 'Y/d/m',
            '/\b\d{4}\/(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'Y/m/d',
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/\d{4}\b/' => 'd/m/Y',
            '/\b(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/\d{4}\b/' => 'm/d/Y',
    
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\b/' => 'd/m',
            '/\b(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'm/d',
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\b/' => 'd/m',
            '/\b(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'm/d',
    
    
            '/\b\d{4}\.(0[1-9]|1[0-2])\.(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'Y.m.d',
            '/\b\d{4}\.(0[1-9]|[1-2][0-9]|3[0-1])\.(0[1-9]|1[0-2])\b/' => 'Y.d.m',
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])\.(0[1-9]|1[0-2])\.\d{4}\b/' => 'd.m.Y',
            '/\b(0[1-9]|1[0-2])\.(0[1-9]|[1-2][0-9]|3[0-1])\.\d{4}\b/' => 'm.d.Y',
    
            '/\b(0[1-9]|1[0-2])\.(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'm.d',
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])\.(0[1-9]|1[0-2])\b/' => 'd.m',
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])\.(0[1-9]|1[0-2])\b/' => 'd.m',
            '/\b(0[1-9]|1[0-2])\.(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'm.d',
    
        ];
    
        $time_patterns = [
            // for 24-hour | hours seconds
            // '/\b(?:2[0-3]|[01][0-9]):[0-5][0-9](:[0-5][0-9])\.\d{3,6}\b/' => 'H:i:s.u',
            '/\b(?:2[0-3]|[01][0-9]):[0-5][0-9](:[0-5][0-9])\b/' => 'H:i:s',
            '/\b(?:2[0-3]|[01][0-9]):[0-5][0-9]\b/' => 'H:i',
    
            // for 12-hour | hours seconds
            // '/\b(?:1[012]|0[0-9]):[0-5][0-9](:[0-5][0-9])\.\d{3,6}\b/' => 'h:i:s.u',
            '/\b(?:1[012]|0[0-9]):[0-5][0-9](:[0-5][0-9])\b/' => 'h:i:s',
            '/\b(?:1[012]|0[0-9]):[0-5][0-9]\b/' => 'h:i',
        ];
    
        $dateTimeStr = null;
        $dateTimeFormat = null;
    
        foreach($date_patterns as $date_pattern => $format){
            if(preg_match($date_pattern, $message, $matches)){
                $dateTimeFormat = $format;
                $dateTimeStr = $matches[0];
                break;
            }
        }
        if($dateTimeFormat) $dateTimeFormat .= ' ';
        if($dateTimeStr) $dateTimeStr .= ' ';
        foreach($time_patterns as $time_pattern => $format){
            if(preg_match($time_pattern, $message, $matches)){
                $dateTimeFormat .= $format;
                $dateTimeStr .= $matches[0];
                break;
            }
        }
        if($dateTimeStr == null || $dateTimeFormat == null){
            $d = new \DateTime();
            return $d->format( $returnFormat );
        }
        $d = \DateTime::createFromFormat( $dateTimeFormat, $dateTimeStr );
        return $d->format( $returnFormat );
    }


}