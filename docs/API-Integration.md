# API Integration Guide - Careem Now & Loyverse POS

## Overview

This document provides detailed information about the APIs we'll integrate with, including endpoints, authentication methods, request/response formats, and implementation guidelines.

---

## Table of Contents

1. [Careem Now API](#careem-now-api)
2. [Loyverse POS API](#loyverse-pos-api)
3. [Integration Architecture](#integration-architecture)
4. [Data Mapping](#data-mapping)
5. [Error Handling Strategy](#error-handling-strategy)
6. [Rate Limiting & Best Practices](#rate-limiting--best-practices)

---

## Careem Now API

### Access & Documentation

**Important Note**: Careem Now API documentation is **NOT publicly available**. Access is provided only to approved partners and merchants.

**How to Get Access**:
1. Contact your Careem representative
2. Request the "Merchant Integration Guide"
3. Sign partnership/NDA agreements if required
4. Receive private API credentials and documentation

### Expected Webhook Integration (Based on Industry Standards)

Since Careem Now uses a webhook approach to push order notifications, here's the expected structure:

#### Webhook Endpoint (Our Service)
```
POST https://your-domain.com/api/webhook/careem
```

#### Expected Webhook Payload Structure

**Note**: This is an estimated structure based on common food delivery platforms. Actual structure should be confirmed with Careem documentation.

```json
{
  "event": "order.created",
  "timestamp": "2025-10-17T10:30:00Z",
  "order_id": "CAREEM-2025-10-17-12345",
  "restaurant_id": "RESTO-123",
  "order": {
    "id": "CAREEM-2025-10-17-12345",
    "external_id": "12345",
    "status": "pending",
    "created_at": "2025-10-17T10:30:00Z",
    "updated_at": "2025-10-17T10:30:00Z",
    "customer": {
      "name": "John Doe",
      "phone": "+971501234567",
      "email": "john@example.com"
    },
    "delivery": {
      "type": "delivery",
      "address": {
        "street": "123 Main Street",
        "building": "Tower A",
        "apartment": "501",
        "city": "Dubai",
        "area": "Downtown",
        "landmark": "Near Dubai Mall"
      },
      "notes": "Ring the doorbell twice",
      "scheduled_time": null,
      "estimated_time": "2025-10-17T11:00:00Z"
    },
    "items": [
      {
        "id": "item-1",
        "product_id": "PROD-123",
        "name": "Margherita Pizza",
        "sku": "PIZZA-MARG",
        "quantity": 2,
        "unit_price": 45.00,
        "total_price": 90.00,
        "currency": "AED",
        "modifiers": [
          {
            "name": "Extra Cheese",
            "price": 5.00
          }
        ],
        "special_instructions": "Well done"
      },
      {
        "id": "item-2",
        "product_id": "PROD-456",
        "name": "Caesar Salad",
        "sku": "SALAD-CAESAR",
        "quantity": 1,
        "unit_price": 30.00,
        "total_price": 30.00,
        "currency": "AED"
      }
    ],
    "pricing": {
      "subtotal": 120.00,
      "tax": 6.00,
      "delivery_fee": 10.00,
      "discount": 0.00,
      "total": 136.00,
      "currency": "AED"
    },
    "payment": {
      "method": "card",
      "status": "paid",
      "transaction_id": "TXN-789456"
    }
  },
  "signature": "sha256_hash_for_verification"
}
```

#### Authentication & Security

**Expected Methods**:
1. **Webhook Signature Verification**: HMAC-SHA256 signature in headers
2. **IP Whitelist**: Careem's webhook IPs (to be provided)
3. **Shared Secret**: For signature verification

**Header Structure** (Expected):
```
X-Careem-Signature: sha256=<signature>
X-Careem-Timestamp: <timestamp>
X-Careem-Event: order.created
Content-Type: application/json
```

#### Webhook Verification Process

```php
// Pseudo-code for verification
function verifyWebhook($payload, $signature, $secret) {
    $computedSignature = hash_hmac('sha256', $payload, $secret);
    return hash_equals($signature, $computedSignature);
}
```

#### Expected Events

- `order.created` - New order placed
- `order.updated` - Order status changed
- `order.cancelled` - Order cancelled by customer
- `order.completed` - Order delivered/completed

#### Response Required

```json
{
  "success": true,
  "message": "Order received and queued for processing",
  "order_id": "CAREEM-2025-10-17-12345",
  "internal_id": "12345"
}
```

**HTTP Status Codes**:
- `200 OK` - Webhook processed successfully
- `400 Bad Request` - Invalid payload
- `401 Unauthorized` - Signature verification failed
- `500 Internal Server Error` - Processing error

#### Careem Now Status Callback (If Supported)

If Careem supports status callbacks, we may need to send status updates back:

```
POST https://api.careem.com/v1/orders/{order_id}/status
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "status": "preparing",
  "estimated_ready_time": "2025-10-17T10:45:00Z",
  "notes": "Order accepted and being prepared"
}
```

### Action Items for Careem Integration

- [ ] Contact Careem representative for API documentation
- [ ] Obtain webhook URL registration process
- [ ] Get webhook secret key for signature verification
- [ ] Obtain IP whitelist for Careem webhooks
- [ ] Clarify exact payload structure
- [ ] Confirm supported events
- [ ] Test webhook in sandbox environment

---

## Loyverse POS API

### Official Documentation
- **Base URL**: `https://api.loyverse.com`
- **Documentation**: https://developer.loyverse.com/docs/
- **Rate Limit**: 60 requests per minute
- **Support**: https://loyverse.town/clubs/2-loyverse-api/

### Authentication

Loyverse supports two authentication methods:

#### Option 1: Access Token (Recommended for Simple Integration)

**How to Generate**:
1. Go to Loyverse Back Office
2. Navigate to Settings → Access Tokens
3. Click "+ Add access token"
4. Enter token name (e.g., "Careem Integration")
5. Optionally set expiration date
6. Copy and securely store the token

**Usage**:
```http
GET https://api.loyverse.com/v1/items
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
```

**Characteristics**:
- Unlimited access to all API resources
- Maximum 20 tokens per account
- Can be revoked anytime from Back Office

#### Option 2: OAuth 2.0 (For Multi-Merchant Applications)

**Authorization Flow**:

1. **Get Authorization Code**:
```http
GET https://api.loyverse.com/oauth/authorize
  ?response_type=code
  &client_id={CLIENT_ID}
  &redirect_uri={REDIRECT_URI}
  &scope=RECEIPTS_READ RECEIPTS_WRITE ITEMS_READ
  &state={RANDOM_STATE}
```

2. **Exchange Code for Token**:
```http
POST https://api.loyverse.com/oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&code={AUTHORIZATION_CODE}
&client_id={CLIENT_ID}
&client_secret={CLIENT_SECRET}
&redirect_uri={REDIRECT_URI}
```

**Response**:
```json
{
  "access_token": "eyJ...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "def..."
}
```

3. **Refresh Token** (when expired):
```http
POST https://api.loyverse.com/oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=refresh_token
&refresh_token={REFRESH_TOKEN}
&client_id={CLIENT_ID}
&client_secret={CLIENT_SECRET}
```

**Scopes**:
- `RECEIPTS_READ` - Read receipts/orders
- `RECEIPTS_WRITE` - Create receipts/orders
- `ITEMS_READ` - Read products/items
- `ITEMS_WRITE` - Create/update products
- `CUSTOMERS_READ` - Read customer data
- `CUSTOMERS_WRITE` - Create/update customers

### API Endpoints

#### 1. Create Receipt (Order)

**Important**: In Loyverse, completed transactions are called "receipts". You cannot create open tickets via API.

```http
POST https://api.loyverse.com/v1/receipts
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json
```

**Request Body**:
```json
{
  "receipt_type": "SALE",
  "receipt_date": "2025-10-17T10:30:00.000Z",
  "receipt_number": "R-12345",
  "note": "Careem Order: CAREEM-2025-10-17-12345",
  "order": 1,
  "source": "API",
  "pos_device_id": "{POS_DEVICE_ID}",
  "store_id": "{STORE_ID}",
  "customer_id": "{CUSTOMER_ID}",
  "employee_id": "{EMPLOYEE_ID}",
  "line_items": [
    {
      "item_id": "{ITEM_ID}",
      "quantity": 2,
      "price": 45.00,
      "cost": 20.00,
      "line_note": "Extra cheese",
      "taxes": [
        {
          "tax_id": "{TAX_ID}",
          "tax_amount": 4.50
        }
      ],
      "modifiers": [
        {
          "modifier_id": "{MODIFIER_ID}",
          "cost": 5.00
        }
      ]
    }
  ],
  "payments": [
    {
      "payment_type_id": "{PAYMENT_TYPE_ID}",
      "amount": 136.00
    }
  ],
  "dining_option": "DELIVERY"
}
```

**Response** (201 Created):
```json
{
  "id": "loyverse-receipt-uuid",
  "receipt_type": "SALE",
  "refund_for": null,
  "receipt_number": "R-12345",
  "receipt_date": "2025-10-17T10:30:00.000Z",
  "total_money": 136.00,
  "total_tax": 6.00,
  "points_earned": 13,
  "points_deducted": 0,
  "note": "Careem Order: CAREEM-2025-10-17-12345",
  "pos_device_id": "device-uuid",
  "store_id": "store-uuid",
  "customer_id": "customer-uuid",
  "employee_id": "employee-uuid",
  "created_at": "2025-10-17T10:30:15.000Z",
  "updated_at": "2025-10-17T10:30:15.000Z"
}
```

#### 2. Get Items (Products)

```http
GET https://api.loyverse.com/v1/items
Authorization: Bearer {ACCESS_TOKEN}
```

**Query Parameters**:
- `limit` - Results per page (default: 100, max: 250)
- `cursor` - Pagination cursor
- `deleted` - Include deleted items (true/false)

**Response**:
```json
{
  "items": [
    {
      "id": "item-uuid",
      "handle": "PIZZA-MARG",
      "item_name": "Margherita Pizza",
      "category_id": "category-uuid",
      "variants": [
        {
          "variant_id": "variant-uuid",
          "sku": "PIZZA-MARG-L",
          "variant_name": "Large",
          "price": 45.00,
          "cost": 20.00,
          "stores": [
            {
              "store_id": "store-uuid",
              "inventory_tracked": true,
              "stock_quantity": 100
            }
          ]
        }
      ],
      "modifiers": [
        {
          "modifier_id": "modifier-uuid",
          "modifier_name": "Extra Cheese",
          "price": 5.00
        }
      ],
      "taxes": [
        {
          "tax_id": "tax-uuid"
        }
      ]
    }
  ],
  "cursor": "next-page-cursor"
}
```

#### 3. Get/Create Customer

**Get Customer by ID**:
```http
GET https://api.loyverse.com/v1/customers/{customer_id}
Authorization: Bearer {ACCESS_TOKEN}
```

**Create Customer**:
```http
POST https://api.loyverse.com/v1/customers
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "name": "Careem",
  "email": "careem@integration.local",
  "phone_number": "+971501234567",
  "note": "Careem Now orders customer",
  "customer_code": "CAREEM-001"
}
```

**Response** (201 Created):
```json
{
  "id": "customer-uuid",
  "name": "Careem",
  "email": "careem@integration.local",
  "phone_number": "+971501234567",
  "customer_code": "CAREEM-001",
  "total_purchases": 0,
  "total_visits": 0,
  "created_at": "2025-10-17T10:00:00.000Z",
  "updated_at": "2025-10-17T10:00:00.000Z"
}
```

#### 4. Get Stores

```http
GET https://api.loyverse.com/v1/stores
Authorization: Bearer {ACCESS_TOKEN}
```

**Response**:
```json
{
  "stores": [
    {
      "id": "store-uuid",
      "name": "Main Branch",
      "address": "123 Main Street, Dubai",
      "phone_number": "+971501234567",
      "created_at": "2025-01-01T00:00:00.000Z"
    }
  ]
}
```

#### 5. Get POS Devices

```http
GET https://api.loyverse.com/v1/pos_devices
Authorization: Bearer {ACCESS_TOKEN}
```

#### 6. Get Employees

```http
GET https://api.loyverse.com/v1/employees
Authorization: Bearer {ACCESS_TOKEN}
```

#### 7. Get Payment Types

```http
GET https://api.loyverse.com/v1/payment_types
Authorization: Bearer {ACCESS_TOKEN}
```

**Response**:
```json
{
  "payment_types": [
    {
      "id": "payment-type-uuid",
      "name": "Cash",
      "type": "CASH"
    },
    {
      "id": "payment-type-uuid-2",
      "name": "Card",
      "type": "CARD"
    }
  ]
}
```

#### 8. Get Taxes

```http
GET https://api.loyverse.com/v1/taxes
Authorization: Bearer {ACCESS_TOKEN}
```

#### 9. Webhooks (If Supported)

```http
POST https://api.loyverse.com/v1/webhooks
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "url": "https://your-domain.com/api/webhook/loyverse",
  "events": ["receipt.created", "receipt.updated"]
}
```

### Loyverse API Rate Limiting

**Limit**: 60 requests per minute

**Rate Limit Headers** (in responses):
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1697539200
```

**Handling Rate Limits**:
```php
if ($response->status() === 429) {
    $retryAfter = $response->header('Retry-After', 60);
    // Wait and retry
    sleep($retryAfter);
}
```

### Error Responses

**400 Bad Request**:
```json
{
  "error_code": "VALIDATION_ERROR",
  "message": "Invalid request parameters",
  "details": {
    "line_items[0].item_id": ["This field is required"]
  }
}
```

**401 Unauthorized**:
```json
{
  "error_code": "UNAUTHORIZED",
  "message": "Invalid or expired access token"
}
```

**429 Too Many Requests**:
```json
{
  "error_code": "RATE_LIMIT_EXCEEDED",
  "message": "Rate limit exceeded. Please retry after 60 seconds"
}
```

**500 Internal Server Error**:
```json
{
  "error_code": "INTERNAL_ERROR",
  "message": "An unexpected error occurred"
}
```

---

## Integration Architecture

### Flow Diagram

```
┌─────────────────┐
│  Careem Now     │
│   Platform      │
└────────┬────────┘
         │
         │ Webhook POST
         │ (New Order)
         ▼
┌─────────────────────────────────┐
│   Our Laravel Service           │
│                                 │
│  ┌──────────────────────────┐  │
│  │  Webhook Controller      │  │
│  │  - Verify Signature      │  │
│  │  - Validate Payload      │  │
│  │  - Log Webhook           │  │
│  └──────────┬───────────────┘  │
│             │                   │
│             ▼                   │
│  ┌──────────────────────────┐  │
│  │  Queue Job               │  │
│  │  ProcessCareemOrderJob   │  │
│  └──────────┬───────────────┘  │
│             │                   │
│             ▼                   │
│  ┌──────────────────────────┐  │
│  │  Order Transformer       │  │
│  │  Service                 │  │
│  │  - Map Careem → Loyverse │  │
│  │  - Set customer "Careem" │  │
│  └──────────┬───────────────┘  │
│             │                   │
│             ▼                   │
│  ┌──────────────────────────┐  │
│  │  Queue Job               │  │
│  │  SyncToLoyverseJob       │  │
│  └──────────┬───────────────┘  │
│             │                   │
└─────────────┼───────────────────┘
              │
              │ API Request
              │ POST /v1/receipts
              ▼
     ┌─────────────────┐
     │  Loyverse API   │
     │   (Create       │
     │   Receipt)      │
     └─────────────────┘
```

### Service Classes to Create

#### 1. CareemWebhookService
**Responsibilities**:
- Verify webhook signatures
- Parse and validate webhook payload
- Log webhook data
- Extract order information

**File**: `app/Services/CareemWebhookService.php`

#### 2. OrderTransformerService
**Responsibilities**:
- Transform Careem order format to Loyverse receipt format
- Map product SKUs to Loyverse item IDs
- Calculate taxes and totals
- Set customer to "Careem"
- Handle modifiers and special instructions

**File**: `app/Services/OrderTransformerService.php`

#### 3. LoyverseApiService
**Responsibilities**:
- Handle Loyverse API authentication
- Create receipts via API
- Fetch items, stores, employees, payment types
- Handle rate limiting with retry logic
- Parse API responses and errors

**File**: `app/Services/LoyverseApiService.php`

#### 4. ProductMappingService
**Responsibilities**:
- Map Careem product IDs to Loyverse item IDs
- Cache product mappings
- Handle missing products
- Sync product catalog

**File**: `app/Services/ProductMappingService.php`

#### 5. SyncService
**Responsibilities**:
- Coordinate the sync process
- Track sync status
- Handle failures and retries
- Update order status
- Broadcast events

**File**: `app/Services/SyncService.php`

---

## Data Mapping

### Order/Receipt Mapping

| Careem Field | Loyverse Field | Transformation |
|--------------|----------------|----------------|
| `order.id` | `receipt_number` or `note` | Store in note for reference |
| `order.created_at` | `receipt_date` | Convert to ISO 8601 |
| Customer (any) | `customer_id` | Always map to "Careem" customer |
| `items[].product_id` | `line_items[].item_id` | Use ProductMappingService |
| `items[].quantity` | `line_items[].quantity` | Direct mapping |
| `items[].unit_price` | `line_items[].price` | Direct mapping |
| `items[].modifiers` | `line_items[].modifiers` | Map modifier names to IDs |
| `pricing.total` | Calculated from line_items | Verify totals match |
| `pricing.tax` | `line_items[].taxes` | Distribute tax across items |
| `payment.method` | `payments[].payment_type_id` | Map to Loyverse payment types |
| "delivery" | `dining_option` | Set to "DELIVERY" |

### Customer Mapping

**Strategy**: Create ONE customer called "Careem" for ALL orders.

**Loyverse Customer**:
```json
{
  "name": "Careem",
  "email": "careem@integration.local",
  "customer_code": "CAREEM-001",
  "note": "All Careem Now orders"
}
```

**Why?**: According to requirements, all Careem orders should be attributed to a single customer "Careem" in Loyverse.

### Product Mapping Strategy

**Option 1: Manual Mapping** (Recommended for Start)
- Create a `product_mappings` database table
- Admin interface to map Careem SKUs to Loyverse item IDs
- Cache mappings in Redis

**Option 2: SKU-Based Automatic Mapping**
- Match products by SKU field
- Requires identical SKUs in both systems

**Option 3: API Sync**
- Periodically sync Careem product catalog (if API available)
- Auto-create products in Loyverse

**Database Table**: `product_mappings`
```sql
CREATE TABLE product_mappings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    careem_product_id VARCHAR(255) UNIQUE NOT NULL,
    careem_sku VARCHAR(255),
    careem_name VARCHAR(255),
    loyverse_item_id VARCHAR(255) NOT NULL,
    loyverse_variant_id VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_careem_product_id (careem_product_id),
    INDEX idx_careem_sku (careem_sku)
);
```

---

## Error Handling Strategy

### Webhook Reception Errors

| Error | HTTP Code | Action |
|-------|-----------|--------|
| Invalid signature | 401 | Reject, log security alert |
| Missing required fields | 400 | Reject, log validation error |
| Duplicate order | 409 | Accept but skip processing |
| Server error | 500 | Accept, queue for retry |

### Loyverse API Errors

| Error Code | Description | Retry Strategy |
|------------|-------------|----------------|
| 400 | Bad Request | No retry, log and alert |
| 401 | Unauthorized | Refresh token, retry once |
| 404 | Not Found | No retry, check product mapping |
| 429 | Rate Limit | Exponential backoff (60s, 300s, 900s) |
| 500 | Server Error | Exponential backoff, max 5 retries |
| 503 | Service Unavailable | Exponential backoff, max 5 retries |

### Retry Logic Implementation

```php
class SyncToLoyverseJob implements ShouldQueue
{
    public $tries = 5;
    public $backoff = [60, 300, 900, 1800, 3600]; // 1min, 5min, 15min, 30min, 1hr

    public function handle()
    {
        try {
            $this->loyverseService->createReceipt($this->orderData);
        } catch (RateLimitException $e) {
            $this->release($e->getRetryAfter());
        } catch (ValidationException $e) {
            // Don't retry validation errors
            $this->fail($e);
        } catch (ApiException $e) {
            // Retry with backoff
            throw $e;
        }
    }

    public function failed(Throwable $exception)
    {
        // Log failure
        // Send alert
        // Update order status to 'failed'
    }
}
```

---

## Rate Limiting & Best Practices

### Loyverse Rate Limiting

**Limit**: 60 requests/minute

**Best Practices**:

1. **Batch Processing**:
   - Process orders in batches during low-traffic periods
   - Don't process all orders immediately

2. **Request Throttling**:
```php
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::attempt(
    'loyverse-api',
    $perMinute = 55, // Leave buffer
    function() {
        // Make API call
    },
    $decaySeconds = 60
);
```

3. **Monitor Rate Limits**:
```php
$remaining = $response->header('X-RateLimit-Remaining');
if ($remaining < 5) {
    // Slow down or wait
    sleep(10);
}
```

4. **Cache API Responses**:
   - Cache product catalog for 1 hour
   - Cache stores, employees, payment types for 24 hours

### Webhook Best Practices

1. **Quick Response**: Return 200 OK within 5 seconds
2. **Async Processing**: Queue all heavy processing
3. **Idempotency**: Handle duplicate webhooks gracefully
4. **Logging**: Log all webhooks for debugging
5. **Monitoring**: Alert on webhook failures

### Security Best Practices

1. **Signature Verification**: Always verify webhook signatures
2. **HTTPS Only**: Use SSL for all API calls
3. **Token Security**: Store tokens encrypted in database
4. **IP Whitelist**: Restrict webhook access to known IPs
5. **Rate Limiting**: Apply rate limits to webhook endpoint

---

## Implementation Checklist

### Careem Integration
- [ ] Contact Careem for API documentation
- [ ] Register webhook URL with Careem
- [ ] Obtain and store webhook secret
- [ ] Implement webhook signature verification
- [ ] Create webhook controller and routes
- [ ] Implement webhook logging
- [ ] Test with Careem sandbox

### Loyverse Integration
- [ ] Create Loyverse account
- [ ] Generate API access token
- [ ] Test API authentication
- [ ] Create "Careem" customer in Loyverse
- [ ] Get store, POS device, employee IDs
- [ ] Implement LoyverseApiService
- [ ] Test receipt creation
- [ ] Implement rate limiting
- [ ] Test error scenarios

### Product Mapping
- [ ] Create product_mappings table
- [ ] Build admin interface for mapping
- [ ] Implement ProductMappingService
- [ ] Import initial product mappings
- [ ] Handle unmapped products

### Queue & Processing
- [ ] Configure Redis queue
- [ ] Create ProcessCareemOrderJob
- [ ] Create SyncToLoyverseJob
- [ ] Implement retry logic
- [ ] Test job failures
- [ ] Monitor queue performance

### Testing
- [ ] Unit tests for services
- [ ] Feature tests for webhook
- [ ] Integration tests for full flow
- [ ] Load testing
- [ ] Error scenario testing

---

## Environment Variables

Add to `.env`:

```env
# Careem Configuration
CAREEM_WEBHOOK_SECRET=your_webhook_secret_here
CAREEM_WEBHOOK_URL=https://your-domain.com/api/webhook/careem

# Loyverse Configuration
LOYVERSE_API_URL=https://api.loyverse.com
LOYVERSE_ACCESS_TOKEN=your_access_token_here
LOYVERSE_STORE_ID=your_store_uuid
LOYVERSE_POS_DEVICE_ID=your_pos_device_uuid
LOYVERSE_EMPLOYEE_ID=your_employee_uuid
LOYVERSE_CUSTOMER_ID_CAREEM=careem_customer_uuid

# Loyverse OAuth (if using OAuth instead of token)
LOYVERSE_CLIENT_ID=
LOYVERSE_CLIENT_SECRET=
LOYVERSE_REDIRECT_URI=

# Rate Limiting
LOYVERSE_RATE_LIMIT_PER_MINUTE=55
```

---

## Next Steps

1. **Update changelog.md** with API research findings
2. **Create service classes** based on this documentation
3. **Set up local development** environment
4. **Contact Careem** for official API access
5. **Test Loyverse API** with sample data
6. **Implement product mapping** system

---

**Document Version**: 1.0
**Last Updated**: 2025-10-17
**Status**: Research Complete - Ready for Implementation
