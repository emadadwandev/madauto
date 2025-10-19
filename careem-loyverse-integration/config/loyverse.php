<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Loyverse API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Loyverse POS API integration.
    |
    */

    /**
     * Loyverse API Base URL
     */
    'api_url' => env('LOYVERSE_API_URL', 'https://api.loyverse.com'),

    /**
     * Loyverse API Access Token
     * Note: In production, this should be stored in the database via api_credentials table
     */
    'access_token' => env('LOYVERSE_ACCESS_TOKEN'),

    /**
     * OAuth Configuration (if using OAuth instead of access token)
     */
    'oauth' => [
        'client_id' => env('LOYVERSE_CLIENT_ID'),
        'client_secret' => env('LOYVERSE_CLIENT_SECRET'),
        'redirect_uri' => env('LOYVERSE_REDIRECT_URI'),
    ],

    /**
     * Default Store ID
     */
    'store_id' => env('LOYVERSE_STORE_ID'),

    /**
     * Default POS Device ID
     */
    'pos_device_id' => env('LOYVERSE_POS_DEVICE_ID'),

    /**
     * Default Employee ID
     */
    'employee_id' => env('LOYVERSE_EMPLOYEE_ID'),

    /**
     * Careem Customer ID in Loyverse
     * This is the customer ID for the "Careem" customer that all orders will be attributed to
     */
    'customer_id_careem' => env('LOYVERSE_CUSTOMER_ID_CAREEM'),

    /**
     * Rate Limiting
     * Loyverse API allows 60 requests per minute
     * We set it to 55 to leave a buffer
     */
    'rate_limit_per_minute' => env('LOYVERSE_RATE_LIMIT_PER_MINUTE', 55),

    /**
     * Request Retry Configuration
     */
    'retry' => [
        'max_attempts' => 3,
        'initial_delay' => 100, // milliseconds
        'backoff_multiplier' => 2,
    ],

    /**
     * Cache Configuration (in seconds)
     */
    'cache' => [
        'items' => 3600, // 1 hour
        'stores' => 86400, // 24 hours
        'employees' => 86400, // 24 hours
        'payment_types' => 86400, // 24 hours
        'taxes' => 86400, // 24 hours
        'customers' => 3600, // 1 hour
    ],

    /**
     * Default Receipt Configuration
     */
    'receipt_defaults' => [
        'receipt_type' => 'SALE',
        'source' => 'API',
        'dining_option' => 'DELIVERY',
    ],

    /**
     * Logging Configuration
     */
    'logging' => [
        'enabled' => env('LOYVERSE_LOGGING_ENABLED', true),
        'channel' => env('LOYVERSE_LOG_CHANNEL', 'stack'),
        'log_requests' => env('LOYVERSE_LOG_REQUESTS', false),
        'log_responses' => env('LOYVERSE_LOG_RESPONSES', false),
    ],

];
