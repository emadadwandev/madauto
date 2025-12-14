# Careem Menu Sync API Documentation

## API Endpoint

**Method:** `PUT`  
**URL:** `https://apigateway-stg.careemdash.com/pos/api/v1/catalogs/{catalogId}`

Where `{catalogId}` is generated as: `catalog_{menu_id}_{branch_id}`  
Example: `catalog_5_main_branch`

---

## Authentication

**Type:** OAuth2 Bearer Token  
**Token Endpoint:** `https://identity.qa.careem-engineering.com/token`  
**Grant Type:** `client_credentials`  
**Scope:** `pos`

### Token Request:
```bash
curl -X POST "https://identity.qa.careem-engineering.com/token" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials&client_id={CLIENT_ID}&client_secret={CLIENT_SECRET}&scope=pos"
```

---

## Required Headers

| Header | Description | Example |
|--------|-------------|---------|
| `Authorization` | Bearer token from OAuth2 | `Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6...` |
| `User-Agent` | POS integration identifier | `loyverse-integration/1.0` |
| `Brand-Id` | Careem brand ID | `brand_abc123` |
| `Branch-Id` | Careem branch ID | `branch_xyz789` |
| `Content-Type` | Request content type | `application/json` |
| `Accept` | Response content type | `application/json` |

---

## Full cURL Example

```bash
curl -X PUT "https://apigateway-stg.careemdash.com/pos/api/v1/catalogs/catalog_5_main_branch" \
  -H "Authorization: Bearer {ACCESS_TOKEN}" \
  -H "User-Agent: loyverse-integration/1.0" \
  -H "Brand-Id: {BRAND_ID}" \
  -H "Branch-Id: {BRANCH_ID}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "diff": false,
    "catalog": {
      "id": "catalog_5_main_branch",
      "name": "Careem menu",
      "description": null,
      "status": "active",
      "image_url": null
    },
    "categories": [
      {
        "id": "burrata",
        "name": "Burrata"
      },
      {
        "id": "folded",
        "name": "folded"
      },
      {
        "id": "flatbread",
        "name": "flatbread"
      }
    ],
    "sub_categories": [],
    "items": [
      {
        "id": "6",
        "name": "Burrata pistachio mortadella",
        "description": "",
        "price": 6.94,
        "currency": "AED",
        "category_id": "burrata",
        "sku": "Burrata 001",
        "sort_order": 1,
        "is_available": true,
        "is_active": true,
        "image_url": "http://madautomation.cloud/storage/menu-items/NXCVvl42qtS4I2A1zQDyZj15f5bMT7jiQBebeV0R.jpg",
        "tax_rate": 16,
        "modifier_group_ids": [4],
        "external_id": "691f11cb-6885-44e6-a8d6-4ff4968960d9"
      },
      {
        "id": "7",
        "name": "Roast beef folded",
        "description": "Roast beef folded",
        "price": 6.94,
        "currency": "AED",
        "category_id": "folded",
        "sku": "002",
        "sort_order": 2,
        "is_available": true,
        "is_active": true,
        "image_url": "http://madautomation.cloud/storage/menu-items/RvxJDYUbytFYDPAmR05tGoP2jzmvcrxXTviF1cEw.webp",
        "tax_rate": 16,
        "modifier_group_ids": [4],
        "external_id": "355eec92-10fa-4443-b90c-8476c11d583c"
      }
    ],
    "groups": [
      {
        "id": "4",
        "name": "extras",
        "description": "",
        "selection_type": "multiple",
        "is_required": false,
        "min_selections": 0,
        "max_selections": 10,
        "sort_order": 0,
        "modifiers": [
          {
            "id": "193",
            "name": "Extra on Flatbread",
            "description": "",
            "price_adjustment": 0,
            "sku": null,
            "is_active": true,
            "is_available": false,
            "sort_order": 0,
            "is_default": false
          },
          {
            "id": "202",
            "name": "Extra on Flatbread - DW SPECIAL BURRATA",
            "description": "",
            "price_adjustment": 1.85,
            "sku": null,
            "is_active": true,
            "is_available": false,
            "sort_order": 1,
            "is_default": false
          }
        ]
      }
    ],
    "options": [
      {
        "id": "193",
        "name": "Extra on Flatbread",
        "description": "",
        "price_adjustment": 0,
        "sku": null,
        "is_active": true,
        "is_available": false,
        "sort_order": 0,
        "is_default": false
      },
      {
        "id": "202",
        "name": "Extra on Flatbread - DW SPECIAL BURRATA",
        "description": "",
        "price_adjustment": 1.85,
        "sku": null,
        "is_active": true,
        "is_available": false,
        "sort_order": 1,
        "is_default": false
      }
    ]
  }'
```

---

## Payload Structure

### Root Object

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `diff` | boolean | Yes | Set to `false` for full catalog sync |
| `catalog` | object | Yes | Catalog metadata |
| `categories` | array | Yes | List of item categories |
| `sub_categories` | array | No | Sub-categories (currently unused) |
| `items` | array | Yes | Menu items |
| `groups` | array | Yes | Modifier groups |
| `options` | array | Yes | Flattened list of all modifiers |

### Catalog Object

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | string | Yes | Catalog ID (matches URL parameter) |
| `name` | string | Yes | Catalog/menu name |
| `description` | string | No | Catalog description |
| `status` | string | Yes | `active` or `inactive` |
| `image_url` | string | No | Catalog image URL |

### Category Object

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | string | Yes | Category slug (e.g., `burrata`) |
| `name` | string | Yes | Display name |

### Item Object

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | string | Yes | Item ID |
| `name` | string | Yes | Item name |
| `description` | string | No | Item description |
| `price` | number | Yes | Item price |
| `currency` | string | Yes | Currency code (e.g., `AED`) |
| `category_id` | string | Yes | Category slug |
| `sku` | string | No | Stock keeping unit |
| `sort_order` | integer | No | Display order |
| `is_available` | boolean | Yes | Availability status |
| `is_active` | boolean | Yes | Active status |
| `image_url` | string | No | Item image URL |
| `tax_rate` | number | No | Tax percentage |
| `modifier_group_ids` | array | No | List of modifier group IDs |
| `external_id` | string | No | Loyverse item UUID |

### Group Object (Modifier Group)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | string | Yes | Group ID |
| `name` | string | Yes | Group name |
| `description` | string | No | Group description |
| `selection_type` | string | Yes | `single` or `multiple` |
| `is_required` | boolean | Yes | Whether selection is mandatory |
| `min_selections` | integer | Yes | Minimum selections allowed |
| `max_selections` | integer | Yes | Maximum selections allowed |
| `sort_order` | integer | No | Display order |
| `modifiers` | array | Yes | List of modifiers in this group |

### Option Object (Modifier)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | string | Yes | Modifier ID |
| `name` | string | Yes | Modifier name |
| `description` | string | No | Modifier description |
| `price_adjustment` | number | Yes | Price adjustment (can be 0) |
| `sku` | string | No | Stock keeping unit |
| `is_active` | boolean | Yes | Active status |
| `is_available` | boolean | Yes | Availability status |
| `sort_order` | integer | No | Display order |
| `is_default` | boolean | Yes | Default selection |

---

## Key Changes from Previous Implementation

### 1. **Endpoint Changed**
- **Old:** `POST /catalogs`
- **New:** `PUT /catalogs/{catalogId}`

### 2. **Added Required Headers**
- `User-Agent`: Identifies the POS integration
- `Brand-Id`: Careem brand identifier
- `Branch-Id`: Careem branch identifier

### 3. **Payload Structure Changes**

#### Added `catalog.id` field:
```json
"catalog": {
  "id": "catalog_5_main_branch",  // ✅ NEW
  "name": "Main catalog"
}
```

#### Simplified `categories`:
```json
// OLD
{
  "id": "burgers",
  "name": "Burgers",
  "description": "Items in Burgers category",
  "sort_order": 0,
  "item_ids": [1, 2, 3]
}

// NEW
{
  "id": "burgers",
  "name": "Burgers"
}
```

#### Changed `category` to `category_id` in items:
```json
// OLD
"category": "burgers"

// NEW
"category_id": "burgers"
```

#### Added `currency` field to items:
```json
"currency": "AED"  // ✅ NEW - Required by Careem
```

---

## Response

### Success Response (200 OK)
```json
{
  "catalog_id": "catalog_5_main_branch",
  "status": "accepted",
  "message": "Catalog updated successfully"
}
```

### Error Response (400/401/422)
```json
{
  "error": "validation_error",
  "message": "Invalid catalog data",
  "details": {
    "items.0.currency": ["The currency field is required"]
  }
}
```

---

## How System Generates Payload

### 1. **CareemMenuTransformer** (`app/Services/CareemMenuTransformer.php`)
- Transforms Laravel `Menu` model to Careem format
- Generates `catalogId` from menu and branch
- Adds required fields like `currency`, `category_id`

### 2. **CareemApiService** (`app/Services/CareemApiService.php`)
- Handles OAuth2 authentication
- Adds required headers (`User-Agent`, `Brand-Id`, `Branch-Id`)
- Makes `PUT` request to `/catalogs/{catalogId}`

### 3. **SyncMenuToPlatformJob** (`app/Jobs/SyncMenuToPlatformJob.php`)
- Gets brand and branch IDs from menu's location
- Generates or retrieves `catalogId`
- Saves `catalogId` to database for future updates
- Dispatches sync to background queue

---

## Testing the API

### Generate Test Payload
```bash
php artisan careem:generate-payload {menu_id} --format=pretty --save=test-payload.json
```

### View Sync Logs
```bash
tail -f storage/logs/laravel.log | grep "Careem catalog"
```

---

## Troubleshooting

### Common Errors

**401 Unauthorized**
- Check `client_id` and `client_secret` in API Credentials
- Verify token is not expired (50-minute cache)

**400 Bad Request - Missing currency**
- Ensure all items have `"currency": "AED"` field
- Check transformer adds currency to all items

**400 Bad Request - Invalid category_id**
- Verify `category_id` in items matches a category `id`
- Category IDs must be slugified (lowercase, hyphens)

**422 Validation Error - Missing Brand-Id/Branch-Id**
- Ensure menu is assigned to a location
- Location must be mapped to a Careem branch
- Branch must be linked to a Careem brand

---

## Related Files

- **Transformer:** `app/Services/CareemMenuTransformer.php`
- **API Client:** `app/Services/CareemApiService.php`
- **Sync Job:** `app/Jobs/SyncMenuToPlatformJob.php`
- **Controller:** `app/Http/Controllers/Dashboard/MenuController.php`
- **Config:** `config/platforms.php`
- **Command:** `app/Console/Commands/GenerateCareemPayload.php`
