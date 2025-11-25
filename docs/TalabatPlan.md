 Talabat Integration Plan

     Overview

     Integrate Talabat food delivery platform using a similar architecture to the existing Careem integration, with separate customer and payment tracking.

     Architecture Approach

     Pattern: Multi-Platform Delivery Integration
     - Shared infrastructure (queue, database, Loyverse sync)
     - Platform-specific webhooks, customers, and payment types
     - Unified dashboard for all platforms

     Implementation Steps

     1. Database Changes

     Add platform identifier to orders table
     - Add platform column (enum: 'careem', 'talabat')
     - Update careem_order_id → rename to platform_order_id OR keep and add talabat_order_id
     - Add index on platform column

     2. Authentication Middleware

     Create VerifyTalabatApiKey middleware
     - Verify API Key from request headers (e.g., X-Talabat-API-Key or Authorization: Bearer)
     - Fetch API key from api_credentials table (service: 'talabat')
     - Return 401 if invalid

     3. Webhook Endpoint

     Add Talabat webhook handler
     - Route: POST /api/webhook/talabat
     - Controller: WebhookController::handleTalabat()
     - Middleware: VerifyTalabatApiKey
     - Log to webhook_logs with platform identifier
     - Dispatch ProcessTalabatOrderJob

     4. Order Processing Jobs

     Create ProcessTalabatOrderJob
     - Validate Talabat order structure
     - Store in orders table with platform = 'talabat'
     - Dispatch to shared SyncToLoyverseJob

     5. Order Transformer Updates

     Modify OrderTransformerService
     - Add platform detection
     - Use "Talabat" customer for Talabat orders
     - Use "Talabat" payment type for Talabat orders
     - Support both Careem and Talabat order formats

     6. Services Updates

     Enhance LoyverseApiService
     - Add findOrCreateTalabatCustomer() method
     - Cache Talabat customer ID separately

     7. Product Mapping

     Reuse existing product mapping system
     - Add platform filter (optional)
     - Support mapping same product to different platforms
     - OR use shared mappings (recommended for simplicity)

     8. Dashboard Updates

     Enhance UI to show platform
     - Add platform badge/icon on orders list
     - Filter orders by platform
     - Platform-specific statistics
     - Update navigation (optional: "Orders" → dropdown with "Careem" / "Talabat" / "All")

     9. API Credentials Management

     Add Talabat credentials
     - Store in api_credentials table
     - Fields: service='talabat', credential_type='api_key'
     - Add to Settings page with connection test

     10. Configuration

     Add Talabat config
     - .env variables for Talabat API endpoint
     - Webhook URL display in settings
     - Customer/payment type defaults

     Files to Create

     New Files:

     1. app/Http/Middleware/VerifyTalabatApiKey.php
     2. app/Jobs/ProcessTalabatOrderJob.php
     3. app/Http/Requests/TalabatOrderRequest.php
     4. database/migrations/YYYY_MM_DD_add_platform_to_orders_table.php

     Files to Modify:

     1. app/Http/Controllers/Api/WebhookController.php - Add handleTalabat() method
     2. app/Services/OrderTransformerService.php - Platform detection & customer/payment selection
     3. app/Services/LoyverseApiService.php - Add findOrCreateTalabatCustomer()
     4. routes/api.php - Add Talabat webhook route
     5. app/Http/Kernel.php - Register new middleware
     6. resources/views/dashboard/* - Add platform filters/badges
     7. .env.example - Add Talabat variables

     Key Design Decisions

     Shared vs Separate:

     - ✅ Shared: Database tables, queue system, product mappings, Loyverse sync logic
     - ❌ Separate: Webhooks, customers, payment types, authentication

     Customer Strategy:

     - Careem orders → "Careem" customer
     - Talabat orders → "Talabat" customer
     - Easy to track revenue per platform in Loyverse

     Payment Type Strategy:

     - Careem orders → "Careem" payment type
     - Talabat orders → "Talabat" payment type
     - Clear financial reporting per platform

     Testing Strategy

     1. Setup Talabat API credentials in Settings
     2. Test webhook with sample Talabat order payload
     3. Verify "Talabat" customer created in Loyverse
     4. Verify "Talabat" payment type used
     5. Check dashboard shows platform correctly
     6. Test product mapping works for Talabat orders

     Future Extensibility

     This architecture allows easy addition of more platforms (Deliveroo, Uber Eats, etc.) by:
     1. Adding new webhook handler
     2. Creating platform-specific job
     3. Adding customer/payment type
     4. No changes to core sync logic needed

     Ready to implement? 🚀