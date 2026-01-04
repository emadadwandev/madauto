# Careem Webhook Integration Guide

## For Careem Support Team

This document provides the technical details for configuring the Careem webhook to send order notifications to our integration system.

---

## Webhook Endpoint

```
POST https://yourdomain.com/api/webhook/careem/{tenant_subdomain}
```

**Example**: `https://yourdomain.com/api/webhook/careem/restaurant1`

Replace `{tenant_subdomain}` with the actual tenant subdomain assigned to the restaurant.

---

## Required Authentication Header

```
x-careem-api-key: ck_your_generated_32_character_key
```

**Important Notes**:
- Header name is **case-sensitive**: Must be exactly `x-careem-api-key` (lowercase with hyphens)
- The API key is auto-generated and displayed in Settings → API Credentials page
- Key format: `ck_` prefix followed by 32 random characters
- Each tenant has a unique API key

---

## Test cURL Request

Use this command to test the webhook integration:

```bash
curl -X POST https://yourdomain.com/api/webhook/careem/restaurant1 \
  -H "Content-Type: application/json" \
  -H "x-careem-api-key: ck_abc123def456xyz789..." \
  -d '{
    "order_id": "14588895",
    "status": "accepted",
    "created_at": "2026-01-04T14:30:00Z",
    "items": [
      {
        "id": 123,
        "name": "Test Item",
        "quantity": 1,
        "price": 25.50
      }
    ],
    "pricing": {
      "total": 25.50,
      "subtotal": 25.50,
      "tax": 0
    },
    "customer": {
      "name": "Test Customer",
      "phone": "+971501234567"
    }
  }'
```

---

## Authentication Flow

Our webhook endpoint validates the `x-careem-api-key` header through the following process:

### Step 1: URL Parsing
```
URL: /api/webhook/careem/restaurant1
     ↓
Extract: tenant_subdomain = "restaurant1"
```

### Step 2: Tenant Lookup
```php
$tenant = Tenant::where('subdomain', 'restaurant1')->first();
```

### Step 3: Header Validation
```php
$apiKey = $request->header('x-careem-api-key');
if ($apiKey !== $tenant->careem_api_key) {
    abort(401, 'Invalid or missing x-careem-api-key header.');
}
```

### Step 4: Response

**✅ Success (HTTP 200)**
```json
{
  "success": true,
  "message": "Careem order received and queued for processing"
}
```

**❌ Invalid Key (HTTP 401)**
```json
{
  "message": "Invalid or missing x-careem-api-key header."
}
```

**❌ Tenant Not Found (HTTP 404)**
```json
{
  "message": "Tenant not found."
}
```

**❌ Bad Request (HTTP 400)**
```json
{
  "message": "Tenant not specified in webhook URL."
}
```

---

## Implementation Details

### Middleware: `VerifyWebhookSignature`
Location: `app/Http/Middleware/VerifyWebhookSignature.php`

```php
public function handle(Request $request, Closure $next)
{
    // 1. Extract tenant subdomain from URL
    $tenant = $request->route('tenant');
    
    // 2. Find tenant in database
    $tenantModel = Tenant::where('subdomain', $tenant)->first();
    
    // 3. Validate x-careem-api-key header
    $apiKey = $request->header('x-careem-api-key');
    if (!$apiKey || $apiKey !== $tenantModel->careem_api_key) {
        abort(401, 'Invalid or missing x-careem-api-key header.');
    }
    
    // 4. Allow request to proceed
    return $next($request);
}
```

### Controller: `WebhookController`
Location: `app/Http/Controllers/Api/WebhookController.php`

```php
public function handleCareem(CareemOrderRequest $request, string $tenant)
{
    // Log webhook receipt
    WebhookLog::create([
        'tenant_id' => $tenantModel->id,
        'payload' => $request->all(),
        'headers' => $request->header(),
        'status' => 'received',
    ]);
    
    // Queue order processing
    ProcessCareemOrderJob::dispatch($request->validated(), $tenantModel->id);
    
    return response()->json([
        'success' => true,
        'message' => 'Careem order received and queued for processing'
    ]);
}
```

---

## Troubleshooting Checklist

Please verify the following on Careem's end:

### ✅ Webhook Configuration
- [ ] **Webhook URL**: Correct format with tenant subdomain
- [ ] **HTTP Method**: POST (not GET)
- [ ] **Content-Type**: `application/json`
- [ ] **Timeout**: At least 30 seconds

### ✅ Header Configuration
- [ ] **Header Name**: Exactly `x-careem-api-key` (lowercase)
- [ ] **Header Value**: Complete API key with `ck_` prefix
- [ ] **Character Length**: 35 characters total (ck_ + 32 random)
- [ ] **No Spaces**: Key should not have leading/trailing spaces

### ✅ Payload Requirements
- [ ] **Valid JSON**: Properly formatted JSON body
- [ ] **Required Fields**: order_id, status, items array
- [ ] **Encoding**: UTF-8 character encoding

---

## Common Issues & Solutions

### Issue 1: 401 Unauthorized
**Symptoms**: Receiving "Invalid or missing x-careem-api-key header"

**Possible Causes**:
1. Header name is incorrect (e.g., `X-Careem-API-Key` instead of `x-careem-api-key`)
2. API key doesn't match the tenant's stored key
3. API key has extra spaces or characters
4. Header is not being sent at all

**Solution**:
- Copy the exact API key from Settings → API Credentials
- Verify header name is lowercase with hyphens
- Test with cURL command above

### Issue 2: 404 Not Found
**Symptoms**: Receiving "Tenant not found"

**Possible Causes**:
1. Tenant subdomain in URL is incorrect
2. Tenant doesn't exist in database
3. URL format is wrong

**Solution**:
- Verify tenant subdomain matches Settings page
- Check URL format: `/api/webhook/careem/{subdomain}`

### Issue 3: 400 Bad Request
**Symptoms**: Receiving "Tenant not specified in webhook URL"

**Possible Causes**:
1. Missing tenant subdomain in URL
2. URL doesn't match route pattern

**Solution**:
- Ensure URL includes tenant subdomain after `/careem/`

---

## Testing Steps

### 1. Get Your Credentials
Navigate to your tenant's dashboard:
```
https://yourdomain.com/dashboard/{subdomain}/api-credentials
```

Copy the following:
- **Webhook URL**: Full URL displayed on the page
- **x-careem-api-key**: The auto-generated key shown

### 2. Test with cURL
Replace the placeholders in the cURL command with your actual values:

```bash
# Replace these values:
# - yourdomain.com → Your actual domain
# - restaurant1 → Your tenant subdomain
# - ck_abc123... → Your actual API key

curl -X POST https://yourdomain.com/api/webhook/careem/restaurant1 \
  -H "Content-Type: application/json" \
  -H "x-careem-api-key: ck_abc123def456xyz789..." \
  -d '{
    "order_id": "TEST123",
    "status": "accepted",
    "created_at": "2026-01-04T14:30:00Z",
    "items": [{"id": 1, "name": "Test", "quantity": 1, "price": 10}],
    "pricing": {"total": 10}
  }'
```

### 3. Expected Success Response
```json
{
  "success": true,
  "message": "Careem order received and queued for processing"
}
```

### 4. Verify in System
- Check Dashboard → Orders for the test order
- Check Dashboard → Webhook Logs for the request details
- Check Dashboard → Sync Logs for processing status

---

## Security Features

### 1. Tenant Isolation
- Each tenant has a unique API key
- Webhooks are isolated per tenant
- No cross-tenant data access

### 2. API Key Authentication
- **Required**: Every request must include valid `x-careem-api-key`
- **Validation**: Header compared against tenant's stored key
- **Rejection**: Invalid keys receive 401 Unauthorized

### 3. Optional Signature Verification
- **Additional Layer**: HMAC signature validation (if configured)
- **Header**: `X-Careem-Signature`
- **Algorithm**: SHA-256 HMAC
- **Note**: This is optional and not required by Careem's official documentation

### 4. Request Logging
- All webhook requests are logged
- Includes: payload, headers, timestamp
- Used for debugging and audit trail

---

## Production Deployment

### Current Status
✅ **Endpoint is LIVE and ready to receive webhooks**

We have successfully:
- ✅ Implemented webhook endpoint with proper routing
- ✅ Added authentication middleware
- ✅ Configured order processing queue
- ✅ Set up error logging and monitoring
- ✅ Tested with sample payloads

### What We Need from Careem
1. **Configure Webhook URL** in your Careem merchant dashboard
2. **Add x-careem-api-key header** to webhook requests
3. **Test webhook** with a real order
4. **Confirm receipt** of successful webhook delivery

---

## Support & Contact

If you encounter any issues:

1. **Check Webhook Logs**: Available in tenant dashboard
2. **Verify API Key**: Must match exactly (including `ck_` prefix)
3. **Test with cURL**: Use the command provided above
4. **Share Error Details**: Include HTTP status code and response body

We're ready to assist with:
- Webhook configuration questions
- Authentication troubleshooting
- Payload format clarification
- Integration testing support

---

## Quick Reference

| Item | Value |
|------|-------|
| **Endpoint Format** | `POST /api/webhook/careem/{subdomain}` |
| **Required Header** | `x-careem-api-key` |
| **Content-Type** | `application/json` |
| **Success Status** | `200 OK` |
| **Auth Failure** | `401 Unauthorized` |
| **Not Found** | `404 Not Found` |
| **Key Format** | `ck_` + 32 characters |
| **Timeout** | 30 seconds recommended |

---

**Document Version**: 1.0  
**Last Updated**: January 4, 2026  
**Status**: Production Ready ✅
