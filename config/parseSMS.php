<?php

return [

    'enabled' => (bool) env('PARSE_SMS_ENABLED', false),

    'senders' => json_decode(env('PARSE_SMS_SENDERS', '{}'), true),

    'store_invalid_sms' => (bool) env('PARSE_SMS_STORE_INVALID_SMS', false),

    'min_sms_length' => (int) env('PARSE_SMS_MIN_SMS_LENGTH', 30),

    'store_valid_sms' => (bool) env('PARSE_SMS_STORE_VALID_SMS', false),

    'auto_detect_non_transaction_sms' => (bool) env('PARSE_SMS_AUTO_DETECT_NON_TRANSACTION_SMS', true),

    'failback_openAI' => (bool) env('PARSE_SMS_FAILBACK_OPENAI', false),

    'detect_category_openai' => (bool) env('PARSE_SMS_DETECT_CATEGORY_OPENAI', false),

    'clean_processed_sms' => (bool) env('PARSE_SMS_CLEAN_PROCCESSED_SMS', true),
    'clean_invalid_sms' => (bool) env('PARSE_SMS_CLEAN_INVALID_SMS', true),
    'clean_error_sms' => (bool) env('PARSE_SMS_CLEAN_ERROR_SMS', false),

];
