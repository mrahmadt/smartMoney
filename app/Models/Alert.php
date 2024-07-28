<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

use App\Models\User;
use App\Notifications\WebPush;
use App\Mail\newTransaction;
use App\Mail\info;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Helpers\fireflyIII;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
    ];
    public static function newTransaction($transaction)
    {
        $fireflyIII = new fireflyIII();
        $budget = null;
        if($transaction['budget_id']){
            $budget = $fireflyIII->getBudget($transaction['budget_id']);
        }
        if(isset($budget->data)){
            $budget = $budget->data;
        }

        $notes = json_decode($transaction['notes']);
        // check if json is valid
        if(json_last_error() != JSON_ERROR_NONE){
            return false;
        }

        if(!isset($notes->sendTransactionAlert)){
            return false;
        }

        if($notes && isset($notes->sendTransactionAlert) && $notes->sendTransactionAlert == false){
            return false;
        }

        if(is_numeric($transaction['amount'])){
            $transaction['amount'] = number_format($transaction['amount'], 2);
            $transaction['amount'] = str_replace('.00','',$transaction['amount']);
        }

        if($transaction['type'] == 'withdrawal'){
            $title = __('alert.newTransactionTitle_withdrawal', ['amount' => $transaction['amount'], 'currency_symbol' => $transaction['currency_symbol'], 'destination_name' => $transaction['destination_name']]);
        }elseif($transaction['type'] == 'deposit'){
            $title = __('alert.newTransactionTitle_deposit', ['amount' => $transaction['amount'], 'currency_symbol' => $transaction['currency_symbol'], 'source_name' => $transaction['source_name']]);
        }else{
            $title = __('alert.newTransactionTitle_transfer', ['amount' => $transaction['amount'], 'currency_symbol' => $transaction['currency_symbol'], 'source_name' => $transaction['source_name'], 'destination_name' => $transaction['destination_name']]);
        }


        $message = null;

        if(isset($budget->attributes) && isset($budget->attributes->spent[0])){
            $remaining = number_format($budget->attributes->auto_budget_amount+$budget->attributes->spent[0]->sum,0);
            $message = __('alert.newTransactionMessage_Budget', ['budget' => $budget->attributes->name, 'remaining' => $remaining, 'currency_code' => $budget->attributes->auto_budget_currency_code]);
        }

        if($notes && isset($notes->message)){
            $message = __('alert.newTransactionMessage', ['description' => $notes->message]);
        }else{
            $message = __('alert.newTransactionMessage', ['description' => $transaction['description']]);
        }

        $user_id = null;
        if($notes && isset($notes->user_id)){
            $user_id = $notes->user_id;
        }
        // if($transaction['errors']){
            // $title = 'Error: '. $title;
        // }
        Alert::createAlert($title, $message, 'Transaction', $user_id);
    }

    public static function info($title, $message, $user = null){
        Alert::createAlert($title, $message, 'Info', $user);
    }

    public static function abnormalTransaction($item, $transaction, $percentage,$averageTransaction, $user = null){
        $itemName = null;
        if($item == 'source' || $item == 'destination'){
            $itemName = __('alert.abnormalTransactionAccount', ['account' => $transaction[$item.'_name']]);
        }elseif($item == 'all'){
            $itemName = __('alert.abnormalTransactionAllTransactions');
        }elseif($item == 'category'){
            $itemName = __('alert.abnormalTransactionCategory', ['category' => $transaction[$item]]);
        }

        $message = __('alert.abnormalTransactionMessage', ['description' => $transaction['description'], 'amount' => $transaction['amount'], 'currency_symbol' => $transaction['currency_symbol'], 'percentage' => $percentage, 'average_amount' => $averageTransaction->average_amount, 'itemName' => $itemName, 'type' => $transaction['type']]);
        Alert::createAlert(__('alert.abnormalTransactionTitle'), $message, 'Info', $user);
    }

    public static function billOverMaxAmount($bill, $transaction, $billPercentage, $user = null){
        $message = __('alert.billOverMaxAmountMessage', ['bill' => $bill->attributes->name, 'maxAmount' => $bill->attributes->amount_max, 'amount' => $transaction->amount, 'currency_symbol' => $transaction->currency_symbol, 'billPercentage' => $billPercentage]);
        Alert::createAlert(__('alert.billOverMaxAmountTitle'), $message, 'Info', $user);
    }

    public static function createAlert($title, $message, $type = 'info', $user = null, $error = [])
    {
        $user_id = null;
        if($user != null){
            if(is_numeric($user)){
                $user = User::find($user);
                $user_id = $user->id;
            }
            if($user){
                $user->notify(new WebPush($title, $message));
                if($user->alertViaEmail && $type == 'Transaction' ){
                    Mail::to($user)->send(new newTransaction($title, $message, $error));
                }elseif($user->alertViaEmail && $type == 'Info'){
                    Mail::to($user)->send(new info($title, $message));
                }
            }
        }
        if( $user_id == null ) return false;
        $alert = new Alert();
        $alert->title = $title;
        $alert->user_id = $user_id;
        $alert->message = $message;
        $alert->type = $type;
        $alert->save();

    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
