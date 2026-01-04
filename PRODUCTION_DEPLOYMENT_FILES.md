# Production Deployment Files - Order Item Display Fix

**Date**: December 31, 2025  
**Purpose**: Fix order item display by saving catalog items locally when pushing to Careem  
**Previous Deployment**: December 13, 2025 - Careem Brand & Branch API Implementation

---

## 📋 Overview - December 31, 2025 Update
This deployment fixes the order item display issue where items showed as "Item #15" instead of actual product names. 

**Root Cause**: Careem API doesn't provide an endpoint to fetch catalog items back.  
**Solution**: Save catalog items to local database when pushing catalogs TO Careem.

**Changes**:
- New table: `careem_catalog_items` to store product details locally
- Enhanced order display to lookup names from local database
- Auto-save items during catalog push to Careem
- Fixed PHP type hints to prevent migration errors

---

## 🆕 New Files to Upload

### Database Migration
1. **database/migrations/2025_12_31_110243_create_careem_catalog_items_table.php**
   - Creates `careem_catalog_items` table
   - Stores product details (name, SKU, price) from Careem catalogs
   - **Action Required**: Run `php artisan migrate --force` in production

### Models
2. **app/Models/CareemCatalogItem.php**
   - New model for managing catalog items
   - Includes tenant isolation and helper methods
   - Methods: `findByItemId()`, `findBySku()`, `productMapping()`

### Commands (Optional - Deprecated)
3. **app/Console/Commands/SyncCareemCatalog.php**
   - ⚠️ **Deprecated**: Marked as deprecated since Careem API doesn't support fetching catalogs
   - Can be deployed but won't be functional
   - Kept for reference only

---

## 📝 Updated Files to Upload

### Core Services
4. **app/Services/CareemApiService.php** ⚠️ **CRITICAL UPDATE**
   - **NEW:** Added `api_key` property to store x-careem-api-key
   - **NEW:** Load `api_key` from credentials table
   - **NEW:** Include `x-careem-api-key` header in ALL API requests (acceptOrder, markOrderReady, cancelOrder, requestOrderDelay)
   - Fixed nullable type hints for `clientId` and `clientSecret` (prevents migration errors)
   - Removed `getCatalogItems()` method (Careem API doesn't have this endpoint)
   - Added `saveCatalogItemsLocally()` method to save items during catalog push
   - Updated `submitCatalog()` to automatically save catalog items to database
   - Fixed credential validation for console/artisan commands
   - **⚠️ IMPORTANT:** This fix is required for order acceptance to work - Careem API requires x-careem-api-key header

### Configuration
5. **config/platforms.php**
   - Removed `catalog_items` endpoint (doesn't exist in Careem API)
   - Updated comment for `catalog_items_availability` (PATCH only)

### Views
6. **resources/views/dashboard/orders/show.blade.php**
   - Enhanced order item display logic
   - Lookup chain: 1) `careem_catalog_items` → 2) `product_mappings` → 3) Fallback to "Item #ID"
   - Fixed PHP 8.2 syntax for chained null coalescing operators
   - Now displays actual product names instead of "Item #15"

---

## 🚫 Files to EXCLUDE from Deployment

These are diagnostic/helper scripts for local development only:
```
check_catalog_items.php
check_credentials.php
check_branches.php
check_product_mappings.php
```

---

## 🗄️ Database Changes

### Tables to Create
Run migration in production:
```bash
php artisan migrate --force
```

This will create:
- `careem_catalog_items` table
  - Columns: id, tenant_id, careem_item_id, careem_catalog_id, name, description, sku, price, currency, category_id, is_available, is_active, image_url, modifier_group_ids, external_id, raw_data, timestamps
  - Indexes: unique(tenant_id, careem_item_id), sku
  - Foreign key: tenant_id → tenants(id)

---

## 📦 Deployment Checklist

### Pre-Deployment
- [ ] Backup production database (critical - new migration)
- [ ] Review all file changes
- [ ] Test in staging environment (if available)
- [ ] Verify Careem API credentials are configured for each tenant
- [ ] **⚠️ CRITICAL:** Ensure `api_key` credential is added to `api_credentials` table for each tenant (see Step 3.5 below)

### Deployment Steps

1. **Upload New Files (3 files)**
   ```
   database/migrations/2025_12_31_110243_create_careem_catalog_items_table.php
   app/Models/CareemCatalogItem.php
   app/Console/Commands/SyncCareemCatalog.php
   ```

2. **Update Modified Files (3 files)**
   ```
   app/Services/CareemApiService.php
   config/platforms.php
   resources/views/dashboard/orders/show.blade.php
   ```

3. **Run Database Migration**
   ```bash
   php artisan migrate --force
   ```

3.5. **⚠️ CRITICAL: Add API Key to Credentials**
   ```bash
   # The API key already exists on the tenants.careem_api_key field
   # It needs to be copied to the api_credentials table for CareemApiService to use it
   
   # Via Laravel Tinker (RECOMMENDED):
   php artisan tinker
   
   # For tenant 'dw':
   >>> $tenant = App\Models\Tenant::where('subdomain', 'dw')->first();
   >>> App\Models\ApiCredential::create([
       'tenant_id' => $tenant->id,
       'service' => 'careem_catalog',
       'credential_type' => 'api_key',
       'credential_value' => $tenant->careem_api_key,  // Uses existing key from tenant
       'is_active' => true
   ]);
   
   # For ALL tenants with a careem_api_key (bulk):
   >>> App\Models\Tenant::whereNotNull('careem_api_key')->get()->each(function($tenant) {
       App\Models\ApiCredential::updateOrCreate(
           ['tenant_id' => $tenant->id, 'service' => 'careem_catalog', 'credential_type' => 'api_key'],
           ['credential_value' => $tenant->careem_api_key, 'is_active' => true]
       );
   });
   
   # Or via SQL (if you prefer):
   INSERT INTO api_credentials (tenant_id, service, credential_type, credential_value, is_active, created_at, updated_at)
   SELECT id, 'careem_catalog', 'api_key', careem_api_key, 1, NOW(), NOW()
   FROM tenants
   WHERE careem_api_key IS NOT NULL
   ON DUPLICATE KEY UPDATE credential_value = VALUES(credential_value), updated_at = NOW();
   ```

4. **Clear Caches**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

5. **Verify Deployment**
   - Check database: `careem_catalog_items` table exists
   - **⚠️ Verify API key configured:** Check `api_credentials` table has `api_key` for each tenant
   - Test: Push a catalog to Careem (items should auto-save to local DB)
   - Test: View an existing order - items should show actual names
   - **Test: Accept an order** - should not return "Invalid or missing x-careem-api-key header" error
   - Check logs: No errors related to catalog sync or API key

---

## 🧪 Testing Guide

### ⚠️ Test API Key Configuration (CRITICAL - Do This First!)
1. Verify API key exists on tenant:
   ```bash
   php artisan tinker
   >>> $tenant = App\Models\Tenant::where('subdomain', 'dw')->first();
   >>> $tenant->careem_api_key;  // Should show: "ck_pdU8nACi3bqfBZsV2hBNw2ZyKdQF6xmC"
   ```

2. Copy API key to api_credentials table (if not already done in Step 3.5):
   ```bash
   php artisan tinker
   >>> $tenant = App\Models\Tenant::where('subdomain', 'dw')->first();
   >>> App\Models\ApiCredential::updateOrCreate(
       ['tenant_id' => $tenant->id, 'service' => 'careem_catalog', 'credential_type' => 'api_key'],
       ['credential_value' => $tenant->careem_api_key, 'is_active' => true]
   );
   ```

3. Verify API key is in api_credentials table:
   ```sql
   SELECT credential_type, LENGTH(credential_value) as key_length, is_active
   FROM api_credentials
   WHERE service = 'careem_catalog' AND credential_type = 'api_key'
   AND tenant_id = (SELECT id FROM tenants WHERE subdomain = 'dw');
   ```
   You should see: `api_key` with key_length = 32 and is_active = 1

4. Test order acceptance (this will fail WITHOUT the api_key):
   ```bash
   # Use test_order_acceptance.php script or try accepting a test order
   # Should NOT return: "Invalid or missing x-careem-api-key header"
   ```

### Test Catalog Push
1. Use existing catalog sync functionality
2. Push a catalog to Careem via `submitCatalog()`
3. Verify items are saved to `careem_catalog_items` table:
   ```sql
   SELECT COUNT(*) FROM careem_catalog_items WHERE tenant_id = 'YOUR_TENANT_ID';
   SELECT name, sku, price FROM careem_catalog_items LIMIT 10;
   ```

### Test Order Display
1. Navigate to an existing order details page
2. Verify items show actual product names (e.g., "American", "Cream Cheese Bagel")
3. Should NOT show "Item #15" anymore
4. Check that SKU and price display correctly

### Test Fallback Logic
1. Items from `careem_catalog_items`: Show catalog name
2. Items from `product_mappings`: Show mapped name
3. Items not found: Show "Item #ID"

---

## 🔄 How It Works

### Before This Fix
- Orders displayed: "Item #15", "Item #16" (only IDs)
- No local copy of Careem catalog items
- Manual product mapping required for every item

### After This Fix

#### When Pushing Catalog to Careem
```php
// In your catalog sync code
$result = $careemApiService->submitCatalog($catalogData, $brandId, $branchId);

// Items are automatically saved locally
// Result includes: 'items_saved_locally' => 42
```

#### When Viewing Orders
1. Order contains item IDs (e.g., "15", "16", "26")
2. Blade template looks up names:
   - First: `careem_catalog_items` table (from catalog push)
   - Second: `product_mappings` table (manual mappings)
   - Last: Display "Item #ID" if nothing found
3. Shows actual names: "American", "Cream Cheese Bagel"

#### Database Flow
```
Push Catalog → submitCatalog() → saveCatalogItemsLocally() → careem_catalog_items table
Receive Order → show.blade.php → CareemCatalogItem::findByItemId() → Display name
```

---

## 🐛 Troubleshooting

### Common Issues

**Issue:** ⚠️ **"Invalid or missing x-careem-api-key header" error when accepting orders**
- **Root Cause:** API key not configured in `api_credentials` table (even though it exists on `tenants.careem_api_key`)
- **Solution:**
  1. The API key already exists: Check `tenants.careem_api_key` field for your tenant
  2. Copy it to `api_credentials` table using Tinker:
     ```bash
     php artisan tinker
     >>> $tenant = App\Models\Tenant::where('subdomain', 'dw')->first();
     >>> App\Models\ApiCredential::updateOrCreate(
         ['tenant_id' => $tenant->id, 'service' => 'careem_catalog', 'credential_type' => 'api_key'],
         ['credential_value' => $tenant->careem_api_key, 'is_active' => true]
     );
     ```
  3. Restart queue workers: `php artisan queue:restart`
  4. Test order acceptance again
- **Why?** `CareemApiService` loads API key from `api_credentials` table, not from `tenants.careem_api_key`
- **Verification:**
  ```sql
  -- Check if api_key exists for tenant 'dw'
  SELECT t.subdomain, t.careem_api_key as 'Tenant Key', 
         ac.credential_value as 'ApiCredential Key',
         ac.is_active
  FROM tenants t
  LEFT JOIN api_credentials ac ON ac.tenant_id = t.id 
    AND ac.service = 'careem_catalog' 
    AND ac.credential_type = 'api_key'
  WHERE t.subdomain = 'dw';
  ```

**Issue:** Migration fails with "clientId cannot be null"
- **Solution:** Already fixed in `CareemApiService.php` - nullable type hints added
- If still occurs: Check PHP version is 8.2+

**Issue:** Items still showing as "Item #ID"
- **Solution:** 
  1. Check `careem_catalog_items` table has data
  2. Verify tenant_id matches in query
  3. Re-push catalog to Careem to trigger auto-save

**Issue:** "getCatalogItems method not found"
- **Solution:** Already removed - Careem API doesn't support fetching catalogs
- Only push (PUT) is supported, not fetch (GET)

**Issue:** Catalog push doesn't save items locally
- **Solution:** Check logs for save errors
- Verify $this->tenant is set in CareemApiService
- Ensure migration ran successfully

---

## 📞 Support Information
- **Implementation Date:** December 31, 2025
- **Framework:** Laravel 12
- **PHP Version:** 8.2.12
- **Purpose:** Fix order item display + auto-save catalog items
- **Related Documentation:** https://docs.careemnow.com/#tag/Catalog-API-endpoints

---

## 🔄 Rollback Plan

If issues occur after deployment:

```bash
# Step 1: Rollback migration
php artisan migrate:rollback --step=1

# Step 2: Restore old files
# (restore backed up versions of updated files)

# Step 3: Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 4: Verify rollback
# Check that orders display (even if showing "Item #ID")
```

---

## 📊 File Summary - December 31, 2025 Update

| Category | New Files | Modified Files | Total |
|----------|-----------|----------------|-------|
| Migrations | 1 | 0 | 1 |
| Models | 1 | 0 | 1 |
| Commands | 1 | 0 | 1 |
| Services | 0 | 1 | 1 |
| Configuration | 0 | 1 | 1 |
| Views | 0 | 1 | 1 |
| **TOTAL** | **3** | **3** | **6** |

---

## ✅ Expected Results After Deployment

- ✅ **Order acceptance works** - no more "Invalid or missing x-careem-api-key header" errors
- ✅ Order details page shows actual product names
- ✅ No more "Item #15" - displays real product names
- ✅ Catalog items auto-saved when pushing to Careem
- ✅ **All Careem API calls include x-careem-api-key header** (acceptOrder, markOrderReady, cancelOrder, requestOrderDelay)
- ✅ No errors during migrations or artisan commands
- ✅ Item names persist in database for future orders
- ✅ Fallback to `product_mappings` if catalog item not found
- ✅ Performance improved (no API calls during order display)

---

## 📝 Important Notes

### x-careem-api-key Header (NEW - CRITICAL)
- **Required by Careem API**: All API calls to Careem MUST include `x-careem-api-key` header
- **Error without it**: "Invalid or missing x-careem-api-key header"
- **Configuration**: API key must be added to `api_credentials` table for each tenant
- **Affected operations**: Order acceptance, marking ready, cancellation, delay requests
- **Where to get it**: Contact Careem support to obtain your API key for each tenant

### Careem API Limitation
- **Documentation confirms**: No GET endpoint exists for fetching catalog items
- **API Behavior**: You can only PUSH catalogs TO Careem, not retrieve them
- **Our Solution**: Save items locally during the push operation
- **Reference**: https://docs.careemnow.com/#tag/Catalog-API-endpoints

### Migration Safety
- New table only - no modifications to existing tables
- Safe to run multiple times (idempotent)
- Foreign key constraint on tenant_id prevents orphaned records

---

## 🔗 Related to Previous Deployment

This update builds on the December 13, 2025 deployment (Brand & Branch API).  
All previous files remain unchanged and functional.

**Previous deployment included**: Brand management, Branch management, Location mapping, POS integration, Visibility controls

**This deployment adds**: Catalog item storage, Order item display enhancement, Auto-save during catalog push

---

*End of December 31, 2025 Update*

---
---

# Previous Deployment Record

## December 13, 2025 - Careem Brand & Branch API Implementation

### Files Deployed (20 files total)
   - Actions: index, create, store, edit, update, destroy, sync, fetchFromCareem, togglePosIntegration, updateVisibility, setTemporaryStatus, deleteFromCareem
   - Full branch lifecycle management with POS and visibility controls

### Views - Brands
8. **resources/views/dashboard/careem-brands/index.blade.php**
   - Grid layout with brand cards
   - Shows: brand name, ID, state, branch count, sync status
   - Actions: Edit, Sync, Fetch from Careem, Delete

9. **resources/views/dashboard/careem-brands/create.blade.php**
   - Brand creation form
   - Fields: Brand ID, Name, optional immediate sync checkbox

10. **resources/views/dashboard/careem-brands/edit.blade.php**
    - Brand editing form
    - Read-only Brand ID, editable name, optional sync checkbox

### Views - Branches
11. **resources/views/dashboard/careem-branches/index.blade.php**
    - Tabular layout with filters (brand, state, POS integration)
    - Shows: branch name, brand, state, POS toggle, visibility status, location mapping, sync status
    - Actions: Edit, Sync, Toggle POS, Update Visibility, Set Temporary Closure, Fetch, Delete

12. **resources/views/dashboard/careem-branches/create.blade.php**
    - Branch creation form
    - Fields: Brand dropdown, Branch ID, Name, Location mapping (optional), POS integration toggle, Visibility status, optional immediate sync

13. **resources/views/dashboard/careem-branches/edit.blade.php**
    - Branch editing form with comprehensive controls
    - Read-only: Brand, Branch ID
    - Editable: Name, Location mapping, Visibility status
    - Actions: Toggle POS Integration, Set Temporary Closure, Sync changes

### Navigation
14. **resources/views/layouts/navigation.blade.php** *(Modified - see below)*
    - Added "Careem" dropdown menu in main navigation
    - Contains links to Brands and Branches management
    - Added mobile responsive menu items

### Documentation
15. **CAREEM_BRAND_BRANCH_IMPLEMENTATION.md**
    - Complete implementation guide
    - API reference
    - Usage instructions
    - Integration workflow

---

## ✏️ Modified Files to Update

### Service Layer
16. **app/Services/CareemApiService.php**
    - **Added 15 new methods:**
      - **Brand API:** createBrand(), getBrand(), listBrands(), updateBrand(), deleteBrand()
      - **Branch API:** createOrUpdateBranch(), getBranch(), listBranches(), deleteBranch()
      - **Branch Controls:** toggleBranchPosIntegration(), updateBranchVisibilityStatus(), setBranchStatusExpiry()
      - **Operational Hours:** setBranchOperationalHours(), getBranchOperationalHours()
    - All methods follow official Careem API v2.1.0 specification
    - Proper Bearer token authentication, User-Agent, Brand-Id, Branch-Id headers

### Configuration
17. **config/platforms.php**
    - **Updated:** api_url from `pos-stg.careemdash-internal.com` to `apigateway-stg.careemdash.com/pos/api/v1`
    - **Added:** Complete endpoint mapping for brands, branches, catalogs, orders, operational_hours
    - **Lines Modified:** api_url, added endpoints array

### Routes
18. **routes/tenant.php**
    - **Added 21 new routes:**
      - **Brand routes (9):** dashboard/careem-brands/* (index, create, store, edit, update, destroy, sync, fetch, delete-from-careem)
      - **Branch routes (12):** dashboard/careem-branches/* (index, create, store, edit, update, destroy, sync, fetch, toggle-pos, update-visibility, temporary-status, delete-from-careem)
    - All routes use subdomain parameter for multi-tenancy

### Models (Modified Relationships)
19. **app/Models/Location.php**
    - **Added:** careemBranch() hasOne relationship
    - Allows linking locations to Careem branches for order routing

---

## 🗄️ Database Changes

### Tables to Create
Run these migrations in production:
```bash
php artisan migrate
```

This will create:
- `careem_brands` table
- `careem_branches` table

### Important Notes
- Both tables use `char(36)` for `tenant_id` to match UUID format
- Proper foreign key constraints are in place
- Indexes on `careem_brand_id` and `careem_branch_id` (unique)
- JSON columns for flexible metadata storage

---

## 📦 Deployment Checklist

### Pre-Deployment
- [ ] Backup production database
- [ ] Review all files for any environment-specific configurations
- [ ] Ensure Careem API credentials are configured in `api_credentials` table for each tenant
- [ ] Verify OAuth2 tokens are working (existing authentication should be functional)

### Deployment Steps
1. **Upload New Files (14 files)**
   - Upload all files listed in "New Files to Upload" section above
   - Maintain directory structure exactly as shown

2. **Update Modified Files (6 files)**
   - Update `app/Services/CareemApiService.php` with new methods
   - Update `config/platforms.php` with new endpoints
   - Update `routes/tenant.php` with new routes
   - Update `app/Models/Location.php` with new relationship
   - Update `resources/views/layouts/navigation.blade.php` with Careem menu items
   - Update `bootstrap/app.php` with route loading fix (already done)
   - Ensure proper file permissions (644 for files, 755 for directories)

3. **Run Database Migrations**
   ```bash
   php artisan migrate --force
   ```

4. **Clear Caches**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan cache:clear
   ```

5. **Optimize for Production**
   ```bash
   php artisan config:cache
   php artisan view:cache
   php artisan event:cache
   ```
   
   **⚠️ IMPORTANT:** Do NOT use `php artisan route:cache` or `php artisan optimize` commands for this multi-domain application. Both commands attempt to cache routes, which requires unique route names across all routes. Our subdomain-based multi-tenancy architecture has the same route names on multiple domains (localhost, www.localhost, tenant.localhost, admin.localhost), making route caching incompatible with this application.

### Post-Deployment
- [ ] Verify brand management interface: `/dashboard/careem-brands`
- [ ] Verify branch management interface: `/dashboard/careem-branches`
- [ ] Test brand creation and sync to Careem staging
- [ ] Test branch creation with location mapping
- [ ] Test POS integration toggle
- [ ] Test visibility status controls
- [ ] Test temporary closure feature
- [ ] Verify all 21 routes are accessible

---

## 🧪 Testing Guide

### Brand Management Testing
1. Navigate to `/dashboard/careem-brands`
2. Click "Add Brand"
3. Create a test brand (e.g., Brand ID: "TEST_BRAND", Name: "Test Restaurant")
4. Check "Sync to Careem immediately" and submit
5. Verify brand appears in listing with "MAPPED" state
6. Test Edit, Sync, and Fetch from Careem actions

### Branch Management Testing
1. Navigate to `/dashboard/careem-branches`
2. Click "Add Branch"
3. Select the test brand created above
4. Create a test branch (e.g., Branch ID: "TEST_BRANCH_001", Name: "Test Location 1")
5. Map to an existing location (optional)
6. Enable POS integration
7. Set visibility to Active
8. Check "Sync to Careem immediately" and submit
9. Verify branch appears in listing
10. Test POS toggle (should switch between enabled/disabled)
11. Test visibility status update
12. Test temporary closure (e.g., 30 minutes)
13. Test Edit and Sync actions

### API Integration Testing
- Verify API calls are made to correct staging endpoint: `https://apigateway-stg.careemdash.com/pos/api/v1`
- Check that Bearer token is properly included in requests
- Confirm Brand-Id and Branch-Id headers are sent when required
- Monitor logs for any API errors or rate limiting issues

---

## 🔒 Security Notes
- All routes use multi-tenancy middleware (`IdentifyTenant`)
- API credentials are encrypted in database
- OAuth2 tokens are cached securely
- CSRF protection enabled on all forms
- Input validation on all user-submitted data

---

## 🐛 Troubleshooting

### Common Issues

**Issue:** Migration fails with foreign key constraint error
- **Solution:** Ensure `tenants` table exists and uses `char(36)` for primary key

**Issue:** "Brand not found" when creating branch
- **Solution:** Ensure brand is synced to Careem first (check brand state is "MAPPED")

**Issue:** API calls return 401 Unauthorized
- **Solution:** Check `api_credentials` table has valid Careem credentials for tenant
- Verify OAuth2 token is not expired (tokens cached for 50 minutes)

**Issue:** Routes not found (404)
- **Solution:** Run `php artisan route:clear` and `php artisan route:cache`

**Issue:** Views not loading correctly
- **Solution:** Run `php artisan view:clear` and verify file permissions

---

## 📞 Support Information
- **Implementation Date:** December 13, 2025
- **Framework:** Laravel 12
- **PHP Version:** 8.4
- **API Version:** Careem API v2.1.0
- **Documentation:** See CAREEM_BRAND_BRANCH_IMPLEMENTATION.md for detailed usage

---

## 📊 File Summary

| Category | New Files | Modified Files | Total |
|----------|-----------|----------------|-------|
| Configuration | 0 | 1 | 1 |
| Migrations | 2 | 0 | 2 |
| Models | 2 | 1 | 3 |
| Controllers | 2 | 0 | 2 |
| Services | 0 | 1 | 1 |
| Routes | 0 | 1 | 1 |
| Views | 6 | 1 | 7 |
| Core Files | 0 | 1 | 1 |
| Documentation | 2 | 0 | 2 |
| **TOTAL** | **14** | **6** | **20** |

---

## ✅ Completion Status

**All Features Implemented:**
- ✅ Brand API integration (5 methods)
- ✅ Branch API integration (10 methods)
- ✅ Database schema with multi-tenancy
- ✅ Eloquent models with relationships
- ✅ Controllers with CRUD operations
- ✅ Admin dashboard views (6 views)
- ✅ Route registration (21 routes)
- ✅ Comprehensive documentation

**Ready for Production Deployment** 🚀

---

**End of Deployment Guide**
