<?php

return [
    'enabled' => (bool) env('BILL_DETECTOR_ENABLED', false),
    'go_back_days' => (int) env('BILL_DETECTOR_GO_BACK_DAYS', 500),
    'min_amount' => (int) env('BILL_DETECTOR_MIN_AMOUNT', 50),
    'transactions_recurring_types' => env('BILL_DETECTOR_TRANSACTIONS_RECURRING_TYPES', 'daily,weekly,monthly,quarterly,half-year,yearly'),
];
