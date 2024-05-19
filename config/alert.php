<?php

return [
    // 'bill_created_user_id' => (bool) env('ALERT_BILL_CREATED_USER_ID', 0),
    'bill_over_amount_percentage' => (int) env('ALERT_BILL_OVER_AMOUNT_PERCENTAGE', 10),
    
    'abnormal_transactions_withdrawal_enabled' => (boolean) env('ALERT_ABNORMAL_TRANSACTIONS_WITHDRAWAL_ENABLED', true),
    'abnormal_transactions_deposit_enabled' => (boolean) env('ALERT_ABNORMAL_TRANSACTIONS_DEPOSIT_ENABLED', true),
    
    'abnormal_transactions_withdrawal_all_percentage' => (int) env('ALERT_ABNORMAL_TRANSACTIONS_WITHDRAWAL_ALL_PERCENTAGE', 10),
    'abnormal_transactions_withdrawal_source_percentage' => (int) env('ALERT_ABNORMAL_TRANSACTIONS_WITHDRAWAL_SOURCE_PERCENTAGE', 10),
    'abnormal_transactions_withdrawal_destination_percentage' => (int) env('ALERT_ABNORMAL_TRANSACTIONS_WITHDRAWAL_DESTINATION_PERCENTAGE', 10),
    'abnormal_transactions_withdrawal_category_percentage' => (int) env('ALERT_ABNORMAL_TRANSACTIONS_WITHDRAWAL_CATEGORY_PERCENTAGE', 10),
    'abnormal_transactions_withdrawal_order' => explode(',', env('ALERT_ABNORMAL_TRANSACTIONS_WITHDRAWAL_ORDER', 'all,source,destination,category')),
    
    'abnormal_transactions_deposit_all_percentage' => (int) env('ALERT_ABNORMAL_TRANSACTIONS_DEPOSIT_ALL_PERCENTAGE', 10),
    'abnormal_transactions_deposit_source_percentage' => (int) env('ALERT_ABNORMAL_TRANSACTIONS_DEPOSIT_SOURCE_PERCENTAGE', 10),
    'abnormal_transactions_deposit_destination_percentage' => (int) env('ALERT_ABNORMAL_TRANSACTIONS_DEPOSIT_DESTINATION_PERCENTAGE', 10),
    'abnormal_transactions_deposit_category_percentage' => (int) env('ALERT_ABNORMAL_TRANSACTIONS_DEPOSIT_CATEGORY_PERCENTAGE', 10),
    'abnormal_transactions_deposit_order' => explode(',', env('ALERT_ABNORMAL_TRANSACTIONS_DEPOSIT_ORDER', 'all,source,destination,category')),

];
