<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SMS;
use App\Models\SMSSender;
use App\Jobs\parseSMSJob;

class SMSController extends Controller
{
    public function store(Request $request)
    {
        if(config('parseSMS.enabled') == false){
            return response()->json(['filter' => true, 'error'=>'disabled'], 200);
        }
        /* input for iOS
        {
            "_version": 1,
            "query": {
                "sender": "saib",
                "message": {
                    "text": "PoS Purchase\nBy: ***7632;Credit Card\nAmount: USD 100.00\nAt: ALDREES 239\nBalance: USD 122238785.99\nDate: 2024-01-24 12:40:23"
                }
            },
            "app": {
                "version": "1.1"
            }
        }
        */
        $data = $request->all();

        if(!isset($data['query']['sender']) || !isset($data['query']['message']['text'])){
            return response()->json(['filter' => true, 'error'=>'noData'], 200);
        } 

        $sender = $data['query']['sender'];
        if(!SMSSender::isValidSender($sender)){
            return response()->json(['filter' => true, 'error'=>'invalidSender'], 200);
        }

        $message = SMS::removeHiddenChars($data['query']['message']['text']);

        $stripedMessage = SMS::preClean($message);
        $status = SMS::isValidBankTransaction($stripedMessage);
        if($status == false){
            if(config('parseSMS.store_invalid_sms')){
                $sms = new SMS();
                $sms->sender = $sender;
                $sms->message = $message;
                $sms->content = $data;
                $sms->is_valid = false;
                $sms->is_processed = true;
                $sms->errors = ['reason' => 'invalid SMS'];
                $sms->save();
            }
            return response()->json(['filter' => true, 'error'=>'invalidSMS'], 200);
        }

        $sms = new SMS();
        $sms->sender = strtolower($sender);
        $sms->message = $message;
        $sms->content = $data;
        $sms->is_valid = true;
        $sms->is_processed = false;
        $sms->save();

        try {
            dispatch(new parseSMSJob($sms));
            return response()->json(['filter' => true], 200);
        } catch (\Exception $e) {
            $sms->errors = ['message' => 'processingError'];
            $sms->save();
            return response()->json(['filter' => true, 'error'=>'processingError'], 200);
        }
    }
}
