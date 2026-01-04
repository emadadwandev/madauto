# Email Response to Careem Support Team

---

**Subject**: Re: Webhook Integration - x-careem-api-key Header Configuration

---

Hello,

Thank you for looking into this issue. I appreciate your help with the webhook integration.

I want to clarify that the error message you mentioned is actually coming **FROM our system**, not from Careem's system. This means our webhook endpoint is successfully receiving the requests from Careem, but we're rejecting them because the required `x-careem-api-key` header is **missing or incorrect** in the webhook requests that Careem is sending to us.

## The Issue

Our system requires authentication for all incoming webhook requests. The error message:
```
"Invalid or missing x-careem-api-key header."
```

This indicates that Careem's webhook system needs to **include** the `x-careem-api-key` header when making POST requests to our webhook endpoint.

## What Needs to Be Done

**Action Required**: Careem's webhook configuration needs to add the `x-careem-api-key` header to all webhook POST requests sent to our endpoint.

## Our Webhook Endpoint Details

**URL Format**:
```
POST https://yourdomain.com/api/webhook/careem/{tenant_subdomain}
```

**Example**: `https://dw.madautomation.cloud/api/webhook/careem/dw`

## Required Authentication Header (Must Be Added by Careem)

When Careem's webhook system sends POST requests to our endpoint, it **MUST include** this header:

```
x-careem-api-key: ck_your_generated_32_character_key
```

**Important Notes**:
- Header name must be **lowercase** with hyphens: `x-careem-api-key`
- The API key is auto-generated and displayed in our Settings → API Credentials page
- Key format: `ck_` prefix followed by 32 random characters (total 35 characters)
- Each tenant has a unique API key

**This header must be configured in Careem's webhook settings** so that it is automatically included in every webhook POST request sent to our endpoint.

## Example: How Careem's Webhook Should Send Requests

Here's a cURL example showing exactly how Careem's webhook system should make the request to our endpoint:

```bash
curl -X POST https://yourdomain.com/api/webhook/careem/restaurant1 \
  -H "Content-Type: application/json" \
  -H "x-careem-api-key: ck_abc123def456xyz789..." \
  -d '{
    "order_id": "TEST123",
    "status": "accepted",
    "created_at": "2026-01-04T14:30:00Z",
    "items": [
      {
        "id": 1,
        "name": "Test Item",
        "quantity": 1,
        "price": 10.00
      }
    ],
    "pricing": {
      "total": 10.00
    }
  }'
```

**Notice**: The `x-careem-api-key` header is included in the request. This is what's currently missing from Careem's webhook requests to our system.

## Summary

**Current Situation**:
- ✅ Our webhook endpoint is live and working
- ✅ Careem is successfully reaching our endpoint
- ❌ Careem is NOT including the `x-careem-api-key` header in the requests
- ❌ Our system rejects the requests due to missing authentication header

**What We Need from Careem**:
Please configure your webhook system to include the `x-careem-api-key` header in all POST requests to our webhook endpoint. The API key value that should be sent is: `ck_xxxxx...` (I can provide the exact value separately for security).

**Next Steps**:
1. Add the `x-careem-api-key` header to Careem's webhook configuration
2. Set the header value to the API key we provide
3. Test the webhook with a sample order
4. Confirm successful delivery (our system will respond with HTTP 200 and success message)

## Expected Responses After Header Is Added

**Success (HTTP 200)**:
```json
{
  "success": true,
  "message": "Careem order received and queued for processing"
}
```

**Invalid or Missing Key (HTTP 401)** - This is what's currently happening:
```json
{
  "message": "Invalid or missing x-careem-api-key header."
}
```

## Where to Configure the Header in Careem's System

In your webhook configuration panel, there should be an option to add custom headers. Please add:
- **Header Name**: `x-careem-api-key`
- **Header Value**: `ck_xxxxx...` (the API key specific to this tenant)

If you need the exact API key value or have questions about where to configure this in your system, please let me know and I'll provide the specific key and any additional details you need.

I hope this clarifies the situation. Our endpoint is ready and waiting - we just need Careem's webhook system to include the authentication header in the requests.

Please let me know once the header has been added so we can test the integration together.

Best regards,  
[Your Name]  
[Your Company]

