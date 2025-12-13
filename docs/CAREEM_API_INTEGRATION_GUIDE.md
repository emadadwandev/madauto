# Careem Now API Integration Guide

**Document Version:** 1.0  
**Last Updated:** December 10, 2025  
**System:** Careem to Loyverse POS Integration

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Authentication](#authentication)
4. [Webhook Registration](#webhook-registration)
5. [Order Webhooks](#order-webhooks)
6. [Catalog/Menu Management](#catalogmenu-management)
7. [Implementation Details](#implementation-details)
8. [Error Handling](#error-handling)
9. [Testing](#testing)

---

## Overview

This document provides complete technical details for integrating with Careem Now's API. The integration consists of two main components:

1. **Webhook Registration** - Configure your client and webhook endpoint with Careem
2. **Order Processing** - Receive and process order notifications from Careem
3. **Catalog Management** - Publish and manage your restaurant menu on Careem (Coming Soon)

### API Environments

| Environment | API Base URL | Token URL |
|-------------|--------------|-----------|
| QA/Staging | `https://pos-stg.careemdash-internal.com` | `https://identity.qa.careem-engineering.com/token` |
| Production | `https://pos.careemdash-internal.com` | `https://identity.careem-engineering.com/token` (verify with Careem) |

### Authentication

Careem uses **OAuth2 Client Credentials** flow for API authentication.

#### Step 1: Obtain Access Token

```http
POST https://identity.qa.careem-engineering.com/token
Content-Type: application/x-www-form-urlencoded

grant_type=client_credentials
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
&scope=pos
```

#### Step 2: Use Access Token

Include the token in subsequent API requests:
```
Authorization: Bearer {access_token}
```

---

## Architecture

### System Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    CAREEM NOW PLATFORM                          │
│                                                                 │
│  1. Customer places order                                       │
│  2. Careem sends webhook to your registered URL                 │
│  3. (Optional) Careem sends catalog sync status updates         │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   YOUR MULTI-TENANT SAAS                        │
│                                                                 │
│  ┌──────────────────────┐      ┌──────────────────────┐        │
│  │  Webhook Endpoint    │      │   Background Queue   │        │
│  │  (/api/webhook/      │─────▶│                      │        │
│  │   careem/{tenant})   │      │  ProcessCareemOrder  │        │
│  └──────────────────────┘      └──────────────────────┘        │
│                                           │                     │
│                                           ▼                     │
│                              ┌──────────────────────┐           │
│                              │  SyncToLoyverseJob   │           │
│                              └──────────────────────┘           │
│                                           │                     │
└───────────────────────────────────────────┼─────────────────────┘
                                            ▼
                              ┌──────────────────────┐
                              │   LOYVERSE POS API   │
                              └──────────────────────┘
```



---

## Authentication

### Credentials Required

To integrate with Careem, you need OAuth2 credentials:

1. **Client ID** - Unique identifier for your restaurant (UUID format)
2. **Client Secret** - Secret key for OAuth2 authentication (UUID format)
3. **Scope** - API scope (default: `pos`)

**Example credentials:**
- Client ID: `ea056665-e859-46d1-9acd-abe39e7dbbc9`
- Client Secret: `6131a2a1-f56c-48ed-8ab8-8b4b640977ed`
- Scope: `pos`

### Obtaining Credentials

Contact your Careem account manager or support team to request OAuth2 credentials for your restaurant.

### Storing Credentials (Multi-Tenant)

Credentials are stored per tenant in the `api_credentials` table:

```sql
INSERT INTO api_credentials (tenant_id, service, credential_type, credential_value, is_active)
VALUES 
  (1, 'careem_catalog', 'client_id', 'ea056665-e859-46d1-9acd-abe39e7dbbc9', 1),
  (1, 'careem_catalog', 'client_secret', '6131a2a1-f56c-48ed-8ab8-8b4b640977ed', 1),
  (1, 'careem_catalog', 'client_name', 'My Restaurant', 1);
```

---

## Authentication Flow

### Get Access Token

Before making any API calls, you must obtain an OAuth2 access token.

#### Endpoint

```
POST https://identity.qa.careem-engineering.com/token
```

#### cURL Example

```bash
curl --location 'https://identity.qa.careem-engineering.com/token' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--data-urlencode 'grant_type=client_credentials' \
--data-urlencode 'client_id=ea056665-e859-46d1-9acd-abe39e7dbbc9' \
--data-urlencode 'client_secret=6131a2a1-f56c-48ed-8ab8-8b4b640977ed' \
--data-urlencode 'scope=pos'
```

#### PowerShell Example

```powershell
$body = @{
    grant_type = "client_credentials"
    client_id = "ea056665-e859-46d1-9acd-abe39e7dbbc9"
    client_secret = "6131a2a1-f56c-48ed-8ab8-8b4b640977ed"
    scope = "pos"
}

$response = Invoke-RestMethod `
    -Uri "https://identity.qa.careem-engineering.com/token" `
    -Method Post `
    -ContentType "application/x-www-form-urlencoded" `
    -Body $body

$accessToken = $response.access_token
Write-Output "Access Token: $accessToken"
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `grant_type` | string | Yes | Must be `client_credentials` |
| `client_id` | string | Yes | Your OAuth2 Client ID (UUID) |
| `client_secret` | string | Yes | Your OAuth2 Client Secret (UUID) |
| `scope` | string | Yes | API scope (use `pos`) |

#### Response (Success - 200 OK)

```json
{
  "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "scope": "pos"
}
```

#### Response (Error - 401 Unauthorized)

```json
{
  "error": "invalid_client",
  "error_description": "Client authentication failed"
}
```

### Token Caching

Access tokens are valid for **1 hour** (3600 seconds). The system automatically caches tokens to minimize authentication requests.

---

## Webhook Registration

> **Note:** Webhook registration endpoints may vary. Confirm with Careem's official documentation.

#### Request Payload

```json
{
  "name": "My Restaurant",
  "active": true,
  "webhook_url": "https://yourapp.com/api/webhook/careem/demo",
  "token": "sk_test_51abc123xyz"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Friendly name for your client/restaurant |
| `active` | boolean | Yes | Whether the client is active (can receive orders) |
| `webhook_url` | string | Yes | Your tenant-specific webhook endpoint |
| `token` | string | Yes | Secret token for webhook authentication |

#### Response (Success - 200 OK)

```json
{
  "client_id": "YOUR_CLIENT_ID",
  "name": "My Restaurant",
  "active": true,
  "webhook_url": "https://yourapp.com/api/webhook/careem/demo",
  "created_at": "2025-12-10T10:30:00Z",
  "updated_at": "2025-12-10T10:30:00Z"
}
```

#### Response (Error - 401 Unauthorized)

```json
{
  "error": "Invalid API Key"
}
```

#### Response (Error - 404 Not Found)

```json
{
  "error": "Client not found"
}
```

### 2. Verify Webhook Configuration

#### Endpoint

```
GET /internal/clients/{client_id}
```

#### cURL Example

```bash
curl --location --request GET 'https://pos-stg.careemdash-internal.com/internal/clients/YOUR_CLIENT_ID' \
--header 'x-careem-api-key: YOUR_API_KEY'
```

#### Response (Success - 200 OK)

```json
{
  "client_id": "YOUR_CLIENT_ID",
  "name": "My Restaurant",
  "active": true,
  "webhook_url": "https://yourapp.com/api/webhook/careem/demo",
  "token": "sk_test_51abc123xyz",
  "created_at": "2025-12-10T10:30:00Z",
  "updated_at": "2025-12-10T10:30:00Z"
}
```

---

## Order Webhooks

### Webhook Endpoint (Your Server)

Careem will send order notifications to your registered webhook URL.

#### Endpoint

```
POST /api/webhook/careem/{tenant-subdomain}
```

**Example:** `https://yourapp.com/api/webhook/careem/demo`

### Webhook Request from Careem

#### Headers

```
Content-Type: application/json
x-careem-signature: HMAC-SHA256-SIGNATURE
Authorization: Bearer YOUR_WEBHOOK_TOKEN
```

> **Note:** The exact authentication method (signature vs bearer token) should be confirmed with Careem documentation. The system currently validates using the webhook token.

#### Example Payload

```json
{
  "order_id": "CAREEM-2025-12-10-12345",
  "order": {
    "id": "CAREEM-2025-12-10-12345",
    "external_order_id": "12345",
    "status": "confirmed",
    "created_at": "2025-12-10T14:30:00Z",
    "customer": {
      "name": "John Doe",
      "phone": "+971501234567",
      "email": "john@example.com"
    },
    "delivery_address": {
      "street": "123 Sheikh Zayed Road",
      "building": "Building 5",
      "apartment": "Apt 402",
      "city": "Dubai",
      "area": "Dubai Marina",
      "coordinates": {
        "lat": 25.0760,
        "lng": 55.1330
      }
    },
    "items": [
      {
        "product_id": "BURGER-001",
        "sku": "BRG-CLASSIC",
        "name": "Classic Burger",
        "quantity": 2,
        "price": 45.00,
        "total": 90.00,
        "modifiers": [
          {
            "id": "MOD-CHEESE",
            "name": "Extra Cheese",
            "price": 5.00
          }
        ]
      },
      {
        "product_id": "DRINK-001",
        "sku": "COLA-500",
        "name": "Coca Cola 500ml",
        "quantity": 2,
        "price": 8.00,
        "total": 16.00,
        "modifiers": []
      }
    ],
    "subtotal": 106.00,
    "delivery_fee": 10.00,
    "tax": 5.30,
    "discount": 0.00,
    "total": 121.30,
    "payment_method": "card",
    "delivery_time": "2025-12-10T15:00:00Z",
    "notes": "Please ring the doorbell",
    "restaurant_id": "REST-123",
    "branch_id": "BRANCH-001"
  }
}
```

### Webhook Response (Your Server)

Your server should respond immediately with a success status:

#### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Careem order received and queued for processing"
}
```

#### Error Response (400 Bad Request)

```json
{
  "success": false,
  "message": "Invalid order data",
  "errors": {
    "order_id": ["The order_id field is required."]
  }
}
```

#### Error Response (401 Unauthorized)

```json
{
  "success": false,
  "message": "Invalid webhook signature"
}
```

### Webhook Processing Flow

1. **Receive Webhook** (`WebhookController@handleCareem`)
   - Validate tenant exists
   - Verify webhook signature/token
   - Log webhook in `webhook_logs` table
   
2. **Queue Job** (`ProcessCareemOrderJob`)
   - Dispatch to high-priority queue
   - Restores tenant context
   - Creates order record in database
   
3. **Sync to Loyverse** (`SyncToLoyverseJob`)
   - Transform order data to Loyverse format
   - Map products using SKU matching
   - Submit receipt to Loyverse API
   - Update order status

### Webhook Validation

#### Signature Verification (if using HMAC)

```php
// Middleware: VerifyWebhookSignature
$signature = $request->header('x-careem-signature');
$payload = $request->getContent();
$webhookToken = tenant()->careem_api_key; // From database

$expectedSignature = hash_hmac('sha256', $payload, $webhookToken);

if (!hash_equals($expectedSignature, $signature)) {
    abort(401, 'Invalid webhook signature');
}
```

#### Bearer Token Verification

```php
// If Careem uses Bearer token instead
$token = $request->bearerToken();
$expectedToken = tenant()->careem_webhook_token;

if ($token !== $expectedToken) {
    abort(401, 'Invalid webhook token');
}
```

---

## Catalog/Menu Management

> **Status:** This section describes the expected API structure for catalog management. Implementation may vary based on actual Careem API documentation.

### 1. Submit/Update Catalog

#### Endpoint (Expected)

```
POST /api/v1/catalog
PUT /api/v1/catalog/{catalog_id}
```

#### Authentication

```
x-careem-api-key: YOUR_API_KEY
Content-Type: application/json
```

#### Request Payload Structure

```json
{
  "restaurant_id": "REST-123",
  "branch_id": "BRANCH-001",
  "catalog": {
    "categories": [
      {
        "id": "CAT-001",
        "name": "Burgers",
        "description": "Delicious burgers",
        "sort_order": 1,
        "active": true,
        "image_url": "https://example.com/images/burgers.jpg"
      },
      {
        "id": "CAT-002",
        "name": "Drinks",
        "description": "Refreshing beverages",
        "sort_order": 2,
        "active": true,
        "image_url": "https://example.com/images/drinks.jpg"
      }
    ],
    "items": [
      {
        "id": "BURGER-001",
        "sku": "BRG-CLASSIC",
        "name": "Classic Burger",
        "description": "Beef patty with lettuce, tomato, and special sauce",
        "category_id": "CAT-001",
        "price": 45.00,
        "currency": "AED",
        "available": true,
        "image_url": "https://example.com/images/classic-burger.jpg",
        "modifiers": [
          {
            "id": "MOD-CHEESE",
            "name": "Extra Cheese",
            "price": 5.00,
            "available": true
          },
          {
            "id": "MOD-BACON",
            "name": "Add Bacon",
            "price": 8.00,
            "available": true
          }
        ],
        "variants": [],
        "preparation_time": 15,
        "calories": 650,
        "allergens": ["dairy", "gluten"]
      },
      {
        "id": "DRINK-001",
        "sku": "COLA-500",
        "name": "Coca Cola 500ml",
        "description": "Chilled Coca Cola",
        "category_id": "CAT-002",
        "price": 8.00,
        "currency": "AED",
        "available": true,
        "image_url": "https://example.com/images/cola.jpg",
        "modifiers": [],
        "variants": [
          {
            "id": "VAR-001",
            "name": "Size",
            "options": [
              {
                "id": "SIZE-500",
                "name": "500ml",
                "price": 8.00,
                "default": true
              },
              {
                "id": "SIZE-1L",
                "name": "1 Liter",
                "price": 12.00,
                "default": false
              }
            ]
          }
        ]
      }
    ],
    "modifier_groups": [
      {
        "id": "GRP-EXTRAS",
        "name": "Extra Toppings",
        "min_selection": 0,
        "max_selection": 3,
        "modifiers": ["MOD-CHEESE", "MOD-BACON"]
      }
    ]
  }
}
```

#### cURL Example

```bash
curl --location --request POST 'https://pos-stg.careemdash-internal.com/api/v1/catalog' \
--header 'Content-Type: application/json' \
--header 'x-careem-api-key: YOUR_API_KEY' \
--data @catalog.json
```

#### Response (Success - 200 OK)

```json
{
  "success": true,
  "catalog_id": "CAT-2025-12-10-001",
  "status": "published",
  "message": "Catalog published successfully",
  "published_at": "2025-12-10T14:45:00Z",
  "stats": {
    "categories": 2,
    "items": 2,
    "modifiers": 2
  }
}
```

#### Response (Error - 400 Bad Request)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": [
    {
      "field": "items[0].price",
      "message": "Price must be greater than 0"
    }
  ]
}
```

### 2. Get Catalog Status

#### Endpoint (Expected)

```
GET /api/v1/catalog/{catalog_id}
```

#### cURL Example

```bash
curl --location --request GET 'https://pos-stg.careemdash-internal.com/api/v1/catalog/CAT-2025-12-10-001' \
--header 'x-careem-api-key: YOUR_API_KEY'
```

#### Response

```json
{
  "catalog_id": "CAT-2025-12-10-001",
  "status": "published",
  "published_at": "2025-12-10T14:45:00Z",
  "last_updated": "2025-12-10T14:45:00Z",
  "stats": {
    "categories": 2,
    "items": 2,
    "active_items": 2
  }
}
```

### 3. Update Item Availability

#### Endpoint (Expected)

```
PATCH /api/v1/catalog/items/{item_id}/availability
```

#### Request Payload

```json
{
  "available": false,
  "reason": "Out of stock"
}
```

#### cURL Example

```bash
curl --location --request PATCH 'https://pos-stg.careemdash-internal.com/api/v1/catalog/items/BURGER-001/availability' \
--header 'Content-Type: application/json' \
--header 'x-careem-api-key: YOUR_API_KEY' \
--data '{
    "available": false,
    "reason": "Out of stock"
}'
```

---

## Implementation Details

### Laravel Service Class

**File:** `app/Services/CareemApiService.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Exceptions\PlatformApiException;

class CareemApiService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $apiKey;
    protected int $timeout;

    public function __construct(string|int|null $tenantId = null)
    {
        $this->timeout = 30;
        $this->baseUrl = 'https://pos-stg.careemdash-internal.com';

        if ($tenantId) {
            $credentials = $this->loadTenantCredentials($tenantId);
            $this->clientId = $credentials['client_id'];
            $this->apiKey = $credentials['api_key'];
            $this->baseUrl = $credentials['api_url'] ?? $this->baseUrl;
        }
    }

    /**
     * Register or update webhook configuration
     */
    public function registerWebhook(
        string $webhookUrl, 
        string $webhookToken, 
        bool $active = true
    ): array {
        $url = $this->baseUrl . "/internal/clients/{$this->clientId}";
        
        $payload = [
            'name' => tenant()->name ?? 'Restaurant Client',
            'active' => $active,
            'webhook_url' => $webhookUrl,
            'token' => $webhookToken,
        ];

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-careem-api-key' => $this->apiKey,
            ])
            ->put($url, $payload);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Webhook registered successfully',
                'data' => $response->json(),
            ];
        }

        throw new PlatformApiException(
            'Careem',
            'Webhook registration failed: ' . $response->body(),
            $response->status()
        );
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool
    {
        $url = $this->baseUrl . "/internal/clients/{$this->clientId}";

        $response = Http::timeout($this->timeout)
            ->withHeaders(['x-careem-api-key' => $this->apiKey])
            ->get($url);

        if ($response->successful()) {
            Log::info('Careem API connection test successful', [
                'client_id' => $this->clientId,
            ]);
            return true;
        }

        throw new \Exception(
            'Connection test failed: ' . $response->body() . 
            ' (Status: ' . $response->status() . ')'
        );
    }
}
```

### Webhook Controller

**File:** `app/Http/Controllers/Api/WebhookController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CareemOrderRequest;
use App\Jobs\ProcessCareemOrderJob;
use App\Models\Tenant;
use App\Models\WebhookLog;

class WebhookController extends Controller
{
    public function handleCareem(CareemOrderRequest $request, string $tenant)
    {
        // Find tenant by subdomain
        $tenantModel = Tenant::where('subdomain', $tenant)->firstOrFail();
        
        // Set tenant context
        app()->instance('tenant', $tenantModel);

        // Log webhook
        WebhookLog::create([
            'tenant_id' => $tenantModel->id,
            'payload' => array_merge($request->all(), ['platform' => 'careem']),
            'headers' => $request->header(),
            'status' => 'received',
        ]);

        // Dispatch to queue
        ProcessCareemOrderJob::dispatch($request->validated(), $tenantModel->id);

        return response()->json([
            'success' => true,
            'message' => 'Careem order received and queued for processing'
        ]);
    }
}
```

### Form Request Validation

**File:** `app/Http/Requests/CareemOrderRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CareemOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authentication handled by middleware
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|string',
            'order' => 'required|array',
            'order.id' => 'required|string',
            'order.items' => 'required|array',
            'order.items.*.product_id' => 'required|string',
            'order.items.*.name' => 'required|string',
            'order.items.*.quantity' => 'required|numeric',
            'order.items.*.price' => 'required|numeric',
        ];
    }
}
```

### Queue Job

**File:** `app/Jobs/ProcessCareemOrderJob.php`

```php
<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCareemOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;
    protected $tenantId;
    public $queue = 'high';

    public function __construct(array $payload, string $tenantId)
    {
        $this->payload = $payload;
        $this->tenantId = $tenantId;
    }

    public function handle(): void
    {
        // Set tenant context
        $tenant = Tenant::findOrFail($this->tenantId);
        app()->instance('tenant', $tenant);

        // Create order record
        $order = Order::create([
            'tenant_id' => $this->tenantId,
            'careem_order_id' => $this->payload['order_id'],
            'order_data' => $this->payload,
            'status' => 'pending',
        ]);

        // Dispatch to Loyverse sync
        SyncToLoyverseJob::dispatch($order);
    }
}
```

---

## Error Handling

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 400 | Bad Request | Invalid request payload |
| 401 | Unauthorized | Invalid or missing API key |
| 404 | Not Found | Client ID not found |
| 422 | Unprocessable Entity | Validation errors |
| 500 | Internal Server Error | Server error on Careem's side |

### Common Error Responses

#### Invalid API Key

```json
{
  "error": "Invalid API Key",
  "code": "INVALID_API_KEY"
}
```

#### Client Not Found

```json
{
  "error": "Client not found",
  "code": "CLIENT_NOT_FOUND"
}
```

#### Validation Error

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": [
    {
      "field": "webhook_url",
      "message": "The webhook_url field is required."
    }
  ]
}
```

### Retry Strategy

For failed requests, implement exponential backoff:

```php
// In your job class
public $tries = 3;
public $backoff = [60, 120, 300]; // 1min, 2min, 5min

public function handle()
{
    try {
        // Your logic
    } catch (\Exception $e) {
        if ($this->attempts() < $this->tries) {
            $this->release($this->backoff[$this->attempts() - 1]);
        } else {
            // Log failure
            Log::error('Careem webhook processing failed after retries', [
                'order_id' => $this->payload['order_id'],
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

---

## Testing

### 1. Test Webhook Registration

**PowerShell:**

```powershell
$headers = @{
    "Content-Type" = "application/json"
    "x-careem-api-key" = "YOUR_API_KEY"
}

$body = @{
    name = "Test Restaurant"
    active = $true
    webhook_url = "https://yourapp.com/api/webhook/careem/demo"
    token = "test-token-123"
} | ConvertTo-Json

$response = Invoke-RestMethod `
    -Uri "https://pos-stg.careemdash-internal.com/internal/clients/YOUR_CLIENT_ID" `
    -Method Put `
    -Headers $headers `
    -Body $body

$response | ConvertTo-Json -Depth 10
```

### 2. Test Webhook Endpoint (Simulate Careem)

**PowerShell:**

```powershell
$headers = @{
    "Content-Type" = "application/json"
    "Authorization" = "Bearer test-token-123"
}

$orderPayload = @{
    order_id = "TEST-ORDER-001"
    order = @{
        id = "TEST-ORDER-001"
        items = @(
            @{
                product_id = "TEST-PROD-001"
                name = "Test Burger"
                quantity = 1
                price = 45.00
            }
        )
    }
} | ConvertTo-Json -Depth 10

$response = Invoke-RestMethod `
    -Uri "https://yourapp.com/api/webhook/careem/demo" `
    -Method Post `
    -Headers $headers `
    -Body $orderPayload

$response | ConvertTo-Json
```

### 3. Test Connection via Dashboard

1. Navigate to **Settings → API Credentials**
2. Enter your Careem credentials:
   - Client ID
   - API Key
3. Click **Test Careem Connection**
4. Verify success message

### 4. Monitor Webhook Logs

```sql
-- View recent webhook logs
SELECT 
    id,
    tenant_id,
    created_at,
    status,
    JSON_EXTRACT(payload, '$.order_id') as order_id
FROM webhook_logs
WHERE JSON_EXTRACT(payload, '$.platform') = 'careem'
ORDER BY created_at DESC
LIMIT 20;
```

### 5. Monitor Queue Jobs

```bash
# View failed jobs
php artisan queue:failed

# Retry a specific failed job
php artisan queue:retry {job-id}

# Retry all failed jobs
php artisan queue:retry all
```

---

## Additional Resources

### API Endpoints Summary

| Method | Endpoint | Purpose |
|--------|----------|---------|
| PUT | `/internal/clients/{client_id}` | Register/update webhook |
| GET | `/internal/clients/{client_id}` | Get client configuration |
| POST | `YOUR_WEBHOOK_URL` | Receive order webhooks (from Careem) |

### Required Configuration

**Environment Variables (`.env`):**

```env
# Careem API (Development Fallback)
CAREEM_API_URL=https://pos-stg.careemdash-internal.com
CAREEM_CLIENT_ID=
CAREEM_API_KEY=
```

**Database Tables:**

- `api_credentials` - Stores tenant-specific Careem credentials
- `webhook_logs` - Logs all incoming webhooks
- `orders` - Stores order data from Careem
- `tenants` - Multi-tenant configuration

### Support Contacts

For API access and technical support:
- **Careem Partner Support**: Contact your account manager
- **API Documentation**: https://docs.careemnow.com/ (Private - Requires Partner Access)

---

## Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-12-10 | Initial documentation with webhook registration and order processing |

---

**Document End**
