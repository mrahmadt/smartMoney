<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SMS;
use App\Models\SMSSender;
use App\Models\Setting;
use App\Jobs\parseSMSJob;
use App\Models\User;
use App\Notifications\WebPush;

class SMSController extends Controller
{
    /**
     * Store a new SMS and dispatch it for processing.
     *
     * Expected JSON format:
     * {
     *     "_version": 1,
     *     "query": {
     *         "sender": "BankName",
     *         "date": "2024-01-24 12:40:23",  // optional, SMS received date (highest priority for transaction date)
     *         "message": {
     *             "text": "SMS body text"
     *         }
     *     },
     *     "app": {
     *         "version": "1.1"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $apiKey = config('app.sms_api_key');
        if ($apiKey !== null && $apiKey !== '') {
            if ($request->query('key') !== $apiKey) {
                return response()->json([], 401);
            }
        }

        if (Setting::getBool('parsesms_enabled', false) == false) {
            return response()->json(['filter' => true, 'error' => 'disabled'], 200);
        }

        $data = $request->all();

        if (!isset($data['query']['sender']) || !isset($data['query']['message']['text'])) {
            return response()->json(['filter' => true, 'error' => 'noData'], 200);
        }

        $sender = $data['query']['sender'];
        if (!SMSSender::isValidSender($sender)) {
            \Log::debug('SMSController: invalid sender', ['sender' => $sender]);
            $user = User::find(1);
            $user->notify(new WebPush(
                title: 'invalid sender',
                body: 'invalid sender (' . $sender . ')',
                url: '/alerts'
            ));

            return response()->json(['filter' => true, 'error' => 'invalidSender'], 200);
        }

        $message = SMS::removeHiddenChars($data['query']['message']['text']);

        if (SMS::isDuplicate($sender, $message)) {
            return response()->json(['filter' => true, 'error' => 'duplicate'], 200);
        }

        $stripedMessage = SMS::preClean($message);
        $status = SMS::isValidBankTransaction($stripedMessage);
        if ($status == false) {
            if (Setting::getBool('parsesms_store_invalid_sms', false)) {
                $sms = new SMS();
                $sms->sender = $sender;
                $sms->message = $message;
                $sms->content = $data;
                $sms->is_valid = false;
                $sms->is_processed = true;
                $sms->message_hash = SMS::generateHash($sender, $message);
                $sms->errors = ['reason' => 'invalid SMS'];
                $sms->save();
            }
            return response()->json(['filter' => true, 'error' => 'invalidSMS'], 200);
        }

        $sms = new SMS();
        $sms->sender = strtolower($sender);
        $sms->message = $message;
        $sms->content = $data;
        $sms->is_valid = true;
        $sms->is_processed = false;
        $sms->message_hash = SMS::generateHash($sender, $message);
        $sms->save();

        try {
            dispatch(new parseSMSJob($sms));
            return response()->json(['filter' => true], 200);
        } catch (\Exception $e) {
            $sms->errors = ['message' => 'processingError'];
            $sms->save();
            return response()->json(['filter' => true, 'error' => 'processingError'], 200);
        }
    }
}
