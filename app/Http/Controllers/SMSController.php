<?php

namespace App\Http\Controllers;

use App\Jobs\parseSMSJob;
use App\Models\Alert;
use App\Models\Setting;
use App\Models\SMS;
use App\Models\SMSSender;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        if (! isset($data['query']['sender']) || ! isset($data['query']['message']['text'])) {
            return response()->json(['filter' => true, 'error' => 'noData'], 200);
        }

        $sender = $data['query']['sender'];
        if (! SMSSender::isValidSender($sender)) {
            \Log::debug('SMSController: invalid sender', ['sender' => $sender]);

            return response()->json(['filter' => true, 'error' => 'invalidSender'], 200);
        }

        // SMS Flood / Fraud Detection
        $this->checkSmsFlood($sender);

        $message = SMS::removeHiddenChars($data['query']['message']['text']);

        if (SMS::isDuplicate($sender, $message)) {
            return response()->json(['filter' => true, 'error' => 'duplicate'], 200);
        }

        $smsSender = SMSSender::where('sender', $sender)->where('is_active', true)->first();
        $stripedMessage = SMS::preClean($message, $smsSender?->id);
        $status = SMS::isValidBankTransaction($stripedMessage, false, $smsSender?->id);
        if ($status == false) {
            if (Setting::getBool('parsesms_store_invalid_sms', false)) {
                $sms = new SMS;
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

        $sms = new SMS;
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

    protected function checkSmsFlood(string $sender): void
    {
        $threshold = Setting::getInt('sms_flood_threshold_count', 3);
        $minutes = Setting::getInt('sms_flood_threshold_minutes', 5);

        if ($threshold <= 0 || $minutes <= 0) {
            return;
        }

        $cacheKey = 'sms_flood_alerted:'.strtolower($sender);
        if (Cache::has($cacheKey)) {
            return;
        }

        $recentCount = SMS::where('sender', strtolower($sender))
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();

        if ($recentCount >= $threshold) {
            Cache::put($cacheKey, true, now()->addMinutes($minutes));

            $admin = User::find(1);
            if ($admin) {
                app()->setLocale($admin->language ?? 'en');
                Alert::createAlertWithAdminCopy(
                    title: __('alert.sms_flood_title'),
                    message: __('alert.sms_flood_message', [
                        'count' => $recentCount,
                        'sender' => $sender,
                        'minutes' => $minutes,
                    ]),
                    user_id: $admin,
                    pin: true,
                    topic: 'fraud',
                );
            }
        }
    }
}
