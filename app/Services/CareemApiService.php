<?php

namespace App\Services;

use App\Exceptions\PlatformApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Careem Now API Service
 * Handles catalog/menu synchronization with Careem Now platform
 *
 * API Documentation: https://docs.careemnow.com/#tag/Catalog-API-overview
 */
class CareemApiService
{
    protected string $baseUrl;
    protected string $tokenUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $scope;
    protected int $timeout;

    /**
     * Initialize service with tenant-specific credentials
     *
     * @throws \Exception If tenant credentials are not configured
     */
    public function __construct(?int $tenantId = null)
    {
        $this->timeout = config('platforms.careem.sync.timeout', 30);
        $this->scope = config('platforms.careem.auth.scope', 'catalog:write');

        // Load tenant-specific credentials from api_credentials table (REQUIRED for SaaS)
        if ($tenantId) {
            $credentials = $this->loadTenantCredentials($tenantId);

            if (empty($credentials) || !isset($credentials['client_id']) || !isset($credentials['client_secret'])) {
                throw new \Exception('Careem Catalog API credentials not configured for this tenant. Please configure in Settings → API Credentials.');
            }

            $this->clientId = $credentials['client_id'];
            $this->clientSecret = $credentials['client_secret'];
            $this->baseUrl = $credentials['api_url'] ?? config('platforms.careem.api_url');
            $this->tokenUrl = config('platforms.careem.auth.token_url');
        } else {
            // Fallback to .env only for development/testing (not recommended for production)
            $this->clientId = config('platforms.careem.auth.client_id');
            $this->clientSecret = config('platforms.careem.auth.client_secret');
            $this->baseUrl = config('platforms.careem.api_url');
            $this->tokenUrl = config('platforms.careem.auth.token_url');

            if (empty($this->clientId) || empty($this->clientSecret)) {
                throw new \Exception('Careem Catalog API credentials not configured. Please configure tenant-specific credentials in Settings.');
            }
        }
    }

    /**
     * Load tenant-specific Careem credentials from database
     */
    protected function loadTenantCredentials(int $tenantId): array
    {
        $credential = \App\Models\ApiCredential::where('tenant_id', $tenantId)
            ->where('service', 'careem_catalog')
            ->first();

        return $credential ? $credential->credentials : [];
    }

    /**
     * Get OAuth2 access token using client credentials flow
     */
    protected function getAccessToken(): string
    {
        $cacheKey = "careem_token_{$this->clientId}";

        return Cache::remember($cacheKey, now()->addHours(1), function () {
            try {
                $response = Http::timeout($this->timeout)
                    ->asForm()
                    ->post($this->tokenUrl, [
                        'grant_type' => 'client_credentials',
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'scope' => $this->scope,
                    ]);

                if (!$response->successful()) {
                    throw new PlatformApiException(
                        'Careem',
                        'Failed to obtain access token: ' . $response->body(),
                        $response->status()
                    );
                }

                $data = $response->json();

                return $data['access_token'] ?? throw new PlatformApiException(
                    'Careem',
                    'No access token in response'
                );
            } catch (\Exception $e) {
                Log::error('Careem OAuth2 authentication failed', [
                    'error' => $e->getMessage(),
                    'client_id' => $this->clientId,
                ]);

                throw new PlatformApiException(
                    'Careem',
                    'Authentication failed: ' . $e->getMessage()
                );
            }
        });
    }

    /**
     * Submit catalog to Careem
     *
     * @param array $catalogData Catalog structure
     * @param string|null $restaurantId Restaurant/merchant identifier
     * @return array Response with catalog ID and status
     * @throws PlatformApiException
     */
    public function submitCatalog(array $catalogData, ?string $restaurantId = null): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.catalog');
        $url = $this->baseUrl . $endpoint;

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
                'Catalog submission failed: ' . ($errorBody['message'] ?? $response->body()),
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
                'API request failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Update existing catalog
     *
     * @param string $catalogId Catalog ID
     * @param array $catalogData Updated catalog data
     * @return array Response
     * @throws PlatformApiException
     */
    public function updateCatalog(string $catalogId, array $catalogData): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.catalog');
        $url = $this->baseUrl . $endpoint . '/' . $catalogId;

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
                'Catalog update failed: ' . $response->body(),
                $response->status()
            );

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PlatformApiException(
                'Careem',
                'Catalog update failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Delete catalog
     *
     * @param string $catalogId Catalog ID
     * @return bool Success status
     * @throws PlatformApiException
     */
    public function deleteCatalog(string $catalogId): bool
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.catalog');
        $url = $this->baseUrl . $endpoint . '/' . $catalogId;

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->delete($url);

            if ($response->successful()) {
                return true;
            }

            throw new PlatformApiException(
                'Careem',
                'Catalog deletion failed: ' . $response->body(),
                $response->status()
            );

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PlatformApiException(
                'Careem',
                'Catalog deletion failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Update store status (active/inactive, busy)
     *
     * @param string $storeId Careem store/branch ID
     * @param bool $isActive Whether store is active
     * @param bool $isBusy Whether store is busy
     * @return array Response
     * @throws PlatformApiException
     */
    public function updateStoreStatus(string $storeId, bool $isActive, bool $isBusy): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.store_status', '/stores/{storeId}/status');
        $url = $this->baseUrl . str_replace('{storeId}', $storeId, $endpoint);

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
                'Store status update failed: ' . $response->body(),
                $response->status()
            );

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PlatformApiException(
                'Careem',
                'Store status update failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Update store operating hours
     *
     * @param string $storeId Careem store/branch ID
     * @param array $hours Operating hours in Careem format
     * @return array Response
     * @throws PlatformApiException
     */
    public function updateStoreHours(string $storeId, array $hours): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.store_hours', '/stores/{storeId}/hours');
        $url = $this->baseUrl . str_replace('{storeId}', $storeId, $endpoint);

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
                'Store hours update failed: ' . $response->body(),
                $response->status()
            );

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PlatformApiException(
                'Careem',
                'Store hours update failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get store information from Careem
     *
     * @param string $storeId Careem store/branch ID
     * @return array Store information
     * @throws PlatformApiException
     */
    public function getStore(string $storeId): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.careem.endpoints.store', '/stores/{storeId}');
        $url = $this->baseUrl . str_replace('{storeId}', $storeId, $endpoint);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            throw new PlatformApiException(
                'Careem',
                'Failed to retrieve store information: ' . $response->body(),
                $response->status()
            );

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PlatformApiException(
                'Careem',
                'Failed to retrieve store information: ' . $e->getMessage()
            );
        }
    }

    /**
     * Test API connection and authentication
     *
     * @return bool True if connection successful
     */
    public function testConnection(): bool
    {
        try {
            $this->getAccessToken();
            return true;
        } catch (\Exception $e) {
            Log::warning('Careem connection test failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
