<?php

namespace App\Services;

use App\Exceptions\PlatformApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Talabat (Delivery Hero) API Service
 * Handles catalog/menu synchronization with Delivery Hero POS Middleware API
 *
 * API Documentation: https://integration-middleware.stg.restaurant-partners.com/apidocs/pos-middleware-api
 */
class TalabatApiService
{
    protected string $baseUrl;
    protected string $tokenUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $chainCode;
    protected int $timeout;

    /**
     * Initialize service with tenant-specific credentials
     *
     * @throws \Exception If tenant credentials are not configured
     */
    public function __construct(?int $tenantId = null)
    {
        $this->timeout = config('platforms.talabat.sync.timeout', 60);

        // Load tenant-specific credentials from api_credentials table (REQUIRED for SaaS)
        if ($tenantId) {
            $credentials = $this->loadTenantCredentials($tenantId);

            if (empty($credentials) || !isset($credentials['client_id']) || !isset($credentials['client_secret']) || !isset($credentials['chain_code'])) {
                throw new \Exception('Talabat Catalog API credentials not configured for this tenant. Please configure in Settings → API Credentials.');
            }

            $this->clientId = $credentials['client_id'];
            $this->clientSecret = $credentials['client_secret'];
            $this->chainCode = $credentials['chain_code'];
            $this->baseUrl = $credentials['api_url'] ?? config('platforms.talabat.api_url');
            $this->tokenUrl = config('platforms.talabat.auth.token_url');
        } else {
            // Fallback to .env only for development/testing (not recommended for production)
            $this->clientId = config('platforms.talabat.auth.client_id');
            $this->clientSecret = config('platforms.talabat.auth.client_secret');
            $this->chainCode = config('platforms.talabat.chain_code');
            $this->baseUrl = config('platforms.talabat.api_url');
            $this->tokenUrl = config('platforms.talabat.auth.token_url');

            if (empty($this->clientId) || empty($this->clientSecret) || empty($this->chainCode)) {
                throw new \Exception('Talabat Catalog API credentials not configured. Please configure tenant-specific credentials in Settings.');
            }
        }
    }

    /**
     * Load tenant-specific Talabat credentials from database
     */
    protected function loadTenantCredentials(int $tenantId): array
    {
        $credential = \App\Models\ApiCredential::where('tenant_id', $tenantId)
            ->where('service', 'talabat')
            ->first();

        return $credential ? $credential->credentials : [];
    }

    /**
     * Get OAuth2 access token using client credentials flow
     */
    protected function getAccessToken(): string
    {
        $cacheKey = "talabat_token_{$this->clientId}";

        return Cache::remember($cacheKey, now()->addHours(1), function () {
            try {
                $response = Http::timeout($this->timeout)
                    ->post($this->tokenUrl, [
                        'grant_type' => 'client_credentials',
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                    ]);

                if (!$response->successful()) {
                    throw new PlatformApiException(
                        'Talabat',
                        'Failed to obtain access token: ' . $response->body(),
                        $response->status()
                    );
                }

                $data = $response->json();

                return $data['access_token'] ?? throw new PlatformApiException(
                    'Talabat',
                    'No access token in response'
                );
            } catch (\Exception $e) {
                Log::error('Talabat OAuth2 authentication failed', [
                    'error' => $e->getMessage(),
                    'client_id' => $this->clientId,
                ]);

                throw new PlatformApiException(
                    'Talabat',
                    'Authentication failed: ' . $e->getMessage()
                );
            }
        });
    }

    /**
     * Submit full catalog to Talabat (Delivery Hero)
     *
     * @param array $catalogData Full catalog structure
     * @param string|null $posVendorId Optional vendor ID (defaults to chainCode)
     * @param string|null $callbackUrl URL for async validation results
     * @return array Response with import ID and status
     * @throws PlatformApiException
     */
    public function submitCatalog(array $catalogData, ?string $posVendorId = null, ?string $callbackUrl = null): array
    {
        $token = $this->getAccessToken();
        $vendorId = $posVendorId ?? $this->chainCode;

        $endpoint = str_replace('{chainCode}', $this->chainCode, config('platforms.talabat.endpoints.catalog'));
        $url = $this->baseUrl . $endpoint;

        // Build request payload
        $payload = [
            'vendors' => [$vendorId],
            'items' => $catalogData['items'] ?? [],
        ];

        if ($callbackUrl) {
            $payload['callbackUrl'] = $callbackUrl;
        }

        Log::info('Submitting catalog to Talabat', [
            'vendor_id' => $vendorId,
            'items_count' => count($payload['items']),
            'has_callback' => !is_null($callbackUrl),
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->put($url, $payload);

            if ($response->status() === 202) {
                // Success - Catalog accepted for processing
                $result = $response->json();

                Log::info('Talabat catalog submitted successfully', [
                    'import_id' => $result['importId'] ?? null,
                    'vendor_id' => $vendorId,
                ]);

                return [
                    'success' => true,
                    'status' => 'accepted',
                    'import_id' => $result['importId'] ?? null,
                    'message' => 'Catalog submitted successfully. Validation in progress.',
                ];
            }

            // Handle errors
            $errorBody = $response->json();

            Log::error('Talabat catalog submission failed', [
                'status' => $response->status(),
                'error' => $errorBody,
                'vendor_id' => $vendorId,
            ]);

            throw new PlatformApiException(
                'Talabat',
                'Catalog submission failed: ' . ($errorBody['message'] ?? $response->body()),
                $response->status()
            );

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Talabat API request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new PlatformApiException(
                'Talabat',
                'API request failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get menu import logs for a vendor
     *
     * @param string $posVendorId Vendor ID
     * @param array $options Query options (from, to, limit, sort)
     * @return array Import logs
     */
    public function getMenuImportLogs(string $posVendorId, array $options = []): array
    {
        $token = $this->getAccessToken();

        $endpoint = str_replace(
            ['{chainCode}', '{posVendorId}'],
            [$this->chainCode, $posVendorId],
            config('platforms.talabat.endpoints.menu_logs')
        );
        $url = $this->baseUrl . $endpoint;

        $queryParams = [];

        if (isset($options['from'])) {
            $queryParams['from'] = $options['from'];
        }

        if (isset($options['to'])) {
            $queryParams['to'] = $options['to'];
        }

        if (isset($options['limit'])) {
            $queryParams['limit'] = min($options['limit'], 100);  // Max 100
        }

        if (isset($options['sort'])) {
            $queryParams['sort'] = $options['sort'];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->get($url, $queryParams);

            if ($response->successful()) {
                return $response->json();
            }

            throw new PlatformApiException(
                'Talabat',
                'Failed to retrieve menu import logs: ' . $response->body(),
                $response->status()
            );

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PlatformApiException(
                'Talabat',
                'Failed to retrieve menu import logs: ' . $e->getMessage()
            );
        }
    }

    /**
     * Update vendor availability status
     *
     * @param string $vendorId POS vendor ID
     * @param string $status ONLINE, OFFLINE, or BUSY
     * @param string|null $reason Reason for status change (optional)
     * @return array Response
     * @throws PlatformApiException
     */
    public function updateVendorStatus(string $vendorId, string $status, ?string $reason = null): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.talabat.endpoints.vendor_status', '/pos/vendors/{vendorId}/status');
        $url = $this->baseUrl . str_replace('{vendorId}', $vendorId, $endpoint);

        $validStatuses = ['ONLINE', 'OFFLINE', 'BUSY'];
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status. Must be one of: " . implode(', ', $validStatuses));
        }

        try {
            $payload = [
                'status' => $status,
            ];

            if ($reason) {
                $payload['reason'] = $reason;
            }

            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->patch($url, $payload);

            if ($response->successful()) {
                Log::info('Talabat vendor status updated', [
                    'vendor_id' => $vendorId,
                    'status' => $status,
                    'reason' => $reason,
                ]);

                return [
                    'success' => true,
                    'message' => 'Vendor status updated successfully.',
                    'data' => $response->json(),
                ];
            }

            throw new PlatformApiException(
                'Talabat',
                'Vendor status update failed: ' . $response->body(),
                $response->status()
            );

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PlatformApiException(
                'Talabat',
                'Vendor status update failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get vendor availability status
     *
     * @param string $vendorId POS vendor ID
     * @return array Vendor status information
     * @throws PlatformApiException
     */
    public function getVendorStatus(string $vendorId): array
    {
        $token = $this->getAccessToken();
        $endpoint = config('platforms.talabat.endpoints.vendor_status', '/pos/vendors/{vendorId}/status');
        $url = $this->baseUrl . str_replace('{vendorId}', $vendorId, $endpoint);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            throw new PlatformApiException(
                'Talabat',
                'Failed to retrieve vendor status: ' . $response->body(),
                $response->status()
            );

        } catch (PlatformApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PlatformApiException(
                'Talabat',
                'Failed to retrieve vendor status: ' . $e->getMessage()
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
            Log::warning('Talabat connection test failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
