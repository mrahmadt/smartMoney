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
            ['keyword', 'keyword_type', 'is_regularExp', 'sender_id'],
            ['replaceWith', 'is_active', 'name', 'updated_at']
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
            ['keyword' => '/\b(0{2})?[+]?966\d{7,10}\b/m', 'name' => 'Saudi phone +966'],
            ['keyword' => '/\b9200\d{5}\b/m', 'name' => 'Saudi toll-free 9200'],
            ['keyword' => '/\b800\d{7}\b/m', 'name' => 'Saudi toll-free 800'],
        ];
    }

    public static function regex_passcodes(): array
    {
        return [];
    }

    public static function regex_misc(): array
    {
        return [
            ['keyword' => '/\b[A-Z]{2}\d{2}\d{2}\d{10}\d{4}\d{4}\d{4}\b/m', 'name' => 'IBAN format'],
            ['keyword' => '/\b\d{12}\b/m', 'name' => '12-digit account number'],
            ['keyword' => '/(\d+%|%\d+)/m', 'name' => 'Percentage values'],
        ];
    }

    public static function regex_date(): array
    {
        return [
            ['keyword' => '/(\d{2,4})[\/-](\d{2,4})[\/-](\d{2,4})/m', 'name' => 'Date YYYY-MM-DD or DD/MM/YYYY'],
            ['keyword' => '/(\d{2})[\:](\d{2})[\:](\d{2})/m', 'name' => 'Time HH:MM:SS'],
            ['keyword' => '/(\d{2,4})[\/-](\d{2,4})/m', 'name' => 'Partial date YYYY-MM or DD/MM'],
            ['keyword' => '/(\d+\s)?\b(\d{2,4}\s)?(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|(Nov|Dec)(?:ember)?)(\s\d{4})?\b/m', 'name' => 'English month name date'],
            ['keyword' => '/(\d+\s)?\b(\d{2,4}\s)?(?:Sun(?:day)?|Mon(?:day)?|Tue(?:sday)?|Wed(?:nesday)?|Thu(?:rsday)?|Fri(?:day)?|Sat(?:urday)?)(\s\d{4})?\b/m', 'name' => 'English day name date'],
            ['keyword' => '/الموافق \d{1,2} \W{3,16} \d{4}م/m', 'name' => 'Arabic date with الموافق'],
            ['keyword' => '/\b\d{1,2} \W{3,16} \d{4}م[$\s\n]/m', 'name' => 'Arabic date ending with م'],
            ['keyword' => '/الساعة \d{1,2}/m', 'name' => 'Arabic time الساعة'],
            ['keyword' => '/تاريخ \d{1,2} \W{3,16} \d{4}/m', 'name' => 'Arabic date with تاريخ'],
            ['keyword' => '/\d{1,2} \W{3,16} \d{4}هـ/m', 'name' => 'Hijri date ending with هـ'],
            ['keyword' => '/الموافق \d{1,2} و\d{1,2} \W{3,16} \d{4}/m', 'name' => 'Arabic date range with الموافق'],
            ['keyword' => '/تاريخ \d{1,2} و\d{1,2} \W{3,16} \d{4}/m', 'name' => 'Arabic date range with تاريخ'],
            ['keyword' => '/ Date: \d{4}-\d{1,2}-\d{1,2}, \d{1,2}:\d{1,2}/', 'name' => 'Date label format'],
            ['keyword' => '/\b\d{1,2}:\d{1,2}-\d{1,2}:\d{1,2}/m', 'name' => 'Time range HH:MM-HH:MM'],
            ['keyword' => '/(\d{1,2}\:)?\d{1,2}\s?[a|p]m/mi', 'name' => 'Time with AM/PM'],
            ['keyword' => '/(\d{1,2})st of \w+/m', 'name' => 'Ordinal date 1st of'],
            ['keyword' => '/(\d{1,2})nd of \w+/m', 'name' => 'Ordinal date 2nd of'],
            ['keyword' => '/(\d{1,2})th of \w+/m', 'name' => 'Ordinal date Nth of'],
            ['keyword' => '/\b\d{1,2}th/m', 'name' => 'Ordinal suffix th'],
            ['keyword' => '/\b\d{1,2}nd/m', 'name' => 'Ordinal suffix nd'],
            ['keyword' => '/\b\d{1,2}st/m', 'name' => 'Ordinal suffix st'],
            ['keyword' => '/\b\d{1,2} minutes/m', 'name' => 'Duration minutes'],
            ['keyword' => '/\b\d{1,2} minute/m', 'name' => 'Duration minute'],
            ['keyword' => '/\b\d{1,2} hour/m', 'name' => 'Duration hour'],
            ['keyword' => '/\b\d{1,2} hours/m', 'name' => 'Duration hours'],
            ['keyword' => '/\b\d{1,2} days/m', 'name' => 'Duration days'],
            ['keyword' => '/\b\d{1,2} day/m', 'name' => 'Duration day'],
            ['keyword' => '/\b\d{10,11}/m', 'name' => 'Long number as date'],
            ['keyword' => '/\d{1,2}[\\\\|\/]\d{1,2}[\\\\|\/]\d{4}/m', 'name' => 'Date with backslash/slash'],
        ];
    }

    public static function regex_breaks(): array
    {
        return [
            ['keyword' => ':', 'name' => 'Colon break'],
            ['keyword' => '-', 'name' => 'Dash break'],
            ['keyword' => '/', 'name' => 'Slash break'],
            ['keyword' => ',', 'name' => 'Comma break'],
            ['keyword' => '.', 'name' => 'Period break'],
            ['keyword' => '\\', 'name' => 'Backslash break'],
            ['keyword' => '?', 'name' => 'Question mark break'],
            ['keyword' => '!', 'name' => 'Exclamation break'],
        ];
    }

    public static function regex_urls(): array
    {
        return [
            ['keyword' => "/(?i)\b((?:[a-z][\w-]+:(?:\/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»\"\"\u2018\u2019']))/", 'name' => 'URL pattern'],
        ];
    }

    public static function regex_ignoreSMS(): array
    {
        return [
            ['keyword' => '/\bCode \d+/im', 'name' => 'Verification code'],
            ['keyword' => '/\bCode:\s?\d+/im', 'name' => 'Verification code with colon'],
            ['keyword' => '/\bOTP \d+/im', 'name' => 'OTP number'],
            ['keyword' => '/\bOTP:\s?\d+/im', 'name' => 'OTP with colon'],
            ['keyword' => '/security code/im', 'name' => 'Security code mention'],
            ['keyword' => '/verification code/im', 'name' => 'Verification code mention'],
            ['keyword' => '/passcode/im', 'name' => 'Passcode mention'],
            ['keyword' => '/^الاسم\:/', 'name' => 'Arabic name prefix'],
            ['keyword' => '/رمز:(\d{4,6})/m', 'name' => 'Arabic OTP رمز'],
            ['keyword' => '/الدخول (\d{4,6})/m', 'name' => 'Arabic login code'],
        ];
    }

    public static function str_ignoreSMS(): array
    {
        return [
            ['keyword' => 'security code', 'name' => 'Ignore security code'],
            ['keyword' => 'activated successfully', 'name' => 'Ignore activation success'],
            ['keyword' => 'One Time Password', 'name' => 'Ignore OTP'],
            ['keyword' => 'wrong PIN', 'name' => 'Ignore wrong PIN'],
            ['keyword' => 'be completed', 'name' => 'Ignore completion notice'],
            ['keyword' => 'has been received', 'name' => 'Ignore received notice'],
            ['keyword' => 'under process', 'name' => 'Ignore processing notice'],
            ['keyword' => 'ready for Apple', 'name' => 'Ignore Apple Pay ready'],
            ['keyword' => 'not processed ', 'name' => 'Ignore not processed'],
            ['keyword' => 'is ready for', 'name' => 'Ignore ready notice'],
            ['keyword' => 'code will expire', 'name' => 'Ignore code expiry'],
            ['keyword' => 'verification code', 'name' => 'Ignore verification code'],
            ['keyword' => 'Your service request', 'name' => 'Ignore service request'],
            ['keyword' => 'temporarily out of service', 'name' => 'Ignore out of service'],
            ['keyword' => 'Application Request', 'name' => 'Ignore app request'],
            ['keyword' => 'completed within', 'name' => 'Ignore completion time'],
            ['keyword' => 'due to scheduled', 'name' => 'Ignore scheduled notice'],
            ['keyword' => 'declined due', 'name' => 'Ignore declined notice'],
            ['keyword' => 'Declined transaction', 'name' => 'Ignore declined transaction'],
            ['keyword' => 'login authorization', 'name' => 'Ignore login auth'],
            ['keyword' => 'under maintenance', 'name' => 'Ignore maintenance notice'],
            ['keyword' => 'Activate the card', 'name' => 'Ignore card activation'],
            ['keyword' => 'Change the credit limit', 'name' => 'Ignore credit limit change'],
            ['keyword' => 'Use OTP', 'name' => 'Ignore use OTP'],
            ['keyword' => 'your OTP', 'name' => 'Ignore your OTP'],
            ['keyword' => 'do not share it', 'name' => 'Ignore do not share'],
            ['keyword' => "don't share it with anyone", 'name' => 'Ignore sharing warning'],
            ['keyword' => 'Hello Ahmad,', 'name' => 'Ignore greeting'],
            ['keyword' => 'is your security', 'name' => 'Ignore security notice'],
            ['keyword' => 'secret code', 'name' => 'Ignore secret code'],
            ['keyword' => 'will call you to', 'name' => 'Ignore call notice'],
            ['keyword' => 'Successfully', 'name' => 'Ignore success notice'],
            ['keyword' => 'account has been opened', 'name' => 'Ignore account opened'],
            ['keyword' => 'has been closed', 'name' => 'Ignore account closed'],
            ['keyword' => 'Activated', 'name' => 'Ignore activated'],
            ['keyword' => 'Insufficient balance', 'name' => 'Ignore insufficient balance'],
            ['keyword' => 'Open a savings account', 'name' => 'Ignore savings promo'],
            ['keyword' => 'account is closed', 'name' => 'Ignore closed account'],
            ['keyword' => 'Status: Activated', 'name' => 'Ignore status activated'],
            ['keyword' => 'One-time password', 'name' => 'Ignore one-time password'],
            ['keyword' => 'Issuing an account', 'name' => 'Ignore account issuance'],
            ['keyword' => 'has been completed', 'name' => 'Ignore completion'],
            ['keyword' => 'added to Apple Pay', 'name' => 'Ignore Apple Pay added'],
            ['keyword' => 'has been expired', 'name' => 'Ignore expiry notice'],
            ['keyword' => 'scheduled maintenance', 'name' => 'Ignore scheduled maintenance'],
            ['keyword' => 'activation code', 'name' => 'Ignore activation code'],
            ['keyword' => 'لا يكفي لإتمام', 'name' => 'تجاهل رصيد غير كافي'],
            ['keyword' => 'تم تجديد بطاقتك', 'name' => 'تجاهل تجديد بطاقة'],
            ['keyword' => 'تم اصدار الكشف', 'name' => 'تجاهل إصدار كشف'],
            ['keyword' => 'الرقم السري', 'name' => 'تجاهل رقم سري'],
            ['keyword' => 'تم إغلاق حسابكم', 'name' => 'تجاهل إغلاق حساب'],
            ['keyword' => 'غير نشطة', 'name' => 'تجاهل غير نشطة'],
            ['keyword' => 'تم رفض عمليتك', 'name' => 'تجاهل رفض عملية'],
            ['keyword' => 'نود تذكيرك', 'name' => 'تجاهل تذكير'],
            ['keyword' => 'الرجاء تفعيلها', 'name' => 'تجاهل طلب تفعيل'],
            ['keyword' => 'حالة بطاقة مرتجعة', 'name' => 'تجاهل بطاقة مرتجعة'],
            ['keyword' => 'حالة إلغاء نهائي', 'name' => 'تجاهل إلغاء نهائي'],
            ['keyword' => 'تم ربط حساب', 'name' => 'تجاهل ربط حساب'],
            ['keyword' => 'حالة مفقودة', 'name' => 'تجاهل حالة مفقودة'],
            ['keyword' => 'إصدار بطاقة', 'name' => 'تجاهل إصدار بطاقة'],
            ['keyword' => 'رمز التحقق', 'name' => 'تجاهل رمز تحقق'],
            ['keyword' => 'تمت المعالجه', 'name' => 'تجاهل معالجة'],
            ['keyword' => 'تم تحديث', 'name' => 'تجاهل تحديث'],
            ['keyword' => 'لتنشيط Apple Pay', 'name' => 'تجاهل تنشيط Apple Pay'],
            ['keyword' => 'استخدم رمز التفعيل', 'name' => 'تجاهل رمز تفعيل'],
            ['keyword' => 'تفعيل البطاقة', 'name' => 'تجاهل تفعيل بطاقة'],
            ['keyword' => 'بنجاح', 'name' => 'تجاهل بنجاح'],
            ['keyword' => 'تغيير الحد الائتماني', 'name' => 'تجاهل حد ائتماني'],
            ['keyword' => 'الرمز السري', 'name' => 'تجاهل رمز سري'],
            ['keyword' => 'لايمكن إتمام', 'name' => 'تجاهل لايمكن إتمام'],
            ['keyword' => 'تم فتح حساب', 'name' => 'تجاهل فتح حساب'],
            ['keyword' => 'إلغاء بطاقتك', 'name' => 'تجاهل إلغاء بطاقة'],
            ['keyword' => 'تم تفعيل', 'name' => 'تجاهل تفعيل'],
            ['keyword' => 'رصيد غير كافي', 'name' => 'تجاهل رصيد غير كافي'],
            ['keyword' => 'فتح حساب الادخار', 'name' => 'تجاهل فتح ادخار'],
            ['keyword' => 'يغلق حسابك', 'name' => 'تجاهل إغلاق حساب'],
            ['keyword' => 'حالة: تنشيط', 'name' => 'تجاهل حالة تنشيط'],
            ['keyword' => 'كلمة مرور لمرة واحدة', 'name' => 'تجاهل كلمة مرور مؤقتة'],
            ['keyword' => 'إصدار كشف حساب', 'name' => 'تجاهل إصدار كشف'],
            ['keyword' => 'يرجى تحديث البيانات', 'name' => 'تجاهل تحديث بيانات'],
            ['keyword' => 'تم اضافة عملة', 'name' => 'تجاهل إضافة عملة'],
            ['keyword' => 'كلمة مرور', 'name' => 'تجاهل كلمة مرور'],
            ['keyword' => 'تسجيل دخول', 'name' => 'تجاهل تسجيل دخول'],
            ['keyword' => 'جهاز جديد', 'name' => 'تجاهل جهاز جديد'],
            ['keyword' => 'تعريف أمر مستديم', 'name' => 'تجاهل أمر مستديم'],
            ['keyword' => 'رمز التفعيل', 'name' => 'تجاهل رمز تفعيل'],
            ['keyword' => 'ترقية النظام', 'name' => 'تجاهل ترقية نظام'],
            ['keyword' => 'يرجى تحديث', 'name' => 'تجاهل طلب تحديث'],
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
                'name' => null,
                'is_regularExp' => true,
                'replaceWith' => $replace,
                'keyword_type' => 'replace',
                'is_active' => true,
                'sender_id' => null,
            ];
        }

        foreach (self::str_replaceWith() as $str => $replace) {
            $rows[] = [
                'keyword' => $str,
                'name' => null,
                'is_regularExp' => false,
                'replaceWith' => $replace,
                'keyword_type' => 'replace',
                'is_active' => true,
                'sender_id' => null,
            ];
        }

        foreach (self::regex_phoneNumbers() as $item) {
            $rows[] = [
                'keyword' => $item['keyword'],
                'name' => $item['name'],
                'is_regularExp' => true,
                'replaceWith' => null,
                'keyword_type' => 'phone',
                'is_active' => true,
                'sender_id' => null,
            ];
        }

        foreach (self::regex_passcodes() as $item) {
            $rows[] = [
                'keyword' => $item['keyword'],
                'name' => $item['name'],
                'is_regularExp' => true,
                'replaceWith' => null,
                'keyword_type' => 'passcodes',
                'is_active' => true,
                'sender_id' => null,
            ];
        }

        foreach (self::regex_misc() as $item) {
            $rows[] = [
                'keyword' => $item['keyword'],
                'name' => $item['name'],
                'is_regularExp' => true,
                'replaceWith' => null,
                'keyword_type' => 'misc',
                'is_active' => true,
                'sender_id' => null,
            ];
        }

        foreach (self::regex_date() as $item) {
            $rows[] = [
                'keyword' => $item['keyword'],
                'name' => $item['name'],
                'is_regularExp' => true,
                'replaceWith' => null,
                'keyword_type' => 'date',
                'is_active' => true,
                'sender_id' => null,
            ];
        }

        foreach (self::regex_urls() as $item) {
            $rows[] = [
                'keyword' => $item['keyword'],
                'name' => $item['name'],
                'is_regularExp' => true,
                'replaceWith' => null,
                'keyword_type' => 'url',
                'is_active' => true,
                'sender_id' => null,
            ];
        }

        foreach (self::regex_ignoreSMS() as $item) {
            $rows[] = [
                'keyword' => $item['keyword'],
                'name' => $item['name'],
                'is_regularExp' => true,
                'replaceWith' => null,
                'keyword_type' => 'ignore',
                'is_active' => true,
                'sender_id' => null,
            ];
        }

        foreach (self::str_ignoreSMS() as $item) {
            $rows[] = [
                'keyword' => $item['keyword'],
                'name' => $item['name'],
                'is_regularExp' => false,
                'replaceWith' => null,
                'keyword_type' => 'ignore',
                'is_active' => true,
                'sender_id' => null,
            ];
        }

        // breaks as misc strings
        foreach (self::regex_breaks() as $item) {
            $rows[] = [
                'keyword' => $item['keyword'],
                'name' => $item['name'],
                'is_regularExp' => false,
                'replaceWith' => null,
                'keyword_type' => 'breaks',
                'is_active' => true,
                'sender_id' => null,
            ];
        }

        return $rows;
    }
}
