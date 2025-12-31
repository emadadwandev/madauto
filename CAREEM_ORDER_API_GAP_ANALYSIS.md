# Careem Order API Integration Gap Analysis

## 🔴 CRITICAL FINDING: Missing Order Acceptance Implementation

After reviewing the Careem Order API documentation against the current implementation, **your system is NOT properly integrated** with Careem's Order API. This is why orders placed on Careem app are not showing up.

---

## 📋 What Careem API Requires (Per Documentation)

### Required Order Flow

```
1. Customer places order on Careem app
2. Careem sends ORDER_CREATED webhook → Partner system (status: "pending")
3. Partner MUST call PUT /orders/{order_id} with state: "accepted" ← **MISSING**
4. Careem sends ORDER_STATUS_UPDATED webhook (status: "accepted")
5. Partner marks order as "ready" when prepared
6. Captain picks up and delivers
```

### Critical API Endpoints Careem Expects Partners to Use

#### 1. **Accept/Cancel Order** (REQUIRED - **NOT IMPLEMENTED**)
```http
PUT /orders/{order_id}
Headers:
  - Authorization: Bearer {token}
  - Brand-Id: {brand_id}
  - Branch-Id: {branch_id}
  - User-Agent: {user_agent}

Body:
{
  "state": "accepted"  // or "cancelled" with cancellation_reason
}
```

#### 2. **Mark Order as Ready** (OPTIONAL - **NOT IMPLEMENTED**)
```http
PUT /orders/{order_id}
Body:
{
  "state": "ready"
}
```

#### 3. **Request More Preparation Time** (OPTIONAL - **NOT IMPLEMENTED**)
```http
PUT /orders/{order_id}/delay-request
Body:
{
  "delay_in_minutes": 10
}
```

#### 4. **Tag Order with Metadata** (OPTIONAL - **NOT IMPLEMENTED**)
```http
PATCH /orders/{order_id}/tags
Body:
{
  "tag": "reject"
}
```

---

## ✅ What IS Currently Implemented

| Feature | Status | File |
|---------|--------|------|
| Receive ORDER_CREATED webhook | ✅ Implemented | `WebhookController::handleCareem()` |
| Store webhook logs | ✅ Implemented | `webhook_logs` table |
| Create order in database | ✅ Implemented | `ProcessCareemOrderJob` |
| Sync order to Loyverse POS | ✅ Implemented | `SyncToLoyverseJob` |
| Webhook endpoint configured | ✅ Implemented | `/api/webhook/careem/{tenant}` |

## ❌ What IS MISSING (Critical)

| Feature | Status | Impact |
|---------|--------|--------|
| **Accept order back to Careem** | ❌ **NOT IMPLEMENTED** | **🔴 CRITICAL - Orders stuck in "pending" state** |
| Mark order as ready | ❌ NOT IMPLEMENTED | ⚠️ No status updates to Careem |
| Request preparation delay | ❌ NOT IMPLEMENTED | ⚠️ Can't adjust timing |
| Cancel order via API | ❌ NOT IMPLEMENTED | ⚠️ Can't programmatically cancel |
| Handle ORDER_STATUS_UPDATED webhook | ❌ NOT IMPLEMENTED | ⚠️ Missing status tracking |

---

## 🎯 Root Cause of Your Issue

**Why orders don't show up:**

1. You place order on Careem app ✅
2. Careem attempts to send ORDER_CREATED webhook to your system
3. **BUT:** Webhook URL might not be registered correctly in Careem dashboard ❌
4. **OR:** Even if webhook is received, your system doesn't "accept" the order back to Careem ❌
5. Without acceptance, Careem may:
   - Not process the order further
   - Show error to customer
   - Auto-cancel the order
   - Not send the order to your webhook at all (if POS integration not fully active)

---

## 📊 Current vs Required Implementation

### Current Flow
```
Careem App → ORDER_CREATED webhook → Your System → Store in DB → Sync to Loyverse
                                            ↓
                                    ❌ NO RESPONSE TO CAREEM
```

### Required Flow (Per Careem Documentation)
```
Careem App → ORDER_CREATED webhook → Your System → Store in DB
                                            ↓
                                    ✅ PUT /orders/{id} {state: "accepted"}
                                            ↓
                                    Careem confirms acceptance
                                            ↓
                                    Continue to Loyverse sync
                                            ↓
                                    ✅ PUT /orders/{id} {state: "ready"}
                                            ↓
                                    Captain assigned & picks up
```

---

## 🔧 What Needs to Be Implemented

### Priority 1: CRITICAL (Must Implement Now)

#### 1.1 Accept Order Method in CareemApiService
```php
/**
 * Accept a pending order
 * 
 * @param string $orderId Careem order ID from webhook
 * @param string $brandId Brand ID
 * @param string $branchId Branch ID
 * @return array Response from Careem
 */
public function acceptOrder(string $orderId, string $brandId, string $branchId): array
{
    $token = $this->getAccessToken();
    $endpoint = str_replace('{order_id}', $orderId, config('platforms.careem.endpoints.order_detail'));
    
    return Http::timeout($this->timeout)
        ->withToken($token)
        ->withHeaders([
            'User-Agent' => $this->userAgent,
            'Brand-Id' => $brandId,
            'Branch-Id' => $branchId,
        ])
        ->put($this->baseUrl . $endpoint, [
            'state' => 'accepted'
        ])
        ->json();
}
```

#### 1.2 Modify ProcessCareemOrderJob
Add order acceptance BEFORE or AFTER Loyverse sync:

```php
public function handle(): void
{
    $tenant = Tenant::findOrFail($this->tenantId);
    app()->instance('tenant', $tenant);
    
    // Extract order details
    $orderId = $this->payload['details']['id'] ?? $this->payload['id'];
    $brandId = $tenant->getSetting('careem_brand_id');
    $branchId = $tenant->getSetting('careem_branch_id');
    
    // Store order in database
    $order = Order::create([
        'tenant_id' => $this->tenantId,
        'careem_order_id' => $orderId,
        'order_data' => $this->payload,
        'status' => 'pending',
    ]);
    
    // AUTO-ACCEPT ORDER TO CAREEM (IF ENABLED)
    if ($tenant->getSetting('auto_accept_careem', false) && $brandId && $branchId) {
        try {
            $careemService = new \App\Services\CareemApiService($this->tenantId);
            $careemService->acceptOrder($orderId, $brandId, $branchId);
            
            \Log::info('Order accepted in Careem', ['order_id' => $orderId]);
            
            $order->update(['careem_status' => 'accepted']);
        } catch (\Exception $e) {
            \Log::error('Failed to accept order in Careem', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            // Don't fail the job - continue with Loyverse sync
        }
    }
    
    // Continue with Loyverse sync
    SyncToLoyverseJob::dispatch($order);
}
```

#### 1.3 Update Orders Table Migration
Add column to track Careem order status:

```php
$table->enum('careem_status', [
    'pending', 
    'accepted', 
    'ready', 
    'driver_coming', 
    'driver_here', 
    'trip_started', 
    'trip_ended', 
    'cancelled'
])->nullable()->after('status');
```

### Priority 2: HIGH (Implement Soon)

#### 2.1 Mark Order as Ready
```php
public function markOrderReady(string $orderId, string $brandId, string $branchId): array
{
    // Similar to acceptOrder but with state: "ready"
}
```

#### 2.2 Cancel Order
```php
public function cancelOrder(
    string $orderId, 
    string $brandId, 
    string $branchId, 
    string $cancellationReason
): array
{
    // PUT /orders/{order_id} with state: "cancelled"
}
```

#### 2.3 Handle ORDER_STATUS_UPDATED Webhook
Add new webhook handler to track captain assignment, delivery status, etc.

### Priority 3: MEDIUM (Nice to Have)

- Request preparation delay
- Tag orders with metadata
- List orders via GET /orders
- Fetch individual order details

---

## 🚨 Immediate Action Required

### Step 1: Verify Webhook Registration
Before implementing code, confirm with Careem:

1. **Is your webhook URL registered?**
   - URL: `https://yourdomain.com/api/webhook/careem/{tenant_subdomain}`
   - Contact: Careem Partner Support

2. **Is POS integration enabled for your branch?**
   - Check via: Dashboard → Careem Branches → Toggle POS Integration
   - Or call: `CareemApiService::toggleBranchPosIntegration()`

3. **Are you receiving ORDER_CREATED webhooks?**
   - Check: `webhook_logs` table
   - If empty, webhook URL is not configured or branch not active

### Step 2: Get Brand ID and Branch ID
Required for order acceptance:

```php
// Check tenant settings
$tenant = Tenant::find('your-tenant-id');
$brandId = $tenant->getSetting('careem_brand_id');
$branchId = $tenant->getSetting('careem_branch_id');

// Or from Careem Branches management
$branch = \App\Models\CareemBranch::where('tenant_id', $tenant->id)->first();
$brandId = $branch->careem_brand_id;
$branchId = $branch->careem_branch_id;
```

### Step 3: Test with Manual Acceptance
Before implementing auto-accept, test manually:

```php
// In artisan tinker
$service = new \App\Services\CareemApiService('tenant-id');
$response = $service->acceptOrder(
    '62504546',  // Order ID from webhook
    'your-brand-id',
    'your-branch-id'
);
```

---

## 📖 Careem Webhook Event Types

Your system should handle these webhooks:

### 1. ORDER_CREATED (Currently Handled - Partial)
- **When**: Customer places order
- **Status**: `pending`
- **Action Required**: Accept order via PUT /orders/{order_id}
- **Current Status**: ✅ Webhook received, ❌ Not accepted back to Careem

### 2. ORDER_STATUS_UPDATED (NOT Handled)
- **When**: Order progresses through states
- **States**:
  - `accepted` - Partner accepted, preparing
  - `slot_upcoming` - Scheduled order approaching
  - `slot_started` - Scheduled time slot started
  - `driver_coming` - Captain assigned, on the way
  - `driver_here` - Captain arrived at restaurant
  - `trip_started` - Captain picked up order
  - `trip_ended` - Delivered to customer
  - `cancelled` - Order cancelled
- **Current Status**: ❌ Not implemented

### 3. ORDER_ITEM_REPLACEMENT_ACCEPTED (NOT Handled)
- **When**: Customer accepts item replacement
- **Current Status**: ❌ Not implemented

---

## 📝 Configuration Required in Tenant Settings

For order acceptance to work, tenants need:

```php
// In tenants.settings JSON column
{
  "auto_accept_careem": true,  // ✅ Already exists
  "careem_brand_id": "bddd1d9f-3839-4186-9f10-33b46047c846",  // ❌ MISSING
  "careem_branch_id": "5f915a5a-1d24-4101-ba8c-f4e5f00703bf",  // ❌ MISSING
  "auto_mark_ready": true,  // ❌ NEW - auto mark as ready when order prepared
  "preparation_time_minutes": 15  // ❌ NEW - default preparation time
}
```

**OR** retrieve from `careem_branches` table:

```php
$branch = $tenant->careemBranches()->where('pos_integration_enabled', true)->first();
$brandId = $branch->careem_brand_id;
$branchId = $branch->careem_branch_id;
```

---

## 🎯 Summary

### The Problem
Your implementation is **one-way only** (receive orders) when Careem requires **two-way communication** (receive + accept/update orders).

### The Solution
Implement order acceptance API calls so Careem knows you've received and are preparing the order.

### Implementation Files to Modify
1. `app/Services/CareemApiService.php` - Add acceptOrder(), markOrderReady(), cancelOrder()
2. `app/Jobs/ProcessCareemOrderJob.php` - Add order acceptance logic
3. `database/migrations/*_orders_table.php` - Add careem_status column
4. `app/Http/Controllers/Api/WebhookController.php` - Add ORDER_STATUS_UPDATED handler

### Priority
🔴 **CRITICAL** - Without this, the integration is incomplete and orders will fail or not be sent to your system.

---

## 📞 Next Steps

1. ✅ Implement order acceptance (provided in next message)
2. ✅ Add brand_id and branch_id to tenant settings
3. ✅ Test with real order from Careem app
4. ✅ Monitor webhook_logs and orders tables
5. ✅ Implement ORDER_STATUS_UPDATED handler for tracking

---

**Generated:** <?= date('Y-m-d H:i:s') ?>
