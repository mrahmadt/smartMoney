<?php

namespace App\parseSMS\Parsers;
use App\parseSMS\parseSMS;

class parseSAB extends parseSMS{

    public static $transactionTypes = [
        [
            'keywords' => [ 
                'بطاقة إئتمانية: تأكيد السداد',
                'تم خصم دفعة من حسابك', //"/تم خصم دفعة من حسابك (?'source'.*) بمبلغ (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)/",

            ],
            'values'=>[
                'transactionType' => 'Ignore',
            ],
        ]
    ];

    public static $smsKeywords = [
        [
            'regex' => [
"/حوالة صادرة: محلية
من: (?'source'.*)
إلى: \d+ (?<destination>.*)
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
الرسوم: \.?(?'fees'\d{1,}(?:,\d{3})*(?:\.\d+)?)/",

"/حوالة صادرة: محلية
من: (?'source'.*)
إلى: \d+ (?<destination>.*)
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
الرسوم: ?(?'fees'\d{1,}(?:,\d{3})*(?:\.\d+)?)/",

"/حوالة صادرة: محلية
من: (?'source'.*)
إلى: \d+ (?<destination>.*)
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
الرسوم: (?'currencyFees'[\w]+) (?'fees'\d{1,}(?:,\d{3})*(?:\.\d+)?)/",

"/حوالة صادرة مقبولة
من: (?'source'.*)
إلى: (?<destination>.*)
.*
.*
المبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
الرسوم: (?'currencyFees'[\w]+) (?'fees'\d{1,}(?:,\d{3})*(?:\.\d+)?)
/",

"/شراء عبر نقاط البيع
بطاقة: (?'source'[^;]*); .*
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
لدى: (?<destination>.*)
/",

"/شراء عن طريق الإنترنت
بطاقة: (?'source'[^;]*); .*
.*
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
لدى: (?<destination>.*)
/",

"/شراء إنترنت
بطاقة: (?'source'[^;]*);.*
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
لدى: (?<destination>.*)
/",

"/شراء إنترنت
بطاقة: .*;.*
من: (?'source'.*)
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
لدى: (?<destination>.*)
/",

"/سداد فاتورة
من: (?'source'[^;]*); .*
مبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
الجهة: (?'destination'.*)
/",

"/سداد فاتورة
من: (?'source'[^;]*)
مفوتر: \d+ (?'destination'.*)
لخدمة: (?'bill'.*)
رقم الفاتورة: (?'tag'.*)
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
/",

"/شراء عبر نقاط البيع الدولية
بطاقة: (?'source'[^;]*); .*
لدى: (?'destination'.*)
دولة: (?'country'.*)
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
/",

"/حوالة صادرة: داخلية
من: (?'source'.*)
إلى: \d+ (?<destination>.*)
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
/",

            ],
            'values' => [
                'transactionType' => 'Withdrawal',
            ],
        ],
        [
            'regex' => [
"/تم دفع (?'source'راتب) الى حسابك (?'destination'.*) بمبلغ (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) /",
"/بطاقة إئتمانية: إسترداد مبلغ
بطاقة: .*
رقم: (?'destination'.*)
من: (?'source'.*)
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
/",

"/حوالة عكسية
من: (?'source'.*)
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
/",

"/حوالة واردة: داخلية
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
إلى: (?'source'.*)
/",

"/حوالة واردة: محلية
عبر: .*
من: \d+ (?'source'.*)
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
إلى: (?'destination'.*)
/",

"/إيداع حوالة واردة
إلى: (?'destination'.*)
من: (?'source'.*)
الآيبان: .*
.*
المبلغ: (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
/"
            ],
            'values' => [
                'transactionType' => 'Deposit',
                'destination' => '--NA--',
                'source' => '--NA--',
            ],
        ],
        [
            'regex' => [
"/بطاقة إئتمانية: تسديد
بطاقة: (?'destination'[^;]*);.*
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
من: (?'source'.*)
/",
            ],
            'values' => [
                'transactionType' => 'Transfer',
            ],
        ],
    ];


}