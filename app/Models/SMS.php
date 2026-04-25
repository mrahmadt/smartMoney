<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log; 

class SMS extends Model
{
    use HasFactory;

    protected $table = 'smses';

    protected $fillable = [
        'sender',
        'message',
        'message_hash',
        'transaction_id',
        'content',
        'is_valid',
        'is_processed',
        'errors',
    ];

    public static function generateHash($sender, $message): string
    {
        return md5(strtolower($sender).$message);
    }

    public static function isDuplicate($sender, $message): bool
    {
        return self::where('message_hash', self::generateHash($sender, $message))->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_valid' => 'boolean',
            'is_processed' => 'boolean',
            'content' => 'array',
            'errors' => 'array',
        ];
    }

    // Remove Noise and hidden char
    public static function removeHiddenChars($message)
    {
        return preg_replace("/[^\PC\s]+/um", '', $message);
    }

    // PreClean SMS before any process
    public static function preClean($message)
    {
        // To Arabic Numbers
        $message = str_replace(['١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'], ['1', '2', '3', '4', '5', '6', '7', '8', '9'], $message);

        // Remove phone numbers
        foreach (Keyword::regex_phoneNumbers() as $re) {
            $message = preg_replace($re, '', $message);
        }

        // Remove Passcodes
        foreach (Keyword::regex_passcodes() as $re) {
            $message = preg_replace($re, '', $message);
        }

        // Remove Misc
        foreach (Keyword::regex_misc() as $re) {
            $message = preg_replace($re, '', $message);
        }

        // Remove Date
        foreach (Keyword::regex_date() as $re) {
            $message = preg_replace($re, '', $message);
        }

        // Remove URLs
        foreach (Keyword::regex_urls() as $re) {
            $message = preg_replace($re, '', $message);
        }

        // $message = preg_replace('/(\d+%|%\d+)/m', '', $message);

        // remove breaks
        foreach (Keyword::regex_breaks() as $re) {
            $message = preg_replace($re, '', $message);
        }
        foreach (Keyword::non_regex_breaks() as $re) {
            $message = str_replace($re, '', $message);
        }
        // remove double spaces
        $message = preg_replace('/\s{2,}/', ' ', $message);

        // //trim message
        $message = trim($message);

        return $message;
    }

    public static function isValidBankTransaction($message, $cleanSMS = true, ?int $senderId = null)
    {
        if ($cleanSMS) {
            $message = self::preClean($message, $senderId);
        }

        // No numbers
        if (! preg_match('~[0-9]+~', $message)) {
            Log::debug('SMS ignored due to no numbers', ['message' => $message]);
            return false;
        }

        // Tiny text
        if (mb_strlen($message) <= Setting::getInt('parsesms_min_sms_length', 30)) {
            Log::debug('SMS ignored due to short length', ['message' => $message]);
            return false;
        }

        foreach (Keyword::regex_ignoreSMS($senderId) as $re) {
            if (preg_match($re, $message)) {
                Log::debug('SMS ignored due to regex: '.$re, ['message' => $message]);
                return false;
            }
        }

        foreach (Keyword::str_ignoreSMS($senderId) as $keyword) {
            if (stripos($message, $keyword) !== false) {
                Log::debug('SMS ignored due to keyword: '.$keyword, ['message' => $message]);
                return false;
            }
        }

        // if(config('parseSMS.auto_detect_non_transaction_sms')){
        //     // ignore if only one number
        //     $re = '/(\d+)/m';
        //     preg_match_all($re, $message, $matches, PREG_SET_ORDER, 0);
        //     $digits = [];
        //     foreach($matches as $match){
        //         if (is_numeric($match[0])) {
        //             $digits[] = $match[0];
        //         }
        //     }
        //     //small text & one number len 5 or below OR only time
        //     if(count($digits) == 1 && $digits[0] <= 99999 && mb_strlen($message)<=80 && stripos($message, 'amount') === false) {
        //         return false; // ignore small text and one number below 99999
        //     }
        //     if(count($digits) == 1 && $digits[0] <= 99) {
        //         return false; // ignore if one number is below 99
        //     }
        //     if(count($digits) == 1 && $digits[0] >= 9000000) {
        //         return false; // I don't think you need this code if one of you transactions is above 1000000
        //     }
        // }

        return true;
    }

    public static function processInvalidSMS($sms, $errors = null, $message = null, $keep = false)
    {
        if (Setting::getBool('parsesms_store_invalid_sms', false) || $keep) {
            $sms->is_valid = false;
            $sms->is_processed = true;
            if ($errors) {
                if (! is_array($errors)) {
                    $errors = ['reason' => $errors];
                }
                $sms->errors = $errors;
            }
            $sms->save();
            $user = User::find(1);
            if ($user) {
                if ($message == null && $errors) {
                    $message = is_array($errors) ? implode(', ', $errors) : $errors;
                    if (mb_strlen($message) > 100) {
                        $message = mb_substr($message, 0, 100);
                    }
                }
                $message = $sms->sender.': '.($message ?: 'Invalid SMS');
                app()->setLocale($user->language ?? 'en');
                Alert::createAlert(
                    title: __('alert.invalid_sms_title'),
                    message: $message,
                    user: $user,
                    topic: 'Invalid SMS',
                    data: [
                        'sms_id' => $sms->id,
                        'sender' => $sms->sender,
                        'sms_message' => mb_substr($sms->message ?? '', 0, 200),
                        'errors' => ($errors ? json_encode($errors) : 'Unknown'),
                    ]
                );
            }
        } else {
            $sms->delete();
        }
    }
}
