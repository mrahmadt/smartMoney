<?php

return [

    'newTransactionTitle_withdrawal' => '-:amount :currency_symbol :destination_name',
    'newTransactionTitle_deposit' => '+:amount :currency_symbol :source_name',
    'newTransactionTitle_transfer' => ':amount :currency_symbol من :source_name إلى :destination_name',
    'newTransactionMessage_Budget' => "الميزانية: :budget المتبقي: :remaining :currency_code\n\n",
    'newTransactionMessage' => ':description',

    'abnormalTransactionAccount' => 'الحساب ":account"',
    'abnormalTransactionCategory' => 'الفئة ":category"',
    'abnormalTransactionAllTransactions' => 'كل المعاملات',

    'abnormalTransactionTitle' => 'معاملة غير طبيعية',
    'abnormalTransactionMessage' => 'المعاملة ":description" تحتوي على مبلغ غير طبيعي :amount :currency_symbol (:percentage%) مقارنة بالمبلغ المتوسط :average_amount لـ :itemName (:type)',

    'billOverMaxAmountTitle' => 'الفاتورة تتجاوز الحد الأقصى',
    'billOverMaxAmountMessage' => 'الفاتورة: :bill تجاوزت الحد الأقصى :maxAmount :currency_symbol وقد أنفقت :amount :currency_symbol (:billPercentage%)',

];