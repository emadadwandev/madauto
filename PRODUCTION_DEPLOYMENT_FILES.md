# Production Deployment Files - Careem Brand & Branch API Implementation

**Date:** December 13, 2025  
**Feature:** Complete Brand and Branch Management API Integration  
**Status:** Ready for Production Deployment

---

## 📋 Overview
This deployment includes the complete implementation of Careem Brand API and Branch API integration, including:
- Service layer with 15 new API methods
- Database migrations for brands and branches
- Eloquent models with relationships
- Controllers with comprehensive CRUD operations
- Admin dashboard views for brand and branch management
- Multi-tenant support throughout

---

## 🆕 New Files to Upload

### Configuration Files
1. **config/platforms.php** *(Modified - see below)*

### Migrations
2. **database/migrations/2025_12_13_100000_create_careem_brands_table.php**
   - Creates `careem_brands` table with tenant support
   - Columns: id, tenant_id, careem_brand_id, name, state, metadata, synced_at, timestamps

3. **database/migrations/2025_12_13_100001_create_careem_branches_table.php**
   - Creates `careem_branches` table with relationships
   - Columns: id, tenant_id, careem_brand_id, location_id, careem_branch_id, name, state, pos_integration_enabled, visibility_status, metadata, synced_at, timestamps

### Models
4. **app/Models/CareemBrand.php**
   - Brand entity model
   - Relationships: belongsTo(Tenant), hasMany(CareemBranch)
   - Helper methods: isMapped(), needsSync(), markAsSynced()

5. **app/Models/CareemBranch.php**
   - Branch entity model
   - Relationships: belongsTo(Tenant, CareemBrand, Location)
   - Helper methods: isMapped(), isPosIntegrationEnabled(), isActive(), hasLocation(), needsSync(), markAsSynced()
   - Attributes: getVisibilityStatusLabelAttribute(), getStateBadgeColorAttribute()

### Controllers
6. **app/Http/Controllers/Dashboard/CareemBrandController.php**
   - Actions: index, create, store, edit, update, destroy, sync, fetchFromCareem, deleteFromCareem
   - Comprehensive brand management with optional immediate sync

7. **app/Http/Controllers/Dashboard/CareemBranchController.php**
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
