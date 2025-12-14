# Testing Careem API with Postman

This guide shows you how to test the Careem API directly using Postman to inspect API responses and debug issues.

## Prerequisites

1. **Postman installed** (Download from https://www.postman.com/downloads/)
2. **Careem API Credentials** from your Settings → API Credentials page

## Step 1: Get Your API Credentials

First, retrieve your Careem API credentials from the application:

### Option A: From UI
1. Log into your tenant dashboard
2. Go to **Settings** → **API Credentials**
3. Find the **Careem Catalog API** section
4. Copy these values:
   - Client ID
   - Client Secret
   - Client Name (optional)
   - User Agent (optional, default: `loyverse-integration/1.0`)

### Option B: From Database
```bash
php artisan tinker
```
```php
DB::table('api_credentials')
    ->where('service', 'careem_catalog')
    ->where('is_active', true)
    ->get(['credential_type', 'credential_value']);
```

### Option C: From Config (Dev Only)
Check your `.env` file:
```env
CAREEM_CLIENT_ID=your_client_id_here
CAREEM_CLIENT_SECRET=your_client_secret_here
```

## Step 2: Get OAuth2 Access Token

### Request Details
- **Method**: `POST`
- **URL**: `https://identity.qa.careem-engineering.com/token`
- **Content-Type**: `application/x-www-form-urlencoded`

### Headers
```
Content-Type: application/x-www-form-urlencoded
```

### Body (x-www-form-urlencoded)
```
grant_type: client_credentials
client_id: YOUR_CLIENT_ID
client_secret: YOUR_CLIENT_SECRET
scope: pos
```

### Postman Setup
1. Create new request in Postman
2. Set method to **POST**
3. Enter URL: `https://identity.qa.careem-engineering.com/token`
4. Go to **Body** tab
5. Select **x-www-form-urlencoded**
6. Add the key-value pairs above
7. Click **Send**

### Expected Response (200 OK)
```json
{
  "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "scope": "pos"
}
```

### Save the Token
Copy the `access_token` value - you'll need it for the next step.

## Step 3: Submit Catalog to Careem

### Get Required Data

**Brand ID and Branch ID:**
```bash
php artisan tinker
```
```php
// Get a menu with branch mapping
$menu = App\Models\Menu::with('locations.careemBranch.brand')->first();
$branchId = $menu->locations->first()?->careemBranch?->careem_branch_id;
$brandId = $menu->locations->first()?->careemBranch?->brand?->careem_brand_id;
$catalogId = $menu->careem_catalog_id ?? 'catalog_' . $menu->id . '_' . $branchId;

echo "Branch ID: $branchId\n";
echo "Brand ID: $brandId\n";
echo "Catalog ID: $catalogId\n";
```

**Catalog Payload:**
```php
// Generate the actual payload
$menu = App\Models\Menu::with(['items.modifierGroups.modifiers', 'locations'])->find(1);
$transformer = new App\Services\CareemMenuTransformer();
$catalogData = $transformer->transform($menu, $catalogId);
echo json_encode($catalogData, JSON_PRETTY_PRINT);
```

### Request Details
- **Method**: `PUT`
- **URL**: `https://apigateway-stg.careemdash.com/pos/api/v1/catalogs/{catalogId}`
- **Replace** `{catalogId}` with your actual catalog ID (e.g., `catalog_1_456`)

### Headers
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
Accept: application/json
User-Agent: loyverse-integration/1.0
Brand-Id: YOUR_BRAND_ID
Branch-Id: YOUR_BRANCH_ID
```

### Body (raw JSON)
Use the payload generated from the tinker command above, or use this template:

```json
{
  "diff": false,
  "catalog": {
    "id": "catalog_1_456"
  },
  "categories": [
    {
      "category_id": "cat_1",
      "title": "Main Dishes"
    }
  ],
  "sub_categories": [],
  "items": [
    {
      "item_id": "item_1",
      "title": "Burger",
      "description": "Delicious burger",
      "price": 25.00,
      "currency": "AED",
      "category_id": "cat_1",
      "available": true,
      "group_ids": []
    }
  ],
  "groups": [],
  "options": []
}
```

### Postman Setup
1. Create new request
2. Set method to **PUT**
3. Enter URL with your catalog ID
4. Go to **Headers** tab, add all headers above
5. Go to **Body** tab
6. Select **raw** and **JSON**
7. Paste your catalog payload
8. Click **Send**

### Expected Responses

#### Success (200 OK)
```json
{
  "catalog_id": "catalog_1_456",
  "request_id": "req_abc123xyz",
  "status": "accepted",
  "message": "Catalog submitted successfully"
}
```

#### Processing (202 Accepted)
```json
{
  "request_id": "req_abc123xyz",
  "status": "processing",
  "message": "Catalog is being processed"
}
```

#### Error (400 Bad Request)
```json
{
  "error": "INVALID_BRAND_ID",
  "message": "The provided brand_id is not valid",
  "details": {
    "field": "brand_id",
    "value": "invalid_brand"
  }
}
```

#### Error (401 Unauthorized)
```json
{
  "error": "UNAUTHORIZED",
  "message": "Invalid or expired access token"
}
```

#### Error (422 Unprocessable Entity)
```json
{
  "error": "VALIDATION_ERROR",
  "message": "The request data is invalid",
  "errors": {
    "items.0.price": ["The price field is required"],
    "items.0.currency": ["The currency must be AED"]
  }
}
```

## Step 4: Check Catalog Status (Optional)

If you received a `request_id`, you can check the processing status:

### Request Details
- **Method**: `GET`
- **URL**: `https://apigateway-stg.careemdash.com/pos/api/v1/catalogs/status/{request_id}`

### Headers
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

### Expected Response
```json
{
  "request_id": "req_abc123xyz",
  "status": "accepted",
  "catalog_id": "catalog_1_456",
  "processed_at": "2025-12-14T10:30:00Z"
}
```

Possible status values:
- `pending` - Submitted, awaiting processing
- `processing` - Being processed
- `accepted` - Successfully processed
- `rejected` - Processing failed (check message for reason)

## Step 5: Debugging Common Issues

### Issue 1: 401 Unauthorized
**Problem**: Token expired or invalid

**Solution**:
1. Get a fresh access token (Step 2)
2. Make sure you're using `Bearer` prefix in Authorization header
3. Check token hasn't expired (valid for 60 minutes)

### Issue 2: 400 Invalid Brand/Branch ID
**Problem**: Brand ID or Branch ID doesn't exist in Careem system

**Solution**:
```bash
php artisan tinker
```
```php
// List all configured branches
$branches = App\Models\CareemBranch::with('brand')->get();
foreach ($branches as $branch) {
    echo "Branch: {$branch->name}\n";
    echo "Branch ID: {$branch->careem_branch_id}\n";
    echo "Brand ID: {$branch->brand->careem_brand_id}\n";
    echo "---\n";
}
```

### Issue 3: 422 Validation Error
**Problem**: Catalog data doesn't match Careem's schema

**Solution**:
1. Check error response for specific field errors
2. Common issues:
   - Missing required fields (title, price, currency)
   - Wrong currency (must be "AED")
   - Invalid category_id references
   - Price must be positive number

### Issue 4: Timeout
**Problem**: Request taking too long

**Solution**:
1. Check your internet connection
2. Reduce payload size (fewer items)
3. Try staging URL instead of production

## Step 6: Export for Support

If you need to share your API test with support:

1. In Postman, click the **Code** button (</> icon)
2. Select **cURL** from dropdown
3. Copy the cURL command
4. Share with support team

Example cURL export:
```bash
curl --location --request PUT 'https://apigateway-stg.careemdash.com/pos/api/v1/catalogs/catalog_1_456' \
--header 'Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...' \
--header 'Content-Type: application/json' \
--header 'Brand-Id: brand_123' \
--header 'Branch-Id: branch_456' \
--header 'User-Agent: loyverse-integration/1.0' \
--data-raw '{
  "diff": false,
  "catalog": { "id": "catalog_1_456" },
  "categories": [...],
  "items": [...]
}'
```

## Environment Variables

For easier testing, create a Postman Environment:

1. Click **Environments** (left sidebar)
2. Click **+** to create new environment
3. Add these variables:

| Variable | Initial Value | Current Value |
|----------|--------------|---------------|
| `base_url` | `https://apigateway-stg.careemdash.com/pos/api/v1` | Same |
| `token_url` | `https://identity.qa.careem-engineering.com/token` | Same |
| `client_id` | Your client ID | Same |
| `client_secret` | Your client secret | Same |
| `access_token` | (empty) | (gets filled after token request) |
| `brand_id` | Your brand ID | Same |
| `branch_id` | Your branch ID | Same |
| `catalog_id` | `catalog_1_456` | Same |

4. In token request, add this to **Tests** tab:
```javascript
var jsonData = pm.response.json();
pm.environment.set("access_token", jsonData.access_token);
```

5. Now use `{{access_token}}` in Authorization header
6. Use `{{base_url}}/catalogs/{{catalog_id}}` for catalog URL

## Quick Test Collection

Import this Postman collection:

```json
{
  "info": {
    "name": "Careem Catalog API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "1. Get Access Token",
      "event": [
        {
          "listen": "test",
          "script": {
            "exec": [
              "var jsonData = pm.response.json();",
              "pm.environment.set(\"access_token\", jsonData.access_token);"
            ]
          }
        }
      ],
      "request": {
        "method": "POST",
        "header": [],
        "body": {
          "mode": "urlencoded",
          "urlencoded": [
            {"key": "grant_type", "value": "client_credentials"},
            {"key": "client_id", "value": "{{client_id}}"},
            {"key": "client_secret", "value": "{{client_secret}}"},
            {"key": "scope", "value": "pos"}
          ]
        },
        "url": "{{token_url}}"
      }
    },
    {
      "name": "2. Submit Catalog",
      "request": {
        "method": "PUT",
        "header": [
          {"key": "Authorization", "value": "Bearer {{access_token}}"},
          {"key": "Content-Type", "value": "application/json"},
          {"key": "Brand-Id", "value": "{{brand_id}}"},
          {"key": "Branch-Id", "value": "{{branch_id}}"},
          {"key": "User-Agent", "value": "loyverse-integration/1.0"}
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"diff\": false,\n  \"catalog\": {\n    \"id\": \"{{catalog_id}}\"\n  },\n  \"categories\": [],\n  \"items\": []\n}"
        },
        "url": "{{base_url}}/catalogs/{{catalog_id}}"
      }
    }
  ]
}
```

Save this as `careem-api.postman_collection.json` and import it into Postman.

## Comparing with Application Logs

After testing in Postman, compare with application logs:

1. Run the same catalog sync from your application
2. Check the menu sync log in dashboard
3. Compare API responses:
   - Postman response vs database metadata
   - Look for differences in payload structure
   - Check if headers match

```bash
# View latest sync log
php artisan tinker
```
```php
$log = App\Models\MenuSyncLog::latest()->first();
echo "Status: {$log->status}\n";
echo "Message: {$log->message}\n";
echo "API Response:\n";
print_r($log->metadata['api_response'] ?? 'No response data');
```

## Production vs Staging

**Staging URLs (Current):**
- Token: `https://identity.qa.careem-engineering.com/token`
- API: `https://apigateway-stg.careemdash.com/pos/api/v1`

**Production URLs (When ready):**
- Token: `https://identity.careem.com/token`
- API: `https://apigateway.careemdash.com/pos/api/v1`

Make sure you're testing against the correct environment!

## Need Help?

If you still face issues after testing with Postman:

1. Capture the full request/response from Postman
2. Check the menu sync log in your dashboard
3. Compare both responses
4. Share both with the development team
5. Include:
   - HTTP status code
   - Request headers
   - Request body
   - Response body
   - Error messages
