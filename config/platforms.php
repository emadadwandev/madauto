<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform API Configurations
    |--------------------------------------------------------------------------
    |
    | Configuration for delivery platform APIs (Careem, Talabat)
    | Used for menu synchronization and catalog management
    |
    | MULTI-TENANCY IMPORTANT:
    | - API credentials (client_id, client_secret, chain_code) are stored
    |   PER TENANT in the api_credentials database table
    | - .env values here are ONLY for development/testing fallback
    | - Production tenants MUST configure credentials in Settings → API Credentials
    |
    */

    'careem' => [
        'enabled' => env('CAREEM_CATALOG_ENABLED', true),
        'api_url' => env('CAREEM_API_URL', 'https://apigateway-stg.careemdash.com/pos/api/v1'),
        'auth' => [
            'type' => 'oauth2_client_credentials',
            'token_url' => env('CAREEM_TOKEN_URL', 'https://apigateway-stg.careemdash.com/pos/api/v1/token'),
            // ⚠️ DEV ONLY - Production uses tenant-specific credentials from database
            'client_id' => env('CAREEM_CLIENT_ID'),
            'client_secret' => env('CAREEM_CLIENT_SECRET'),
            'scope' => env('CAREEM_SCOPE', 'pos'),
        ],
        'endpoints' => [
            // Catalog API
            'catalog' => '/v1/catalog',  // TODO: Verify with official docs
            'menu' => '/v1/menu',
            'items' => '/v1/items',

            // Store API (for location management)
            'store' => '/stores/{storeId}',  // TODO: Verify with official docs
            'store_status' => '/stores/{storeId}/status',  // TODO: Verify with official docs
            'store_hours' => '/stores/{storeId}/hours',  // TODO: Verify with official docs
        ],
        'sync' => [
            'timeout' => 30,  // seconds
            'retry_attempts' => 3,
            'retry_delay' => 60,  // seconds
        ],
        // Callback URL includes tenant_id for proper routing
        'callback_url_pattern' => env('APP_URL').'/api/callbacks/careem/{tenant_id}',
    ],

    'talabat' => [
        'enabled' => env('TALABAT_CATALOG_ENABLED', true),
        'api_url' => env('TALABAT_API_URL', 'https://integration-middleware.stg.restaurant-partners.com'),
        'auth' => [
            'type' => 'oauth2_client_credentials',
            'token_url' => env('TALABAT_TOKEN_URL', 'https://integration-middleware.stg.restaurant-partners.com/v2/login'),
            // ⚠️ DEV ONLY - Production uses tenant-specific credentials from database
            'client_id' => env('TALABAT_CLIENT_ID'),
            'client_secret' => env('TALABAT_CLIENT_SECRET'),
        ],
        'endpoints' => [
            // Catalog API - Delivery Hero (Talabat) uses chain code
            'catalog' => '/v2/chains/{chainCode}/catalog',
            'catalog_global' => '/v2/chains/{chainCode}/global-entity/{globalEntityId}/catalog',
            'menu_logs' => '/v2/chains/{chainCode}/vendors/{posVendorId}/menu-import-logs',

            // POS Vendor Availability API (for location management)
            'vendor_status' => '/pos/vendors/{vendorId}/status',  // TODO: Verify with official docs
        ],
        'sync' => [
            'timeout' => 60,  // seconds (catalog validation can take time)
            'retry_attempts' => 3,
            'retry_delay' => 120,  // seconds
            'full_catalog_required' => true,  // Talabat requires full catalog push
        ],
        // Callback URL includes tenant_id for proper routing
        'callback_url_pattern' => env('APP_URL').'/api/callbacks/talabat/{tenant_id}',
        // ⚠️ DEV ONLY - Production uses tenant-specific chain_code from database
        'chain_code' => env('TALABAT_CHAIN_CODE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | General Platform Settings
    |--------------------------------------------------------------------------
    */

    'sync_settings' => [
        'queue' => 'platform-sync',  // Dedicated queue for platform syncs
        'log_retention_days' => 90,
        'max_concurrent_syncs' => 5,
    ],

    'image_settings' => [
        'max_size_mb' => 20,  // Max image size for platforms
        'max_pixels' => 16000000,  // 16 megapixels
        'allowed_formats' => ['jpg', 'jpeg', 'png', 'webp', 'svg'],
        'cdn_url' => env('CDN_URL', env('APP_URL').'/storage'),
    ],

];
