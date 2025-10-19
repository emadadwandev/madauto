<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Client\Response;
use App\Repositories\ApiCredentialRepository;
use App\Exceptions\LoyverseApiException;

class LoyverseApiService
{
    protected $baseUrl;
    protected $token;
    protected $rateLimitPerMinute;

    /**
     * Cache TTL for various resources (in seconds).
     */
    const CACHE_TTL_ITEMS = 3600; // 1 hour
    const CACHE_TTL_STORES = 86400; // 24 hours
    const CACHE_TTL_EMPLOYEES = 86400; // 24 hours
    const CACHE_TTL_PAYMENT_TYPES = 86400; // 24 hours
    const CACHE_TTL_TAXES = 86400; // 24 hours
    const CACHE_TTL_CUSTOMERS = 3600; // 1 hour

    public function __construct(ApiCredentialRepository $apiCredentialRepository)
    {
        $this->baseUrl = config('loyverse.api_url', env('LOYVERSE_API_URL', 'https://api.loyverse.com'));

        // Try to get credentials from database first, then fall back to .env
        try {
            $accessToken = $apiCredentialRepository->getCredential('loyverse', 'access_token');
            $this->token = $accessToken;
        } catch (\Exception $e) {
            // Fall back to .env for development
            $this->token = env('LOYVERSE_ACCESS_TOKEN');
        }

        // If still no token from database, try .env as final fallback
        if (empty($this->token)) {
            $this->token = env('LOYVERSE_ACCESS_TOKEN');
        }

        $this->rateLimitPerMinute = config('loyverse.rate_limit_per_minute', 55);
    }

    /**
     * Check if API credentials are configured.
     */
    public function hasCredentials(): bool
    {
        return !empty($this->token);
    }

    /**
     * Ensure credentials are configured before making API calls.
     */
    protected function ensureCredentials(): void
    {
        if (!$this->hasCredentials()) {
            throw new \RuntimeException('Loyverse API credentials not configured. Please configure them in Settings.');
        }
    }

    /**
     * Make a rate-limited API request.
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [], int $retries = 3)
    {
        $this->ensureCredentials();

        return RateLimiter::attempt(
            'loyverse-api',
            $this->rateLimitPerMinute,
            function () use ($method, $endpoint, $data, $retries) {
                return $this->sendRequest($method, $endpoint, $data, $retries);
            },
            60 // decay in seconds
        );
    }

    /**
     * Send HTTP request to Loyverse API.
     */
    protected function sendRequest(string $method, string $endpoint, array $data = [], int $retries = 3)
    {
        $response = Http::withToken($this->token)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->retry($retries, 1000, function ($exception, $request) {
                // Retry on connection exceptions
                if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                    return true;
                }

                // Retry on 429 (rate limit) and 503 (service unavailable)
                if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                    $statusCode = $exception->response->status();
                    return in_array($statusCode, [429, 503]);
                }

                return false;
            }, true);

        // Execute the request
        switch (strtoupper($method)) {
            case 'GET':
                $response = $response->get($this->baseUrl . $endpoint);
                break;
            case 'POST':
                $response = $response->post($this->baseUrl . $endpoint, $data);
                break;
            case 'PUT':
                $response = $response->put($this->baseUrl . $endpoint, $data);
                break;
            case 'DELETE':
                $response = $response->delete($this->baseUrl . $endpoint);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
        }

        // Handle response
        return $this->handleResponse($response, $endpoint);
    }

    /**
     * Handle API response and errors.
     */
    protected function handleResponse(Response $response, string $endpoint)
    {
        $statusCode = $response->status();

        // Success responses
        if ($statusCode >= 200 && $statusCode < 300) {
            return $response->json();
        }

        // Error responses
        $errorData = $response->json();
        $errorMessage = $errorData['message'] ?? $response->body();
        $errorCode = $errorData['error_code'] ?? 'UNKNOWN_ERROR';

        \Log::error('Loyverse API error', [
            'endpoint' => $endpoint,
            'status' => $statusCode,
            'error_code' => $errorCode,
            'message' => $errorMessage,
            'response' => $errorData,
        ]);

        throw new LoyverseApiException(
            $errorMessage,
            $statusCode,
            $errorCode,
            $errorData
        );
    }

    /**
     * Create a receipt (order) in Loyverse.
     */
    public function createReceipt(array $data): array
    {
        return $this->makeRequest('POST', '/v1.0/receipts', $data);
    }

    /**
     * Get receipt by ID.
     */
    public function getReceipt(string $receiptId): array
    {
        return $this->makeRequest('GET', "/v1.0/receipts/{$receiptId}");
    }

    /**
     * Get all items (products) with pagination.
     */
    public function getItems(?string $cursor = null, int $limit = 250): array
    {
        $params = ['limit' => $limit];

        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        $queryString = http_build_query($params);
        return $this->makeRequest('GET', "/v1.0/items?{$queryString}");
    }

    /**
     * Get all items with caching.
     */
    public function getAllItems(bool $forceRefresh = false): array
    {
        $cacheKey = 'loyverse:items:all';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_ITEMS, function () {
            $allItems = [];
            $cursor = null;

            do {
                $response = $this->getItems($cursor);
                $allItems = array_merge($allItems, $response['items'] ?? []);
                $cursor = $response['cursor'] ?? null;
            } while ($cursor);

            return $allItems;
        });
    }

    /**
     * Get item by ID.
     */
    public function getItem(string $itemId): array
    {
        return $this->makeRequest('GET', "/v1.0/items/{$itemId}");
    }

    /**
     * Get stores.
     */
    public function getStores(): array
    {
        $cacheKey = 'loyverse:stores:all';

        return Cache::remember($cacheKey, self::CACHE_TTL_STORES, function () {
            $response = $this->makeRequest('GET', '/v1.0/stores');
            return $response['stores'] ?? [];
        });
    }

    /**
     * Get store by ID.
     */
    public function getStore(string $storeId): ?array
    {
        $stores = $this->getStores();

        foreach ($stores as $store) {
            if ($store['id'] === $storeId) {
                return $store;
            }
        }

        return null;
    }

    /**
     * Get POS devices.
     */
    public function getPosDevices(): array
    {
        $cacheKey = 'loyverse:pos_devices:all';

        return Cache::remember($cacheKey, self::CACHE_TTL_STORES, function () {
            $response = $this->makeRequest('GET', '/v1.0/pos_devices');
            return $response['pos_devices'] ?? [];
        });
    }

    /**
     * Get employees.
     */
    public function getEmployees(): array
    {
        $cacheKey = 'loyverse:employees:all';

        return Cache::remember($cacheKey, self::CACHE_TTL_EMPLOYEES, function () {
            $response = $this->makeRequest('GET', '/v1.0/employees');
            return $response['employees'] ?? [];
        });
    }

    /**
     * Get payment types.
     */
    public function getPaymentTypes(): array
    {
        $cacheKey = 'loyverse:payment_types:all';

        return Cache::remember($cacheKey, self::CACHE_TTL_PAYMENT_TYPES, function () {
            $response = $this->makeRequest('GET', '/v1.0/payment_types');
            return $response['payment_types'] ?? [];
        });
    }

    /**
     * Get payment type by name or type.
     */
    public function getPaymentTypeByName(string $name): ?array
    {
        $paymentTypes = $this->getPaymentTypes();
        $name = strtolower($name);

        foreach ($paymentTypes as $paymentType) {
            if (strtolower($paymentType['name']) === $name || strtolower($paymentType['type']) === $name) {
                return $paymentType;
            }
        }

        return null;
    }

    /**
     * Get taxes.
     */
    public function getTaxes(): array
    {
        $cacheKey = 'loyverse:taxes:all';

        return Cache::remember($cacheKey, self::CACHE_TTL_TAXES, function () {
            $response = $this->makeRequest('GET', '/v1.0/taxes');
            return $response['taxes'] ?? [];
        });
    }

    /**
     * Get customers.
     */
    public function getCustomers(?string $cursor = null, int $limit = 250): array
    {
        $params = ['limit' => $limit];

        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        $queryString = http_build_query($params);
        return $this->makeRequest('GET', "/v1.0/customers?{$queryString}");
    }

    /**
     * Get customer by ID.
     */
    public function getCustomer(string $customerId): array
    {
        $cacheKey = "loyverse:customer:{$customerId}";

        return Cache::remember($cacheKey, self::CACHE_TTL_CUSTOMERS, function () use ($customerId) {
            return $this->makeRequest('GET', "/v1.0/customers/{$customerId}");
        });
    }

    /**
     * Create a customer.
     */
    public function createCustomer(array $data): array
    {
        return $this->makeRequest('POST', '/v1.0/customers', $data);
    }

    /**
     * Find or create "Careem" customer.
     */
    public function findOrCreateCareemCustomer(): array
    {
        $cacheKey = 'loyverse:customer:careem';

        return Cache::remember($cacheKey, self::CACHE_TTL_CUSTOMERS, function () {
            // Try to find existing "Careem" customer
            $response = $this->getCustomers();
            $customers = $response['customers'] ?? [];

            foreach ($customers as $customer) {
                if (strtolower($customer['name']) === 'careem') {
                    return $customer;
                }
            }

            // Create new "Careem" customer
            return $this->createCustomer([
                'name' => 'Careem',
                'customer_code' => 'CAREEM-001',
                'note' => 'All Careem Now orders',
                'email' => 'careem@integration.local',
            ]);
        });
    }

    /**
     * Find or create "Talabat" customer.
     */
    public function findOrCreateTalabatCustomer(): array
    {
        $cacheKey = 'loyverse:customer:talabat';

        return Cache::remember($cacheKey, self::CACHE_TTL_CUSTOMERS, function () {
            // Try to find existing "Talabat" customer
            $response = $this->getCustomers();
            $customers = $response['customers'] ?? [];

            foreach ($customers as $customer) {
                if (strtolower($customer['name']) === 'talabat') {
                    return $customer;
                }
            }

            // Create new "Talabat" customer
            return $this->createCustomer([
                'name' => 'Talabat',
                'customer_code' => 'TALABAT-001',
                'note' => 'All Talabat orders',
                'email' => 'talabat@integration.local',
            ]);
        });
    }

    /**
     * Clear all cached data.
     */
    public function clearCache(): void
    {
        $keys = [
            'loyverse:items:all',
            'loyverse:stores:all',
            'loyverse:pos_devices:all',
            'loyverse:employees:all',
            'loyverse:payment_types:all',
            'loyverse:taxes:all',
            'loyverse:customer:careem',
            'loyverse:customer:talabat',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Test API connection.
     */
    public function testConnection(): bool
    {
        try {
            $this->getStores();
            return true;
        } catch (\Exception $e) {
            \Log::error('Loyverse API connection test failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
