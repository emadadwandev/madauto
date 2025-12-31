# Careem Webhook Handling Guide

## Overview

Your system handles incoming webhook events from Careem to process orders and sync them to Loyverse POS. This guide explains the complete webhook flow, authentication, and event processing.

---

## 🔐 Webhook Authentication

### Two-Layer Security

Your system implements **TWO layers** of authentication (though Careem docs only mention one):

#### Layer 1: API Key Verification (Required by Careem)
```http
x-careem-api-key: ck_pdU8nACi3bqfBZsV2hBNw2ZykdQF6xmC
```
- **Auto-generated** when tenant is created
- Format: `ck_` + 32 random characters
- **You provide this to Careem** during onboarding
- Careem sends it back in every webhook request

#### Layer 2: Webhook Signature (Your Additional Security)
```http
X-Careem-Signature: sha256=<hmac_signature>
```
- **Optional extra security** (not mentioned in Careem docs)
- Uses HMAC SHA256 signature verification
- Requires a `webhook_secret` you configure in the dashboard

### ⚠️ Important Note

**Careem's official documentation ONLY mentions `x-careem-api-key`**, not signature verification. Your implementation adds signature verification as an **optional** extra security layer:

- ✅ **Webhook Secret NOT configured**: Only `x-careem-api-key` is required (matches Careem docs)
- ⚠️ **Webhook Secret IS configured**: Both `x-careem-api-key` AND `X-Careem-Signature` are required

**Recommendation:** Leave webhook secret blank unless you have confirmed with Careem that they support signature verification.

---

## 📍 Webhook URL Structure

### Format
```
https://{subdomain}.{domain}/api/webhook/careem/{subdomain}
```

### Example
```
https://dw.madautomation.cloud/api/webhook/careem/dw
```

### Configuration
- **Subdomain-based routing** for multi-tenancy
- Each tenant gets a unique webhook URL
- Must be HTTPS with valid SSL certificate (port 443)
- Must accept POST requests

---

## 📨 Webhook Events from Careem

According to Careem API documentation, they send:

### 1. ORDER_CREATED
**When:** Customer places an order on Careem app

**Payload Example:**
```json
{
  "event_type": "ORDER_CREATED",
  "details": {
    "id": 62504546,
    "status": "pending",
    "branch": {
      "id": "a34587b290784c06",
      "name": "KFC, JLT"
    },
    "merchant_pay_type": "prepaid",
    "delivery_type": "careem",
    "customer": {
      "name": "Derek Falcon",
      "phone_number": 971588371761,
      "payment_type": "cash"
    },
    "items": [
      {
        "id": "12345",
        "name": "Burger",
        "quantity": 2,
        "price": 25.5
      }
    ],
    "price": {
      "original_total_price": 51.0,
      "total_taxable_price": 35.4,
      "tax_percentage": 5
    }
  }
}
```

### 2. ORDER_STATUS_UPDATED
**When:** Order advances to a new state

**Possible Statuses:**
- `accepted` - Order accepted by restaurant
- `ready` - Order prepared and ready for pickup
- `slot_upcoming` - Scheduled order slot approaching
- `slot_started` - Scheduled order slot started
- `driver_coming` - Captain assigned and coming
- `driver_here` - Captain arrived at pickup location
- `trip_started` - Captain picked up order
- `trip_ended` - Order delivered
- `cancelled` - Order cancelled

### 3. CATALOG_REQUEST_STATUS_UPDATED
**When:** Catalog sync completes or fails

---

## 🔄 Your System's Webhook Processing Flow

### Step 1: Webhook Received
```
POST /api/webhook/careem/dw
Headers:
  x-careem-api-key: ck_pdU8nACi3bqfBZsV2hBNw2ZykdQF6xmC
  X-Careem-Signature: sha256=abc123... (optional)
  Content-Type: application/json
```

### Step 2: Authentication Middleware
**File:** `app/Http/Middleware/VerifyWebhookSignature.php`

```php
1. Extract tenant from URL (/careem/{tenant})
2. Verify x-careem-api-key matches tenant's key (REQUIRED)
3. OPTIONAL: Verify X-Careem-Signature if webhook_secret is configured
4. Set tenant context
5. Pass to controller
```

### Step 3: Webhook Controller
**File:** `app/Http/Controllers/Api/WebhookController.php`

```php
1. Find tenant by subdomain
2. Log webhook to webhook_logs table
3. Dispatch ProcessCareemOrderJob to queue
4. Return 200 OK immediately
```

### Step 4: Process Order Job
**File:** `app/Jobs/ProcessCareemOrderJob.php`

**Queue:** `high` priority

**Process:**
1. Extract order ID from payload
2. Create order record in database (status: pending)
3. **Auto-Accept** (if enabled):
   - Call Careem API to accept order
   - Update order status to 'accepted'
4. Dispatch `SyncToLoyverseJob`

### Step 5: Sync to Loyverse
**File:** `app/Jobs/SyncToLoyverseJob.php`

**Process:**
1. Transform order data to Loyverse format
2. Map products using SKU matching
3. Create receipt in Loyverse POS
4. Update order status to 'synced'
5. Track usage for subscription limits
6. **Auto-Mark Ready** (if enabled):
   - Dispatch `MarkCareemOrderReadyJob` after 5 seconds

---

## 🎯 Current Implementation Status

### ✅ Implemented
- [x] ORDER_CREATED webhook handling
- [x] Webhook authentication (API key + signature)
- [x] Webhook logging
- [x] Queue-based processing
- [x] Order storage in database
- [x] Auto-accept orders (configurable)
- [x] Sync to Loyverse POS
- [x] Auto-mark ready (configurable)
- [x] Product mapping
- [x] Error handling and retry logic

### ❌ Not Implemented
- [ ] ORDER_STATUS_UPDATED webhook handling
- [ ] CATALOG_REQUEST_STATUS_UPDATED handling
- [ ] Self-delivery order support
- [ ] Real-time order status tracking UI

---

## 📊 Database Tables Involved

### 1. webhook_logs
**Purpose:** Log all incoming webhook requests

**Columns:**
- `tenant_id` - Which tenant received the webhook
- `payload` - Full webhook payload
- `headers` - HTTP headers
- `status` - received/processed/failed
- `error_message` - If failed
- `created_at` - When received

### 2. orders
**Purpose:** Store order details

**Columns:**
- `tenant_id` - Which tenant owns this order
- `careem_order_id` - Careem's order ID
- `order_data` - Full order payload
- `status` - pending/processing/synced/failed
- `platform_status` - Careem's order status
- `platform_status_updated_at` - Last status update

### 3. loyverse_orders
**Purpose:** Track Loyverse sync status

**Columns:**
- `order_id` - Reference to orders table
- `loyverse_order_id` - Loyverse receipt ID
- `sync_status` - success/failed
- `sync_response` - Loyverse API response
- `synced_at` - When synced

### 4. sync_logs
**Purpose:** Detailed sync operation logs

---

## 🛠️ Configuration in Dashboard

### Location
`https://dw.madautomation.cloud/api-credentials`

### Settings

#### 1. Webhook Secret (OPTIONAL)
```
Field: webhook_secret
Purpose: Optional extra security layer for webhook verification
Usage: HMAC SHA256 signature verification
Status: Not required by Careem - leave blank unless specifically needed
```

**How to set:**
1. Go to API Credentials page
2. Enter any random string (64+ characters recommended) - **or leave blank**
3. Click "Save Webhook Secret"

**⚠️ Important:** 
- **Careem's documentation does NOT require this** - they only use `x-careem-api-key`
- **Leave this blank** unless you have confirmed with Careem that they send `X-Careem-Signature` header
- If configured, webhooks without signature will be rejected

#### 2. Webhook URL
```
Display: https://dw.madautomation.cloud/api/webhook/careem/dw
Action: Copy and provide to Careem team
```

#### 3. x-careem-api-key
```
Display: ck_pdU8nACi3bqfBZsV2hBNw2ZykdQF6xmC
Action: Copy and provide to Careem team
```

---

## 🚀 How to Test Webhooks

### 1. Check Webhook Logs
```php
php artisan tinker
>>> App\Models\WebhookLog::latest()->first()
```

### 2. Simulate Webhook
Use the test scripts:
```bash
php test_webhook_validation.php dw
php compare_webhook_urls.php dw
```

### 3. Manual CURL Test
```bash
curl -X POST \
  'https://dw.madautomation.cloud/api/webhook/careem/dw' \
  -H 'Content-Type: application/json' \
  -H 'x-careem-api-key: ck_pdU8nACi3bqfBZsV2hBNw2ZykdQF6xmC' \
  -H 'X-Careem-Signature: sha256=<computed_signature>' \
  -d '{
    "event_type": "ORDER_CREATED",
    "details": {
      "id": "TEST-123",
      "status": "pending",
      "items": []
    }
  }'
```

### 4. Monitor Queue
```bash
# Check pending jobs
php artisan tinker
>>> DB::table('jobs')->count()

# Check failed jobs
>>> DB::table('failed_jobs')->count()

# Run queue worker
php artisan queue:work database --queue=high,default
```

---

## ⚙️ Tenant Settings

### Auto-Accept Orders
```php
// Enable
$tenant->setSetting('auto_accept_careem', true);

// Check status
$tenant->getSetting('auto_accept_careem', false);
```

**Effect:** Orders are automatically accepted in Careem immediately after webhook receipt

### Auto-Mark Ready
```php
// Enable
$tenant->setSetting('auto_mark_ready_careem', true);

// Check status
$tenant->getSetting('auto_mark_ready_careem', false);
```

**Effect:** Orders are automatically marked as ready in Careem 5 seconds after Loyverse sync

---

## 🔍 Troubleshooting

### Webhook Not Received

**Check:**
1. Webhook URL is correct in Careem dashboard
2. SSL certificate is valid
3. Server allows incoming connections on port 443
4. Firewall allows Careem's IP addresses

**Debug:**
```bash
# Check server logs
tail -f storage/logs/laravel-*.log

# Check webhook logs
php artisan tinker
>>> App\Models\WebhookLog::where('created_at', '>', now()->subHours(1))->count()
```

### Authenticationis configured but Careem doesn't send signature
3. Signature verification failing (if enabled)

**Fix:**
```php
// Check tenant's API key
php artisan tinker
>>> $tenant = App\Models\Tenant::where('subdomain', 'dw')->first();
>>> $tenant->careem_api_key

// Check if webhook secret is configured (should be blank for Careem)
>>> $credentials = app(App\Repositories\ApiCredentialRepository::class)->getActiveCredentials('careem');
>>> $credentials['webhook_secret'] ?? 'NOT SET'

// If webhook secret is set, consider removing it:
>>> app(App\Repositories\ApiCredentialRepository::class)->createOrUpdate('careem', ['webhook_secret' => null]);

// Check webhook secret
>>> $credentials = app(App\Repositories\ApiCredentialRepository::class)->getActiveCredentials('careem');
>>> $credentials['webhook_secret'] ?? 'NOT SET'
```

### Orders Not Processing

**Check:**
1. Queue worker is running
2. No failed jobs
3. Careem branch is mapped and active

**Commands:**
```bash
# Restart queue worker
run-queue-worker.bat

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---
✅ Authentication Simplified (DONE)
Signature verification is now **optional**:
- If `webhook_secret` is NOT configured → Only `x-careem-api-key` is required
- If `webhook_secret` IS configured → Both headers are required
- **Recommendation:** Leave webhook secret blank for Careem integrationsider:
- Making signature verification optional
- Only requiring it if `webhook_secret` is configured
- This prevents issues if Careem doesn't send `X-Careem-Signature`

### 2. Implement ORDER_STATUS_UPDATED Handler
Currently missing - would allow real-time order status tracking

### 3. Add Webhook Event Type Detection
Handle different event types (ORDER_CREATED, ORDER_STATUS_UPDATED, etc.)

### 4. Add Webhook Retry Logic
If processing fails, store for retry instead of losing the event

---

## 📞 Careem Integration Checklist

### Initial Setup
- [ ] Provide webhook URL to Careem team
- [ ] Provide x-careem-api-key to Careem team
- [ ] Create brand via Careem API
- [ ] Create branch via Careem API
- [ ] Request branch mapping from Careem operations team
- [ ] Enable POS integration for branch
- [ ] Push catalog to Careem
- [ ] Test with real order from Careem app

### Production Deployment
- [ ] Update APP_DOMAIN to production domain
- [ ] Update APP_URL to HTTPS production URL
- [ ] Configure SSL certificate
- [ ] Configure queue worker with supervisor
- [ ] Enable auto-accept and auto-mark-ready
- [ ] Monitor webhook logs
- [ ] Monitor failed jobs

---

## 🎓 Summary

Your webhook system:
1. ✅ Receives webhooks at subdomain-specific URLs
2. ✅ Verifies authentication via `x-careem-api-key` (required)
3. ✅ Optional signature verification if configured
4. ✅ Logs all webhook requests
5. ✅ Queues order processing for reliability
6. ✅ Auto-accepts orders (configurable)
7. ✅ Syncs to Loyverse POS
8. ✅ Auto-marks orders ready (configurable)
9. ⚠️ Currently only handles ORDER_CREATED events

**Next Steps:**
- Leave webhook secret blank for standard Careem integration
- Implement ORDER_STATUS_UPDATED handler for better tracking
- Test with actual Careem webhooks
