<?php

namespace App\Services;

use App\Exceptions\PlatformApiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Careem Now API Service
 * Handles webhook registration and client configuration with Careem Now platform
 *
 * API Documentation: https://docs.careemnow.com/
 */
class CareemApiService
{
    protected string $baseUrl;

    protected string $tokenUrl;

    protected ?string $clientId = null;

    protected ?string $clientSecret = null;

    protected ?string $clientName = null;

    protected string $scope;

    protected string $userAgent;

    protected int $timeout;

    protected $tenant;

    /**
     * Initialize service with tenant-specific credentials
     *
     * @throws \Exception If tenant credentials are not configured
     */
    public function __construct(string|int|null $tenantId = null)
    {
        $this->timeout = config('platforms.careem.sync.timeout', 30);
        $this->scope = config('platforms.careem.auth.scope', 'pos');
        $this->baseUrl = config('platforms.careem.api_url', 'https://pos-stg.careemdash-internal.com');
        $this->tokenUrl = config('platforms.careem.auth.token_url', 'https://identity.qa.careem-engineering.com/token');

        // Load tenant-specific credentials from api_credentials table (REQUIRED for SaaS)
        if ($tenantId) {
            // Load tenant object for logging
            $this->tenant = \App\Models\Tenant::find($tenantId);
            $credentials = $this->loadTenantCredentials($tenantId);

            if (empty($credentials) || ! isset($credentials['client_id']) || ! isset($credentials['client_secret'])) {
                throw new \Exception('Careem API credentials not configured for this tenant. Please configure Client ID and Client Secret in Settings → API Credentials.');
            }

            $this->clientId = $credentials['client_id'];
            $this->clientSecret = $credentials['client_secret'];
            $this->clientName = $credentials['client_name'] ?? null;
            $this->baseUrl = $credentials['api_url'] ?? $this->baseUrl;
            $this->tokenUrl = $credentials['token_url'] ?? $this->tokenUrl;

            // Store user_agent for tenant-specific identification (optional, falls back to config)
            $this->userAgent = $credentials['user_agent'] ?? config('platforms.careem.user_agent', 'loyverse-integration/1.0');
        } else {
            $this->tenant = null;

            // Fallback to .env only for development/testing (not recommended for production)
            $this->clientId = config('platforms.careem.auth.client_id');
            $this->clientSecret = config('platforms.careem.auth.client_secret');
            $this->clientName = config('platforms.careem.auth.client_name');
            $this->userAgent = config('platforms.careem.user_agent', 'loyverse-integration/1.0');

            // Allow empty credentials during app bootstrap (migrations, etc)
            if (!app()->runningInConsole() && (empty($this->clientId) || empty($this->clientSecret))) {
                throw new \Exception('Careem API credentials not configured. Please configure tenant-specific credentials in Settings.');
            }
        }
    }

    /**
     * Load tenant-specific Careem credentials from database
     */
    protected function loadTenantCredentials(string|int $tenantId): array
    {
        $credentials = \App\Models\ApiCredential::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('service', 'careem_catalog')
            ->where('is_active', true)
            ->get();

        $result = [];
        foreach ($credentials as $cred) {
            $result[$cred->credential_type] = $cred->credential_value;
        }

        return $result;
    }

    /**
     * Get OAuth2 access token using client credentials flow
     *
     * @return string Access token
     *
     * @throws PlatformApiException
     */
    protected function getAccessToken(): string
    {
        $cacheKey = "careem_token_{$this->clientId}";

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            try {
                Log::info('Requesting Careem access token', [
                    'client_id' => $this->clientId,
                    'token_url' => $this->tokenUrl,
                ]);

                $response = Http::timeout($this->timeout)
                    ->asForm()
                    ->post($this->tokenUrl, [
                        'grant_type' => 'client_credentials',
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'scope' => $this->scope,
                    ]);

                if (! $response->successful()) {
                    throw new PlatformApiException(
                        'Careem',
                        'Failed to obtain access token: '.$response->body(),
                        $response->status()
                    );
                }

                $data = $response->json();

                if (! isset($data['access_token'])) {
                    throw new PlatformApiException(
                        'Careem',
                        'No access token in response: '.$response->body()
                    );
                }

                Log::info('Careem access token obtained successfully');

                return $data['access_token'];

            } catch (\Exception $e) {
                Log::error('Careem OAuth2 authentication failed', [
                    'error' => $e->getMessage(),
                    'client_id' => $this->clientId,
                ]);

                throw $e;
            }
        });
    }

    /**
     * Submit catalog to Careem
     *
     * @param  array  $catalogData  Catalog structure
     * @param  string  $brandId  Brand ID (REQUIRED)
     * @param  string  $branchId  Branch ID (REQUIRED)
     * @return array Response with catalog ID and status
     *
     * @throws PlatformApiException
     */
    public function submitCatalog(array $catalogData, string $brandId, string $branchId): array
    {
        $token = $this->getAccessToken();

        // Use base /catalogs endpoint as per official Careem API documentation
        // The catalog ID is sent in the payload, NOT in the URL path
        $endpoint = config('platforms.careem.endpoints.catalogs', '/catalogs');
        $url = $this->baseUrl.$endpoint;

        Log::info('Submitting catalog to Careem', [
            'catalog_id' => $catalogData['catalog']['id'] ?? null,
            'brand_id' => $brandId,
            'branch_id' => $branchId,
            'categories_count' => count($catalogData['categories'] ?? []),
            'items_count' => count($catalogData['items'] ?? []),
            'groups_count' => count($catalogData['groups'] ?? []),
            'options_count' => count($catalogData['options'] ?? []),
            'url' => $url,
            'payload_size_kb' => round(strlen(json_encode($catalogData)) / 1024, 2),
        ]);

        // Log full payload in debug mode for troubleshooting
        if (config('app.debug')) {
            Log::debug('Full Careem catalog payload', [
                'catalog_id' => $catalogData['catalog']['id'] ?? null,
                'payload' => $catalogData,
            ]);
        }

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => $this->userAgent,
                'Brand-Id' => $brandId,  // REQUIRED by Careem API
                'Branch-Id' => $branchId,  // REQUIRED by Careem API
            ];

            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders($headers)
                ->put($url, $catalogData);

            if ($response->successful()) {
                $result = $response->json();

                Log::info('Careem catalog submitted successfully', [
                    'catalog_id' => $result['catalog_id'] ?? $result['id'] ?? null,
                    'brand_id' => $brandId,
                    'branch_id' => $branchId,
                    'status_code' => $response->status(),
                    'response_body' => $result,
                ]);

                // Save catalog items to local database for order display
                $itemsSaved = 0;
                if ($this->tenant) {
                    $actualCatalogId = $catalogData['catalog']['id'] ?? null;
                    if ($actualCatalogId) {
                        $itemsSaved = $this->saveCatalogItemsLocally($catalogData, $this->tenant->id, $actualCatalogId);
                    }
                }

                return [
                    'success' => true,
                    'status' => 'accepted',
                    'catalog_id' => $result['catalog_id'] ?? $result['id'] ?? null,
                    'message' => 'Catalog submitted successfully.',
                    'data' => $result,
                    'http_status' => $response->status(),
                    'raw_response' => $result,
                    'items_saved_locally' => $itemsSaved,
                ];
            }

            // Handle errors
            $errorBody = $response->json();

            Log::error('Careem catalog submission failed', [
                'status' => $response->status(),
                'status_text' => $response->reason(),
                'error' => $errorBody,
                'raw_body' => $response->body(),
                'brand_id' => $brandId,
                'branch_id' => $branchId,
                'catalog_id' => $catalogData['catalog']['id'] ?? null,
                'headers_sent' => $headers,
                'url' => $url,
                'payload_summary' => [
                    'categories_count' => count($catalogData['categories'] ?? []),
                    'items_count' => count($catalogData['items'] ?? []),
                    'groups_count' => count($catalogData['groups'] ?? []),
                    'options_count' => count($catalogData['options'] ?? []),
                ],
            ]);

            // Log full failed payload in debug mode
            if (config('app.debug')) {
                Log::debug('Failed Careem catalog payload', [
                    'payload' => $catalogData,
                ]);
            }

            $exception = new PlatformApiException(
                'Careem',
                'Catalog submission failed: '.($errorBody['message'] ?? $response->body()),
                $response->status()
            );
            $exception->setResponse([
                'status' => $response->status(),
                'body' => $errorBody,
                'raw_body' => $response->body(),
            ]);
            throw $exception;

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Careem API request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new PlatformApiException(
                'Careem',
                'API request failed: '.$e->getMessage()
            );
        }
    }

    /**
     * Save catalog items to local database after successful push to Careem
     *
     * @param  array  $catalogData  The catalog data that was pushed
     * @param  string  $tenantId  Tenant ID
     * @param  string  $catalogId  Catalog ID
     * @return int Number of items saved
     */
    protected function saveCatalogItemsLocally(array $catalogData, string $tenantId, string $catalogId): int
    {
        if (empty($catalogData['items'])) {
            return 0;
        }

        $saved = 0;
        foreach ($catalogData['items'] as $item) {
            try {
                \App\Models\CareemCatalogItem::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'careem_item_id' => $item['item_id'] ?? $item['id'],
                    ],
                    [
                        'careem_catalog_id' => $catalogId,
                        'name' => $item['title'] ?? $item['name'] ?? 'Unknown',
                        'description' => $item['description'] ?? null,
                        'sku' => $item['sku'] ?? null,
                        'price' => $item['price'] ?? 0,
                        'currency' => $item['currency'] ?? 'AED',
                        'category_id' => $item['category_id'] ?? null,
                        'is_available' => $item['available'] ?? $item['is_available'] ?? true,
                        'is_active' => $item['is_active'] ?? true,
                        'image_url' => $item['image_url'] ?? null,
                        'modifier_group_ids' => $item['group_ids'] ?? $item['modifier_group_ids'] ?? null,
                        'external_id' => $item['external_id'] ?? null,
                        'raw_data' => $item,
                    ]
                );
                $saved++;
            } catch (\Exception $e) {
                Log::error('Failed to save catalog item locally', [
                    'item_id' => $item['item_id'] ?? $item['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Saved catalog items locally', [
            'tenant_id' => $tenantId,
            'catalog_id' => $catalogId,
            'items_saved' => $saved,
        ]);

        return $saved;
    }

    /**
     * Get catalog sync status
     *
     * @param  string  $requestId  Request ID returned from catalog submission
     * @param  string  $brandId  Brand ID (REQUIRED by Careem API)
     * @param  string  $branchId  Branch ID (REQUIRED by Careem API)
     * @return array Status information
     *
     * @throws PlatformApiException
     */
    public function getCatalogStatus(string $requestId, string $brandId, string $branchId): array
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{request_id}', $requestId, config('platforms.careem.endpoints.catalog_status'));
        $url = $this->baseUrl.$endpoint;

        Log::debug('Checking Careem catalog status', [
            'request_id' => $requestId,
            'brand_id' => $brandId,
            'branch_id' => $branchId,
            'url' => $url,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Brand-Id' => $brandId,  // REQUIRED by Careem API
                    'Branch-Id' => $branchId,  // REQUIRED by Careem API
                    'Accept' => 'application/json',
                ])
                ->get($url);

            if ($response->successful()) {
                $result = $response->json();

                Log::info('Careem catalog status retrieved successfully', [
                    'request_id' => $requestId,
                    'status' => $result['status'] ?? 'unknown',
                    'response' => $result,
                ]);

                return $result;
            }

            throw new PlatformApiException(
                'Careem',
                'Failed to get catalog status: '.$response->body(),
                $response->status()
            );

        } catch (\Exception $e) {
            Log::error('Careem catalog status check failed', [
                'request_id' => $requestId,
                'brand_id' => $brandId,
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get branch visibility status
     *
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @return array Branch visibility information
     *
     * @throws PlatformApiException
     */
    public function getBranchVisibility(string $brandId, string $branchId): array
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{branch_id}', $branchId, config('platforms.careem.endpoints.branch_visibility'));
        $url = $this->baseUrl.$endpoint;

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
                ])
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            throw new PlatformApiException(
                'Careem',
                'Failed to get branch visibility: '.$response->body(),
                $response->status()
            );

        } catch (\Exception $e) {
            Log::error('Careem branch visibility check failed', [
                'brand_id' => $brandId,
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update existing catalog
     *
     * @param  string  $catalogId  Catalog ID
     * @param  array  $catalogData  Updated catalog data
     * @return array Response
     *
     * @throws PlatformApiException
     */
    public function updateCatalog(string $catalogId, array $catalogData): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.catalog');
        $url = $this->baseUrl.$endpoint.'/'.$catalogId;

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->put($url, $catalogData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Catalog updated successfully.',
                    'data' => $response->json(),
                ];
            }

            throw new PlatformApiException(
                'Careem',
                'Catalog update failed: '.$response->body(),
                $response->status()
            );

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PlatformApiException(
                'Careem',
                'Catalog update failed: '.$e->getMessage()
            );
        }
    }

    /**
     * Delete catalog
     *
     * @param  string  $catalogId  Catalog ID
     * @return bool Success status
     *
     * @throws PlatformApiException
     */
    public function deleteCatalog(string $catalogId): bool
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.catalog');
        $url = $this->baseUrl.$endpoint.'/'.$catalogId;

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->delete($url);

            if ($response->successful()) {
                return true;
            }

            throw new PlatformApiException(
                'Careem',
                'Catalog deletion failed: '.$response->body(),
                $response->status()
            );

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PlatformApiException(
                'Careem',
                'Catalog deletion failed: '.$e->getMessage()
            );
        }
    }

    /**
     * Update store status (active/inactive, busy)
     *
     * @param  string  $storeId  Careem store/branch ID
     * @param  bool  $isActive  Whether store is active
     * @param  bool  $isBusy  Whether store is busy
     * @return array Response
     *
     * @throws PlatformApiException
     */
    public function updateStoreStatus(string $storeId, bool $isActive, bool $isBusy): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.store_status', '/stores/{storeId}/status');
        $url = $this->baseUrl.str_replace('{storeId}', $storeId, $endpoint);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->patch($url, [
                    'is_active' => $isActive,
                    'is_busy' => $isBusy,
                ]);

            if ($response->successful()) {
                Log::info('Careem store status updated', [
                    'store_id' => $storeId,
                    'is_active' => $isActive,
                    'is_busy' => $isBusy,
                ]);

                return [
                    'success' => true,
                    'message' => 'Store status updated successfully.',
                    'data' => $response->json(),
                ];
            }

            throw new PlatformApiException(
                'Careem',
                'Store status update failed: '.$response->body(),
                $response->status()
            );

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PlatformApiException(
                'Careem',
                'Store status update failed: '.$e->getMessage()
            );
        }
    }

    /**
     * Update store operating hours
     *
     * @param  string  $storeId  Careem store/branch ID
     * @param  array  $hours  Operating hours in Careem format
     * @return array Response
     *
     * @throws PlatformApiException
     */
    public function updateStoreHours(string $storeId, array $hours): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.store_hours', '/stores/{storeId}/hours');
        $url = $this->baseUrl.str_replace('{storeId}', $storeId, $endpoint);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->put($url, [
                    'operating_hours' => $hours,
                ]);

            if ($response->successful()) {
                Log::info('Careem store hours updated', [
                    'store_id' => $storeId,
                    'hours_count' => count($hours),
                ]);

                return [
                    'success' => true,
                    'message' => 'Store hours updated successfully.',
                    'data' => $response->json(),
                ];
            }

            throw new PlatformApiException(
                'Careem',
                'Store hours update failed: '.$response->body(),
                $response->status()
            );

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PlatformApiException(
                'Careem',
                'Store hours update failed: '.$e->getMessage()
            );
        }
    }

    /**
     * Get store information from Careem
     *
     * @param  string  $storeId  Careem store/branch ID
     * @return array Store information
     *
     * @throws PlatformApiException
     */
    public function getStore(string $storeId): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.store', '/stores/{storeId}');
        $url = $this->baseUrl.str_replace('{storeId}', $storeId, $endpoint);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            throw new PlatformApiException(
                'Careem',
                'Failed to retrieve store information: '.$response->body(),
                $response->status()
            );

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PlatformApiException(
                'Careem',
                'Failed to retrieve store information: '.$e->getMessage()
            );
        }
    }

    /**
     * Test API connection and authentication
     *
     * @return bool True if connection successful
     *
     * @throws \Exception If connection fails
     */
    public function testConnection(): bool
    {
        // Test OAuth2 authentication
        $this->getAccessToken();

        Log::info('Careem API connection test successful', [
            'client_id' => $this->clientId,
        ]);

        return true;
    }

    // ============================================================================
    // BRAND API METHODS
    // ============================================================================

    /**
     * Create a new brand
     *
     * @param  string  $brandId  A unique brand ID string (e.g., "KFC")
     * @param  string  $name  Brand name (e.g., "KFC")
     * @return array Response with brand details
     *
     * @throws PlatformApiException
     */
    public function createBrand(string $brandId, string $name): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.brands');

        Log::info('Creating Careem brand', [
            'brand_id' => $brandId,
            'name' => $name,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
                ])
                ->post($this->baseUrl.$endpoint, [
                    'id' => $brandId,
                    'name' => $name,
                ]);

            if (! $response->successful()) {
                throw new PlatformApiException(
                    'Careem',
                    'Brand creation failed: '.$response->body(),
                    $response->status()
                );
            }

            Log::info('Careem brand created successfully', [
                'brand_id' => $brandId,
                'response' => $response->json(),
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Careem brand creation failed', [
                'brand_id' => $brandId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get brand details by ID
     *
     * @param  string  $brandId  Brand ID
     * @return array Brand details
     *
     * @throws PlatformApiException
     */
    public function getBrand(string $brandId): array
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{brand_id}', $brandId, config('platforms.careem.endpoints.brand_detail'));

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
                ])
                ->get($this->baseUrl.$endpoint);

            if (! $response->successful()) {
                throw new PlatformApiException(
                    'Careem',
                    'Failed to fetch brand: '.$response->body(),
                    $response->status()
                );
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Careem brand fetch failed', [
                'brand_id' => $brandId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * List all brands with pagination
     *
     * @param  int  $pageNumber  Page number (default: 1)
     * @param  int  $pageSize  Results per page (default: 20, max: 20)
     * @return array Paginated brand list
     *
     * @throws PlatformApiException
     */
    public function listBrands(int $pageNumber = 1, int $pageSize = 20): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.brands');

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
                ])
                ->get($this->baseUrl.$endpoint, [
                    'page_number' => $pageNumber,
                    'page_size' => min($pageSize, 20),
                ]);

            if (! $response->successful()) {
                throw new PlatformApiException(
                    'Careem',
                    'Failed to list brands: '.$response->body(),
                    $response->status()
                );
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Careem brand list failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update existing brand
     *
     * @param  string  $brandId  Brand ID
     * @param  string  $name  New brand name
     * @return array Updated brand details
     *
     * @throws PlatformApiException
     */
    public function updateBrand(string $brandId, string $name): array
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{brand_id}', $brandId, config('platforms.careem.endpoints.brand_detail'));

        Log::info('Updating Careem brand', [
            'brand_id' => $brandId,
            'name' => $name,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
                ])
                ->put($this->baseUrl.$endpoint, [
                    'name' => $name,
                ]);

            if (! $response->successful()) {
                throw new PlatformApiException(
                    'Careem',
                    'Brand update failed: '.$response->body(),
                    $response->status()
                );
            }

            Log::info('Careem brand updated successfully', [
                'brand_id' => $brandId,
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Careem brand update failed', [
                'brand_id' => $brandId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete brand
     *
     * @param  string  $brandId  Brand ID
     * @return array Deletion confirmation
     *
     * @throws PlatformApiException
     */
    public function deleteBrand(string $brandId): array
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{brand_id}', $brandId, config('platforms.careem.endpoints.brand_detail'));

        Log::warning('Deleting Careem brand', [
            'brand_id' => $brandId,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
                ])
                ->delete($this->baseUrl.$endpoint);

            if (! $response->successful()) {
                throw new PlatformApiException(
                    'Careem',
                    'Brand deletion failed: '.$response->body(),
                    $response->status()
                );
            }

            Log::info('Careem brand deleted successfully', [
                'brand_id' => $brandId,
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Careem brand deletion failed', [
                'brand_id' => $brandId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // ============================================================================
    // BRANCH API METHODS
    // ============================================================================

    /**
     * Create or update a branch
     *
     * @param  string  $brandId  Brand ID that owns this branch
     * @param  string  $branchId  A unique branch ID string
     * @param  string  $name  Branch name (e.g., "KFC, Marina Mall")
     * @return array Branch details
     *
     * @throws PlatformApiException
     */
    public function createOrUpdateBranch(string $brandId, string $branchId, string $name): array
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{branch_id}', $branchId, config('platforms.careem.endpoints.branch_detail'));

        Log::info('Creating/updating Careem branch', [
            'brand_id' => $brandId,
            'branch_id' => $branchId,
            'name' => $name,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
                    'Brand-Id' => $brandId,
                ])
                ->put($this->baseUrl.$endpoint, [
                    'name' => $name,
                ]);

            if (! $response->successful()) {
                throw new PlatformApiException(
                    'Careem',
                    'Branch creation/update failed: '.$response->body(),
                    $response->status()
                );
            }

            Log::info('Careem branch created/updated successfully', [
                'branch_id' => $branchId,
                'status_code' => $response->status(),
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Careem branch creation/update failed', [
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get branch details by ID
     *
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @return array Branch details
     *
     * @throws PlatformApiException
     */
    public function getBranch(string $brandId, string $branchId): array
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{branch_id}', $branchId, config('platforms.careem.endpoints.branch_detail'));

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
                    'Brand-Id' => $brandId,
                ])
                ->get($this->baseUrl.$endpoint);

            if (! $response->successful()) {
                throw new PlatformApiException(
                    'Careem',
                    'Failed to fetch branch: '.$response->body(),
                    $response->status()
                );
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Careem branch fetch failed', [
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * List all branches for a brand with pagination
     *
     * @param  string  $brandId  Brand ID
     * @param  int  $pageNumber  Page number (default: 1)
     * @param  int  $pageSize  Results per page (default: 20, max: 20)
     * @return array Paginated branch list
     *
     * @throws PlatformApiException
     */
    public function listBranches(string $brandId, int $pageNumber = 1, int $pageSize = 20): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.branches');

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
                    'Brand-Id' => $brandId,
                ])
                ->get($this->baseUrl.$endpoint, [
                    'page_number' => $pageNumber,
                    'page_size' => min($pageSize, 20),
                ]);

            if (! $response->successful()) {
                throw new PlatformApiException(
                    'Careem',
                    'Failed to list branches: '.$response->body(),
                    $response->status()
                );
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Careem branch list failed', [
                'brand_id' => $brandId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete branch
     *
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @return array Deletion confirmation
     *
     * @throws PlatformApiException
     */
    public function deleteBranch(string $brandId, string $branchId): array
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{branch_id}', $branchId, config('platforms.careem.endpoints.branch_detail'));

        Log::warning('Deleting Careem branch', [
            'branch_id' => $branchId,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
                    'Brand-Id' => $brandId,
                ])
                ->delete($this->baseUrl.$endpoint);

            if (! $response->successful()) {
                throw new PlatformApiException(
                    'Careem',
                    'Branch deletion failed: '.$response->body(),
                    $response->status()
                );
            }

            Log::info('Careem branch deleted successfully', [
                'branch_id' => $branchId,
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Careem branch deletion failed', [
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Toggle POS integration for a branch (enable/disable order flow)
     *
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @param  bool  $active  true to enable POS integration, false to disable
     * @return array Updated branch details
     *
     * @throws PlatformApiException
     */
    public function toggleBranchPosIntegration(string $brandId, string $branchId, bool $active): array
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{branch_id}', $branchId, config('platforms.careem.endpoints.branch_status'));

        Log::info('Toggling branch POS integration', [
            'branch_id' => $branchId,
            'active' => $active,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
                    'Brand-Id' => $brandId,
                ])
                ->patch($this->baseUrl.$endpoint, [
                    'active' => $active,
                ]);

            if (! $response->successful()) {
                throw new PlatformApiException(
                    'Careem',
                    'Failed to toggle POS integration: '.$response->body(),
                    $response->status()
                );
            }

            Log::info('Branch POS integration toggled successfully', [
                'branch_id' => $branchId,
                'active' => $active,
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Branch POS integration toggle failed', [
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update branch visibility status on SuperApp (active/inactive)
     *
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @param  int  $statusId  1 = Active (customers can order), 2 = Inactive (cannot order)
     * @return bool Success status (204 response means success)
     *
     * @throws PlatformApiException
     */
    public function updateBranchVisibilityStatus(string $brandId, string $branchId, int $statusId): bool
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{branch_id}', $branchId, config('platforms.careem.endpoints.branch_visibility'));

        Log::info('Updating branch visibility status', [
            'branch_id' => $branchId,
            'status_id' => $statusId,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
                    'Brand-Id' => $brandId,
                ])
                ->post($this->baseUrl.$endpoint, [
                    'status_id' => $statusId,
                ]);

            if (! $response->successful() && $response->status() !== 204) {
                throw new PlatformApiException(
                    'Careem',
                    'Failed to update branch visibility: '.$response->body(),
                    $response->status()
                );
            }

            Log::info('Branch visibility status updated successfully', [
                'branch_id' => $branchId,
                'status_id' => $statusId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Branch visibility status update failed', [
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Set branch status for a fixed duration (e.g., temporarily close for 15 minutes)
     *
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @param  int  $statusId  Status ID (2 = Inactive)
     * @param  int  $tillTimeMinutes  Duration in minutes
     * @return array Expiry details
     *
     * @throws PlatformApiException
     */
    public function setBranchStatusExpiry(string $brandId, string $branchId, int $statusId, int $tillTimeMinutes): array
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{branch_id}', $branchId, config('platforms.careem.endpoints.branch_visibility_expiry'));

        Log::info('Setting branch status expiry', [
            'branch_id' => $branchId,
            'status_id' => $statusId,
            'till_time' => $tillTimeMinutes,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
                    'Brand-Id' => $brandId,
                ])
                ->post($this->baseUrl.$endpoint, [
                    'status_id' => $statusId,
                    'till_time' => $tillTimeMinutes,
                ]);

            if (! $response->successful()) {
                throw new PlatformApiException(
                    'Careem',
                    'Failed to set branch status expiry: '.$response->body(),
                    $response->status()
                );
            }

            Log::info('Branch status expiry set successfully', [
                'branch_id' => $branchId,
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Branch status expiry setting failed', [
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Set branch operational hours
     *
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @param  array  $operationalHours  Array of operational hours
     * @return array Operational hours response
     *
     * @throws PlatformApiException
     */
    public function setBranchOperationalHours(string $brandId, string $branchId, array $operationalHours): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.operational_hours');

        $payload = [
            'operational_hours' => $operationalHours,
        ];

        Log::info('Setting branch operational hours', [
            'brand_id' => $brandId,
            'branch_id' => $branchId,
            'endpoint' => $this->baseUrl.$endpoint,
            'shifts_count' => count($operationalHours),
        ]);

        Log::debug('Operational hours payload', [
            'payload' => $payload,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Brand-Id' => $brandId,
                    'Branch-Id' => $branchId,
                ])
                ->put($this->baseUrl.$endpoint, $payload);

            if (! $response->successful()) {
                Log::error('Operational hours API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'headers' => $response->headers(),
                    'payload' => $payload,
                    'brand_id' => $brandId,
                    'branch_id' => $branchId,
                    'url' => $this->baseUrl.$endpoint,
                ]);

                $errorMessage = $response->body() ?: 'Empty error response from Careem';
                $statusCode = $response->status();

                // Provide helpful context based on status code
                if ($statusCode === 500) {
                    $errorMessage .= ' (500 Internal Server Error - This may indicate the branch is not properly configured in Careem\'s system for operational hours, or the staging API endpoint has issues. Contact Careem support if this persists.)';
                } elseif ($statusCode === 404) {
                    $errorMessage .= ' (404 Not Found - Verify the branch exists and is mapped in Careem\'s system)';
                } elseif ($statusCode === 400) {
                    $errorMessage .= ' (400 Bad Request - Payload validation failed)';
                }

                throw new PlatformApiException(
                    'Careem',
                    'Failed to set operational hours: '.$errorMessage,
                    $statusCode
                );
            }

            Log::info('Branch operational hours set successfully', [
                'branch_id' => $branchId,
                'response' => $response->json(),
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Branch operational hours setting failed', [
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get branch operational hours
     *
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @return array Operational hours
     *
     * @throws PlatformApiException
     */
    public function getBranchOperationalHours(string $brandId, string $branchId): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.operational_hours');

        Log::info('Fetching branch operational hours', [
            'brand_id' => $brandId,
            'branch_id' => $branchId,
            'endpoint' => $this->baseUrl.$endpoint,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Brand-Id' => $brandId,
                    'Branch-Id' => $branchId,
                ])
                ->get($this->baseUrl.$endpoint);

            if (! $response->successful()) {
                throw new PlatformApiException(
                    'Careem',
                    'Failed to fetch operational hours: '.$response->body(),
                    $response->status()
                );
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Branch operational hours fetch failed', [
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Accept a pending order
     *
     * @param  string  $orderId  Order ID from Careem
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @return array Response from Careem
     *
     * @throws PlatformApiException
     */
    public function acceptOrder(string $orderId, string $brandId, string $branchId): array
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{order_id}', $orderId, config('platforms.careem.endpoints.order_detail'));
        $fullUrl = $this->baseUrl.$endpoint;
        $payload = ['state' => 'accepted'];

        $this->logApiActivity('accept_order_request', [
            'order_id' => $orderId,
            'brand_id' => $brandId,
            'branch_id' => $branchId,
            'endpoint' => $endpoint,
            'full_url' => $fullUrl,
            'payload' => $payload,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => $this->userAgent,
                    'Brand-Id' => $brandId,
                    'Branch-Id' => $branchId,
                ])
                ->put($fullUrl, $payload);

            if (! $response->successful()) {
                $responseBody = $response->body();
                $responseJson = $response->json();

                $this->logApiActivity('accept_order_failed', [
                    'order_id' => $orderId,
                    'brand_id' => $brandId,
                    'branch_id' => $branchId,
                    'status_code' => $response->status(),
                    'request_url' => $fullUrl,
                    'request_payload' => $payload,
                    'response_body' => $responseBody,
                    'response_json' => $responseJson,
                    'headers_sent' => [
                        'Authorization' => 'Bearer ' . substr($token, 0, 20) . '...',
                        'User-Agent' => $this->userAgent,
                        'Brand-Id' => $brandId,
                        'Branch-Id' => $branchId,
                    ],
                ], 'error');

                // Extract error message from response
                $errorMessage = $responseJson['message'] ?? $responseBody;
                $errorCode = $responseJson['code'] ?? 'UNKNOWN';

                throw new PlatformApiException(
                    'Careem',
                    "Failed to accept order (Code: {$errorCode}): {$errorMessage}",
                    $response->status()
                );
            }

            $this->logApiActivity('accept_order_success', [
                'order_id' => $orderId,
                'response' => $response->json(),
            ]);

            return $response->json();

        } catch (\Exception $e) {
            $this->logApiActivity('accept_order_exception', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ], 'error');

            throw $e;
        }
    }

    /**
     * Mark an order as ready for pickup
     *
     * @param  string  $orderId  Order ID from Careem
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @return array Response from Careem
     *
     * @throws PlatformApiException
     */
    public function markOrderReady(string $orderId, string $brandId, string $branchId): array
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{order_id}', $orderId, config('platforms.careem.endpoints.order_detail'));
        $fullUrl = $this->baseUrl.$endpoint;
        $payload = ['state' => 'ready'];

        $this->logApiActivity('mark_order_ready_request', [
            'order_id' => $orderId,
            'brand_id' => $brandId,
            'branch_id' => $branchId,
            'endpoint' => $endpoint,
            'full_url' => $fullUrl,
            'payload' => $payload,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => $this->userAgent,
                    'Brand-Id' => $brandId,
                    'Branch-Id' => $branchId,
                ])
                ->put($fullUrl, $payload);

            if (! $response->successful()) {
                $responseBody = $response->body();
                $responseJson = $response->json();

                $this->logApiActivity('mark_order_ready_failed', [
                    'order_id' => $orderId,
                    'brand_id' => $brandId,
                    'branch_id' => $branchId,
                    'status_code' => $response->status(),
                    'request_url' => $fullUrl,
                    'request_payload' => $payload,
                    'response_body' => $responseBody,
                    'response_json' => $responseJson,
                    'headers_sent' => [
                        'Authorization' => 'Bearer ' . substr($token, 0, 20) . '...',
                        'User-Agent' => $this->userAgent,
                        'Brand-Id' => $brandId,
                        'Branch-Id' => $branchId,
                    ],
                ], 'error');

                // Extract error message from response
                $errorMessage = $responseJson['message'] ?? $responseBody;
                $errorCode = $responseJson['code'] ?? 'UNKNOWN';

                throw new PlatformApiException(
                    'Careem',
                    "Failed to mark order as ready (Code: {$errorCode}): {$errorMessage}",
                    $response->status()
                );
            }

            $this->logApiActivity('mark_order_ready_success', [
                'order_id' => $orderId,
                'response' => $response->json(),
            ]);

            return $response->json();

        } catch (\Exception $e) {
            $this->logApiActivity('mark_order_ready_exception', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ], 'error');

            throw $e;
        }
    }

    /**
     * Cancel an order
     *
     * @param  string  $orderId  Order ID from Careem
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @param  string  $cancellationReason  Reason for cancellation (must be from predefined list)
     * @return array Response from Careem
     *
     * @throws PlatformApiException
     */
    public function cancelOrder(string $orderId, string $brandId, string $branchId, string $cancellationReason): array
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{order_id}', $orderId, config('platforms.careem.endpoints.order_detail'));

        // Valid cancellation reasons per Careem API documentation
        $validReasons = [
            'ITEM_PERMANENTLY_NOT_AVAILABLE',
            'ITEM_TEMPORARILY_UNAVAILABLE',
            'KITCHEN_TOO_BUSY_TO_PREPARE_ORDER',
            'OUT_OF_KITCHEN_OPERATIONAL_HOURS',
            'OUTLET_CLOSED',
            'PARTNER_POS_OUTAGE',
            'PARTNER_ORDER_TIMEOUT',
            'OTHER',
        ];

        if (! in_array($cancellationReason, $validReasons)) {
            throw new \InvalidArgumentException("Invalid cancellation reason. Must be one of: ".implode(', ', $validReasons));
        }

        $this->logApiActivity('cancel_order_request', [
            'order_id' => $orderId,
            'brand_id' => $brandId,
            'branch_id' => $branchId,
            'reason' => $cancellationReason,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => $this->userAgent,
                    'Brand-Id' => $brandId,
                    'Branch-Id' => $branchId,
                ])
                ->put($this->baseUrl.$endpoint, [
                    'state' => 'cancelled',
                    'cancellation_reason' => $cancellationReason,
                ]);

            if (! $response->successful()) {
                $this->logApiActivity('cancel_order_failed', [
                    'order_id' => $orderId,
                    'reason' => $cancellationReason,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ], 'error');

                throw new PlatformApiException(
                    'Careem',
                    'Failed to cancel order: '.$response->body(),
                    $response->status()
                );
            }

            $this->logApiActivity('cancel_order_success', [
                'order_id' => $orderId,
                'reason' => $cancellationReason,
                'response' => $response->json(),
            ]);

            return $response->json();

        } catch (\Exception $e) {
            $this->logApiActivity('cancel_order_exception', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ], 'error');

            throw $e;
        }
    }

    /**
     * Request more preparation time for an order
     *
     * @param  string  $orderId  Order ID from Careem
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @param  int  $delayMinutes  Additional minutes needed (max 60)
     * @return array Response from Careem
     *
     * @throws PlatformApiException
     */
    public function requestOrderDelay(string $orderId, string $brandId, string $branchId, int $delayMinutes): array
    {
        if ($delayMinutes < 1 || $delayMinutes > 60) {
            throw new \InvalidArgumentException('Delay must be between 1 and 60 minutes');
        }

        $token = $this->getAccessToken();
        $endpoint = str_replace('{order_id}', $orderId, config('platforms.careem.endpoints.order_delay'));

        $this->logApiActivity('request_order_delay_request', [
            'order_id' => $orderId,
            'brand_id' => $brandId,
            'branch_id' => $branchId,
            'delay_minutes' => $delayMinutes,
            'endpoint' => $endpoint,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Brand-Id' => $brandId,
                    'Branch-Id' => $branchId,
                ])
                ->put($this->baseUrl.$endpoint, [
                    'delay_in_minutes' => $delayMinutes,
                ]);

            if (! $response->successful()) {
                $this->logApiActivity('request_order_delay_failed', [
                    'order_id' => $orderId,
                    'delay_minutes' => $delayMinutes,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ], 'error');

                throw new PlatformApiException(
                    'Careem',
                    'Failed to request order delay: '.$response->body(),
                    $response->status()
                );
            }

            $this->logApiActivity('request_order_delay_success', [
                'order_id' => $orderId,
                'delay_minutes' => $delayMinutes,
                'response' => $response->json(),
            ]);

            return $response->json();

        } catch (\Exception $e) {
            $this->logApiActivity('request_order_delay_exception', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ], 'error');

            throw $e;
        }
    }

    /**
     * Get order details by ID
     *
     * @param  string  $orderId  Order ID from Careem
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @return array Order details
     *
     * @throws PlatformApiException
     */
    public function getOrder(string $orderId, string $brandId, string $branchId): array
    {
        $token = $this->getAccessToken();
        $endpoint = str_replace('{order_id}', $orderId, config('platforms.careem.endpoints.order_detail'));

        $this->logApiActivity('get_order_request', [
            'order_id' => $orderId,
            'brand_id' => $brandId,
            'branch_id' => $branchId,
            'endpoint' => $endpoint,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Brand-Id' => $brandId,
                    'Branch-Id' => $branchId,
                ])
                ->get($this->baseUrl.$endpoint);

            if (! $response->successful()) {
                $this->logApiActivity('get_order_failed', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ], 'error');

                throw new PlatformApiException(
                    'Careem',
                    'Failed to fetch order: '.$response->body(),
                    $response->status()
                );
            }

            $orderData = $response->json();

            $this->logApiActivity('get_order_success', [
                'order_id' => $orderId,
                'order_state' => $orderData['status'] ?? 'unknown',
                'items_count' => isset($orderData['items']) ? count($orderData['items']) : 0,
                'has_items_details' => isset($orderData['items'][0]['options']) ? 'yes' : 'no',
                'response_keys' => array_keys($orderData),
            ]);

            // Log a sample item structure for debugging if items exist
            if (isset($orderData['items'][0])) {
                $this->logApiActivity('get_order_item_structure', [
                    'order_id' => $orderId,
                    'first_item_keys' => array_keys($orderData['items'][0]),
                    'first_item' => $orderData['items'][0],
                ]);
            }

            return $orderData;

        } catch (\Exception $e) {
            $this->logApiActivity('get_order_exception', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ], 'error');

            throw $e;
        }
    }

    /**
     * List orders for a branch
     *
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @param  int  $pageNumber  Page number (default: 1)
     * @param  int  $pageSize  Results per page (1-20, default: 20)
     * @return array Orders list with pagination
     *
     * @throws PlatformApiException
     */
    public function listOrders(string $brandId, string $branchId, int $pageNumber = 1, int $pageSize = 20): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.orders');

        $this->logApiActivity('list_orders_request', [
            'brand_id' => $brandId,
            'branch_id' => $branchId,
            'page' => $pageNumber,
            'size' => $pageSize,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => $this->userAgent,
                    'Brand-Id' => $brandId,
                    'Branch-Id' => $branchId,
                ])
                ->get($this->baseUrl.$endpoint, [
                    'page_number' => $pageNumber,
                    'page_size' => min(max($pageSize, 1), 20), // Ensure between 1-20
                ]);

            if (! $response->successful()) {
                $this->logApiActivity('list_orders_failed', [
                    'brand_id' => $brandId,
                    'branch_id' => $branchId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ], 'error');

                throw new PlatformApiException(
                    'Careem',
                    'Failed to list orders: '.$response->body(),
                    $response->status()
                );
            }

            $ordersData = $response->json();

            $this->logApiActivity('list_orders_success', [
                'brand_id' => $brandId,
                'branch_id' => $branchId,
                'count' => count($ordersData['data'] ?? []),
            ]);

            return $ordersData;

        } catch (\Exception $e) {
            $this->logApiActivity('list_orders_exception', [
                'brand_id' => $brandId,
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ], 'error');

            throw $e;
        }
    }

    /**
     * Sync branches from Careem API to local database
     *
     * @param  string  $careemBrandId  Careem API Brand ID (e.g., UUID from Careem)
     * @param  string|null  $tenantId  Tenant ID (uses current tenant if null)
     * @return array Sync results with created/updated counts
     *
     * @throws PlatformApiException
     */
    public function syncBranches(string $careemBrandId, ?string $tenantId = null): array
    {
        $tenantId = $tenantId ?? tenant()->id ?? null;

        if (! $tenantId) {
            throw new \Exception('Tenant ID is required for branch sync');
        }

        // Find the local brand record using the Careem API brand ID
        $localBrand = \App\Models\CareemBrand::where('tenant_id', $tenantId)
            ->where('careem_brand_id', $careemBrandId)
            ->first();

        if (! $localBrand) {
            throw new \Exception("Local brand not found for Careem brand ID: {$careemBrandId}");
        }

        Log::info('Starting Careem branch sync', [
            'tenant_id' => $tenantId,
            'careem_brand_id' => $careemBrandId,
            'local_brand_id' => $localBrand->id,
            'brand_name' => $localBrand->name,
        ]);

        $this->logApiActivity('sync_branches_start', [
            'careem_brand_id' => $careemBrandId,
            'local_brand_id' => $localBrand->id,
            'tenant_id' => $tenantId,
        ]);

        $created = 0;
        $updated = 0;
        $errors = [];

        try {
            // Fetch all branches from Careem using the Careem API brand ID
            $response = $this->listBranches($careemBrandId, 1, 20);

            if (! isset($response['data']) || ! is_array($response['data'])) {
                throw new \Exception('Invalid response format from Careem API');
            }

            foreach ($response['data'] as $careemBranch) {
                try {
                    $branchId = $careemBranch['id'] ?? null;
                    $branchName = $careemBranch['name'] ?? 'Unknown Branch';

                    if (! $branchId) {
                        $errors[] = "Branch '{$branchName}' has no ID";
                        continue;
                    }

                    // Find or create local branch
                    $localBranch = \App\Models\CareemBranch::where('tenant_id', $tenantId)
                        ->where('careem_branch_id', $branchId)
                        ->first();

                    $branchData = [
                        'tenant_id' => $tenantId,
                        'careem_brand_id' => $localBrand->id, // Use local brand ID, not Careem API ID
                        'careem_branch_id' => $branchId,
                        'name' => $branchName,
                        'state' => $careemBranch['state'] ?? 'UNMAPPED',
                        'pos_integration_enabled' => $careemBranch['active'] ?? false,
                        'visibility_status' => 1, // Default to active
                        'metadata' => $careemBranch,
                        'synced_at' => now(),
                    ];

                    if ($localBranch) {
                        $localBranch->update($branchData);
                        $updated++;

                        Log::info('Updated Careem branch', [
                            'branch_id' => $branchId,
                            'name' => $branchName,
                        ]);
                    } else {
                        \App\Models\CareemBranch::create($branchData);
                        $created++;

                        Log::info('Created Careem branch', [
                            'branch_id' => $branchId,
                            'name' => $branchName,
                        ]);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to sync branch '{$branchName}': ".$e->getMessage();
                    Log::error('Branch sync error', [
                        'branch' => $careemBranch,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $result = [
                'success' => true,
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
                'total_processed' => $created + $updated,
            ];

            $this->logApiActivity('sync_branches_complete', $result);

            Log::info('Careem branch sync completed', $result);

            return $result;

        } catch (\Exception $e) {
            $this->logApiActivity('sync_branches_failed', [
                'error' => $e->getMessage(),
            ]);

            Log::error('Branch sync failed', [
                'brand_id' => $careemBrandId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate and sync branch if needed
     *
     * @param  string  $brandId  Brand ID
     * @param  string  $branchId  Branch ID
     * @param  int|null  $tenantId  Tenant ID
     * @return bool True if branch exists/was synced successfully
     */
    public function validateOrSyncBranch(string $brandId, string $branchId, ?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?? tenant()->id ?? null;

        if (! $tenantId) {
            return false;
        }

        // Check if branch exists locally
        $localBranch = \App\Models\CareemBranch::where('tenant_id', $tenantId)
            ->where('careem_branch_id', $branchId)
            ->first();

        if ($localBranch) {
            // Check if branch needs refresh (older than 24 hours)
            if ($localBranch->synced_at && $localBranch->synced_at->lt(now()->subDay())) {
                Log::info('Branch data is stale, triggering sync', [
                    'branch_id' => $branchId,
                    'last_sync' => $localBranch->synced_at,
                ]);

                try {
                    $this->syncBranches($brandId, $tenantId);
                } catch (\Exception $e) {
                    Log::warning('Background branch sync failed', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return true;
        }

        // Branch not found locally, try to fetch and sync
        Log::warning('Branch not found locally, attempting sync', [
            'branch_id' => $branchId,
            'brand_id' => $brandId,
        ]);

        try {
            // Try to get specific branch details
            $branchDetails = $this->getBranch($brandId, $branchId);

            // Create the branch locally
            \App\Models\CareemBranch::create([
                'tenant_id' => $tenantId,
                'careem_brand_id' => $brandId,
                'careem_branch_id' => $branchId,
                'name' => $branchDetails['name'] ?? 'Unknown Branch',
                'state' => $branchDetails['state'] ?? 'MAPPED',
                'pos_integration_enabled' => true,
                'visibility_status' => 1,
                'metadata' => $branchDetails,
                'synced_at' => now(),
            ]);

            Log::info('Branch created successfully during validation', [
                'branch_id' => $branchId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to validate/sync branch', [
                'branch_id' => $branchId,
                'brand_id' => $brandId,
                'error' => $e->getMessage(),
            ]);

            // Last resort: try full sync
            try {
                $this->syncBranches($brandId, $tenantId);
                return true;
            } catch (\Exception $e2) {
                return false;
            }
        }
    }

    /**
     * Log API activity for monitoring and debugging
     *
     * @param  string  $action  Action name
     * @param  array  $data  Additional data to log
     * @param  string  $level  Log level (info, warning, error)
     */
    protected function logApiActivity(string $action, array $data = [], string $level = 'info'): void
    {
        $logData = array_merge([
            'service' => 'careem_api',
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
            'tenant_id' => $this->tenant->id ?? tenant()->id ?? null,
            'tenant_subdomain' => $this->tenant->subdomain ?? tenant('subdomain') ?? null,
        ], $data);

        // Log to Laravel log
        Log::{$level}("Careem API: {$action}", $logData);

    }
}
