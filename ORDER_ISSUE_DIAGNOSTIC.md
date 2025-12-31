# Order Reception Issue - Diagnostic Summary & Solutions

## 🔍 Diagnostic Results

Based on the diagnostic scan of your system, here are the findings:

### Issues Identified

1. **❌ No Webhooks Received**
   - Zero webhook logs found in the `webhook_logs` table
   - This indicates that Careem is NOT sending orders to your system

2. **❌ No Orders in Database**
   - Zero orders found in the `orders` table
   - Confirms that the system hasn't received any orders

3. **⚠️ Queue Worker Not Running**
   - 1 job is stuck in the queue
   - Queue worker needs to be started to process jobs

4. **✅ Auto-Accept Configuration Exists**
   - The system has auto-accept settings in tenant configuration
   - However, **auto-accept refers to automatic syncing to Loyverse, NOT accepting orders back to Careem**

---

## 🎯 Root Cause Analysis

### The Order Flow

Here's how the system is designed to work:

```
Careem App → Careem API → Webhook → Your System → Queue → Loyverse POS
     1            2           3          4           5         6
```

**Current Status:**
- ❌ Step 3 is failing - webhooks are not reaching your system
- The problem is BEFORE your application

### Why Orders Aren't Showing Up

**Primary Issue:** Careem is not sending webhook notifications to your system. This could be due to:

1. **Webhook URL Not Configured in Careem Dashboard**
   - You need to register your webhook URL with Careem
   - Format: `https://yourdomain.com/api/webhook/careem/{tenant_subdomain}`

2. **POS Integration Not Enabled for Branch**
   - In Careem's system, the branch must have POS integration enabled
   - This tells Careem to send orders to your webhook

3. **Branch Status Inactive**
   - If the branch visibility is set to "Inactive" in Careem, orders won't flow

4. **Network/Firewall Issues**
   - Careem can't reach your webhook endpoint
   - Check if your domain is publicly accessible

---

## 🛠️ Solutions & Action Steps

### Step 1: Start Queue Worker (Immediate)

```bash
# Windows
run-queue-worker.bat

# Or manually
php artisan queue:work database --queue=high,default
```

Keep this running in a separate terminal window.

### Step 2: Verify Webhook URL Configuration

**Your webhook URL format:**
```
https://yourdomain.com/api/webhook/careem/{tenant_subdomain}
```

**Example:**
If your tenant subdomain is `test-restaurant`, the URL would be:
```
https://yourdomain.com/api/webhook/careem/test-restaurant
```

**Action:** Contact Careem support or check your merchant dashboard to register this webhook URL.

### Step 3: Enable POS Integration

Use the branch management interface to ensure POS integration is enabled:

1. Go to Dashboard → Careem Branches
2. Find your branch
3. Click "Toggle POS Integration" to enable
4. This will call the Careem API to enable order flow

### Step 4: Test Webhook Manually

Create a test script to simulate Careem sending an order:

```php
<?php
// test_webhook.php

$tenantSubdomain = 'your-tenant-subdomain'; // Replace with actual subdomain
$webhookUrl = "http://localhost/api/webhook/careem/{$tenantSubdomain}";

$testOrder = [
    'order_id' => 'TEST-' . time(),
    'customer' => [
        'name' => 'Test Customer',
        'phone' => '+1234567890'
    ],
    'items' => [
        [
            'product_id' => 'PROD-123',
            'name' => 'Test Product',
            'sku' => 'TEST-SKU',
            'quantity' => 1,
            'unit_price' => 10.00,
            'total_price' => 10.00
        ]
    ],
    'total' => 10.00,
    'created_at' => date('c')
];

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testOrder));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Careem-Signature: test-signature' // You may need to adjust based on your signature verification
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n";
echo "Response: {$response}\n";
```

Run: `php test_webhook.php`

This will help you verify if webhooks can reach your system.

### Step 5: Check Application Logs

```bash
# Check recent logs
tail -n 50 storage/logs/laravel.log

# On Windows
Get-Content storage/logs/laravel.log -Tail 50
```

Look for any errors related to webhooks or order processing.

---

## ℹ️ Important Clarification: "Auto-Accept"

### What Auto-Accept Currently Does

The `auto_accept_careem` setting in your system **DOES NOT** accept orders back to Careem. Instead, it controls:

✅ Automatic processing of received webhooks
✅ Automatic syncing to Loyverse POS
✅ No manual intervention required

### What Auto-Accept DOES NOT Do

❌ Send acceptance confirmation back to Careem API
❌ Change order status in Careem's system
❌ Trigger any API call to Careem

### Do You Need to Accept Orders Back to Careem?

**Question to investigate:** Does Careem require you to send an order acceptance response?

Most food delivery platforms work in one of two ways:

**Option A: Webhook Only (One-Way)**
- Careem sends order → Your system receives it
- No confirmation required
- Orders are automatically "accepted" when webhook is received successfully (HTTP 200 response)

**Option B: Webhook + Acceptance API (Two-Way)**
- Careem sends order → Your system receives it
- Your system must call Careem's API to accept/reject the order
- Requires additional implementation

**To find out which applies:**
1. Check Careem's API documentation
2. Contact your Careem integration support
3. Look for an "Accept Order" or "Update Order Status" endpoint in the API docs

---

## 🔧 If Order Acceptance API is Required

If Careem requires you to call an API to accept orders, you'll need to implement:

### 1. Add Accept Order Method to CareemApiService

```php
// app/Services/CareemApiService.php

public function acceptOrder(string $orderId, string $brandId, string $branchId): array
{
    $token = $this->getAccessToken();
    $endpoint = "/orders/{$orderId}/accept"; // Adjust based on actual API
    
    $url = $this->baseUrl . $endpoint;
    
    Log::info('Accepting Careem order', [
        'order_id' => $orderId,
        'brand_id' => $brandId,
        'branch_id' => $branchId
    ]);
    
    try {
        $response = Http::timeout($this->timeout)
            ->withToken($token)
            ->withHeaders([
                'User-Agent' => $this->userAgent,
                'Brand-Id' => $brandId,
                'Branch-Id' => $branchId,
            ])
            ->post($url, [
                'status' => 'accepted',
                'estimated_preparation_time' => 15 // minutes
            ]);
        
        if (!$response->successful()) {
            throw new PlatformApiException(
                'Careem',
                'Failed to accept order: ' . $response->body(),
                $response->status()
            );
        }
        
        Log::info('Order accepted successfully', ['order_id' => $orderId]);
        
        return $response->json();
        
    } catch (\Exception $e) {
        Log::error('Order acceptance failed', [
            'order_id' => $orderId,
            'error' => $e->getMessage()
        ]);
        
        throw $e;
    }
}
```

### 2. Modify ProcessCareemOrderJob

```php
// In app/Jobs/ProcessCareemOrderJob.php

public function handle(): void
{
    $tenant = Tenant::findOrFail($this->tenantId);
    app()->instance('tenant', $tenant);
    
    $order = Order::create([
        'tenant_id' => $this->tenantId,
        'careem_order_id' => $this->payload['order_id'],
        'order_data' => $this->payload,
        'status' => 'pending',
    ]);
    
    // Check if auto-accept is enabled
    if ($tenant->getSetting('auto_accept_careem', false)) {
        try {
            $careemService = new \App\Services\CareemApiService($this->tenantId);
            
            // Get brand and branch IDs from order or tenant settings
            $brandId = $tenant->getSetting('careem_brand_id');
            $branchId = $tenant->getSetting('careem_branch_id');
            
            if ($brandId && $branchId) {
                $careemService->acceptOrder(
                    $this->payload['order_id'],
                    $brandId,
                    $branchId
                );
                
                \Log::info('Order auto-accepted in Careem', [
                    'order_id' => $order->id
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to auto-accept order in Careem', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            // Don't fail the job - continue with Loyverse sync
        }
    }
    
    SyncToLoyverseJob::dispatch($order);
}
```

---

## 📋 Checklist

Use this checklist to resolve the issue:

- [ ] Start queue worker (`php artisan queue:work database --queue=high,default`)
- [ ] Verify webhook URL is registered in Careem dashboard
- [ ] Confirm tenant subdomain is correct
- [ ] Check POS integration is enabled for your branch in Careem
- [ ] Verify branch visibility status is "Active" (status_id = 1)
- [ ] Test webhook endpoint manually using the test script
- [ ] Check application logs for errors
- [ ] Place a test order on Careem app
- [ ] Run diagnostic script: `php diagnose_order_issue.php`
- [ ] Check if Careem requires order acceptance API (consult docs/support)
- [ ] If acceptance required, implement the acceptance API integration

---

## 🔍 Monitoring & Debugging

### Check Webhook Logs

```php
php artisan tinker

// Check recent webhook logs
\App\Models\WebhookLog::latest()->take(10)->get(['id', 'created_at', 'status']);

// See full webhook data
\App\Models\WebhookLog::latest()->first()->payload;
```

### Check Orders

```php
php artisan tinker

// Check recent orders
\App\Models\Order::latest()->take(10)->get(['id', 'careem_order_id', 'status', 'created_at']);

// Check failed orders
\App\Models\Order::where('status', 'failed')->latest()->get();
```

### Check Queue Status

```bash
# Check pending jobs
php artisan queue:work --once

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## 📞 Next Steps

1. **Immediate:** Start the queue worker
2. **Verify:** Check if webhook URL is configured in Careem
3. **Test:** Use the manual webhook test script
4. **Contact Careem:** Ask about:
   - How to register webhook URL
   - Whether order acceptance API is required
   - POS integration activation process

---

## 📚 Related Files

- Webhook Controller: `app/Http/Controllers/Api/WebhookController.php`
- Order Processing Job: `app/Jobs/ProcessCareemOrderJob.php`
- Careem API Service: `app/Services/CareemApiService.php`
- Diagnostic Script: `diagnose_order_issue.php`
- Routes: `routes/api.php`

---

**Generated:** <?= date('Y-m-d H:i:s') ?>
