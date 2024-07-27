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
            $notes = null;
        }
        if($notes && isset($notes->sendTransactionAlert) && $notes->sendTransactionAlert == false){
            return false;
        }

        if(is_numeric($transaction['amount'])){
            $transaction['amount'] = number_format($transaction['amount'], 2);
            $transaction['amount'] = str_replace('.00','',$transaction['amount']);
        }

        if($transaction['type'] == 'withdrawal'){
            $title = '-' . $transaction['amount'] . ' ' . $transaction['currency_symbol'] . ' ' . $transaction['destination_name'];
        }elseif($transaction['type'] == 'deposit'){
            $title = '+' . $transaction['amount'] . ' ' . $transaction['currency_symbol'] . ' ' . $transaction['source_name'];
        }else{
            $title = $transaction['amount'] . ' ' . $transaction['currency_symbol'] . ' ' . $transaction['source_name'] .' to '.$transaction['destination_name'];
        }

        $message = null;

        if(isset($budget->attributes) && isset($budget->attributes->spent[0])){
            $remaining = number_format($budget->attributes->auto_budget_amount+$budget->attributes->spent[0]->sum,0);
            $message = 'Budget: ' . $budget->name . ' Remaining: ' . $remaining . ' ' . $budget->auto_budget_currency_code;
        }
        if($message) $message .= "\n\n";

        if($notes && isset($notes->message)){
            $message = $notes->message;
        }else{
            $message = $transaction['description'];
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
            $itemName = 'account "' . $transaction[$item.'_name'] . '"';
        }elseif($item == 'all'){
            $itemName = 'all transactions';
        }elseif($item == 'category'){
            $itemName = 'category "'  . $transaction[$item] . '"';
        }

        $message = 'Transaction: "'.$transaction['description'].'" has an abnormal amount of '.$transaction['amount'].' '.$transaction['currency_symbol'].' ('.$percentage.'%) compared to the average amount of ' . $averageTransaction->average_amount . ' for ' . $itemName . ' ('.$transaction['type'].')';
        Alert::createAlert('Abnormal Transaction', $message, 'Info', $user);
    }

    public static function billOverMaxAmount($bill, $transaction, $billPercentage, $user = null){
        $message = 'Bill: '.$bill->attributes->name.' has a max amount of '.$bill->attributes->amount_max.' '.$transaction->currency_symbol.' and you have spent '.$transaction->amount.' '.$transaction->currency_symbol.' ('.$billPercentage.'%)';
        Alert::createAlert('Bill Over Max Amount', $message, 'Info', $user);
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
