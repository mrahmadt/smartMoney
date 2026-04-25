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
    public static function preClean($message, ?int $senderId = null)
    {
        $debug = config('app.debug');

        // To Arabic Numbers
        $message = str_replace(['١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'], ['1', '2', '3', '4', '5', '6', '7', '8', '9'], $message);

        // Replace keywords (regex)
        foreach (Keyword::regex_replaceWith($senderId) as $re => $replacement) {
            $before = $message;
            $message = preg_replace($re, $replacement ?? '', $message);
            if ($debug && $before !== $message) {
                Log::debug('preClean: replace regex matched', ['regex' => $re, 'replaceWith' => $replacement]);
            }
        }

        // Replace keywords (string)
        foreach (Keyword::str_replaceWith($senderId) as $str => $replacement) {
            $before = $message;
            $message = str_replace($str, $replacement ?? '', $message);
            if ($debug && $before !== $message) {
                Log::debug('preClean: replace string matched', ['string' => $str, 'replaceWith' => $replacement]);
            }
        }

        // Remove phone numbers
        foreach (Keyword::regex_phoneNumbers($senderId) as $re) {
            $before = $message;
            $message = preg_replace($re, '', $message);
            if ($debug && $before !== $message) {
                Log::debug('preClean: phone regex matched', ['regex' => $re]);
            }
        }

        // Remove Passcodes
        foreach (Keyword::regex_passcodes($senderId) as $re) {
            $before = $message;
            $message = preg_replace($re, '', $message);
            if ($debug && $before !== $message) {
                Log::debug('preClean: passcode regex matched', ['regex' => $re]);
            }
        }

        // Remove Misc
        foreach (Keyword::regex_misc($senderId) as $re) {
            $before = $message;
            $message = preg_replace($re, '', $message);
            if ($debug && $before !== $message) {
                Log::debug('preClean: misc regex matched', ['regex' => $re]);
            }
        }

        // Remove Date
        foreach (Keyword::regex_date($senderId) as $re) {
            $before = $message;
            $message = preg_replace($re, '', $message);
            if ($debug && $before !== $message) {
                Log::debug('preClean: date regex matched', ['regex' => $re]);
            }
        }

        // Remove URLs
        foreach (Keyword::regex_urls($senderId) as $re) {
            $before = $message;
            $message = preg_replace($re, '', $message);
            if ($debug && $before !== $message) {
                Log::debug('preClean: url regex matched', ['regex' => $re]);
            }
        }

        // remove breaks
        foreach (Keyword::regex_breaks($senderId) as $re) {
            $before = $message;
            $message = preg_replace($re, '', $message);
            if ($debug && $before !== $message) {
                Log::debug('preClean: break regex matched', ['regex' => $re]);
            }
        }
        foreach (Keyword::non_regex_breaks($senderId) as $re) {
            $before = $message;
            $message = str_replace($re, '', $message);
            if ($debug && $before !== $message) {
                Log::debug('preClean: break string matched', ['string' => $re]);
            }
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
            if (Setting::getBool('parsesms_alert_invalid_sms', true)) {
                $user = User::find(1);
                if ($user) {
                    if ($message == null && $errors) {
                        $message = is_array($errors) ? implode(', ', $errors) : $errors;
                        if (mb_strlen($message) > 100) {
                            $message = mb_substr($message, 0, 100);
                        }
                    }
                    // $message = $sms->sender.': '.($message ?: 'Invalid SMS');
                    $sms_text = $sms->sender.': '.mb_substr($sms->message ?? '', 0, 200);
                    $message = ($message ?: 'Invalid SMS')."\n\n".$sms_text;
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
            }
        } else {
            $sms->delete();
        }
    }
}
