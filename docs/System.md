Great question! Let me walk you through the complete system flow from when a customer places an order on Careem to when it appears in your Loyverse POS.

  System Flow Overview

  Customer Orders → Careem Now → Webhook → Our Laravel App → Queue Jobs → Loyverse POS
                                                ↓
                                           Database
                                                ↓
                                        Admin Dashboard

  Detailed Step-by-Step Flow

  Step 1: Customer Places Order on Careem Now

  - Customer orders food through Careem Now app
  - Order contains: items, quantities, prices, customer details, etc.

  Step 2: Careem Sends Webhook to Our System

  Endpoint: POST /api/webhook/careem

  What happens:
  1. Careem's system immediately sends an HTTP POST request to our webhook endpoint
  2. The webhook includes:
    - Order details (items, prices, customer info)
    - Digital signature (for security verification)

  File: routes/api.php
  Route::post('/webhook/careem', [WebhookController::class, 'handleCareem'])
      ->middleware('verify.webhook.signature');

  Step 3: Webhook Validation & Logging

  File: app/Http/Middleware/VerifyWebhookSignature.php

  Security Check:
  // Verifies the signature to ensure the request is really from Careem
  X-Careem-Signature header → Compare with our secret → ✓ Valid / ✗ Reject

  File: app/Http/Controllers/Api/WebhookController.php

  What happens:
  public function handleCareem(CareemOrderRequest $request)
  {
      // 1. Validate the incoming data structure
      $orderData = $request->validated();

      // 2. Log the webhook to database for audit trail
      WebhookLog::create([
          'payload' => $orderData,
          'status' => 'received'
      ]);

      // 3. Dispatch to queue for async processing
      ProcessCareemOrderJob::dispatch($orderData);

      // 4. Respond to Careem IMMEDIATELY (< 5 seconds)
      return response()->json(['success' => true]);
  }

  Why respond quickly? Careem expects a response within 5 seconds or they'll retry, causing duplicates.

  Step 4: Queue Job - Process Careem Order

  File: app/Jobs/ProcessCareemOrderJob.php

  This job runs asynchronously in the background:

  public function handle()
  {
      // 1. Check if order already exists (prevent duplicates)
      $existingOrder = Order::where('careem_order_id', $this->orderData['order_id'])->first();

      if ($existingOrder) {
          return; // Already processed, skip
      }

      // 2. Store order in our database
      $order = Order::create([
          'careem_order_id' => $this->orderData['order_id'],
          'order_data' => $this->orderData,
          'status' => 'pending'
      ]);

      // 3. Create sync log entry
      SyncLog::create([
          'order_id' => $order->id,
          'action' => 'order_received',
          'status' => 'success'
      ]);

      // 4. Dispatch next job to sync to Loyverse
      SyncToLoyverseJob::dispatch($order);
  }

  Database Tables Updated:
  - orders - Stores the Careem order
  - sync_logs - Logs that we received it

  Step 5: Product Mapping

  File: app/Services/ProductMappingService.php

  Before we can send to Loyverse, we need to map Careem product IDs to Loyverse item IDs:

  Careem Product         →  Database Mapping  →  Loyverse Item
  "PROD-PIZZA-001"       →  product_mappings  →  "abc123-uuid-456"

  How it works:
  // Look up each Careem product in the mapping table
  $mapping = ProductMapping::where('careem_product_id', 'PROD-PIZZA-001')
      ->where('is_active', true)
      ->first();

  // Returns Loyverse item ID: "abc123-uuid-456"

  Product Mappings Table:
  | careem_product_id | careem_sku  | loyverse_item_id    | is_active |
  |-------------------|-------------|---------------------|-----------|
  | PROD-PIZZA-001    | PIZZA-MARG  | abc123-uuid-456     | 1         |
  | PROD-SALAD-002    | SALAD-CAESAR| def789-uuid-123     | 1         |

  What if product not mapped?
  - Product is logged as unmapped in sync_logs
  - Order continues with OTHER mapped products
  - Order fails ONLY if NO products can be mapped
  - Admin can see unmapped products in dashboard and fix mappings

  Step 6: Order Transformation

  File: app/Services/OrderTransformerService.php

  Transforms Careem format → Loyverse format:

  Careem Order Format:
  {
    "order_id": "CAREEM-12345",
    "items": [
      {
        "product_id": "PROD-PIZZA-001",
        "name": "Margherita Pizza",
        "quantity": 2,
        "unit_price": 45.00
      }
    ],
    "pricing": {
      "subtotal": 90.00,
      "tax": 4.50,
      "total": 94.50
    }
  }

  Loyverse Receipt Format:
  {
    "receipt_type": "SALE",
    "receipt_date": "2025-10-19T10:30:00.000Z",
    "note": "Careem Order: CAREEM-12345",
    "source": "API",
    "dining_option": "DELIVERY",
    "store_id": "store-uuid",
    "pos_device_id": "device-uuid",
    "employee_id": "employee-uuid",
    "customer_id": "careem-customer-uuid",  // Always "Careem" customer
    "line_items": [
      {
        "item_id": "abc123-uuid-456",  // Mapped from PROD-PIZZA-001
        "quantity": 2,
        "price": 45.00
      }
    ],
    "payments": [
      {
        "payment_type_id": "payment-type-uuid",
        "amount": 94.50
      }
    ]
  }

  Key Transformations:
  1. Customer: ALL orders → Single "Careem" customer
  2. Products: Careem product IDs → Loyverse item IDs (via mapping table)
  3. Receipt metadata: Store ID, POS device, employee (from config)
  4. Payment: Default payment type (usually "Card" or "Cash")

  Step 7: Sync to Loyverse

  File: app/Jobs/SyncToLoyverseJob.php

  public function handle()
  {
      try {
          // 1. Update order status
          $this->order->update(['status' => 'processing']);

          // 2. Transform the order
          $loyverseData = $this->orderTransformer->transform(
              $this->order->order_data,
              $this->order->id
          );

          // 3. Send to Loyverse API
          $response = $this->loyverseService->createReceipt($loyverseData);

          // 4. Store the Loyverse receipt details
          LoyverseOrder::create([
              'order_id' => $this->order->id,
              'loyverse_order_id' => $response['id'],
              'loyverse_receipt_number' => $response['receipt_number'],
              'sync_status' => 'success',
              'sync_response' => $response,
              'synced_at' => now()
          ]);

          // 5. Update order status
          $this->order->update(['status' => 'synced']);

          // 6. Log success
          SyncLog::create([
              'order_id' => $this->order->id,
              'action' => 'sync_to_loyverse',
              'status' => 'success',
              'message' => 'Order synced successfully'
          ]);

      } catch (LoyverseApiException $e) {
          // Handle errors (rate limits, validation, etc.)
      }
  }

  Database Tables Updated:
  - orders - Status: pending → processing → synced
  - loyverse_orders - Stores Loyverse receipt ID and details
  - sync_logs - Logs the sync operation

  Step 8: API Call to Loyverse

  File: app/Services/LoyverseApiService.php

  public function createReceipt(array $data): array
  {
      // Rate limiting: Only 55 requests per minute
      return RateLimiter::attempt('loyverse-api', 55, function () use ($data) {

          // Send POST request to Loyverse
          $response = Http::withToken($this->token)
              ->post('https://api.loyverse.com/v1.0/receipts', $data);

          // Handle response
          if ($response->successful()) {
              return $response->json();
          }

          // Handle errors
          throw new LoyverseApiException($response->body(), $response->status());
      });
  }

  Loyverse API Response:
  {
    "id": "loyverse-receipt-uuid",
    "receipt_number": "R-12345",
    "receipt_date": "2025-10-19T10:30:00.000Z",
    "total_money": 94.50,
    "created_at": "2025-10-19T10:30:15.000Z"
  }

  Step 9: Error Handling & Retry Logic

  If something goes wrong:

  Rate Limit (429 Error):
  // Job is released back to queue with delay
  $this->release(60); // Try again in 60 seconds

  Server Error (500, 503):
  // Automatic retry with exponential backoff
  $backoff = [60, 300, 900, 1800, 3600]; // 1min, 5min, 15min, 30min, 1hr
  $tries = 5; // Maximum 5 attempts

  Validation Error (400):
  // Permanent failure - don't retry
  $this->fail($exception);
  // Log to sync_logs with error details

  Queue Configuration:
  class SyncToLoyverseJob implements ShouldQueue
  {
      public $tries = 5;
      public $backoff = [60, 300, 900, 1800, 3600];
      public $timeout = 300;
  }

  Step 10: Dashboard Monitoring

  File: app/Http/Controllers/Dashboard/DashboardController.php

  Admins can monitor everything in real-time:

  Dashboard Shows:
  1. Statistics:
    - Total orders received
    - Successfully synced orders
    - Failed orders
    - Today's orders
  2. Recent Orders Table:
    - Order ID
    - Status (pending/processing/synced/failed)
    - Created time
    - Actions (view, retry)
  3. Sync Logs:
    - All operations
    - Error messages
    - Timestamps
  4. Product Mappings:
    - Manage Careem ↔ Loyverse mappings
    - Auto-map by SKU
    - Import/export CSV

  Complete Flow Diagram

  ┌─────────────────────────────────────────────────────────────────┐
  │                         CUSTOMER                                │
  │                     (Places order on Careem)                    │
  └────────────────────────────┬────────────────────────────────────┘
                               │
                               ▼
  ┌─────────────────────────────────────────────────────────────────┐
  │                      CAREEM NOW PLATFORM                        │
  │                  (Sends webhook immediately)                    │
  └────────────────────────────┬────────────────────────────────────┘
                               │
                               │ POST /api/webhook/careem
                               │ + Signature
                               ▼
  ┌─────────────────────────────────────────────────────────────────┐
  │                    OUR LARAVEL APPLICATION                      │
  │                                                                 │
  │  ┌──────────────────────────────────────────────────────────┐  │
  │  │ 1. VerifyWebhookSignature Middleware                     │  │
  │  │    ✓ Check signature                                     │  │
  │  │    ✓ Validate request                                    │  │
  │  └───────────────────────┬──────────────────────────────────┘  │
  │                          │                                      │
  │                          ▼                                      │
  │  ┌──────────────────────────────────────────────────────────┐  │
  │  │ 2. WebhookController                                     │  │
  │  │    • Log webhook to webhook_logs                         │  │
  │  │    • Dispatch ProcessCareemOrderJob                      │  │
  │  │    • Return 200 OK to Careem (< 5 sec)                   │  │
  │  └───────────────────────┬──────────────────────────────────┘  │
  │                          │                                      │
  │                          ▼                                      │
  │  ┌──────────────────────────────────────────────────────────┐  │
  │  │ 3. Queue: ProcessCareemOrderJob                          │  │
  │  │    • Check for duplicate order                           │  │
  │  │    • Store in orders table                               │  │
  │  │    • Log to sync_logs                                    │  │
  │  │    • Dispatch SyncToLoyverseJob                          │  │
  │  └───────────────────────┬──────────────────────────────────┘  │
  │                          │                                      │
  │                          ▼                                      │
  │  ┌──────────────────────────────────────────────────────────┐  │
  │  │ 4. Queue: SyncToLoyverseJob                              │  │
  │  │    ┌──────────────────────────────────────────────────┐  │  │
  │  │    │ a. ProductMappingService                         │  │  │
  │  │    │    Query product_mappings table                  │  │  │
  │  │    │    Map Careem products → Loyverse items          │  │  │
  │  │    └──────────────────────────────────────────────────┘  │  │
  │  │    ┌──────────────────────────────────────────────────┐  │  │
  │  │    │ b. OrderTransformerService                       │  │  │
  │  │    │    Transform Careem format → Loyverse format     │  │  │
  │  │    │    Set customer to "Careem"                      │  │  │
  │  │    │    Add store/device/employee IDs                 │  │  │
  │  │    └──────────────────────────────────────────────────┘  │  │
  │  │    ┌──────────────────────────────────────────────────┐  │  │
  │  │    │ c. LoyverseApiService                            │  │  │
  │  │    │    Rate limit check (55/min)                     │  │  │
  │  │    │    POST /v1.0/receipts                           │  │  │
  │  │    │    Handle response                               │  │  │
  │  │    └──────────────────────────────────────────────────┘  │  │
  │  │    • Store in loyverse_orders                            │  │
  │  │    • Update order status to 'synced'                     │  │
  │  │    • Log to sync_logs                                    │  │
  │  └───────────────────────┬──────────────────────────────────┘  │
  │                          │                                      │
  └──────────────────────────┼──────────────────────────────────────┘
                             │
                             ▼
  ┌─────────────────────────────────────────────────────────────────┐
  │                      LOYVERSE POS API                           │
  │                  (Order created as receipt)                     │
  └────────────────────────────┬────────────────────────────────────┘
                               │
                               ▼
  ┌─────────────────────────────────────────────────────────────────┐
  │                      LOYVERSE POS SYSTEM                        │
  │              (Order appears in POS for restaurant)              │
  │                   Customer: "Careem"                            │
  └─────────────────────────────────────────────────────────────────┘

  Key Database Tables & Their Roles

  1. orders - Master record of all Careem orders
  id, careem_order_id, order_data (JSON), status, created_at

  2. loyverse_orders - Loyverse receipt tracking
  id, order_id, loyverse_order_id, loyverse_receipt_number, synced_at

  3. product_mappings - Product ID translation
  careem_product_id, careem_sku, loyverse_item_id, is_active

  4. sync_logs - Detailed audit trail
  order_id, action, status, message, metadata, created_at

  5. webhook_logs - Webhook audit trail
  payload (JSON), headers (JSON), status, created_at

  Important Features

  1. Asynchronous Processing:
  - Webhook responds in < 5 seconds
  - Heavy processing happens in background
  - Customer doesn't wait

  2. Retry Logic:
  - Automatic retry on failures
  - Exponential backoff (prevents overwhelming APIs)
  - Manual retry from dashboard

  3. Rate Limiting:
  - Loyverse API: 60 requests/min limit
  - We use 55/min to leave buffer
  - Automatic queue delay on rate limit

  4. Product Mapping:
  - Required for integration to work
  - Can be set up via:
    - Manual entry in dashboard
    - Auto-mapping by SKU
    - CSV import
  - Orders with unmapped products still process with mapped items

  5. Single Customer Strategy:
  - ALL Careem orders → One "Careem" customer in Loyverse
  - Simplifies reporting and management
  - Customer is auto-created if missing

  6. Error Monitoring:
  - All operations logged
  - Failed jobs visible in dashboard
  - Can retry individual or all failed orders
  - Detailed error messages for debugging

  This architecture ensures reliability, scalability, and easy monitoring of the entire integration!
