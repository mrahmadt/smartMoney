<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

use App\Models\User;
use App\Notifications\WebPush;
use App\Mail\newTransaction;
use App\Mail\info;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
    ];

    public static function newTransaction($options)
    {
        $defaultOptions = [
            'type' => null,
            'date' => null,
            'amount' => 0,
            'currency' => null,
            'message' => null,
            'source_name' => null,
            'destination_name' => null,
            'errors' => [],
            'user' => null,
        ];
        $options = array_merge($defaultOptions, $options);
        
        if(is_numeric($options['amount'])){
            $options['amount'] = number_format($options['amount'], 2);
            $options['amount'] = str_replace('.00','',$options['amount']);
        }


        $title = $options['amount'].$options['currency'];

        if($options['type'] == 'withdrawal'){
            $title .= ' To '. $options['destination_name'];
        }elseif($options['type'] == 'deposit'){
            $title .= ' From: '. $options['source_name'];
        }else{
            $title .= ' ' . $options['source_name'].' to '.$options['destination_name'];
        }
        if($options['errors']){
            $title = 'Error: '. $title;
        }

        $message = $options['message'];

        Alert::createAlert($title, $message, 'Transaction', $options['user'], $options['errors']);
    }

    public static function info($title, $message, $user = null){
        Alert::createAlert($title, $message, 'Info', $user);
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
}
