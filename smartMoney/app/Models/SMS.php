<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SMS extends Model
{
    use HasFactory;
    protected $table = 'smses';
    protected $fillable = [
        'sender',
        'message',
        'content',
        'is_valid',
        'is_processed',
        'errors',
    ];

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
        $message = str_replace(['١','٢','٣','٤','٥','٦','٧','٨','٩'], ['1','2','3','4','5','6','7','8','9'], $message);
    
        // Remove phone numbers
        foreach(Keyword::regex_phoneNumbers() as $re) {
            $message = preg_replace($re, '', $message);
        }
        
        // Remove Passcodes
        foreach(Keyword::regex_passcodes() as $re) {
            $message = preg_replace($re, '', $message);
        }
        
        // Remove Misc
        foreach(Keyword::regex_misc() as $re) {
            $message = preg_replace($re, '', $message);
        }
    
        // Remove Date
        foreach(Keyword::regex_date() as $re) {
            $message = preg_replace($re, '', $message);
        }
    
        // Remove URLs
        foreach(Keyword::regex_urls() as $re) {
            $message = preg_replace($re, '', $message);
        }
    
        // $message = preg_replace('/(\d+%|%\d+)/m', '', $message);
    
        // remove breaks
        foreach(Keyword::regex_breaks() as $re) {
            $message = preg_replace($re, '', $message);
        }
                
        // remove double spaces
        $message = preg_replace('/\s{2,}/', ' ', $message);
        
        // //trim message
        $message = trim($message);
        
        return $message;
    }

    public static function isValidBankTransaction($message, $cleanSMS = true){
        if ($cleanSMS) {
            $message = self::preClean($message);
        }

        // No numbers
        if (!preg_match('~[0-9]+~', $message)) {
            return false;
        }
        
        // Tiny text
        if(mb_strlen($message)<=config('parseSMS.min_sms_length')) {
            return false;
        }

        foreach(Keyword::regex_ignoreSMS() as $re){
            if (preg_match($re, $message)) {
                return false;
            }
        }

        foreach (Keyword::str_ignoreSMS() as $keyword) {
            if (stripos($message, $keyword) !== false) {
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

    public static function processInvalidSMS($sms, $errors = null, $keep = false){
            if(config('parseSMS.store_invalid_sms') || $keep) {
                $sms->is_valid = false;
                $sms->is_processed = true;
                if($errors) {
                    if(!is_array($errors)){
                        $errors = ['reason' => $errors];
                    }
                    $sms->errors = $errors;
                }
                $sms->save();
            } else {
                $sms->delete();
            }
    }


}
