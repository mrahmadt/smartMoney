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

    //Remove phone numbers
    public static $regex_phoneNumbers = [
        '/\b(0{2})?[+]?966\d{7,10}\b/m',
        '/\b9200\d{5}\b/m',
        '/\b800\d{7}\b/m',
    ];

    //Remove Passcodes
    public static $regex_passcodes = [
        // '/is\s+(\d{4,6})/m',
        // '/(\d{3,5}) is your security/im',
    ];

    public static $regex_misc = [
        // create regular expression to detect format SA111100000011011111111
        '/\b[A-Z]{2}\d{2}\d{2}\d{10}\d{4}\d{4}\d{4}\b/m',
        // create regular expression to detect format 810110494001
        '/\b\d{12}\b/m',
    ];

    // Remove Date
    public static $regex_date = [

        '/(\d{2,4})[\/-](\d{2,4})[\/-](\d{2,4})/m',
        '/(\d{2})[\:](\d{2})[\:](\d{2})/m',
        '/(\d{2,4})[\/-](\d{2,4})/m',
    
        '/(\d+\s)?\b(\d{2,4}\s)?(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|(Nov|Dec)(?:ember)?)(\s\d{4})?\b/m',
        '/(\d+\s)?\b(\d{2,4}\s)?(?:Sun(?:day)?|Mon(?:day)?|Tue(?:sday)?|Wed(?:nesday)?|Thu(?:rsday)?|Fri(?:day)?|Sat(?:urday)?)(\s\d{4})?\b/m',
    
        '/الموافق \d{1,2} \W{3,16} \d{4}م/m',
    
        '/\b\d{1,2} \W{3,16} \d{4}م[$\s\n]/m',
    
        '/الساعة \d{1,2}/m',
    
        '/تاريخ \d{1,2} \W{3,16} \d{4}/m',
    
        '/\d{1,2} \W{3,16} \d{4}هـ/m',
    
        '/الموافق \d{1,2} و\d{1,2} \W{3,16} \d{4}/m',
    
        '/تاريخ \d{1,2} و\d{1,2} \W{3,16} \d{4}/m',
    
        '/ Date: \d{4}-\d{1,2}-\d{1,2}, \d{1,2}:\d{1,2}/',
    
        '/\b\d{1,2}:\d{1,2}-\d{1,2}:\d{1,2}/m',
    
        '/(\d{1,2}\:)?\d{1,2}\s?[a|p]m/mi',
    
        '/(\d{1,2})st of \w+/m',
        '/(\d{1,2})nd of \w+/m',
        '/(\d{1,2})th of \w+/m',
        '/\b\d{1,2}th/m',
        '/\b\d{1,2}nd/m',
        '/\b\d{1,2}st/m',
    
        '/\b\d{1,2} minutes/m',
        '/\b\d{1,2} minute/m',
        '/\b\d{1,2} hour/m',
        '/\b\d{1,2} hours/m',
        '/\b\d{1,2} days/m',
        '/\b\d{1,2} day/m',
            
        '/\b\d{10,11}/m',
    
        '/\d{1,2}[\\\\|\/]\d{1,2}[\\\\|\/]\d{4}/m',
    ];
    
    // remove breaks
    public static $regex_breaks = [
        ':','-','/',',','.','\\','?','!'
    ];
    
    // Remove URLs
    public static $regex_urls = "/(?i)\b((?:[a-z][\w-]+:(?:\/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";
    // '%\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))%sm';
    
    // Ignore SMS using regular expression
    public static $regex_ignoreSMS = [
        '/\bCode \d+/im',
        '/\bCode:\s?\d+/im',
        '/\bOTP \d+/im',
        '/\bOTP:\s?\d+/im',
        '/security code/im',
        '/verification code/im',
        '/passcode/im',
        '/^الاسم\:/',
        '/رمز:(\d{4,6})/m',
        '/الدخول (\d{4,6})/m',

    ];
    
    // Ignore SMS using string
    public static $str_ignoreSMS = [
        'security code',
        'activated successfully',
        'One Time Password',
        'wrong PIN',
        'be completed',
        'has been received',
        'under process',
        'ready for Apple',
        'not processed ',
        'is ready for',
        'code will expire',
        'verification code',
        'Your service request',
        'temporarily out of service',
        'Application Request',
        'completed within',
        'due to scheduled',
        'declined due',
        'Declined transaction',
        'login authorization',
        'under maintenance',
        'Activate the card',
        'Change the credit limit',
        'Use OTP',
        'your OTP',
        'do not share it',
        "don’t share it with anyone",
        "Hello Ahmad,",
        'is your security',
        'secret code',
        'will call you to',
        'Successfully',
        'account has been opened',
        'has been closed',
        'Activated',
        'Insufficient balance',
        'Open a savings account',
        'account is closed',
        'Status: Activated',
        'One-time password',
        'Issuing an account',
        'has been completed',
        'added to Apple Pay',
        'has been expired',
        'scheduled maintenance',
        'activation code',
        'لا يكفي لإتمام',
        'تم تجديد بطاقتك',
        'تم اصدار الكشف',
        'الرقم السري',
        'تم إغلاق حسابكم',
        'غير نشطة',
        'تم رفض عمليتك',
        'نود تذكيرك',
        'الرجاء تفعيلها',
        'حالة بطاقة مرتجعة',
        'حالة إلغاء نهائي',
        'تم ربط حساب',
        'حالة مفقودة',
        'إصدار بطاقة',
        'رمز التحقق',
        'تمت المعالجه',
        'تم تحديث',
        'لتنشيط Apple Pay',
        'استخدم رمز التفعيل',
        'تفعيل البطاقة',
        'بنجاح',
        'تغيير الحد الائتماني',
        'الرمز السري',
        'بنجاح',
        'لايمكن إتمام',
        'تم فتح حساب',
        'إلغاء بطاقتك',
        'تم تفعيل',
        'رصيد غير كافي',
        'فتح حساب الادخار',
        'يغلق حسابك',
        'حالة: تنشيط',
        'كلمة مرور لمرة واحدة',
        'إصدار كشف حساب',
        'يرجى تحديث البيانات',
        'تم اضافة عملة',
        'كلمة مرور',
        'تسجيل دخول',
        'جهاز جديد',
        'تعريف أمر مستديم',
        'رمز التفعيل',
        'ترقية النظام',
        'يرجى تحديث',
    ];
    

    
    public static function isValidSender($sender)
    {
        return in_array(strtolower($sender), array_keys(config('parseSMS.senders')));
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
        foreach(static::$regex_phoneNumbers as $re) {
            $message = preg_replace($re, '', $message);
        }
        
        // Remove Passcodes
        foreach(static::$regex_passcodes as $re) {
            $message = preg_replace($re, '', $message);
        }
        
        // Remove Misc
        foreach(static::$regex_misc as $re) {
            $message = preg_replace($re, '', $message);
        }
    
        // Remove Date
        foreach(static::$regex_date as $re) {
            $message = preg_replace($re, '', $message);
        }
    
        // Remove URLs
        if(static::$regex_urls) {
            $message = preg_replace(static::$regex_urls, '', $message);
        }
    
        //remove percentage
        $message = preg_replace('/(\d+%|%\d+)/m', '', $message);
    
        // remove breaks
        if(static::$regex_breaks) {
            $message = str_replace(static::$regex_breaks, '', $message);
        }
                
        // remove double spaces
        $message = preg_replace('/\s{2,}/', ' ', $message);
        
        // //trim message
        $message = trim($message);
        
        return $message;
    }

    public static function isValidBankTransaction($message){
        // No numbers
        if (!preg_match('~[0-9]+~', $message)) {
            return false;
        }
        
        // Tiny text
        if(mb_strlen($message)<=config('parseSMS.min_sms_length')) {
            return false;
        }

        foreach(static::$regex_ignoreSMS as $re){
            if (preg_match($re, $message)) {
                return false;
            }
        }

        foreach (static::$str_ignoreSMS as $keyword) {
            if (stripos($message, $keyword) !== false) {
                return false;
            }
        }


        if(config('parseSMS.auto_detect_non_transaction_sms')){
            // ignore if only one number
            $re = '/(\d+)/m';
            preg_match_all($re, $message, $matches, PREG_SET_ORDER, 0);
            $digits = [];
            foreach($matches as $match){
                if (is_numeric($match[0])) {
                    $digits[] = $match[0];
                }
            }
            //small text & one number len 5 or below OR only time
            if(count($digits) == 1 && $digits[0] <= 99999 && mb_strlen($message)<=80 && stripos($message, 'amount') === false) {
                return false; // ignore small text and one number below 99999
            }
            if(count($digits) == 1 && $digits[0] <= 99) {
                return false; // ignore if one number is below 99
            }
            if(count($digits) == 1 && $digits[0] >= 9000000) {
                return false; // I don't think you need this code if one of you transactions is above 1000000
            }
        }

        return true;
    }

}
