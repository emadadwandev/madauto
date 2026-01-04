# Loyverse Receipt Creation Payload

## Overview
This document shows the complete structure of the payload sent to Loyverse API when creating a sales receipt from a Careem order.

## API Endpoint
```
POST https://api.loyverse.com/v1.0/receipts
Authorization: Bearer {access_token}
Content-Type: application/json
```

## Complete Payload Structure

```json
{
  "store_id": "uuid-from-tenant-settings",
  "source": "Careem Integration System",
  "receipt_type": "SALE",
  "receipt_date": "2026-01-04T14:30:00.000Z",
  "note": "Careem Order: 14588895",
  "dining_option": "DELIVERY",
  "customer_id": "customer-uuid-from-loyverse",
  "employee_id": "optional-employee-uuid",
  "pos_device_id": "optional-pos-device-uuid",
  "line_items": [
    {
      "item_id": "product-uuid-from-mapping",
      "variant_id": "variant-uuid-if-applicable",
      "quantity": 2,
      "price": 25.50,
      "line_note": "extra burrata cheese (+1.79), extra caramelized onions (+0.60)"
    },
    {
      "item_id": "another-product-uuid",
      "quantity": 1,
      "price": 15.00,
      "line_note": "Special instructions: No onions, remove rocca"
    }
  ],
  "payments": [
    {
      "payment_type_id": "payment-type-uuid",
      "amount": 66.00
    }
  ]
}
```

## Field Descriptions

### Required Fields

| Field | Type | Description | Source |
|-------|------|-------------|--------|
| `store_id` | string (UUID) | Loyverse store ID | `tenant()->loyverse_store_id` |
| `source` | string | System creating the receipt | Fixed: `"Careem Integration System"` (CIS) |
| `customer_id` | string (UUID) | Customer in Loyverse | Created/fetched: "Careem" or "Talabat" customer |
| `line_items` | array | Items being sold | Mapped from Careem order items |
| `payments` | array | Payment methods | Payment type matching platform name |

### Optional Fields

| Field | Type | Description | Source |
|-------|------|-------------|--------|
| `receipt_type` | string | Type of receipt | Default: `"SALE"` |
| `receipt_date` | string (ISO 8601) | When receipt was created | From order `created_at` or now |
| `note` | string | Receipt notes | Format: `"{Platform} Order: {order_id}"` |
| `dining_option` | string | Service type | Default: `"DELIVERY"` |
| `employee_id` | string (UUID) | Employee who processed | From config (optional) |
| `pos_device_id` | string (UUID) | POS device used | From config (optional) |

### Line Item Structure

```json
{
  "item_id": "uuid",           // Required: Mapped product UUID
  "variant_id": "uuid",         // Optional: If product has variants
  "quantity": 2,                // Required: Number of items
  "price": 25.50,               // Required: Unit price
  "line_note": "modifiers..."   // Optional: Modifiers & instructions
}
```

### Payment Structure

```json
{
  "payment_type_id": "uuid",    // Required: Payment type UUID
  "amount": 66.00               // Required: Total amount
}
```

## Data Flow

### 1. Store Selection
```
User → Settings → Fetch Stores → Select Store
                                     ↓
                          tenant()->loyverse_store_id
```

### 2. Order Received
```
Careem Webhook → ProcessCareemOrderJob → SyncToLoyverseJob
```

### 3. Transformation
```
OrderTransformerService:
  - Fetch customer (Careem/Talabat)
  - Map products (ProductMappingService)
  - Enrich modifiers (OrderModifierEnrichmentService)
  - Get payment type (by platform name)
  - Build receipt payload with store_id
```

### 4. API Call
```
LoyverseApiService:
  - Add Authorization header
  - Rate limit (55 req/min)
  - Retry on 429/503
  - POST to /v1.0/receipts
```

## Example: Complete Order Flow

### Careem Order Input
```json
{
  "id": 14588895,
  "items": [
    {
      "id": 123,
      "name": "Burrata Sandwich",
      "quantity": 2,
      "price": 25.50,
      "groups": [
        {
          "id": 20,
          "name": "EXTRA ON SANDWICHES",
          "options": [
            {
              "id": 227,
              "name": "extra burrata cheese",
              "price": 1.79,
              "quantity": 1
            }
          ]
        }
      ]
    }
  ],
  "pricing": {
    "total": 66.00,
    "subtotal": 60.00,
    "tax": 6.00
  }
}
```

### Loyverse Receipt Output
```json
{
  "store_id": "abc-123-store-uuid",
  "source": "Careem Integration System",
  "receipt_type": "SALE",
  "receipt_date": "2026-01-04T14:30:00.000Z",
  "note": "Careem Order: 14588895",
  "customer_id": "careem-customer-uuid",
  "line_items": [
    {
      "item_id": "burrata-sandwich-uuid",
      "quantity": 2,
      "price": 25.50,
      "line_note": "extra burrata cheese (+1.79)"
    }
  ],
  "payments": [
    {
      "payment_type_id": "careem-payment-type-uuid",
      "amount": 66.00
    }
  ]
}
```

## Validation Rules

### Pre-Submission Checks
1. ✅ Store ID must be set: `tenant()->loyverse_store_id`
2. ✅ Customer must exist in Loyverse
3. ✅ At least one line item must be mapped
4. ✅ Payment type must exist in Loyverse
5. ✅ Total amount must be positive

### API Response Codes
- `200` - Receipt created successfully
- `400` - Bad request (validation error)
- `401` - Unauthorized (invalid token)
- `404` - Resource not found (store/item/customer)
- `429` - Too many requests (rate limit)
- `500` - Internal server error

## Error Handling

### Missing Store ID
```php
throw new \Exception(
    'Loyverse store not selected. Please select a store in API Credentials settings.'
);
```

### No Mapped Items
```php
throw new \Exception(
    'No products could be mapped to Loyverse items. Please configure product mappings.'
);
```

### Payment Type Not Found
```php
// Logs warning and falls back to first available payment type
Log::warning('Careem payment type not found in Loyverse');
```

## Source Field: "Careem Integration System" (CIS)

The `source` field is set to `"Careem Integration System"` as requested. This identifies all orders synced through this integration in Loyverse reports and can be filtered by source.

### Benefits:
- Clear identification in Loyverse reports
- Easy filtering of integration orders
- Distinguishes from manual POS entries
- Audit trail for automated orders

## Store ID: Tenant-Specific Selection

Each tenant selects their own Loyverse store through the Settings UI. This allows:
- Multi-location support (different tenants → different stores)
- Centralized order management per location
- Accurate inventory tracking per store
- Location-specific reporting in Loyverse

---

**Document Version**: 1.0  
**Last Updated**: January 4, 2026  
**Related Documentation**: [LOYVERSE_STORE_SELECTION.md](LOYVERSE_STORE_SELECTION.md)
