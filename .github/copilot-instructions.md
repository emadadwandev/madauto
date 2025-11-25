# Copilot Instructions — Careem → Loyverse SaaS Integration

## Architecture & Core Concepts
- **Type**: Multi-tenant SaaS (Laravel 12) connecting Careem/Talabat webhooks to Loyverse POS.
- **Tenancy**: Hybrid multi-tenancy. `Tenant` model is central. All data (Orders, Products) is scoped by `tenant_id`.
  - **Routing**: Subdomain-based (`{tenant}.app.com`). Routes in `routes/tenant.php`.
  - **Context**: `TenantContext` service & `IdentifyTenant` middleware bind `tenant()` globally.
- **Data Flow**:
  1. **Webhook**: `routes/api.php` -> `WebhookController` (validates signature & tenant).
  2. **Queue**: Dispatches `ProcessCareemOrderJob` (stores raw order) -> `SyncToLoyverseJob`.
  3. **Sync**: `OrderTransformerService` (maps data) -> `ProductMappingService` (SKU match) -> `LoyverseApiService` (API call).

## Critical Files
- `app/Services/LoyverseApiService.php`: API client with rate limiting (55/min), caching, and retry logic.
- `app/Services/OrderTransformerService.php`: Converts platform payloads to Loyverse receipt shape.
- `app/Models/Tenant.php`: The root of tenant isolation. Check `HasTenant` trait usage.
- `routes/tenant.php`: Tenant-specific routes (Dashboard, Settings).
- `app/Jobs/SyncToLoyverseJob.php`: The core logic for syncing orders.

## Development Patterns
- **Tenant Isolation**: ALWAYS ensure models use `HasTenant` trait. Never query across tenants without explicit intent.
  - *Bad*: `Order::all()` (if context missing) | *Good*: `tenant()->orders()->get()` or `Order::all()` (within tenant route).
- **Credentials**: Stored in `api_credentials` table (encrypted), NOT `.env`. Use `ApiCredentialRepository`.
- **Queues**: Heavy reliance on queues.
  - *Dev*: Run `php artisan queue:work database --queue=high,default`.
  - *Code*: Jobs must accept `tenant_id` and restore context: `app()->instance('tenant', Tenant::find($this->tenantId))`.
- **Product Mapping**:
  - Auto-mapping via SKU. Manual overrides in `product_mappings` table.
  - Unmapped items don't fail the order unless *all* items fail.

## Common Commands
- **Setup**: `composer install && npm install && npm run build`.
- **Queue**: `run-queue-worker.bat` (Win) or `php artisan queue:work`.
- **Test**: `php artisan test` (Focus on Unit/Feature tests for logic).
- **Fix Auth**: If login fails locally, check `SESSION_DOMAIN` in `.env` (should match host, e.g., `.localhost`).

## Gotchas
- **Webhooks**: URL format is `/api/webhook/careem/{tenant_subdomain}`.
- **IDs**: `careem_order_id` column is used for ALL platforms (legacy naming).
- **Rate Limits**: Loyverse is strict. `LoyverseApiService` handles 429s automatically.
