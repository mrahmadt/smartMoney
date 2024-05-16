<?php

namespace App\parseSMS\Parsers;

use App\parseSMS\parseSMS;

class parseSTCPay extends parseSMS{


    // Order is important! The first matching will be used
    public static $transactionTypes = [
        [
            'keywords' => [
                'stc bill payment',
                'QR payments',
                'Local Purchase',
                'Online Purchase',
                'debit internal transfer',
                'Debit via international transfer',
                'Debit via wallet transfer',
                'International Purchase',
                'Debit local transfer (SARIE)',
                'sawa recharge',
                'debited with an amount',
            ],
            'values'=>[ // default values
                // set the transaction type to Withdrawal
                'transactionType' => 'Withdrawal',
                'source' => '--STCPay--',
                'destination' => '--STCPay--',
            ],
        ],
        [
            'keywords' => [
                'Incoming internal transfer',
                'Transaction type: wallet topup',
                'Notification: Refund',
                'credit Local transfer',
                'Reverse',
                'Wallet top up via',

            ],
            'config' => [
                // swap between source and destination
                'swapSourceDestination' => false,
            ],
            'values'=>[
                // set the transaction type to Deposit
                'transactionType' => 'Deposit',
                'destination' => '--STCPay--',
                'source' => 'XXXX',
            ],
        ],
    ];
    public static $smsKeywords = [
        [
            //
            'regex' => [
                "/Transaction: (?'source'.*)\s+/",
                "/From: (?'source'.*)\s+Sender account:(?'cardNum'.*)\s+/",
                "/Card Number: (?'source'[^\n;]*)\s+/",
            ],
        ],
        [
            'regex' => [
                "/Amount:\s(?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?(?'currency'[\w]+)?\s+/", //Amount: 23.00 SAR
                "/Amount:\s?(?'currency'[\w]+)\s?(?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s+/", // Amount: SAR 100.00
                "/debited with an amount of (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)\./", // debited with an amount of 169.72 SAR
            ],
        ],
        [
            'regex' => [
                "/debited with an amount of (?'amount'\d{1,}(?:,\d{3})*(?:\.\d+)?) (?'currency'[\w]+)\./", // debited with an amount of 169.72 SAR
            ],
            'values'=>[
                'destination' => '--NA--',
            ],
        ],
        [
            'regex' => [
                "/Fees:\s?(?'currencyFees'[\w]+)\s?(?'fees'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s+/", // Fees: SAR 100.00
                "/Fees:\s?(?'fees'\d{1,}(?:,\d{3})*(?:\.\d+)?)\s?(?'currencyFees'[\w]+)\s+/", // Fees: 100.00 SAR
            ],
        ],
        [
            'regex' => [
                "/Wallet name: (?'destination'.*)\s+/",
                "/Receiver name: (?'destination'.*)\s+/",
                "/(?'destination'stc bill payment)\s+/",
                "/To: (?'destination'.*)\s+receiver account:(?'destinationNum'.*)\s+/",
                "/(?'destination'sawa recharge)\s+/",
                "/At: (?'destination'.*)\s+/",
                "/credit (?'source'Local transfer)\s+/",
                // "/To: (?'destination'.*)\s+/",
                // "/Amount: .*\s+(?'destination'.*)\s+To [A|a]ccount: (?'destinationNum'\w*)\s+/", //Amount: SAR 2,000.00\nAsda sd adasd \nTo account: XXXX1112
            ],
            'config' => [
                'replace' => [
                    // replace below values in destination
                    'destination' => [
                        'stc bill payment' => 'الإتصالات السعودية',
                        'sawa recharge' => 'الإتصالات السعودية',
                        'stc pay' => '--STCPay--',
                    ],
            ]   ,
            ],
        ],
        [
            'regex' => [
                "/Receiver country: (?'country'.*)\s+/",
            ],
        ],
        [
            'regex' => [
                "/Card: ([^;]*); (?'tag'.*)\s+/",
            ],
        ],
        [
            'regex' => [
                "/(?'sadasd'has been closed)/",
            ],
        ],
    ];


}