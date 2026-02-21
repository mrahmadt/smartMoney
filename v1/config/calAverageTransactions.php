<?php

return [

    'withdrawal_enabled' => (bool) env('CAL_AVERAGE_TRANSACTIONS_WITHDRAWAL_ENABLED', false),
    'deposit_enabled' => (bool) env('CAL_AVERAGE_TRANSACTIONS_DEPOSIT_ENABLED', false),

    'months' => (int) env('CAL_AVERAGE_TRANSACTIONS_MONTHS', 6),
    'all_min' => (int) env('CAL_AVERAGE_TRANSACTIONS_ALL_MIN', 100),
    'destination_min' => (int) env('CAL_AVERAGE_TRANSACTIONS_DESTINATION_MIN', 60),
    'source_min' => (int) env('CAL_AVERAGE_TRANSACTIONS_SOURCE_MIN', 60),
    'category_min' => (int) env('CAL_AVERAGE_TRANSACTIONS_CATEGORY_MIN', 60),

];
