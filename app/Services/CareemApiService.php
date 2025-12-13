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

    protected string $clientId;

    protected string $clientSecret;

    protected ?string $clientName;

    protected string $scope;

    protected int $timeout;

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
            $credentials = $this->loadTenantCredentials($tenantId);

            if (empty($credentials) || ! isset($credentials['client_id']) || ! isset($credentials['client_secret'])) {
                throw new \Exception('Careem API credentials not configured for this tenant. Please configure Client ID and Client Secret in Settings → API Credentials.');
            }

            $this->clientId = $credentials['client_id'];
            $this->clientSecret = $credentials['client_secret'];
            $this->clientName = $credentials['client_name'] ?? null;
            $this->baseUrl = $credentials['api_url'] ?? $this->baseUrl;
            $this->tokenUrl = $credentials['token_url'] ?? $this->tokenUrl;
        } else {
            // Fallback to .env only for development/testing (not recommended for production)
            $this->clientId = config('platforms.careem.auth.client_id');
            $this->clientSecret = config('platforms.careem.auth.client_secret');
            $this->clientName = config('platforms.careem.auth.client_name');

            if (empty($this->clientId) || empty($this->clientSecret)) {
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
     * @param  string|null  $restaurantId  Restaurant/merchant identifier
     * @return array Response with catalog ID and status
     *
     * @throws PlatformApiException
     */
    public function submitCatalog(array $catalogData, ?string $restaurantId = null): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.catalog');
        $url = $this->baseUrl.$endpoint;

        // Add restaurant ID if provided
        if ($restaurantId) {
            $catalogData['restaurant_id'] = $restaurantId;
        }

        Log::info('Submitting catalog to Careem', [
            'restaurant_id' => $restaurantId,
            'categories_count' => count($catalogData['categories'] ?? []),
            'items_count' => count($catalogData['items'] ?? []),
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $catalogData);

            if ($response->successful()) {
                $result = $response->json();

                Log::info('Careem catalog submitted successfully', [
                    'catalog_id' => $result['catalog_id'] ?? $result['id'] ?? null,
                    'restaurant_id' => $restaurantId,
                ]);

                return [
                    'success' => true,
                    'status' => 'accepted',
                    'catalog_id' => $result['catalog_id'] ?? $result['id'] ?? null,
                    'message' => 'Catalog submitted successfully.',
                    'data' => $result,
                ];
            }

            // Handle errors
            $errorBody = $response->json();

            Log::error('Careem catalog submission failed', [
                'status' => $response->status(),
                'error' => $errorBody,
                'restaurant_id' => $restaurantId,
            ]);

            throw new PlatformApiException(
                'Careem',
                'Catalog submission failed: '.($errorBody['message'] ?? $response->body()),
                $response->status()
            );

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

        Log::info('Setting branch operational hours', [
            'branch_id' => $branchId,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
                    'Brand-Id' => $brandId,
                    'Branch-Id' => $branchId,
                ])
                ->put($this->baseUrl.$endpoint, [
                    'operational_hours' => $operationalHours,
                ]);

            if (! $response->successful()) {
                throw new PlatformApiException(
                    'Careem',
                    'Failed to set operational hours: '.$response->body(),
                    $response->status()
                );
            }

            Log::info('Branch operational hours set successfully', [
                'branch_id' => $branchId,
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

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'User-Agent' => 'Careem-Loyverse-Integration/1.0',
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
}
