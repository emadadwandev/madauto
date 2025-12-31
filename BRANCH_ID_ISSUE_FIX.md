# Branch ID Issue - Troubleshooting & Fix

## Issue Encountered

```
❌ Error: Failed to list orders: {"message":"branches not found for id=main_branch","code":"NOT_FOUND_ERROR"}
```

## Root Cause

Your local database has **incorrect branch ID**: `"main_branch"`

Careem expects **UUID format** like: `"5f915a5a-1d24-4101-ba8c-f4e5f00703bf"`

## How to Fix

### Option 1: Use Sync Script (RECOMMENDED)

Run the branch sync script to automatically fetch and update correct IDs:

```bash
php sync_careem_branches.php
```

The script will:
1. Fetch all branches from Careem API
2. Show you the correct branch IDs
3. Offer to update your local records automatically
4. Create missing branches if needed

### Option 2: Manual Update via Database

If you know the correct branch ID from Careem:

```php
php artisan tinker

$tenant = \App\Models\Tenant::where('subdomain', 'dw')->first();
$branch = $tenant->careemBranches()->first();

// Update with correct UUID from Careem
$branch->update([
    'careem_branch_id' => 'YOUR-ACTUAL-UUID-FROM-CAREEM',
    'synced_at' => now()
]);
```

### Option 3: Use Careem Dashboard

1. Log into your Careem Merchant Dashboard
2. Navigate to **Branches/Outlets** section
3. Find your branch (e.g., "Abdoun Branch")
4. Copy the **Branch ID** (should be a UUID)
5. Update in your database:

```sql
UPDATE careem_branches 
SET careem_branch_id = 'CORRECT-UUID-HERE',
    synced_at = NOW()
WHERE tenant_id = 'YOUR-TENANT-ID' 
AND name = 'Abdoun Branch';
```

## How to Find Correct IDs

### Method 1: List All Branches via API

```php
php artisan tinker

$tenant = \App\Models\Tenant::where('subdomain', 'dw')->first();
$service = new \App\Services\CareemApiService($tenant->id);

// Replace with your actual brand ID
$brandId = '4'; // or get from: $tenant->careemBranches()->first()->careem_brand_id

// This will show all branches with their correct IDs
$branches = $service->listBranches($brandId, 1, 20);
print_r($branches);
```

Look for your branch in the response and note its `id` field.

### Method 2: List All Brands First

If brand ID is also incorrect:

```php
$service = new \App\Services\CareemApiService($tenant->id);
$brands = $service->listBrands(1, 20);
print_r($brands);
```

This shows all your brands with correct IDs.

## Expected ID Formats

### ❌ WRONG (What you have):
- Brand ID: `"4"` (might be wrong)
- Branch ID: `"main_branch"` (definitely wrong)

### ✅ CORRECT (What Careem expects):
- Brand ID: `"bddd1d9f-3839-4186-9f10-33b46047c846"` (UUID format)
- Branch ID: `"5f915a5a-1d24-4101-ba8c-f4e5f00703bf"` (UUID format)

## Why This Matters

The branch ID is used in ALL order API calls:
- Accept order: `PUT /orders/{id}` with `Branch-Id` header
- List orders: `GET /orders?branch_id={id}`
- Mark ready: `PUT /orders/{id}` with `Branch-Id` header

**Without correct IDs, NO order operations will work!**

## After Fixing

Once you have correct IDs, test again:

```bash
php test_order_acceptance.php
# Choose option 4 to list orders
```

Expected result:
```
✅ Orders retrieved!
```

Or if no orders yet:
```
{
  "data": [],
  "meta": {
    "total": 0,
    "page_size": 20,
    "page_number": 1
  }
}
```

## Prevention for Future

When creating new branches in Dashboard → Careem Branches:

1. **Don't use placeholder IDs** like "main_branch", "branch1", etc.
2. **Fetch from Careem first** using the sync script
3. **Or create in Careem dashboard first**, then sync to your system
4. **Always use UUIDs** - Careem generates these automatically

## Quick Verification

After updating, verify the fix:

```bash
# Check branch in database
php artisan tinker
$branch = \App\Models\CareemBranch::where('name', 'Abdoun Branch')->first();
echo "Branch ID: " . $branch->careem_branch_id . "\n";
# Should show a UUID, not "main_branch"

# Test API connectivity
$service = new \App\Services\CareemApiService($branch->tenant_id);
$response = $service->getBranch($branch->careem_brand_id, $branch->careem_branch_id);
print_r($response);
# Should return branch details, not "NOT_FOUND_ERROR"
```

## Summary

✅ **Run the sync script**:
```bash
php sync_careem_branches.php
```

✅ **Verify IDs are UUIDs**

✅ **Test order operations again**

The issue is purely configuration - once IDs are correct, everything will work!
