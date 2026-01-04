# Loyverse Store Selection & Order Syncing Implementation

## Overview

This implementation adds the ability for users to select a Loyverse store and properly maps Careem orders to that store with the correct source identifier when creating sales receipts.

## What Was Implemented

### 1. Database Schema
- **Migration**: `2026_01_04_134842_add_loyverse_store_id_to_tenants_table.php`
- Added `loyverse_store_id` column to `tenants` table
- Column is nullable and stores the selected Loyverse store ID for order syncing

### 2. Model Updates
- **File**: `app/Models/Tenant.php`
- Added `loyverse_store_id` to the `$fillable` array
- Allows mass assignment when setting the selected store

### 3. Controller Methods
- **File**: `app/Http/Controllers/Dashboard/ApiCredentialController.php`

#### `fetchStores()` Method
- Endpoint: `GET /api-credentials/fetch-stores`
- Fetches all stores from Loyverse API
- Returns JSON response with store list
- Validates API credentials before fetching

#### `setStore()` Method
- Endpoint: `POST /api-credentials/set-store`
- Validates store exists in Loyverse
- Saves selected store ID to tenant settings
- Returns success message with store name

### 4. Routes
- **File**: `routes/tenant.php`
- Added `fetch-stores` route for GET requests
- Added `set-store` route for POST requests
- Both routes are authenticated and tenant-scoped

### 5. View Updates
- **File**: `resources/views/dashboard/api-credentials/index.blade.php`

#### Store Selection Section
- Shows after successful Loyverse connection test
- Displays currently selected store (if any)
- "Fetch Available Stores" button with loading state
- Dropdown populated with stores from Loyverse
- "Set Selected Store" button to save selection

#### JavaScript Functionality
- AJAX call to fetch stores without page reload
- Dynamic dropdown population
- Loading spinner during fetch operation
- Error handling and user feedback

### 6. Order Transformation Service
- **File**: `app/Services/OrderTransformerService.php`

#### Changes Made:
1. **Source Field**: Changed from config-based to `"Careem Integration System"` (CIS)
2. **Store ID**: Now fetched from `tenant()->loyverse_store_id`
3. **Validation**: Throws exception if no store is selected
4. **Logging**: Added store_id to success logs

#### Receipt Structure:
```php
[
    'source' => 'Careem Integration System', // CIS
    'store_id' => tenant()->loyverse_store_id, // Required
    'receipt_type' => 'SALE',
    'customer_id' => $customer['id'],
    'line_items' => [...],
    'payments' => [...],
    // ... other fields
]
```

## Loyverse API Documentation

### Stores Endpoint
- **GET** `/v1.0/stores`
- Returns list of all stores for the account
- Response includes: id, name, address, city, state, country, etc.

### Receipts Endpoint
- **POST** `/v1.0/receipts`
- Creates a sales receipt
- **Required fields**:
  - `store_id`: The store where the receipt was created
  - `line_items`: Array of items being sold
  - `payments`: Array of payment methods
- **Optional fields**:
  - `source`: Name of the system creating the receipt
  - `customer_id`: Customer associated with the receipt
  - `employee_id`: Employee who processed the sale
  - `pos_device_id`: POS device used

## User Workflow

1. **Navigate to Settings**
   - Go to Dashboard → API Credentials

2. **Add Loyverse Token**
   - Enter Loyverse Access Token
   - Click "Save Access Token"

3. **Test Connection**
   - Click "Test Loyverse Connection"
   - On success, store selection section appears

4. **Fetch Stores**
   - Click "Fetch Available Stores"
   - Wait for stores to load (shows spinner)
   - Dropdown populates with available stores

5. **Select Store**
   - Choose a store from dropdown
   - Click "Set Selected Store"
   - Success message shows selected store name

6. **Order Syncing**
   - All future orders will sync to the selected store
   - Orders include source as "Careem Integration System"

## Error Handling

### Missing Store Selection
- If no store is selected, order transformation throws exception:
  ```
  "Loyverse store not selected. Please select a store in API Credentials settings."
  ```

### Store Not Found
- If selected store doesn't exist in Loyverse:
  ```
  "Selected store not found in Loyverse"
  ```

### API Connection Errors
- If Loyverse API is unreachable:
  ```
  "Failed to fetch stores: [error message]"
  ```

## Testing

Run the test script to verify implementation:
```bash
php test_store_selection.php
```

### Test Coverage:
✓ Database schema (loyverse_store_id column)  
✓ Tenant model (fillable array)  
✓ OrderTransformerService (source & store_id)  
✓ API routes (fetch-stores & set-store)  
✓ Controller methods (fetchStores & setStore)  
✓ View components (UI & JavaScript)

## Files Modified

1. ✅ `database/migrations/2026_01_04_134842_add_loyverse_store_id_to_tenants_table.php` - NEW
2. ✅ `app/Models/Tenant.php` - Added fillable field
3. ✅ `app/Http/Controllers/Dashboard/ApiCredentialController.php` - Added methods
4. ✅ `routes/tenant.php` - Added routes
5. ✅ `resources/views/dashboard/api-credentials/index.blade.php` - Added UI
6. ✅ `app/Services/OrderTransformerService.php` - Updated receipt structure

## Configuration

No additional configuration files needed. Settings are stored in:
- **Tenant Table**: `loyverse_store_id` column
- **API Credentials Table**: Loyverse access token (encrypted)

## Security Considerations

- ✅ Store selection is tenant-scoped
- ✅ API calls include authentication checks
- ✅ Credentials stored encrypted
- ✅ Validation ensures store exists in Loyverse
- ✅ Route middleware prevents unauthorized access

## Future Enhancements

1. **Multi-Branch Support**: Allow different branches to sync to different stores
2. **Auto-Store Mapping**: Automatically map branches to stores by name
3. **POS Device Selection**: Allow selection of specific POS device
4. **Employee Assignment**: Map orders to specific employees
5. **Store Caching**: Cache store list to reduce API calls

## Known Limitations

1. Only one store can be selected per tenant currently
2. Store selection applies to all platforms (Careem & Talabat)
3. Changing store doesn't affect previously synced orders

## Support

If store selection is not working:
1. Verify Loyverse access token is valid
2. Ensure "Test Loyverse Connection" succeeds
3. Check that stores exist in Loyverse account
4. Review Laravel logs for detailed error messages
5. Confirm migration was run successfully

---

**Implementation Date**: January 4, 2026  
**Status**: ✅ Complete and Tested  
**Version**: 1.0
