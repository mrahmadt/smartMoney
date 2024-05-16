<?php

namespace App\parseSMS\Parsers;

use App\parseSMS\parseSMS;

class parseAlinmaBank extends parseSMS{
    public static $smsKeywords = [
        [
            'regex' => [

"/شراء عبر نقاط البيع مدى أثير\s?
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)\s?
بطاقة مدى: .*\s?
حساب: (?'source'.*)\s?
من: (?'destination'.*)\s?
/",

"/شراء عبر نقاط البيع مدى أثير\nمبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)\nبطاقة مدى: .*\nحساب: (?'source'.*)\nمن: (?'destination'.*)\nفي: .*/",

"/سداد فاتورة\nرقم: \d+\nمبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)\nالجهة: (?'destination'.*)\nالخدمة:.*\nمن حساب: (?'source'.+)/",

"/تحويل (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)(?'currency'.+) إلى (?'destination'.*) من حساب
(?'source'.*) في/",

"/حوالة صادرة مكفول\s?
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)\s?
المستفيد: (?'destination'.*)\s?
.*\s?
من حساب: (?'source'.*)\s?/",

"/حوالة صادرة مكفول\s?
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)\s?
المستفيد: (?'destination'.*)\s?
من حساب: (?'source'.*)\s?/",

"/حوالة صادرة محلية
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)
المستفيد: (?'destination'.*)
إلى حساب: .*
.*
الرسوم: (?'fees'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currencyFees'.+)/",


"/حوالة صادرة داخلية
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)
المستفيد: (?'destination'.*)
إلى حساب: .*
من حساب: (?'source'.*)/",

"/سداد مدفوعات (?'destination'.*)
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)
حساب: (?'source'.*)
/",

"/عميلنا العزيز
تم الاشتراك في (?'destination'.*)
مبلغ الاشتراك: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)
العملة: (?'currency'.+)/",

"/شراء إنترنت
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)
.*
من حساب: (?'source'.*)
من: (?'destination'.*)
/",

"/حوالة صادرة داخلية
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)
المستفيد: (?'destination'.*)
من حساب: (?'source'.*)
/",

"/عملية حوالة مالية صادرة مقبولة
خصمت من حساب: (?'source'.*)
إلى: (?'destination'.*)
أيبان : .*
القيمة: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)
الرسوم: (?'fees'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currencyFees'.+)
/",
            ],
            'values' => [
                'transactionType' => 'Withdrawal',
                'destination' => '--NA--',
                'source' => '--NA--',
            ],
            'config' => [
                'replace' => [
                    // replace below values in destination
                    'currency' => [
                        'ريال' => 'SAR',
                    ],
                    'currencyFees' => [
                        'ريال' => 'SAR',
                    ],
                ],
            ],
        ],
        [
            'regex' => [

"/حوالة واردة محلية
المبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)
لحساب: (?'destination'.*)
من: (?'source'.*)
/",

"/إيداع عائد (?'source'.*)
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)
للحساب:(?'destination'.*)/",

"/حوالة واردة محلية سريع
المبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)
من: (?'source'.*)
/",

"/حوالة واردة محلية\s?
المبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)\s?
لحساب: (?'destination'.*)\s?
من: (?'source'.*)\s?/",

"/حوالة واردة محلية \(سريع\)\s?
الى حساب: (?'destination'.*)\s?
المبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)\s?
من: (?'source'.*)\s?
/",

"/عملية حوالة واردة محلية
أودعت الى حساب: (?'destination'.*)
القيمة: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)
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
"/حوالة بين حساباتك
صادرة من: (?'source'.*)
واردة إلى: (?'destination'.*)
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)
/"
            ],
            'values' => [
                'transactionType' => 'Transfer',
                'destination' => '--NA--',
                'source' => '--NA--',
            ],
        ],
        [
            'regex' => [

"/سحب صراف آلى
من بطاقة مدى: (?'source'.*)
مبلغ: (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'.+)/",
                
            ],
            'values' => [
                'transactionType' => 'Withdrawal',
                'destination' => '--Cash--',
                'source' => '--NA--',
            ],
        ],
        [
            'regex' => [
"/تم إيداع حوالة محلية\s?
خصمت من حساب: (?'source'.*)\s?
إلى: .*\s?
آيبان: .*\s?
القيمة: .*\s?
/",
            ],
            'values' => [
                'transactionType' => 'ignore',
            ],
        ]
    ];


}