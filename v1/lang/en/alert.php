<?php

return [
    'newTransactionTitle_withdrawal' => '-:amount :currency_symbol :destination_name',
    'newTransactionTitle_deposit' => '+:amount :currency_symbol :source_name',
    'newTransactionTitle_transfer' => ':amount :currency_symbol :source_name to :destination_name',
    'newTransactionMessage_Budget' => "Budget: :budget Remaining: :remaining :currency_code\n\n",
    'newTransactionMessage' => ':description',

    'abnormalTransactionAccount' => 'account ":account"',
    'abnormalTransactionCategory' => 'category ":category"',
    'abnormalTransactionAllTransactions' => 'all transactions',

    'abnormalTransactionTitle' => 'Abnormal Transaction',
    'abnormalTransactionMessage' => 'Transaction: ":description" has an abnormal amount of :amount :currency_symbol (:percentage%) compared to the average amount of :average_amount for :itemName (:type)',

    'billOverMaxAmountTitle' => 'Bill Over Max Amount',
    'billOverMaxAmountMessage' => 'Bill: :bill has a max amount of :maxAmount :currency_symbol and you have spent :amount :currency_symbol (:billPercentage%)',

];
