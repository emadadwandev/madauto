# Manual Deployment Files - x-careem-api-key Implementation

## Date: December 1, 2025

This document lists all files that need to be uploaded manually to the VPS server for the x-careem-api-key feature.

---

## 📁 Files to Upload

### 1. Database Migration - Add Careem API Key
**Path:** `database/migrations/2025_11_27_125152_add_careem_api_key_to_tenants_table.php`
- **Action:** Upload and run migration
- **Purpose:** Adds `careem_api_key` column to `tenants` table

### 2. Database Migration - Fix Unique Constraint (CRITICAL)
**Path:** `database/migrations/2025_12_01_100452_fix_api_credentials_unique_constraint.php`
- **Action:** Upload and run migration FIRST
- **Purpose:** Fixes unique constraint on `api_credentials` to include `tenant_id`
- **⚠️ IMPORTANT:** This migration MUST be run before using the API credentials page

### 3. Model Update
**Path:** `app/Models/Tenant.php`
- **Action:** Replace existing file
- **Changes:** 
  - Added `careem_api_key` to `$fillable` array
  - Added `booted()` method to auto-generate API key on tenant creation

### 3. Middleware Update
**Path:** `app/Http/Middleware/VerifyWebhookSignature.php`
- **Action:** Replace existing file
- **Changes:** Added validation for `x-careem-api-key` header before signature verification

### 4. Console Command (NEW)
**Path:** `app/Console/Commands/GenerateCareemApiKeys.php`
- **Action:** Upload new file
- **Purpose:** Artisan command to generate keys for existing tenants

### 5. View Update
**Path:** `resources/views/dashboard/api-credentials/index.blade.php`
- **Action:** Replace existing file
- **Changes:** Added display section for `x-careem-api-key` with copy button

### 6. Test File (NEW - Optional)
**Path:** `tests/Feature/WebhookTest.php`
- **Action:** Upload new file (optional for production)
- **Purpose:** Tests webhook API key validation

---

## 🔧 Manual Deployment Steps

### Step 1: Upload Files
```bash
# Connect to VPS
ssh deploy@YOUR_VPS_IP

# Navigate to project directory
cd /path/to/careem-loyverse-integration

# Pull latest changes OR upload files manually via SFTP/SCP
git pull origin main
# OR use FileZilla/WinSCP to upload the files listed above
```

### Step 2: Run Migrations (IMPORTANT ORDER)
```bash
# On VPS server
cd /path/to/careem-loyverse-integration

# CRITICAL: Run migrations in this specific order
# 1. First, fix the unique constraint issue
php artisan migrate --path=database/migrations/2025_12_01_100452_fix_api_credentials_unique_constraint.php

# 2. Then add the careem_api_key column
php artisan migrate --path=database/migrations/2025_11_27_125152_add_careem_api_key_to_tenants_table.php

# OR run all pending migrations at once (if uploading all files together)
php artisan migrate
```

### Step 3: Generate API Keys for Existing Tenants
```bash
# On VPS server
php artisan tenants:generate-careem-keys
```

### Step 4: Clear Caches
```bash
# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Restart queue workers if using queues
php artisan queue:restart
```

### Step 5: Verify Deployment
1. Log in to a tenant dashboard (e.g., `https://shady.madautomation.cloud`)
2. Navigate to **API Credentials & Settings**
3. Scroll to **Careem Webhook Settings**
4. Verify that **x-careem-api-key** is displayed with a copy button

---

## 📋 Verification Checklist

- [ ] All files uploaded successfully
- [ ] Migration ran without errors
- [ ] API keys generated for all tenants
- [ ] Caches cleared
- [ ] Queue workers restarted (if applicable)
- [ ] Dashboard displays `x-careem-api-key` correctly
- [ ] Webhook validation works (test with invalid/valid keys)

---

## 🔑 Key Features Implemented

1. **Auto-generation**: New tenants automatically get a unique `x-careem-api-key` (format: `ck_` + 32 random characters)
2. **Security**: Webhook requests must include valid `x-careem-api-key` header
3. **UI Display**: API key visible in tenant dashboard with copy-to-clipboard functionality
4. **Backfill**: Command available to generate keys for existing tenants

---

## 🐛 Troubleshooting

### Issue: Migration fails with "Duplicate entry" error
**Error:** `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'loyverse-access_token'`
**Solution:** The unique constraint needs to be fixed first. Run the constraint fix migration:
```bash
php artisan migrate --path=database/migrations/2025_12_01_100452_fix_api_credentials_unique_constraint.php
```

### Issue: Migration fails
**Solution:** Check if column already exists. If so, skip migration or modify it.

### Issue: API key not showing in dashboard
**Solution:** 
```bash
php artisan view:clear
php artisan config:clear
```

### Issue: Webhooks returning 401
**Solution:** Ensure Careem is sending the `x-careem-api-key` header with the correct key value.

---

## 📞 Support

If you encounter any issues during manual deployment, check:
1. PHP version compatibility (Laravel 12 requires PHP 8.2+)
2. File permissions (storage and bootstrap/cache should be writable)
3. Database connection settings
4. Queue worker status (if using queues)

---

**Deployment completed by:** _______________  
**Date:** _______________  
**Time:** _______________
