<?php

namespace App\parseSMS\Parsers;
use App\parseSMS\parseSMS;

class parseSNBAlAhli extends parseSMS{
    public static $smsKeywords = [
        [
            'regex' => [
"/حوالة صادرة محلية
مبلغ (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
رسوم (?'fees'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currencyFees'[\w]+)
من (?<source>.*)
مستفيد (?<destination>.*)
/",

"/حوالة صادرة داخلية مبلغ:(?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+) مستفيد:(?<destination>.*) إلى:.* في:.*/",
"/حوالة صادرة محلية من:(?<source>.*) إلى:(?<destination>.*) عبر:.* آيبان:.* مبلغ:(?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+) في:.*/",

"/مدفوعات وزارة الداخلية
من (?<source>.*)
مبلغ (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
الجهة (?<destination>.*)/",

"/شراء-POS
بـ(?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
من (?<destination>.*)
إئتمانية (?<source>.*)
/",

"/حوالة صادرة داخلية
مبلغ (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
من (?<source>.*)
مستفيد (?<destination>.*)
/",

"/شراء إنترنت\s?
مبلغ (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)\s?
بطاقة ائتمانية (?<source>.*)\s?
من (?<destination>.*)\s?
/",

"/شراء إنترنت\s?
بطاقة (?<source>.*)\s?
مبلغ (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)\s?
\s?لدى (?<destination>.*)\s? 
/",

"/سحب مبلغ (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)\s?
بطاقة (?<source>.*)\s?
من (?<destination>.*)\s?
/",

"/حوالة صادرة الى (?<destination>.*)\s?
مبلغ (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)(?'currency'[\w]+)\s?
حساب(?<source>.*)\s?
/",

            ],
            'values' => [
                'transactionType' => 'Withdrawal',
                'destination' => '--NA--',
                'source' => '--NA--',
            ],
        ],
        [
            'regex' => [
"/سحب النقدي 
مبلغ (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
من بطاقة إئتمانية (?<source>.*)
/"
            ],
            'values' => [
                'transactionType' => 'Withdrawal',
                'destination' => '--Cash--',
                'source' => '--NA--',
            ],
        ],
        [
            'regex' => [
"/بطاقة ائتمانية تسديد\s?
مبلغ (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)(?'currency'.+)\s?
حساب(?<source>.*)\s?
في.*/"
            ],
            'values' => [
                'transactionType' => 'Withdrawal',
                'destination' => '--Credit Card--',
            ],
        ],
        [
            'regex' => [
"/بطاقة إئتمانية تأكيد سداد
بطاقة (?<destination>.*)
مبلغ (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
في .*
الصرف المتبقي .*/",

"/بطاقة إئتمانية (?<destination>.*)
إسترداد مبلغ
مبلغ (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
من (?'source'.*)
/",

"/ايداع من (?'source'.*)\s?
مبلغ (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)(?'currency'[\w]+)\s?
حساب(?'destination'.*)\s?
/",

"/حوالة واردة من (?'source'.*)\s?
مبلغ (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)(?'currency'[\w]+)\s?
حساب(?'destination'.*)\s?
/",

            ],
            'values' => [
                'transactionType' => 'Deposit',
                'source' => '--Payment--',
            ],
        ],
        [
            'regex' => [
"/حوالة واردة محلية
مبلغ (?'currency'[\w]+) (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) 
مرسل من (?'source'.*)
.*
إلى (?'destination'.*)
/m",

"/حوالة واردة محلية إلى:(?'destination'.*) مبلغ:(?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+) من:(?'source'.*) عبر:.* في:/",

"/استرجاع نقدي لك
في بطاقة (?'destination'.*)
مبلغ(?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)
/",

"/عملية حوالة مالية واردة
إيداع في حساب: (?'destination'.*)
القيمة: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)\s+
من: (?'source'.*)
/",
            ],
            'values' => [
                'transactionType' => 'Deposit',
                'destination' => '--NA--',
                'source' => '--NA--',
            ],
            'config' => [
                'replace' => [
                    // replace below values in destination
                    'currency' => [
                        'ريال' => 'SAR',
                    ],
                ],
            ],
        ],
        [
            'regex' => [
"/سداد فاتورة
مبلغ (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
من (?'source'.*)
مفوتر (?'SadadID'\d+)
فاتورة (?'bill'.*)/
",

"/سداد فاتورة\s?
بطاقة ائتمانية (?'source'.*)\s?
مبلغ (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)\s?
مفوتر (?'SadadID'\d+)\s?
/"
            ],
            'config' => [ // any configuration
                // replace the value of destination with the value of $SadadID_lookup (if exists), $SadadID_lookup is the lookup value of SadadID from $lookupValue
                'overwrite_value' => [ 'destination' => '$SadadID_lookup'],
            ],
            'values' => [
                'transactionType' => 'Withdrawal',
                'destination' => '--NA--',
                'source' => '--NA--',
            ],

            
        ],
//         [
//             'regex' => [
// "/بطاقة إئتمانية: تسديد
// بطاقة: (?'destination'[^;]*);.*
// مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)
// من: (?'source'.*)
// /",
//             ],
//             'values' => [
//                 'transactionType' => 'Transfer',
//             ],
//         ],
    ];


}