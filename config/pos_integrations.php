<?php

return [
    'enforce_production_drivers' => env('APP_ENV') === 'production',
    'outbox' => [
        'max_attempts' => env('POS_OUTBOX_MAX_ATTEMPTS', 8),
    ],
    'payments' => [
        'driver' => env('POS_PAYMENT_DRIVER', 'mock'),
        'mock_fail' => env('POS_PAYMENT_MOCK_FAIL', false),
    ],

    'fiscal' => [
        'driver' => env('POS_FISCAL_DRIVER', 'mock'),
        'issue_on_checkout' => env('POS_FISCAL_ISSUE_ON_CHECKOUT', false),
        'mock_fail' => env('POS_FISCAL_MOCK_FAIL', false),
    ],

    'receipt_printer' => [
        'driver' => env('POS_RECEIPT_PRINTER_DRIVER', 'mock'),
        'print_on_checkout' => env('POS_RECEIPT_PRINT_ON_CHECKOUT', true),
        'mock_fail' => env('POS_RECEIPT_PRINTER_MOCK_FAIL', false),
    ],

    'cash_drawer' => [
        'driver' => env('POS_CASH_DRAWER_DRIVER', 'mock'),
        'open_on_cash_checkout' => env('POS_CASH_DRAWER_OPEN_ON_CASH_CHECKOUT', true),
        'mock_fail' => env('POS_CASH_DRAWER_MOCK_FAIL', false),
    ],

    'barcode_scanner' => [
        'driver' => env('POS_BARCODE_SCANNER_DRIVER', 'mock'),
    ],

    'erp' => [
        'driver' => env('POS_ERP_DRIVER', 'stub'),
    ],
];
