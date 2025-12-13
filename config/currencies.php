<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of currencies supported by the platform. Each currency includes:
    | - code: ISO 4217 currency code
    | - name: Full currency name
    | - symbol: Currency symbol for display
    | - symbol_position: 'before' or 'after' the amount
    | - decimal_separator: Character for decimal separation
    | - thousands_separator: Character for thousands separation
    | - decimals: Number of decimal places
    |
    */

    'supported' => [
        'AED' => [
            'code' => 'AED',
            'name' => 'UAE Dirham',
            'symbol' => 'د.إ',
            'symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ],
        'USD' => [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ],
        'EUR' => [
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
            'symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ],
        'GBP' => [
            'code' => 'GBP',
            'name' => 'British Pound',
            'symbol' => '£',
            'symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ],
        'SAR' => [
            'code' => 'SAR',
            'name' => 'Saudi Riyal',
            'symbol' => 'ر.س',
            'symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ],
        'KWD' => [
            'code' => 'KWD',
            'name' => 'Kuwaiti Dinar',
            'symbol' => 'د.ك',
            'symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 3,
        ],
        'BHD' => [
            'code' => 'BHD',
            'name' => 'Bahraini Dinar',
            'symbol' => 'د.ب',
            'symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 3,
        ],
        'OMR' => [
            'code' => 'OMR',
            'name' => 'Omani Rial',
            'symbol' => 'ر.ع',
            'symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 3,
        ],
        'QAR' => [
            'code' => 'QAR',
            'name' => 'Qatari Riyal',
            'symbol' => 'ر.ق',
            'symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ],
        'EGP' => [
            'code' => 'EGP',
            'name' => 'Egyptian Pound',
            'symbol' => 'ج.م',
            'symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ],
        'JOD' => [
            'code' => 'JOD',
            'name' => 'Jordanian Dinar',
            'symbol' => 'د.ا',
            'symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 3,
        ],
        'INR' => [
            'code' => 'INR',
            'name' => 'Indian Rupee',
            'symbol' => '₹',
            'symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ],
        'PKR' => [
            'code' => 'PKR',
            'name' => 'Pakistani Rupee',
            'symbol' => '₨',
            'symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency to use when no tenant-specific currency is set.
    |
    */

    'default' => env('DEFAULT_CURRENCY', 'AED'),

];
