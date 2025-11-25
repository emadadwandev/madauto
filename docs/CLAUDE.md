# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **multi-tenant SaaS platform** for Careem Now to Loyverse POS Integration built with Laravel 12. The platform allows multiple restaurants/businesses to subscribe and automatically synchronize their food orders from Careem Now (via webhooks) to Loyverse POS system. Each tenant operates independently with their own data, users, and API credentials.

**Key Integration Flow:**
```
Careem Now → Tenant Webhook → Laravel Queue → Transform Order → Loyverse API (Per Tenant)
```

**SaaS Architecture:**
```
Landing Page → Sign Up → Tenant Dashboard (Subdomain) → Order Sync → Super Admin Panel
```

## Technology Stack

- **Framework:** Laravel 12.33
- **PHP:** 8.2+
- **Database:** SQLite (dev) / MySQL 8.0+ (production)
- **Queue:** Database-driven with retry logic (Redis recommended for production)
- **Frontend:** Blade templates, Tailwind CSS, Alpine.js, GSAP (animations)
- **Cache/Session:** Database-backed (dev) / Redis (production)
- **Real-time:** Laravel Echo (optional, for dashboard updates)
- **Authentication:** Laravel Breeze with multi-role support
- **Authorization:** Roles & Permissions (Super Admin, Tenant Admin, Tenant User)
- **Billing:** Laravel Cashier (Stripe)
- **Multi-Tenancy:** Custom implementation with subdomain routing
- **Icons:** Heroicons or Phosphor Icons

## Common Development Commands

### Setup & Installation
```bash
# Initial setup (from careem-loyverse-integration directory)
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate

# Build frontend assets
npm run build        # Production build
npm run dev          # Development with hot reload

# Create first admin user
php artisan tinker
>>> \App\Models\User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'email_verified_at' => now()]);
```

### Development Workflow
```bash
# Start all services (development mode - uses concurrently)
composer dev         # Starts server, queue, logs, and vite concurrently

# Or run services individually:
php artisan serve                                    # Web server on :8000
php artisan queue:work database --tries=3 --verbose  # Queue worker
php artisan pail                                     # Real-time log monitoring
npm run dev                                          # Vite dev server

# Windows: Use provided scripts
run-queue-worker.bat    # Queue worker with restart on failure

# Linux/Mac:
./run-queue-worker.sh   # Queue worker with restart on failure
```

### Testing
```bash
composer test           # Run PHPUnit tests
php artisan test        # Alternative test command
```

### Cache & Optimization
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Queue Management
```bash
# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all              # Retry all failed
php artisan queue:retry <job-id>         # Retry specific job

# Restart queue workers (after code changes)
php artisan queue:restart
```

### Database Operations
```bash
# Run migrations
php artisan migrate
php artisan migrate:fresh    # Drop all tables and re-migrate
php artisan migrate:rollback # Rollback last migration

# Database inspection
php artisan tinker
>>> DB::connection()->getPdo();  # Test connection
>>> Order::count();               # Check order count
```

## Multi-Tenancy & SaaS Features

### Tenant System

**Subdomain-based Routing:**
- Landing page: `www.yourapp.com` or `yourapp.com`
- Super admin: `admin.yourapp.com`
- Tenant dashboards: `{tenant-subdomain}.yourapp.com`

**Tenant Isolation:**
- All tenant data scoped by `tenant_id`
- Global scopes on all tenant models
- Automatic tenant context detection via middleware
- No cross-tenant data access

**Key Models:**
- `Tenant` - Tenant metadata, settings, status
- `Subscription` - Links tenant to subscription plan
- `SubscriptionPlan` - Pricing tiers (Starter, Business, Enterprise)
- `SubscriptionUsage` - Track monthly order count per tenant

### User Roles & Permissions

**Three Role Types:**
1. **Super Admin** - Platform-wide access
   - Manage all tenants
   - View all subscriptions and billing
   - System configuration and monitoring
   - User impersonation for support

2. **Tenant Admin** - Tenant management access
   - Manage own tenant settings
   - Manage API credentials
   - Invite/remove team members
   - Manage subscriptions and billing
   - Configure product mappings

3. **Tenant User** - Read-only access
   - View orders and sync logs
   - View analytics
   - Cannot modify settings or data

### Subscription System

**Pricing Tiers:**
- **Starter**: $29/month - 500 orders, 1 location, 1 user
- **Business**: $79/month - 2,000 orders, 3 locations, 5 users
- **Enterprise**: $199/month - Unlimited orders/locations/users

**Features:**
- 14-day free trial for all plans
- Stripe integration for payment processing
- Usage tracking (order count per billing period)
- Upgrade/downgrade anytime
- Automatic invoicing
- Failed payment recovery (dunning)

### Landing Page

**Purpose:** Marketing site to attract and convert customers

**Sections:**
- Hero with compelling value proposition
- How It Works (3-step process)
- Features showcase
- Pricing cards with comparison
- Testimonials and social proof
- FAQ section

**Design:** Modern, clean, mobile-first with smooth animations

### Super Admin Panel

**Access:** `admin.yourapp.com`

**Features:**
- Dashboard with key metrics (MRR, churn, total tenants, orders)
- Tenant management (list, view, edit, suspend, activate)
- Subscription management
- User impersonation for support
- System health monitoring
- Failed jobs viewer
- Analytics across all tenants

### Tenant Dashboard Enhancements

**Team Management:**
- Invite users by email
- Assign roles (Tenant Admin, Tenant User)
- Remove team members
- View user activity

**Subscription Management:**
- View current plan and usage
- Upgrade/downgrade plans
- Manage payment methods
- View billing history
- Cancel subscription

**Settings:**
- Tenant information
- API credentials (Loyverse, Careem)
- Notification preferences
- Timezone configuration

## Architecture & Code Organization

### Multi-Tenancy Architecture

**Database Strategy: Hybrid Approach**
- Central database with shared tables: `tenants`, `subscriptions`, `subscription_plans`, `users`, `roles`
- Tenant-scoped tables with `tenant_id` foreign key: `orders`, `product_mappings`, `api_credentials`, etc.
- Global scope automatically filters by tenant on all queries
- Row-level security enforced by middleware and policies

**Tenant Context:**
- `TenantContext` service manages current tenant throughout request
- `IdentifyTenant` middleware detects tenant from subdomain
- `tenant()` helper function for easy access
- Tenant context bound to request lifecycle

**Tenant Scoping:**
```php
// HasTenant trait applied to all tenant models
trait HasTenant {
    protected static function booted() {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (!$model->tenant_id) {
                $model->tenant_id = tenant()->id;
            }
        });
    }
}
```

### Core Integration Components

1. **Webhook Reception** (`app/Http/Controllers/Api/WebhookController.php`)
   - Receives POST webhooks from Careem Now at `/api/webhook/careem`
   - Validates signature via `VerifyWebhookSignature` middleware
   - Logs all webhook payloads to `webhook_logs` table
   - Dispatches `ProcessCareemOrderJob` to queue

2. **Queue Processing Pipeline**
   - `ProcessCareemOrderJob`: Validates and stores Careem order
   - `SyncToLoyverseJob`: Transforms and syncs to Loyverse API
   - `RetryFailedSyncJob`: Handles manual retry of failed syncs

3. **Service Layer** (Business Logic)
   - `LoyverseApiService`: All Loyverse API interactions with rate limiting (55 req/min), caching, and error handling
   - `OrderTransformerService`: Maps Careem order format to Loyverse receipt format
   - `ProductMappingService`: Manages Careem SKU → Loyverse item ID mappings

4. **Data Models**

   **Core Integration Models:**
   - `Order`: Careem orders with status tracking (pending/processing/synced/failed) - Tenant scoped
   - `LoyverseOrder`: Records of synced receipts in Loyverse - Tenant scoped
   - `SyncLog`: Detailed logs of all sync operations - Tenant scoped
   - `ProductMapping`: Product mapping table with caching - Tenant scoped
   - `ApiCredential`: Encrypted storage for API credentials - Tenant scoped
   - `WebhookLog`: Audit trail of all webhook calls - Tenant scoped

   **Multi-Tenancy Models:**
   - `Tenant`: Tenant metadata (subdomain, name, status, settings)
   - `Subscription`: Links tenant to subscription plan with billing status
   - `SubscriptionPlan`: Available pricing tiers and features
   - `SubscriptionUsage`: Monthly usage tracking per tenant
   - `Role`: User roles (super_admin, tenant_admin, tenant_user)
   - `Invitation`: User invitations with email and token

### Important Implementation Details

**Product Mapping Strategy:**
- Manual mappings stored in `product_mappings` table
- Auto-mapping by SKU available via admin interface
- CSV import/export for bulk operations
- 1-hour cache TTL for performance
- Unmapped products are logged but order continues with mapped items

**Customer Strategy:**
- Single "Careem" customer for ALL orders
- Created via `findOrCreateCareemCustomer()` in LoyverseApiService
- Customer ID cached for 24 hours

**Error Handling:**
- Webhook failures: Return appropriate HTTP codes, log to `webhook_logs`
- Queue failures: Exponential backoff (60s, 300s, 900s, 1800s, 3600s), max 5 attempts
- Rate limits (429): Job released back to queue with retry delay
- Validation errors: Permanent failure, logged to `sync_logs`
- All errors tracked in `sync_logs` with detailed metadata

**Rate Limiting:**
- Loyverse API: 55 req/min (leaving 5 req buffer)
- Implemented using Laravel's RateLimiter facade
- Automatic rate limit detection and queued retry

**Caching Strategy:**
- Items: 1 hour
- Stores/Employees/PaymentTypes: 24 hours
- Product mappings: 1 hour
- Careem customer ID: 24 hours

### Directory Structure (Key Locations)

```
careem-loyverse-integration/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/WebhookController.php          # Careem webhook endpoint (tenant-scoped)
│   │   ├── Landing/                           # Landing page controllers
│   │   │   ├── LandingController.php          # Homepage
│   │   │   ├── PricingController.php          # Pricing page
│   │   │   └── RegistrationController.php     # Sign up flow
│   │   ├── SuperAdmin/                        # Super admin controllers
│   │   │   ├── DashboardController.php        # Super admin dashboard
│   │   │   ├── TenantController.php           # Tenant management
│   │   │   ├── SubscriptionController.php     # Subscription management
│   │   │   └── SystemController.php           # System monitoring
│   │   └── Dashboard/                         # Tenant dashboard controllers
│   │       ├── DashboardController.php
│   │       ├── OrderController.php
│   │       ├── ProductMappingController.php
│   │       ├── SyncLogController.php
│   │       ├── ApiCredentialController.php
│   │       ├── TeamController.php             # Team member management
│   │       ├── SubscriptionController.php     # Tenant subscription management
│   │       ├── InvitationController.php       # User invitations
│   │       └── OnboardingController.php       # Onboarding wizard
│   ├── Jobs/                                  # Queue jobs
│   │   ├── ProcessCareemOrderJob.php          # Tenant-scoped job
│   │   ├── SyncToLoyverseJob.php              # Tenant-scoped job
│   │   └── RetryFailedSyncJob.php
│   ├── Services/                              # Business logic
│   │   ├── LoyverseApiService.php             # Loyverse API wrapper
│   │   ├── OrderTransformerService.php        # Order transformation
│   │   ├── ProductMappingService.php          # Product mapping logic
│   │   ├── TenantService.php                  # Tenant management
│   │   ├── SubscriptionService.php            # Subscription logic
│   │   ├── UsageTrackingService.php           # Usage tracking
│   │   └── TenantContext.php                  # Tenant context management
│   ├── Models/                                # Eloquent models
│   │   ├── Tenant.php
│   │   ├── Subscription.php
│   │   ├── SubscriptionPlan.php
│   │   ├── SubscriptionUsage.php
│   │   ├── Role.php
│   │   ├── Invitation.php
│   │   └── ... (existing models)
│   ├── Repositories/                          # Data access layer
│   │   ├── ApiCredentialRepository.php
│   │   └── TenantRepository.php
│   ├── Traits/
│   │   ├── HasTenant.php                      # Tenant scoping trait
│   │   └── HasRoles.php                       # Role management trait
│   ├── Scopes/
│   │   └── TenantScope.php                    # Global tenant scope
│   ├── Policies/                              # Authorization policies
│   │   ├── TenantPolicy.php
│   │   ├── OrderPolicy.php
│   │   ├── ProductMappingPolicy.php
│   │   └── UserPolicy.php
│   ├── Exceptions/
│   │   ├── LoyverseApiException.php
│   │   └── TenantNotFoundException.php
│   └── Http/Middleware/
│       ├── VerifyWebhookSignature.php         # Webhook security
│       ├── IdentifyTenant.php                 # Tenant detection
│       ├── EnsureSuperAdmin.php               # Super admin guard
│       ├── EnsureTenantAdmin.php              # Tenant admin guard
│       ├── CheckSubscriptionLimits.php        # Usage limit enforcement
│       └── EnsureOnboardingComplete.php       # Redirect to onboarding if needed
├── config/
│   ├── loyverse.php                           # Loyverse API configuration
│   ├── tenancy.php                            # Multi-tenancy configuration
│   └── subscription.php                       # Subscription plans configuration
├── database/
│   ├── migrations/                            # Database schema
│   │   ├── *_create_tenants_table.php
│   │   ├── *_create_subscription_plans_table.php
│   │   ├── *_create_subscriptions_table.php
│   │   ├── *_create_subscription_usage_table.php
│   │   ├── *_create_roles_table.php
│   │   ├── *_create_invitations_table.php
│   │   ├── *_add_tenant_id_to_all_tables.php
│   │   └── ... (existing migrations)
│   └── seeders/
│       ├── SubscriptionPlanSeeder.php         # Seed pricing plans
│       └── RoleSeeder.php                     # Seed roles
├── resources/views/
│   ├── landing/                               # Landing page views
│   │   ├── layout.blade.php
│   │   ├── index.blade.php                    # Homepage
│   │   ├── pricing.blade.php                  # Pricing page
│   │   ├── register.blade.php                 # Sign up
│   │   └── partials/
│   ├── super-admin/                           # Super admin views
│   │   ├── layout.blade.php
│   │   ├── dashboard.blade.php
│   │   ├── tenants/                           # Tenant management
│   │   ├── subscriptions/                     # Subscription management
│   │   └── system/                            # System monitoring
│   ├── dashboard/                             # Tenant dashboard views
│   │   ├── team/                              # Team management
│   │   ├── subscription/                      # Subscription management
│   │   ├── onboarding/                        # Onboarding wizard
│   │   └── ... (existing views)
│   └── emails/                                # Email templates
│       ├── invitation.blade.php
│       ├── welcome.blade.php
│       └── subscription/
└── routes/
    ├── api.php                                # API routes (webhooks)
    ├── landing.php                            # Landing page routes
    ├── super-admin.php                        # Super admin routes
    ├── tenant.php                             # Tenant dashboard routes
    └── web.php                                # Main web routes
```

## Critical Development Guidelines

### Working with the Queue System

**IMPORTANT:** This application heavily relies on queue jobs. When developing:

1. **Always run the queue worker** during development:
   ```bash
   php artisan queue:work database --verbose
   ```

2. **After modifying job classes**, restart queue workers:
   ```bash
   php artisan queue:restart
   ```

3. **Job Structure Best Practices:**
   - Jobs MUST be idempotent (safe to run multiple times)
   - Always inject dependencies in `handle()` method, not constructor
   - Set appropriate `$tries`, `$timeout`, and `$backoff` properties
   - Implement `failed()` method for cleanup

### Working with Multi-Tenancy

**CRITICAL:** All code must respect tenant boundaries. When developing:

1. **Always use tenant-scoped models**
   ```php
   // HasTenant trait is applied to all tenant models
   use App\Traits\HasTenant;

   class Order extends Model {
       use HasTenant;
   }
   ```

2. **Never query across tenants**
   ```php
   // ❌ WRONG - bypasses tenant scope
   Order::withoutGlobalScope(TenantScope::class)->get();

   // ✅ CORRECT - respects tenant scope
   Order::all(); // Automatically filtered by current tenant
   ```

3. **Always set tenant context in jobs**
   ```php
   class ProcessOrderJob {
       public function __construct(public Order $order) {}

       public function handle() {
           // Tenant context is automatically set from $order->tenant
           app(TenantContext::class)->set($this->order->tenant);
       }
   }
   ```

4. **Test for tenant isolation**
   - Never assume data belongs to current tenant
   - Always verify tenant_id in policies
   - Use feature tests to verify no cross-tenant access

5. **Use subdomain routing correctly**
   ```php
   // routes/tenant.php
   Route::domain('{tenant}.'.config('app.domain'))->group(function () {
       Route::middleware(['identify.tenant', 'auth'])->group(function () {
           // All tenant routes here
       });
   });
   ```

### Working with Roles & Permissions

**Authorization Pattern:**

1. **Use middleware for route protection**
   ```php
   // Require super admin
   Route::middleware(['auth', 'super.admin'])->group(function () {
       // Super admin routes
   });

   // Require tenant admin
   Route::middleware(['auth', 'identify.tenant', 'tenant.admin'])->group(function () {
       // Tenant admin routes
   });
   ```

2. **Use policies for model authorization**
   ```php
   // OrderPolicy
   public function view(User $user, Order $order) {
       // Ensure user belongs to same tenant as order
       return $user->tenant_id === $order->tenant_id;
   }

   // In controller
   $this->authorize('view', $order);
   ```

3. **Check roles in code when needed**
   ```php
   if ($user->hasRole('super_admin')) {
       // Super admin specific logic
   }

   if ($user->hasRole('tenant_admin', $tenant)) {
       // Tenant admin specific logic
   }
   ```

### Working with Subscriptions

**IMPORTANT:** Always check subscription status and limits

1. **Check subscription is active**
   ```php
   if (!tenant()->subscription->isActive()) {
       return redirect()->route('subscription.expired');
   }
   ```

2. **Check usage limits before processing**
   ```php
   // In middleware or controller
   $subscription = tenant()->subscription;
   $usage = app(UsageTrackingService::class)->getCurrentUsage(tenant());

   if ($usage >= $subscription->plan->order_limit) {
       return response()->json(['error' => 'Order limit reached'], 429);
   }
   ```

3. **Track usage after successful operations**
   ```php
   // After order is processed successfully
   app(UsageTrackingService::class)->recordOrder(tenant());
   ```

4. **Handle trial periods**
   ```php
   if (tenant()->subscription->onTrial()) {
       // Show trial banner
       // Display days remaining
   }
   ```

### Service Layer Patterns

**LoyverseApiService:**
- Uses HTTP client with automatic retry for 429/503 errors
- Returns typed responses or throws `LoyverseApiException`
- All resource fetching is cached
- Test connection available via `testConnection()` method

**OrderTransformerService:**
- Validates Careem order structure via `validateCareemOrder()`
- Transforms to Loyverse receipt format
- Handles missing product mappings gracefully
- Logs transformation summary for debugging
- Throws exception only if NO products can be mapped

**ProductMappingService:**
- Primary method: `mapOrderItems(array $items, int $orderId): array`
- Returns `['mapped' => [...], 'unmapped' => [...]]`
- Auto-logs missing mappings to sync_logs
- Cache management via `clearAllCache()`

### Database Conventions

**Status Fields:**
- Orders: `pending`, `processing`, `synced`, `failed`
- Sync Logs: `success`, `error`, `warning`, `info`
- Webhook Logs: `received`, `processed`, `failed`

**JSON Fields:** Use casting in models
```php
protected $casts = [
    'order_data' => 'array',
    'metadata' => 'array',
    'credentials' => 'encrypted:array',  // ApiCredential only
];
```

**Indexes:** All foreign keys and frequently queried columns are indexed

### Security Considerations

1. **Webhook Signature Verification:**
   - Required for `/api/webhook/careem` endpoint
   - Implemented in `VerifyWebhookSignature` middleware
   - Secret stored encrypted in `api_credentials` table

2. **API Credentials:**
   - NEVER store in `.env` for production
   - Use `api_credentials` table with encryption
   - Access via `ApiCredentialRepository`

3. **Environment Variables:**
   - Use `.env` for configuration, not secrets
   - Sensitive data goes in encrypted `api_credentials` table

### Frontend Development (Tailwind + Alpine.js)

**Component Pattern:**
```blade
<div x-data="{ open: false }" class="bg-white rounded-lg shadow">
    <button @click="open = !open" class="px-4 py-2">
        Toggle
    </button>
    <div x-show="open" class="p-4">
        Content
    </div>
</div>
```

**After modifying Tailwind/CSS:**
```bash
npm run build  # Rebuild assets
```

**Vite Asset References:**
```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

## API Integration Details

### Careem Webhook Expected Format

```json
{
  "order_id": "CAREEM-2025-10-17-12345",
  "items": [{
    "product_id": "PROD-123",
    "name": "Product Name",
    "sku": "SKU-123",
    "quantity": 2,
    "unit_price": 45.00
  }],
  "pricing": {
    "subtotal": 90.00,
    "tax": 4.50,
    "total": 94.50
  }
}
```

**Validation:** Handled by `CareemOrderRequest`
**Endpoint:** POST `/api/webhook/careem`
**Headers:** `X-Careem-Signature` for verification

### Loyverse API Integration

**Base URL:** `https://api.loyverse.com/v1`
**Authentication:** Bearer token (stored in `api_credentials`)
**Rate Limit:** 60 req/min (we use 55 to leave buffer)

**Key Endpoints Used:**
- POST `/receipts` - Create order/receipt
- GET `/items` - Fetch product catalog
- GET `/customers` - Search customers
- POST `/customers` - Create "Careem" customer
- GET `/stores`, `/employees`, `/payment_types` - Reference data

**All API calls go through `LoyverseApiService`** - never call directly from controllers or jobs.

## Configuration

**Important Config Files:**
- `.env` - Environment-specific settings
- `config/loyverse.php` - Loyverse API configuration (rate limits, cache TTLs, defaults)
- `config/queue.php` - Queue configuration (database driver)
- `config/cache.php` - Cache configuration (database driver)

**Loyverse Config Structure:**
```php
return [
    'api_url' => env('LOYVERSE_API_URL', 'https://api.loyverse.com/v1'),
    'rate_limit' => ['per_minute' => 55],
    'retry' => ['max_attempts' => 5, 'delays' => [60, 300, 900, 1800, 3600]],
    'cache_ttl' => ['items' => 3600, 'stores' => 86400],
    'defaults' => ['receipt_type' => 'SALE', 'dining_option' => 'DELIVERY'],
];
```

## Debugging Tips

### Common Issues

**Issue: Orders not processing**
```bash
# Check queue worker is running
php artisan queue:work database --verbose

# Check for failed jobs
php artisan queue:failed

# Review sync logs in dashboard or database
```

**Issue: Webhook signature verification fails**
```bash
# Check webhook secret in api_credentials table
php artisan tinker
>>> ApiCredential::where('service', 'careem')->first();

# Review webhook_logs table for raw payloads
```

**Issue: Loyverse API errors**
```bash
# Test connection from admin dashboard
# Or via tinker:
php artisan tinker
>>> app(LoyverseApiService::class)->testConnection();
```

**Issue: Missing product mappings**
```bash
# Check product_mappings table
php artisan tinker
>>> ProductMapping::where('is_active', true)->count();

# Review sync_logs for unmapped products
>>> SyncLog::where('action', 'product_mapping')->get();
```

### Logging

**Log Channels:**
- `storage/logs/laravel.log` - Application logs
- Database: `webhook_logs`, `sync_logs` tables

**Enable Debug Logging (development only):**
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

## Deployment Notes

**Production Checklist:**
1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Use MySQL instead of SQLite
3. Configure Supervisor for queue workers (see `queue-worker.conf`)
4. Set up Redis for better queue/cache performance (optional)
5. Configure SSL certificate
6. Set up database backups
7. Monitor queue depth and failed jobs

**Queue Worker in Production:**
- Use Supervisor to manage queue workers
- Configuration provided in `queue-worker.conf`
- Minimum 2 workers recommended for redundancy

**See `Deployment.md` for complete deployment guide.**

## Testing

**Test Structure:**
- Unit tests: `tests/Unit/`
- Feature tests: `tests/Feature/`

**Running Tests:**
```bash
composer test
php artisan test --parallel  # Parallel execution
```

**Key Areas to Test:**
- Webhook validation and signature verification
- Order transformation logic
- Product mapping with various scenarios
- Queue job retry logic
- API error handling

## Project Documentation

**Read these files for complete context:**

**SaaS Architecture & Planning:**
1. `SAAS-ARCHITECTURE.md` - Complete multi-tenancy architecture, subscription system, security
2. `SAAS-TASKS.md` - Phase-by-phase implementation breakdown with detailed tasks
3. `Context.md` - Original project overview, business requirements, architecture

**Development Guidelines:**
4. `CLAUDE.md` (this file) - SaaS-specific development guidelines
5. `instruction.md` - Detailed coding standards and guidelines (MUST READ for development)
6. `API-Integration.md` - Complete API documentation for Careem and Loyverse

**Deployment & Setup:**
7. `Deployment.md` - Production deployment guide (being updated for SaaS)
8. `SETUP.md` - Local development setup

**Project History:**
9. `changelog.md` - Complete implementation history
10. `Tasks.md` - Original task breakdown (pre-SaaS)

**Key Files to Review Before Making Changes:**
- **SAAS-ARCHITECTURE.md** - Understand multi-tenancy strategy before any SaaS feature
- **SAAS-TASKS.md** - Check task breakdown for implementation order and details
- Always check `changelog.md` to see what's been implemented
- Review `instruction.md` for coding standards
- Check existing implementations for patterns

## Important Notes

### Integration-Specific Notes

1. **All Careem orders → Single "Careem" customer in Loyverse (Per Tenant)**
   - This is a business requirement, not a technical limitation
   - Customer is auto-created if missing for each tenant
   - Each tenant has their own "Careem" customer in their Loyverse account

2. **Payment Method**: All Careem orders use "Careem" payment type
   - System looks for a payment type named "Careem" in Loyverse
   - Falls back to default if "Careem" payment type doesn't exist
   - **Recommended**: Create "Careem" payment type in Loyverse Back Office

3. **Product mappings are required per tenant**
   - Orders with unmapped products will still sync with mapped items
   - Unmapped products are logged for admin review
   - Order fails only if NO products can be mapped
   - **New**: Loyverse items are searchable in mapping interface
   - Each tenant maintains their own product mappings

4. **Queue-based architecture is essential**
   - Webhook responds quickly (< 5 seconds)
   - Heavy processing happens asynchronously
   - Automatic retry with exponential backoff
   - Jobs are tenant-scoped for isolation

5. **Rate limiting is critical**
   - Loyverse API has strict 60 req/min limit per tenant
   - We use 55 to leave buffer for safety
   - Rate limit errors automatically retry with delay
   - Rate limits tracked per tenant

### SaaS-Specific Notes

6. **Multi-tenancy is foundational**
   - All tenant data is isolated by `tenant_id`
   - Never query across tenants without explicit super admin context
   - Global scopes automatically enforce tenant boundaries
   - Test tenant isolation thoroughly

7. **Subdomain routing is required**
   - Each tenant has their own subdomain: `{tenant}.yourapp.com`
   - Super admin panel: `admin.yourapp.com`
   - Landing page: `www.yourapp.com` or `yourapp.com`
   - Configure wildcard DNS and SSL certificate

8. **Three user roles exist**
   - **Super Admin**: Platform-wide access, no tenant association
   - **Tenant Admin**: Full access within their tenant
   - **Tenant User**: Read-only access within their tenant

9. **Subscription management is automatic**
   - Stripe webhooks keep subscriptions in sync
   - Usage is tracked automatically per tenant
   - Limits are enforced before processing orders
   - Email notifications for limit warnings

10. **Onboarding flow is required for new tenants**
    - Redirect to onboarding wizard if not completed
    - Steps: Connect Loyverse, configure webhook, test integration
    - Can be skipped partially for faster setup

11. **Authentication varies by domain**
    - Landing page routes are public
    - Super admin routes require `super_admin` role
    - Tenant routes require authentication + tenant context
    - Webhook endpoints are authenticated via signature (per tenant)

12. **Webhooks are tenant-specific**
    - Each tenant has unique webhook URL with tenant identifier
    - Webhook secrets stored per tenant (encrypted)
    - Example: `/api/webhook/careem/{tenant_id}/{signature}`

13. **API credentials are per tenant**
    - Each tenant provides their own Loyverse API token
    - Each tenant has unique Careem webhook secret
    - All credentials stored encrypted
    - Never share credentials across tenants

14. **Landing page is marketing-focused**
    - Clean, modern design with Tailwind CSS
    - Smooth animations with GSAP or AOS
    - Clear pricing and feature comparison
    - Strong CTAs for sign up
    - Mobile-first responsive design

15. **Update changelog.md after completing features**
    - Required by project guidelines (see `instruction.md`)
    - Documents all changes for future reference

## External Resources

- Laravel Documentation: https://laravel.com/docs/12.x
- Loyverse API: https://developer.loyverse.com/docs/
- Tailwind CSS: https://tailwindcss.com/docs
- Alpine.js: https://alpinejs.dev/

---

**Project Status:** Transforming to Multi-Tenant SaaS Platform

**Current Phase:** Planning & Architecture Complete
**Next Phase:** Phase 1 - Multi-Tenancy Foundation (Database & Core System)

**Original Integration:** Feature-complete and production-ready (single-tenant)
**SaaS Transformation:** In Progress - 8-week timeline (see SAAS-TASKS.md)
- always check the context befoer exexuting any prompt and if the context is not enough run /Compact
- add the app status to the memeory file