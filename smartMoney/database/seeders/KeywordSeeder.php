<?php
// database/seeders/KeywordSeeder.php

namespace Database\Seeders;

use App\Models\Keyword;
use Illuminate\Database\Seeder;

class KeywordSeeder extends Seeder
{
    public function run(): void
    {
        $rows = self::seedRows();

        Keyword::upsert(
            $rows,
            ['keyword', 'keyword_type', 'is_regularExp'],
            ['replaceWith', 'is_active', 'updated_at']
        );
    }

    public static function regex_replaceWith(): array
    {
        return [];
    }

    public static function str_replaceWith(): array
    {
        return [];
    }

    public static function regex_phoneNumbers(): array
    {
        return [
            '/\b(0{2})?[+]?966\d{7,10}\b/m',
            '/\b9200\d{5}\b/m',
            '/\b800\d{7}\b/m',
        ];
    }

    public static function regex_passcodes(): array
    {
        return [];
    }

    public static function regex_misc(): array
    {
        return [
        // create regular expression to detect format SA111100000011011111111
        '/\b[A-Z]{2}\d{2}\d{2}\d{10}\d{4}\d{4}\d{4}\b/m',
        // create regular expression to detect format 810110494001
        '/\b\d{12}\b/m',
        //remove percentage
        '/(\d+%|%\d+)/m',
        ];
    }

    public static function regex_date(): array
    {
        return [
        '/(\d{2,4})[\/-](\d{2,4})[\/-](\d{2,4})/m', // dates like 2024-01-24 or 24/01/2024
        '/(\d{2})[\:](\d{2})[\:](\d{2})/m', // time like 12:40:23
        '/(\d{2,4})[\/-](\d{2,4})/m', // dates like 2024-01 or 24/01
        '/(\d+\s)?\b(\d{2,4}\s)?(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|(Nov|Dec)(?:ember)?)(\s\d{4})?\b/m', // dates like 24 Jan 2024 or Jan 2024
        '/(\d+\s)?\b(\d{2,4}\s)?(?:Sun(?:day)?|Mon(?:day)?|Tue(?:sday)?|Wed(?:nesday)?|Thu(?:rsday)?|Fri(?:day)?|Sat(?:urday)?)(\s\d{4})?\b/m', // dates like 24 Wed 2024 or Wed 2024
        '/الموافق \d{1,2} \W{3,16} \d{4}م/m', // dates like الموافق 24 يناير 2024م
        '/\b\d{1,2} \W{3,16} \d{4}م[$\s\n]/m', // dates like 24 يناير 2024م
        '/الساعة \d{1,2}/m', // time like الساعة 12
        '/تاريخ \d{1,2} \W{3,16} \d{4}/m', // dates like تاريخ 24 يناير 2024
        '/\d{1,2} \W{3,16} \d{4}هـ/m', // dates like 24 يناير 2024هـ
        '/الموافق \d{1,2} و\d{1,2} \W{3,16} \d{4}/m', // dates like الموافق 24 و25 يناير 2024
        '/تاريخ \d{1,2} و\d{1,2} \W{3,16} \d{4}/m', // dates like تاريخ 24 و25 يناير 2024
        '/ Date: \d{4}-\d{1,2}-\d{1,2}, \d{1,2}:\d{1,2}/', // dates like Date: 2024-01-24, 12:40
        '/\b\d{1,2}:\d{1,2}-\d{1,2}:\d{1,2}/m', // time like 12:40-13:40
        '/(\d{1,2}\:)?\d{1,2}\s?[a|p]m/mi', // time like 12:40 PM or 12 PM
        '/(\d{1,2})st of \w+/m', // dates like 1st of Jan
        '/(\d{1,2})nd of \w+/m', // dates like 2nd of Jan
        '/(\d{1,2})th of \w+/m', // dates like 3rd of Jan
        '/\b\d{1,2}th/m', // dates like 3rd
        '/\b\d{1,2}nd/m', // dates like 2nd
        '/\b\d{1,2}st/m',// dates like 1st
        '/\b\d{1,2} minutes/m', // time like 5 minutes
        '/\b\d{1,2} minute/m', // time like 1 minute
        '/\b\d{1,2} hour/m', // time like 1 hour
        '/\b\d{1,2} hours/m', // time like 5 hours
        '/\b\d{1,2} days/m', // time like 5 days
        '/\b\d{1,2} day/m', // time like 1 day
        '/\b\d{10,11}/m',   // long numbers that could be dates like 20240124124023
        '/\d{1,2}[\\\\|\/]\d{1,2}[\\\\|\/]\d{4}/m', // dates like 24/01/2024 or 24\01\2024
        ];
    }

    public static function regex_breaks(): array
    {
        return [':', '-', '/', ',', '.', '\\', '?', '!'];
    }

    public static function regex_urls(): array
    {
        return [
            "/(?i)\b((?:[a-z][\w-]+:(?:\/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/"
        ];
    }

    public static function regex_ignoreSMS(): array
    {
        return [
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
    }

    public static function str_ignoreSMS(): array
    {
        return [
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
    }

    /**
     * Helper to build rows for seeding.
     */
    public static function seedRows(): array
    {
        $rows = [];

        foreach (self::regex_replaceWith() as $regex => $replace) {
            $rows[] = [
                'keyword' => $regex,
                'is_regularExp' => true,
                'replaceWith' => $replace,
                'keyword_type' => 'replace',
                'is_active' => true,
            ];
        }

        foreach (self::str_replaceWith() as $str => $replace) {
            $rows[] = [
                'keyword' => $str,
                'is_regularExp' => false,
                'replaceWith' => $replace,
                'keyword_type' => 'replace',
                'is_active' => true,
            ];
        }

        foreach (self::regex_phoneNumbers() as $regex) {
            $rows[] = [
                'keyword' => $regex,
                'is_regularExp' => true,
                'replaceWith' => null,
                'keyword_type' => 'phone',
                'is_active' => true,
            ];
        }

        foreach (self::regex_passcodes() as $regex) {
            $rows[] = [
                'keyword' => $regex,
                'is_regularExp' => true,
                'replaceWith' => null,
                'keyword_type' => 'passcodes',
                'is_active' => true,
            ];
        }

        foreach (self::regex_misc() as $regex) {
            $rows[] = [
                'keyword' => $regex,
                'is_regularExp' => true,
                'replaceWith' => null,
                'keyword_type' => 'misc',
                'is_active' => true,
            ];
        }

        foreach (self::regex_date() as $regex) {
            $rows[] = [
                'keyword' => $regex,
                'is_regularExp' => true,
                'replaceWith' => null,
                'keyword_type' => 'date',
                'is_active' => true,
            ];
        }
        foreach (self::regex_urls() as $regex) {
            $rows[] = [
                'keyword' => $regex,
                'is_regularExp' => true,
                'replaceWith' => null,
                'keyword_type' => 'url',
                'is_active' => true,
            ];
        }

        foreach (self::regex_ignoreSMS() as $regex) {
            $rows[] = [
                'keyword' => $regex,
                'is_regularExp' => true,
                'replaceWith' => null,
                'keyword_type' => 'ignore',
                'is_active' => true,
            ];
        }

        foreach (self::str_ignoreSMS() as $str) {
            $rows[] = [
                'keyword' => $str,
                'is_regularExp' => false,
                'replaceWith' => null,
                'keyword_type' => 'ignore',
                'is_active' => true,
            ];
        }

        // breaks as misc strings
        foreach (self::regex_breaks() as $ch) {
            $rows[] = [
                'keyword' => $ch,
                'is_regularExp' => false,
                'replaceWith' => null,
                'keyword_type' => 'breaks',
                'is_active' => true,
            ];
        }

        return $rows;
    }

}