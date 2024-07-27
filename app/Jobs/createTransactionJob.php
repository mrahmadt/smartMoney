<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Helpers\fireflyIII;

use App\Models\SMS;
use App\Models\Alert;
use App\Models\Account;

class createTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sms;
    protected $data;
    protected $fireflyIII;
    protected $options;
    /**
     * Create a new job instance.
     */
    public function __construct(SMS $sms, $data, $options = [])
    {
        $this->options = array_merge([
            'dryRun' => false,
        ], $options);

        $this->sms = $sms->withoutRelations();
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->fireflyIII = new fireflyIII();
        $error = [
            'messages' => [],
        ];
        $transaction = [
            'type' => $this->data['transactionType'],
            'date' => $this->data['date'],
            'description' => $this->data['description'],
            'amount' => $this->data['amount'],
            'notes' => ['message'=>$this->sms['message'], 'sms_id'=>$this->sms['id']],
            'internal_reference' => $this->data['internal_reference'],
            'tags' => [],
        ];

        if(isset($this->data['fees']) && is_numeric($this->data['fees'])){
            $transaction['amount'] = $this->data['amount'] + $this->data['fees'];
        }

        if(isset($this->data['currency'])){

            $currency = $this->fireflyIII->getCurrency($this->data['currency']);
            if($currency == false){
                $currency = $this->fireflyIII->newCurrency($this->data['currency'],$this->data['currency'],$this->data['currency']);
            }

            if(isset($currency->data->attributes->enabled) && $currency->data->attributes->enabled == 0){
                $this->fireflyIII->updateCurrency($currency->data->attributes->code, 1);
            }

            if(!isset($currency->data->attributes->code)){
                $error['messages'][] = 'Currency code not found';
            }else{
                $transaction['currency_code'] = $currency->data->attributes->code;
            }
            if(isset($currency->data->attributes->default) && $currency->data->attributes->default == false){
                $transaction['foreign_currency_code'] = $currency->data->attributes->code;
                $transaction['foreign_amount'] = $this->data['currency'];
            }

        }

        if(isset($this->data['tags'])){
            if(is_array($this->data['tags'])){
                $transaction['tags'] = $this->data['tags'];
            }else{
                $transaction['tags'] = implode(',', $this->data['tags']);
            }
        }

        if(isset($this->data['tag'])){
            $transaction['tag'][] = $this->data['tag'];
        }

        if(isset($this->data['category'])){
            $transaction['category_name'] = $this->data['category'];
        }



        // $alertNewTransaction = [
        //     'type' => $this->data['transactionType'],
        //     'date' => $this->data['date'],
        //     'amount' => $this->data['amount'],
        //     'currency' => $this->data['currency'] ?? null,
        //     'message' => $this->sms['sender']."\n".$this->sms['message'],
        //     'source_name'=> $this->data['source'],
        //     'destination_name'=> $this->data['destination'],
        //     'user' => null,
        //     'errors' => [],
        // ];
        
        if($this->data['transactionType'] == 'withdrawal'){
            // lookup account by account code from SMS
            $accountSource = Account::lookupAccountByCode($this->sms['sender'], $this->data['source']);

            if($accountSource) {
                $transaction['source_id'] = $accountSource['FF_account']->id;
                $transaction = $this->transactionOptions($transaction, $accountSource['account']);
                $transaction['notes']['account_id'] = $accountSource['account']->id;
                $transaction['notes']['user_id'] = $accountSource['account']->user_id;
                $transaction['notes']['sendTransactionAlert'] = $accountSource['account']->sendTransactionAlert;
            }else{
                $defaultAccount = Account::where('sms_sender', $this->sms['sender'])->where('defaultAccount', true)->first();
                if($defaultAccount){
                    $transaction['source_id'] = $defaultAccount->FF_account_id;
                    $transaction['notes']['account_id'] = $defaultAccount->id;
                    $transaction['notes']['user_id'] = $defaultAccount->user_id;
                    $transaction['notes']['sendTransactionAlert'] = $defaultAccount->sendTransactionAlert;
                    $transaction = $this->transactionOptions($transaction, $defaultAccount);
                }else{
                    $error['messages'][] = 'Source account not found and no default account set';
                }
            }

            if(!$error['messages']){
                $accountDestination = $this->fireflyIII->getAccountByName($this->data['destination'], 'expense');
                if(isset($accountDestination->id) && $accountDestination->id != $transaction['source_id']) {
                    $transaction['destination_id'] = $accountDestination->id;
                    $newAccountName = Account::isReplaceWithFFAccount($accountDestination);
                    if($newAccountName){
                        $transaction['destination_name'] = $newAccountName;
                        $transaction['destination_id'] = null;
                    }
                }else{
                    $transaction['destination_name'] = $this->data['destination'];
                }
            }
            

        }elseif($this->data['transactionType'] == 'deposit'){
            $accountDestination = Account::lookupAccountByCode($this->sms['sender'], $this->data['destination']);
            if($accountDestination) {
                $transaction['destination_id'] = $accountDestination['FF_account']->id;
                $transaction = $this->transactionOptions($transaction, $accountDestination['account']);
                $transaction['notes']['account_id'] = $accountDestination['account']->id;
                $transaction['notes']['user_id'] = $accountDestination['account']->user_id;
                $transaction['notes']['sendTransactionAlert'] = $accountDestination['account']->sendTransactionAlert;

            }else{
                $defaultAccount = Account::where('sms_sender', $this->sms['sender'])->where('defaultAccount', true)->first();
                if($defaultAccount){
                    $transaction['destination_id'] = $defaultAccount->FF_account_id;
                    $transaction['notes']['account_id'] = $defaultAccount->id;
                    $transaction['notes']['user_id'] = $defaultAccount->user_id;
                    $transaction['notes']['sendTransactionAlert'] = $defaultAccount->sendTransactionAlert;
    
                    $transaction = $this->transactionOptions($transaction, $defaultAccount);
                }else{
                    $error['messages'][] = 'destination account not found and no default account set';
                }
            }
            if(!$error['messages']){
                $accountSource = $this->fireflyIII->getAccountByName($this->data['source'], 'revenue');
                if(isset($accountSource->id) && $accountSource->id != $transaction['destination_id']) {
                    $transaction['source_id'] = $accountSource->id;
                    // $alertNewTransaction['source_name'] = $accountSource->attributes->name ?? $this->data['source'];
                    $newAccountName = Account::isReplaceWithFFAccount($accountSource);
                    if($newAccountName){
                        $transaction['source_name'] = $newAccountName;
                        $transaction['source_id'] = null;
                        // $alertNewTransaction['source_name'] = $newAccountName;
                    }
                }else{
                    $transaction['source_name'] = $this->data['source'];
                }
            }


        }elseif($this->data['transactionType'] == 'transfer'){
            $accountSource = Account::lookupAccountByCode($this->sms['sender'], $this->data['source']);
            if($accountSource) {
                $transaction['source_id'] = $accountSource['FF_account']->id;
                $transaction['notes']['account_id'] = $accountSource['account']->id;
                $transaction['notes']['user_id'] = $accountSource['account']->user_id;
                $transaction['notes']['sendTransactionAlert'] = $accountSource['account']->sendTransactionAlert;
                $transaction = $this->transactionOptions($transaction, $accountSource['account']);
            }else{
                $error['messages'][] = 'Source account not found';
            }
            if(!$error['messages']){
                $accountDestination = Account::lookupAccountByCode($this->sms['sender'], $this->data['destination']);
                if($accountDestination) {
                    if($accountDestination['FF_account']->id != $transaction['source_id']){
                    $transaction['destination_id'] = $accountDestination['FF_account']->id;
                    $transaction = $this->transactionOptions($transaction, $accountDestination['account']);
                    // $alertNewTransaction['destination_name'] = $accountDestination['FF_account']->attributes->name ?? $this->data['destination'];
                    }else{
                        $error['messages'][] = 'Source and destination account are the same';
                    }
                }else{
                    $error['messages'][] = 'Destination account not found';
                }
            }
        }



        if(count($error['messages']) == 0){
            if($this->options['dryRun']){
                print "\nDry Run: Create transaction\n";
            }else{
                $transaction['tags'][] = 'SMS';
                $response = $this->fireflyIII->newTransaction($transaction);
                if(isset($response->exception) || isset($response->errors) || isset($response->message) || (isset($response->message) && strpos($response->message, 'Duplicate of transaction') !== 0)) {
                    $error['messages'][] = $response->message ?? 'Transaction not created';
                }
            }
        }
        $this->sms->is_processed = true;

        if(count($error['messages']) == 0){
            if($this->options['dryRun']){

                print "\nDry Run: No error\n";

                print "\nDry Run: Data\n";
                print_r($this->data);

                print "\nDry Run: Transaction\n";
                print_r($transaction);

            }else{
                $this->sms->errors = null;
                $this->sms->save();
                // Alert::newTransaction($alertNewTransaction);
            }
        }else{
            $error['transaction'] = $transaction;
            $error['data'] = $this->data;
            if(isset($accountSource)) $error['accountSource'] = $accountSource;
            if(isset($accountDestination)) $error['accountDestination'] = $accountDestination;
            if($this->options['dryRun']){
                print "\nDry Run: Error\n";
                print_r($error);

                print "\nDry Run: Data\n";
                print_r($this->data);

                print "\nDry Run: Transaction\n";
                print_r($transaction);

            }else{
                $this->sms->errors = $error;
                $this->sms->save();
                // $alertNewTransaction['errors']['messages'] = $error['messages'];
                // $alertNewTransaction['errors']['transaction'] = $transaction;
                // Alert::newTransaction($alertNewTransaction);
            }
        }

    }

    private static function transactionOptions($transaction, $account){
        if($account->budget_id && !isset($transaction['budget_id'])){
            $transaction['budget_id'] = $account->budget_id;
        }
        if($account->tags){
            if(!isset($transaction['tags'])) $transaction['tags'] = [];
            $transaction['tags'] = array_merge($transaction['tags'], $account->tags);
        }
        if($account->values){
            foreach($account->values as $key => $value){
                if(!isset($transaction[$key]))  $transaction[$key] = $value;
            }
        }
        return $transaction;
    }



}
