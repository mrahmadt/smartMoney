<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class fireflyIII
{

    private $fireflyIII_URL = null;
    private $fireflyIII_API_TOKEN = null;
    private static $categories = [];

    function __construct()
    {
        $this->fireflyIII_URL = config('app.FireFlyIII.url');
        $this->fireflyIII_API_TOKEN = config('app.FireFlyIII.token');
    }
    public function createBill($options){
        $defaultOptions = [
            // 'name' => 'Rent',
            // 'amount_min' => '123.45',
            // 'amount_max' => '123.45',
            // 'repeat_freq' => "monthly",
            'date' => date('c'),
            'active' => true,
        ];
        $options = array_merge($defaultOptions, $options);
        $bill = $this->callAPI(apiName:'bills', parms:$options, method: 'POST');
        if(isset($bill->exception) || isset($bill->errors)) return false;
        return $bill;
    }
    public function getBills($page = 1, $limit = 50){
        $options = [
            'limit' => $limit,
            'page' => $page,
        ];
        $bills = $this->callAPI(apiName:'bills', parms:$options);
        if(isset($bills->exception)) return false;
        return $bills;
    }
    public function findBill($name = null){
        $page = 1;
        while(true){
            $bills = $this->getBills($page, 50);
            if(!$bills) return false;
            foreach($bills->data as $bill){
                if($bill->attributes->name == $name) return $bill;
            }
            if($bills->meta->pagination->current_page == $bills->meta->pagination->total_pages) break;
            $page++;
        }
        return false;
    }

    public function createRole($options){
        $defaultOptions = [
            // 'name' => 'Rent',
            // 'amount_min' => '123.45',
            // 'amount_max' => '123.45',
            // 'repeat_freq' => "monthly",
            'strict' => true,
            'active' => true,
        ];
        $options = array_merge($defaultOptions, $options);
        $role = $this->callAPI(apiName:'rules', parms:$options, method: 'POST');
        // dd($options, $role);
        if(isset($role->exception) || isset($role->errors)) return false;
        return $role;
    }

    public function getRoles($page = 1, $limit = 50){
        $options = [
            'limit' => $limit,
            'page' => $page,
        ];
        $rules = $this->callAPI(apiName:'rules', parms:$options);
        if(isset($rules->exception)) return false;
        return $rules;
    }

    public function findRole($name = null){
        $page = 1;
        while(true){
            $rules = $this->getRoles($page, 50);
            if(!$rules) return false;
            foreach($rules->data as $rule){
                if($rule->attributes->title == $name) return $rule;
            }
            if($rules->meta->pagination->current_page == $rules->meta->pagination->total_pages) break;
            $page++;
        }
        return false;
    }

    public function triggerRole($options){
        $defaultOptions = [
            // 'id' => 'Rent',
            // 'start' => '123.45',
            // 'end' => '123.45',
            // 'accounts' => [],
        ];
        $options = array_merge($defaultOptions, $options);
        $triggerRole = $this->callAPI(apiName:'rules/' . $options['id'] . '/trigger', parms:$options, method: 'POST');
        if(isset($triggerRole->exception)) return false;
        return true;
    }
    public function createRuleGroup($options){
        $defaultOptions = [];
        $options = array_merge($defaultOptions, $options);
        $ruleGroup = $this->callAPI(apiName:'rule-groups', parms:$options, method: 'POST');
        if(isset($ruleGroup->exception) || isset($ruleGroup->errors)) return false;
        return $ruleGroup;
    }


    public function getCategories($limit = 50, $page = 1)
    {
        if (count(self::$categories) > 0) return self::$categories;

        $categories = $this->callAPI('categories', ['limit' => $limit, 'page' => $page]);
        if (isset($categories->exception) || isset($categories->errors) || isset($categories->message)) return [];
        if (!isset($categories->data)) return [];

        self::$categories = [];
        foreach ($categories->data as $category) {
            if (trim($category->attributes->name) == '') continue;
            self::$categories[] = $category->attributes->name;
        }
        return self::$categories;
    }
        public function lookupCategory($shop, $exactMatch = false){
        $query = 'has_any_category:true';
        if($exactMatch){
            $query .= ' account_is:"'.$shop.'"';
        }else{
            $query .= ' account_starts:"'.$shop.'"';
        }
        $category = $this->searchTransactions($query, 1);
        if($category == false) return false;

        if(!isset($category->data[0]->attributes->transactions[0]->category_name)) return false;
        return $category->data[0]->attributes->transactions[0]->category_name;
    }


    public function newCurrency($code, $name, $symbol, $enabled = true, $default = false, $decimal_places = 2)
    {
        $params = ['code' => $code, 'name' => $name, 'symbol' => $symbol, 'enabled' => $enabled, 'default' => $default, 'decimal_places' => $decimal_places];
        $currency = $this->callAPI(apiName: 'currencies', parms: $params, method: 'POST');
        if (isset($currency->exception) || isset($currency->errors)) return false;
        return $currency;
    }

    public function updateCurrency($code, $enabled = true, $default = null)
    {
        $params = ['enabled' => $enabled];
        if ($default != null) $params['default'] = $default;
        $currency = $this->callAPI(apiName: 'currencies/' . $code, parms: $params, method: 'PUT');
        if (isset($currency->exception) || isset($currency->errors) || isset($currency->message)) return false;
        return $currency;
    }

    public function getCurrency($code)
    {
        $currency = $this->callAPI('currencies/' . $code);
        if (isset($currency->exception) || isset($currency->errors)) return false;
        return $currency;
    }
    public function getExchangeRate($from, $to, $limit = 1)
    {
        $exchangeRates = $this->callAPI('exchange-rates/' . $from . '/' . $to, ['limit' => $limit]);
        if (isset($exchangeRates->exception) || isset($exchangeRates->errors)) return false;
        return $exchangeRates;
    }

    public function getBudget($budget_id = null, $start = null, $end = null)
    {
        if ($start == null) $start = date('Y-m-01');
        if ($end == null) $end = date('Y-m-t');
        $budget = $this->callAPI('budgets/' . $budget_id, ['start' => $start, 'end' => $end]);
        if (isset($budget->exception) || isset($budget->errors)) return false;
        return $budget;
    }
    public function getBudgets($limit = 50, $page = 1, $start = null, $end = null)
    {
        if ($start == null) $start = date('Y-m-01');
        if ($end == null) $end = date('Y-m-t');
        $budgets = $this->callAPI('budgets', ['limit' => $limit, 'page' => $page, 'start' => $start, 'end' => $end]);
        if (isset($budgets->exception) || isset($budgets->errors)) return false;
        return $budgets;
    }

        public function newTransaction($data){
        $transaction = [
            // 'type' => null, // withdrawal, deposit, transfer
            // 'date' => date('c'),
            // 'description' => null,
            // 'amount' => null,
            // 'currency_code' => null,
            // 'source_id' => null,
            // 'source_name' => null,
            // 'destination_id' => null,
            // 'destination_name' => null,
            // 'category_name' => null,
            // 'tags' => null,
            // 'notes' => null,
            // 'internal_reference' => null,
        ];
        $transaction = array_merge($transaction, $data);

        // $transaction['notes']['account_id'] = $accountSource['account']->id;
        // $transaction['notes']['user_id'] = $accountSource['account']->user_id;

        if(isset($transaction['notes']) && is_array($transaction['notes'])){
            $notes = json_encode($transaction['notes']);
            // check if json_encode failed
            if($notes == false) {
                $notes = implode("\n", $transaction['notes']);
            }else{
                $transaction['notes'] = $notes;
            }
        }

        $params = [
            'error_if_duplicate_hash' => true,
            'transactions' => [$transaction],
        ];

        $result = $this->callAPI(apiName:'transactions', parms:$params, method: 'POST');
        // if(isset($result->exception) || isset($result->errors) || isset($result->message)) return false;
        return $result;
    }

    
    public function searchTransactions($query, $limit = 10){
        $transactions = $this->callAPI('search/transactions', ['limit' => $limit, 'query'=>$query]);
        if(isset($transactions->exception) || isset($transactions->errors)) return false;
        return $transactions;
    }

    public function getTransactions($start = null, $end = null, $filter = [], $limit = 1000, $page = 1, $type = null)
    {
        // dd($start, $end, $filter, $limit, $page, $type);
        if ($start == null) $start = date('Y-m-01');
        if ($end == null) $end = date('Y-m-t');
        $transactions = $this->callAPI('transactions/', ['start' => $start, 'end' => $end, 'limit' => $limit, 'page' => $page, 'type' => $type]);

        if (isset($transactions->exception) && !isset($transactions->data[0])) return false;
        // dd($transactions->data);
        if ($filter == []) return $transactions;

        $filteredTransactions = [];
        foreach ($transactions->data as $transaction) {
            $include = true;
            foreach ($filter as $key => $value) {
                if ((!is_array($value) && $transaction->attributes->transactions[0]->$key != $value) || (is_array($value) && !in_array($transaction->attributes->transactions[0]->$key, $value))) {
                    $include = false;
                    break;
                }
            }
            if ($include) $filteredTransactions[] = $transaction->attributes->transactions[0];
        }
        return $filteredTransactions;
    }
    public function getTransaction($transaction_id)
    {
        $transaction = $this->callAPI('transactions/' . $transaction_id);
        if (isset($transaction->exception)) return false;
        if (!isset($transaction->data->attributes->transactions[0])) return false;
        return $transaction->data->attributes->transactions[0];
    }
    public function moveTransactions($where_account_id, $to_account_id)
    {
        $options = [
            'query' => [
                'where' => ['account_id' => $where_account_id],
                'update' => ['account_id' => $to_account_id],
            ]
        ];
        $this->callAPI('data/bulk/transactions', $options, 'POST');
        return true;
    }
    public function updateTransaction($id, $data)
    {
        $options = ['transactions' => [$data]];
        $this->callAPI('transactions/' . $id, $options, 'PUT');
        return true;
    }

    public function deleteAccount($account_id)
    {
        $account = $this->callAPI('accounts/' . $account_id, [], 'DELETE');
        return $account;
    }

    public function createAccount($name, $type = 'asset', $SMS_AcctCode = null, $SMS_Sender = null, $SMS_Options = [], $account_options = [])
    {
        $default_options = [
            'name' => $name,
            'type' => $type, // asset, expense, import, revenue, cash, liability, liabilities, initial-balance, reconciliation
            'notes' => null,
        ];
        $options = array_merge($default_options, $account_options);

        $notes = [];

        if ($SMS_Sender !== null) {
            $notes[] = 'SMS_Sender=' . $SMS_Sender;
        }
        if ($SMS_AcctCode !== null) {
            $notes[] = 'SMS_AcctCodes=' . $SMS_AcctCode;
        }

        if (isset($SMS_Options) && is_array($SMS_Options) && count($SMS_Options) > 0) {
            $notes[] = 'SMS_Options=' . json_encode($SMS_Options);
        }

        if (count($notes) > 0) {
            $options['notes'] = implode("\n", $notes);
        }

        $account = $this->callAPI('accounts', $options, 'POST');
        return $account;
    }

    public function getAccount($account_id = null)
    {
        $account = $this->callAPI('accounts/' . $account_id);
        if (isset($account->exception) || isset($account->errors)) return false;
        return $account;
    }

    public function getAccounts($type = 'asset')
    {
        $account = $this->callAPI('accounts', ['type' => $type]);
        if (isset($account->exception) || isset($account->errors)) return false;
        return $account;
    }

    public function getAccountByName($fieldValue = null, $accountType = 'asset')
    {
        return $this->getAccountBy('name', $accountType, $fieldValue);
    }

    public function getAccountBy($field = 'name', $accountType = 'asset', $fieldValue = null)
    {
        $accounts = $this->callAPI('search/accounts', ['limit' => 50, 'query' => $fieldValue, 'field' => $field, 'type' => $accountType, 'page' => 1]);
        if ($accounts == false) return false;
        if (isset($accounts->data[0])) return $accounts->data[0];
        return false;
    }

    public function getAccountBySMSAcctCode($sender, $accountCode, $accountType = 'asset', $failIfAccountCodeNotFound = false)
    {
        $total_pages = 1;
        $sender = strtolower($sender);
        $defaultAccount = false;

        for ($i = 1; $i <= $total_pages; $i++) {
            $accounts = $this->callAPI('accounts', ['limit' => 50, 'type' => $accountType, 'page' => $i]);
            if ($accounts == false) return false;
            if ($accounts->meta->pagination->current_page == 1) {
                $total_pages = $accounts->meta->pagination->total_pages;
            }
            if (!isset($accounts->data)) return false;
            foreach ($accounts->data as $account) {
                $SMSconfigs = $this->getAccountConfig($account->attributes);
                if ($SMSconfigs == false || ($SMSconfigs['sender'] === null && empty($SMSconfigs['acctCodes']))) continue;
                if ($SMSconfigs['sender'] !== null && strtolower($SMSconfigs['sender']) == $sender && in_array($accountCode, $SMSconfigs['acctCodes'])) {
                    if (isset($SMSconfigs['options'])) {
                        $account->_transactionOptions = $SMSconfigs['options'];
                    }
                    $account->_failback = false;
                    return $account;
                } elseif ($SMSconfigs['sender'] !== null && strtolower($SMSconfigs['sender']) == $sender && $failIfAccountCodeNotFound && $defaultAccount == false) {
                    $defaultAccount = $account;
                }
            }
        }
        
        if ($defaultAccount != false) {
            $defaultAccount->_failback = true;
        }
        return $defaultAccount;
    }

    public function getAccountConfig($accountAttributes)
    {
        $account_notes = trim($accountAttributes->notes);
        $account_number = trim($accountAttributes->account_number);
        if (($account_notes == '') && ($account_number == '')) return false;


        $SMSconfigs = [
            'user_id' => null, // user_id
            'sender' => null, // SMS_Sender
            'acctCodes' => [], // SMS_AcctCodes
            'options' => [] // SMS_Options
        ];

        $output = explode(",", $account_number);
        if (count($output) > 1) {
            $SMSconfigs['acctCodes'] = array_map('trim', $output);
        }

        $lines = explode("\n", $account_notes);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'SMS_Sender') === 0) {
                $parts = explode('=', $line, 2);
                if (count($parts) == 2) {
                    $SMSconfigs['sender'] = trim($parts[1]);
                }
            }elseif (strpos($line, 'user_id') === 0) {
                $parts = explode('=', $line, 2);
                if (count($parts) == 2) {
                    $SMSconfigs['user_id'] = trim($parts[1]);
                }
            } elseif (strpos($line, 'SMS_AcctCodes') === 0) {
                $parts = explode('=', $line, 2);
                if (count($parts) == 2) {
                    $codes = trim($parts[1]);
                    $output = explode(",", $codes);
                    if (count($output) > 1) {
                        $SMSconfigs['acctCodes'] = array_map('trim', $output);
                    }
                }
            } elseif (strpos($line, 'SMS_Options') === 0) {
                $parts = explode('=', $line, 2);
                if (count($parts) == 2) {
                    $options = trim($parts[1]);
                    if (substr($options, 0, 1) == '{' && substr($options, -1) == '}') {
                        $SMSconfigs['options'] = json_decode($options, true);
                    }
                }
            }
        }
        return $SMSconfigs;
    }

    public function getAbout()
    {
        return $this->callAPI('about');
    }
    private function callAPI($apiName, $parms = [], $method = 'GET')
    {
        $curl = curl_init();

        if ($method == 'GET') {
            $query = http_build_query($parms);
            curl_setopt($curl, CURLOPT_URL, $this->fireflyIII_URL . $apiName . '?' . $query);
            curl_setopt($curl, CURLOPT_POST, false);
        } else {
            curl_setopt($curl, CURLOPT_URL, $this->fireflyIII_URL . $apiName);
            if ($method !== 'DELETE') {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parms));
            }
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->fireflyIII_API_TOKEN,
            'Accept: application/json',
            'Content-Type: application/json',
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_VERBOSE, false);
        $response = curl_exec($curl);
        if ($response === false) {
            Log::error('fireflyIII error: ' . curl_error($curl));
            return false;
        }
        // curl_close($curl);
        return json_decode($response);
    }
}
