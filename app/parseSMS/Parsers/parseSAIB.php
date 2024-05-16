<?php

namespace App\parseSMS\Parsers;
use App\parseSMS\parseSMS;

class parseSAIB extends parseSMS{


    public static $smsKeywords = [
        [
            'regex' => [
"/شراء عبر نقاط البيع دولي\s?
بطاقة: سفر\(Apple Pay\) [;]?(?'source'.*)\s?
لدى: (?'destination'.*)\s?
دولة: (?'country'.*)\s?
من: .*\s?
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s{0,1}(?'currency'[\w]+)\s?
/",

"/شراء عبر نقاط البيع دولي\s?
بطاقة: سفر\s?(;)?\s?(?'source'.*)\s?
لدى: (?'destination'.*)\s?
دولة: (?'country'.*)\s?
من: .*\s?
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s{0,1}(?'currency'[\w]+)\s?
/",


"/شراء عبر نقاط البيع دولي
بطاقة: ([^;].*); (?'source'.*)
لدى: (?'destination'.*)\s?
دولة: (?'country'.*)\s?
من: .*\s?
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
/",


"/شراء عبر نقاط البيع دولي
بطاقة: (?'source'[^;].*);.*
لدى: (?'destination'.*)\s?
دولة: (?'country'.*)\s?
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)\s?
/",



"/شراء عبر نقاط البيع\s?
بطاقة: .*\(Apple Pay\) (?'source'[^;].*)\s?
من: .*\s?
مبلغ: (?'currency'[\w]+)\s? (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
لدى: (?'destination'.*)\s?
/",

"/شراء عبر نقاط البيع
بطاقة: .* \(Apple Pay\) ;.*
من: (?'source'.*)
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
لدى: (?'destination'.*)\s?
/",

"/شراء عبر نقاط البيع
بطاقة: (?'source'[^;].*);.*
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
لدى: (?'destination'.*)\s?
/",

"/شراء عبر نقاط البيع\s?
بطاقة: .*\s?
من: (?'source'.*)\s?
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
لدى: (?'destination'.*)\s?
/",


"/بطاقة ائتمانية: تسديد
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
من: (?'source'.*)\s?
/",

"/شراء إنترنت
بطاقة: (?'source'[^;].*);ائتمانية.*
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
لدى: (?'destination'.*)
/",


"/شراء انترنت\s?
بطاقة:(?'source'.*) سفر.*\s?
من: .*\s?
مبلغ: (?'currency'[\w]+)(\s:)? (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
لدى: (?'destination'.*)\s?
/",


"/شراء انترنت
بطاقة: .* مدى \(Apple Pay\)\s?
من: (?'source'.*)\s?
مبلغ: (?'currency'[\w]+) (:\s)?(?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
لدى: (?'destination'.*)\s?
/",

"/شراء انترنت
بطاقة: .* مدى.*\s?
من: (?'source'.*)\s?
مبلغ: (?'currency'[\w]+) (:\s)?(?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
لدى: (?'destination'.*)\s?
/",

"/شراء انترنت
بطاقة: مدى; .*\s?
من: (?'source'.*)\s?
مبلغ: (?'currency'[\w]+) (:\s)?(?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
لدى: (?'destination'.*)\s?
/",


"/PoS Purchase
By: (?'source'[^;].*);Credit Card.*
Amount: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
At: (?'destination'.*)
/",

"/حوالة صادرة: محلية\(مقبوله\)
من: (?'source'.*)
عبر: .*
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
(?'destination'.*)
/",

"/حوالة صادرة: محلية
من: (?'source'.*)
الى: (?'destination'.*)
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
رسوم: (?'currencyFees'[\w]+) (?'fees'\d{1,}(?:,\d{3})*(?:\.\d+)?)
/",

"/حوالة صادرة: محلية
من: (?'source'.*)
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
/",


"/حوالة صادرة: داخلية
من: (?'source'.*)
الى: (?'destination'.*)
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
/",


"/حوالة صادرة: داخلية
من: (?'source'.*)
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
/",


"/سداد فاتورة\s?
من: (?'source'[^;].*);.*\s?
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)\s?
مفوتر: (?'SadadID'\d+)\s?
/",


"/سداد فاتورة\s?
من: (?'source'.*)\s?
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
مفوتر:\s?[:]? (?'SadadID'\d+)\s?
/",

"/سداد فاتورة لمرة واحدة
من: (?'source'.*)\s?
مفوتر: (?'SadadID'\d+)\s?
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
/",

"/خصم:(?'destination'.*)
السبب: (?'description'.*)
من: (?'source'.*)
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
/",

"/اضافة مبلغ ل(?'destination'.*)
من حساب ائتماني:(?'source'.*)
مبلغ:(?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
/",

"/اضافة مبلغ ل(?'destination'.*)
من:(?'source'.*)
مبلغ:(?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
/",

"/مدفوعات وزارة الداخلية
من: (?'source'.*)
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
الجهة: (?'destination'.*)
/",

"/تسديد (?'destination'.*) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+) في .*\s?
حساب (?'source'.*)/",

"/امر مستديم حوالة صادرة: .*\s?
من: (?'source'.*)\s?
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
الى: (?'destination'.*)\s?
/",
            ],
            'values' => [
                'transactionType' => 'Withdrawal',
                'destination' => '--NA--',
                'source' => '--NA--',
            ],
            'config' => [ // any configuration
                // replace the value of destination with the value of $SadadID_lookup (if exists), $SadadID_lookup is the lookup value of SadadID from $lookupValue
                'overwrite_value' => [ 'destination' => '$SadadID_lookup'],
            ]
        ],
[
    'regex' => [    
"/سحب صراف\s?
في .*\s?
بطاقةمدى (?'source'.*)\s?
مبلغ:(?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
/",


"/سحب: صراف آلي\s?
بطاقة: ;.* مدى\s?
من: (?'source'.*)\s?
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
/",

    ],
    'values' => [
        'transactionType' => 'Withdrawal',
        'destination' => '--Cash--',
        'source' => '--NA--',
    ],
],
[
    'regex' => [
"/بطاقة إئتمانية: تأكيد السداد
بطاقة: (?'destination'[^;].*);ائتمانية
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
/",

"/حوالة واردة: محلية \(مقبوله\)
من: .*\s?
(?'source'.*)\s?
عبر: .*\s?
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
الى: (?'destination'.*)\s?
/",

"/حوالة واردة: محلية\s?
عبر: .*\s?
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
الى: (?'destination'.*)\s?
من: (?'source'.*)\s?
/",


"/حوالة واردة: محلية\s?
عبر: .*\s?
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
الى: (?'destination'.*)\s?
/",


"/حوالة واردة: داخلية
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
الى: (?'destination'.*)\s?
من: (?'source'.*)\s?
/",


"/عكس عملية
بطاقة: (?'destination'[^;].*);.*
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
لدى: (?'source'.*)
/",

"/عكس عملية
بطاقة: (?'destination'[^;].*);.*\s?
لدى: (?'source'.*)\s?
دولة: (?'country'.*)\s?
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)\s?
/",

"/عكس عملية\s?
بطاقة: سفر; (?'destination'.*)\s?
لدى: (?'source'.*)\s?
دولة: (?'country'.*)\s?
من: .*\s?
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
/",

"/عكس عملية\s?
بطاقة: .* ;(?'destination'.*)\s?
(?'source'.*)\s?
لدى: (?'country'.*) :دولة
من: .*\s?
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
/",



"/استرداد مبلغ\s?
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
لدى: (?'source'.*)\s?
بطاقة: سفر (?'destination'.*)\s?
دولة: (?'country'.*)\s?
/",

"/استرجاع مبلغ من (?'source'.*)
الى حساب ائتماني:(?'destination'.*)
مبلغ:(?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
/",



"/استرجاع مدفوعات (?'source'.*)
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
الى: (?'destination'.*)
/",

"/حوالة واردة: (?'source'راتب)\s?
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?
الى: (?'destination'.*)\s?
/",


"/حوالة واردة: (?'source'راتب)\s?
مبلغ: (?'currency'[\w]+) (?'amount'.*)
الى: (?'destination'.*)\s?
/",


    ],
    'values' => [
        'transactionType' => 'Deposit',
        'destination' => '--NA--',
        'source' => '--NA--',
    ],
],
    ];


}