# Development Instructions - Careem Now to Loyverse POS Multi-Tenant SaaS Platform

## 🎯 Project Overview

This is a **multi-tenant SaaS platform** that enables restaurants to automatically synchronize food orders from Careem Now (via webhooks) to their Loyverse POS system. The platform supports multiple tenants (restaurants/businesses) with independent data, users, API credentials, and subscription management.

**Current Status**: 80% Complete (Phases 1-5 Done, Phase 6 Partial, Phase 7-8 Pending)

**Technology Stack:**
- Laravel 12.33 with PHP 8.2+
- Multi-tenancy with subdomain routing
- Stripe subscriptions via Laravel Cashier
- Queue-based order processing
- TailwindCSS + Alpine.js frontend
- MySQL database with Redis for production

---

## 📋 Before You Develop - CRITICAL PRE-CHECKLIST

### 1. ALWAYSCheck These Files FIRST

Before implementing ANY feature, **ALWAYS** read in this order:
1. **`SAAS-TASKS.md`** - Check current implementation status and what phase you're working on
2. **`CLAUDE.md`** - Understand multi-tenancy patterns and coding guidelines  
3. **`changelog.md`** - See what's already implemented to avoid duplicate work
4. **`Context.md`** - Understand business requirements and architecture
5. **`instruction.md`** - Follow established coding standards

### 2. Understand Current Project State

**Current Phase Status** (from changelog.md):
- ✅ Phase 1 - Multi-Tenancy Foundation (100% Complete)
- ✅ Phase 2 - Authentication & Authorization (100% Complete) 
- ✅ Phase 3 - Subscriptions & Billing (99% Complete - needs Cashier install)
- ✅ Phase 4 - Landing Page & Marketing (100% Complete)
- ✅ Phase 5 - Super Admin Panel (95% Complete)
- 🚧 Phase 6 - Tenant Dashboard Enhancements (80% Complete)
- ⏸️ Phase 7 - Testing & Security (10% Complete)
- ⏸️ Phase 8 - Deployment (0% Complete)

**Critical Missing Components:**
- Phase 6 completion (team management UI polish)
- Phase 7 comprehensive testing
- Phase 8 production deployment setup

### 3. Multi-Tenancy Is NON-NEGOTIABLE

**❌ NEVER Do These:**
```php
// WRONG - Bypasses tenant isolation
Order::withoutGlobalScope(TenantScope::class)->get();

// WRONG - Hardcoded tenant context
$tenant = Tenant::find(1);
```

**✅ ALWAYS Do These:**
```php
// CORRECT - Respects current tenant context
$orders = Order::all(); // Automatically filtered by tenant()

// CORRECT - Jobs maintain tenant context
class ProcessOrderJob {
    public function handle() {
        app(TenantContext::class)->set($this->order->tenant);
    }
}
```

---

## 🏗️ Multi-Tenancy Architecture - MANDATORY PATTERNS

### Tenant Detection & Context

**Domain Structure:**
- Landing: `www.yourapp.com` or `yourapp.com`
- Super Admin: `admin.yourapp.com` 
- Tenants: `{subdomain}.yourapp.com`

**Middleware Stack:**
```php
// routes/tenant.php - All tenant routes MUST use this
Route::domain('{subdomain}.' . config('app.domain'))
    ->middleware(['web', 'identify.tenant', 'auth'])
    ->group(function () {
        // Tenant routes here
    });
```

**Tenant Context Service:**
```php
// Get current tenant (never null in tenant context)
$tenant = tenant();
$tenantId = tenant()->id;

// Check if we have tenant context
if (!tenant()) {
    // Handle appropriately
}
```

### Model Patterns - HAS TENANT TRAIT

**ALL tenant-scoped models MUST use HasTenant trait:**
```php
class Order extends Model {
    use HasTenant; // Automatically adds tenant_id and global scope
    
    protected $fillable = [
        // NEVER include tenant_id here - handled by trait
        'careem_order_id',
        'order_data',
        'status',
    ];
}
```

**Controller Method Signatures - SUBDOMAIN PARAMETER:**
```php
// ALL tenant controller methods with model binding MUST include subdomain
public function edit(string $subdomain, Menu $menu)     // ✅ CORRECT
public function update(string $subdomain, MenuItem $item) // ✅ CORRECT
public function show(string $subdomain, Location $loc)   // ✅ CORRECT

// WRONG - Missing subdomain parameter
public function edit(Menu $menu)                        // ❌ INCORRECT  
```

**Route Redirects - INCLUDE SUBDOMAIN:**
```php
return redirect()->route('dashboard.menus.index', [
    'subdomain' => request()->route('subdomain')
]); // ✅ CORRECT

return redirect()->route('dashboard.menus.index'); // ❌ INCORRECT - Missing subdomain
```

### Database Queries

**Always Filter by Tenant:**
```php
// ✅ CORRECT - Uses tenant scope automatically
$orders = Order::where('status', 'pending')->get();

// ✅ CORRECT - Explicit tenant filtering when needed
$orderCount = Order::where('tenant_id', tenant()->id)->count();
```

---

## 🔐 Authentication & Authorization

### Three Role System

**Role Hierarchy:**
1. **Super Admin**: Platform-wide access, no tenant association
2. **Tenant Admin**: Full access within their tenant  
3. **Tenant User**: Read-only access within their tenant

**Authorization Patterns:**
```php
// Middleware for route protection
Route::middleware(['auth', 'super.admin'])->group(function () {
    // Super admin only routes
});

Route::middleware(['auth', 'identify.tenant', 'tenant.admin'])->group(function () {
    // Tenant admin only routes  
});

// Policy-based authorization
$this->authorize('view', $order); // Ensures user belongs to same tenant
```

### User Invitations

**Invitation Flow:**
```php
// Send invitation
$invitation = Invitation::create([
    'tenant_id' => tenant()->id,
    'email' => $email,
    'role_id' => $roleId->id,
    'token' => Str::random(64),
    'expires_at' => now()->addDays(7),
]);

// Accept invitation (public route)
Route::get('/invitations/{token}', [InvitationController::class, 'accept']);
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);
```

---

## 💳 Subscription System

### Current Status (99% Complete)

**Missing Components:**
- Laravel Cashier installation: `composer require laravel/cashier`
- Stripe configuration in `.env`

**Subscription Workflow:**
```php
// Check if tenant can process orders
if (!tenant()->subscription->isActive()) {
    return redirect()->route('subscription.expired');
}

// Check usage limits
if (app(UsageTrackingService::class)->getCurrentUsage(tenant()) >= tenant()->subscription->plan->order_limit) {
    return response()->json(['error' => 'Order limit reached'], 429);
}

// Track usage after successful order
app(UsageTrackingService::class)->recordOrder(tenant());
```

**Subscription Plans:**
- **Starter**: $29/month - 500 orders, 1 location, 1 user
- **Business**: $79/month - 2,000 orders, 3 locations, 5 users  
- **Enterprise**: $199/month - Unlimited

---

## 🔄 Order Processing Pipeline

### Webhook to Queue to Loyverse

```
Careem Webhook → WebhookController → ProcessCareemOrderJob → SyncToLoyverseJob → Loyverse API
```

**Critical Implementation Rules:**

**1. ALWAYS Run Queue Worker During Development:**
```bash
php artisan queue:work database --verbose --tries=3
```

**2. Jobs MUST Be Idempotent:**
```php
class SyncToLoyverseJob {
    public $tries = 5;
    public $backoff = [60, 300, 900, 1800, 3600]; // Exponential backoff
    
    public function handle() {
        // Set tenant context BEFORE any operations
        app(TenantContext::class)->set($this->order->tenant);
        
        // Process order - safe to run multiple times
    }
}
```

**3. Rate Limiting (Loyverse API: 55 req/min):**
```php
// Already implemented in LoyverseApiService
RateLimiter::attempt('loyverse-api:'.$tenant->id, 55, function () {
    // API call here
}, 60);
```

**4. Product Mapping Strategy:**
- All orders use single "Careem" customer per tenant
- Manual product mappings required (SKU → Loyverse Item ID)
- Orders with unmapped products still sync mapped items
- Order fails only if NO products can be mapped

---

## 🎨 Frontend Development (Tailwind + Alpine.js)

### Multi-Tenant Layouts

**Layout Hierarchy:**
```
landing/
├── layout.blade.php        # Landing page layout (public)
├── index.blade.php         # Homepage
├── pricing.blade.php       # Pricing page
└── register.blade.php      # Registration

super-admin/
├── layout.blade.php        # Super admin layout
└── dashboard.blade.php     # Admin dashboard

dashboard/
├── layout.blade.php        # Tenant dashboard layout
├── menus/
├── locations/
├── modifiers/
└── subscription/
```

### Route Generation - SUBDOMAIN Critical

**✅ CORRECT Patterns:**
```blade
<!-- Include subdomain in ALL route calls -->
<a href="{{ route('dashboard.menus.edit', ['menu' => $menu, 'subdomain' => request()->route('subdomain')]) }}">
    Edit Menu
</a>

<form action="{{ route('dashboard.locations.update', ['location' => $location, 'subdomain' => request()->route('subdomain')]) }}" method="POST">
```

**❌ WRONG Patterns:**
```blade
<!-- Missing subdomain parameter -->
<a href="{{ route('dashboard.menus.edit', $menu) }}">Edit Menu</a>

<!-- Will fail with "Missing parameter: subdomain" -->
```

### Alpine.js Component Patterns

```html
<div x-data="{ 
    open: false,
    busy: @entangle('location.is_busy'),
    toggle() {
        this.busy = !this.busy;
        this.$wire.patch('{{ route("dashboard.locations.toggle-busy", ["location" => $location->id, "subdomain" => request()->route("subdomain")]) }}');
    }
}">
    <button @click="toggle()" :class="{ 'bg-red-500': busy, 'bg-green-500': !busy }">
        <span x-text="busy ? 'Mark Available' : 'Mark Busy'"></span>
    </button>
</div>
```

---

## 📁 File Structure & Patterns

### Controllers Directory

```
app/Http/Controllers/
├── Api/
│   └── WebhookController.php          # Tenant webhook endpoints
├── Landing/                           # Public site controllers
│   ├── LandingController.php
│   ├── RegistrationController.php
│   └── PricingController.php
├── SuperAdmin/                        # Admin panel controllers
│   ├── DashboardController.php
│   ├── TenantController.php
│   ├── SubscriptionController.php
│   └── SystemController.php
├── Dashboard/                         # Tenant dashboard controllers
│   ├── MenuController.php
│   ├── LocationController.php
│   ├── SubscriptionController.php
│   ├── TeamController.php
│   └── InvitationController.php
└── Auth/                              # Laravel Breeze auth
```

### Models Directory

```
app/Models/
├── Tenant.php                         # Core tenant model
├── Subscription.php                    # Tenant subscription
├── SubscriptionPlan.php                # Available plans
├── SubscriptionUsage.php               # Monthly usage tracking
├── Role.php                           # User roles
├── Invitation.php                     # User invitations
├── User.php                           # Laravel user (enhanced)
├── Order.php                          # Careem orders (tenant-scoped)
├── Menu.php                           # Tenant menus
├── MenuItem.php                        # Menu items
├── Location.php                       # Restaurant locations
├── Modifier.php                       # Menu modifiers
├── ModifierGroup.php                  # Modifier groups
└── ...existing models...
```

### Services Directory

```
app/Services/
├── TenantContext.php                  # Tenant context management
├── SubscriptionService.php            # Stripe subscription handling
├── UsageTrackingService.php           # Order usage tracking
├── LoyverseApiService.php             # Loyverse API wrapper
├── OrderTransformerService.php        # Careem → Loyverse mapping
└── ProductMappingService.php          # SKU → Item mapping
```

---

## 🔒 Security - NON-NEGOTIABLE REQUIREMENTS

### Tenant Isolation

**Test for Cross-Tenant Access:**
```php
// In tests - ALWAYS verify no cross-tenant data leakage
public function test_tenant_cannot_access_other_tenant_data() {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $order1 = Order::factory()->create(['tenant_id' => $tenant1->id]);
    $order2 = Order::factory()->create(['tenant_id' => $tenant2->id]);
    
    // Acting as tenant1 user
    $this->actingAs($tenant1Admin)
         ->get('/dashboard/orders')
         ->assertSee($order1->careem_order_id)
         ->assertDontSee($order2->careem_order_id);
}
```

### API Credentials Management

**NEVER store credentials in .env for production:**
```php
// ✅ CORRECT - Use encrypted database storage
$credential = ApiCredential::create([
    'tenant_id' => tenant()->id,
    'service' => 'loyverse',
    'credentials' => Crypt::encryptString($apiKey),
    'is_active' => true,
]);

// ❌ WRONG - Never commit secrets to code
// LOYVERSE_API_KEY=sk_live_... in .env or git
```

### Webhook Security

**Signature Verification:**
```php
// Verify webhook signature per tenant
$secret = ApiCredential::where('service', 'careem')
                     ->where('tenant_id', tenant()->id)
                     ->first()
                     ->getSecret(); // Decrypted

$signature = hash_hmac('sha256', $payload, $secret);
```

---

## ⚡ Performance Requirements

### Database Optimization

**Critical Indexes:**
```sql
-- Multi-tenant indexes (on ALL tenant tables)
CREATE INDEX idx_orders_tenant_status ON orders(tenant_id, status);
CREATE INDEX idx_orders_tenant_created ON orders(tenant_id, created_at);
CREATE INDEX idx_menu_items_tenant_menu ON menu_items(tenant_id, menu_id);
CREATE INDEX idx_subscription_usage_tenant_month ON subscription_usage(tenant_id, month, year);
```

**Eager Loading Prevention:**
```php
// ✅ CORRECT - Prevent N+1 queries
$menus = Menu::with(['items', 'locations', 'modifierGroups'])->get();

// ❌ WRONG - Causes N+1 queries  
$menus = Menu::all();
foreach ($menus as $menu) {
    foreach ($menu->items as $item) { // N+1 query for each menu
        // Process item
    }
}
```

### Caching Strategy

**Per-Tenant Cache Keys:**
```php
// Cache keys MUST include tenant_id
$cacheKey = "loyverse_items:{$tenant->id}";

// TTL recommendations
Cache::put($cacheKey, $items, 3600); // 1 hour for items
Cache::put("stores:{$tenant->id}", $stores, 86400); // 24 hours for stores
```

---

## 🧪 Testing Requirements

### Multi-Tenant Testing

**Isolate Test Tenants:**
```php
public function test_tenant_workflow() {
    // Create fresh tenant for each test
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    
    // Set tenant context
    app(TenantContext::class)->set($tenant);
    
    // TestTenantContext middleware resets context between tests
}
```

### Critical Test Scenarios

**Must Test These Scenarios:**

1. **Tenant Isolation**: Users cannot access other tenants' data
2. **Subscription Limits**: Enforce plan limits correctly  
3. **Order Processing**: End-to-end webhook to Loyverse sync
4. **Role Authorization**: Proper access control for all roles
5. **Queue Failures**: Retry logic and error handling

---

## 🚀 Deployment Checklist

### Production Environment Setup

**Required Environment Variables:**
```env
# App Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourapp.com

# Domain Configuration  
APP_DOMAIN=yourapp.com
ADMIN_SUBDOMAIN=admin

# Database
DB_CONNECTION=mysql
# ... MySQL settings

# Stripe (Critical!)
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Email
MAIL_MAILER=ses
# ... Email settings

# Cache/Queue
REDIS_HOST=...
QUEUE_CONNECTION=redis

# Session
SESSION_DOMAIN=.yourapp.com
```

### Production Commands

**Deploy Sequence:**
```bash
# 1. Install dependencies
composer install --no-dev --optimize-autoloader
npm run build

# 2. Run migrations
php artisan migrate --force

# 3. Clear all caches
php artisan cache:clear
php artisan config:clear  
php artisan route:clear
php artisan view:clear

# 4. Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 5. Restart queue workers
php artisan queue:restart

# 6. Start queue workers via Supervisor
# See queue-worker.conf
```

---

## 📝 Changelog.md - MANDATORY UPDATES

**CRITICAL**: After completing ANY task, you MUST update changelog.md

**Changelog Format:**
```markdown
## [Date: YYYY-MM-DD]

### Added/Changed/Fixed - Feature/Task Name
- Brief description of what was done
  - Files: List of files created/modified  
  - Details: Important implementation notes
  - Status: Complete/Partial/Progress

### Status Updates
- Phase X: 100% Complete (was 95%)
- Ready for: Next Phase
```

**When to Update:**
- ✅ After ANY successful feature implementation
- ✅ After fixing bugs
- ✅ After refactoring code
- ✅ After adding tests
- ✅ After major configuration changes

**Examples of Good Entries:**
See changelog.md for proper format examples

---

## 🚨 Common Pitfalls & Anti-Patterns

### ❌ NEVER Do These

**1. Bypass Tenant Isolation:**
```php
Order::withoutGlobalScope(TenantScope::class)->get(); // NEVER!
```

**2. Hardcode Subdomain:**
```php
return redirect('https://demo.yourapp.com/dashboard'); // NEVER!
```

**3. Forget Subdomain in Routes:**
```php
route('dashboard.menus.edit', $menu) // MISSING SUBDOMAIN!
```

**4. Store Secrets in Code:**
```php
define('LOYVERSE_KEY', 'sk_live_...'); // NEVER!
```

**5. Skip Error Handling:**
```php
// No try-catch in critical paths
$result = $api->call(); // Can fail silently
```

### ✅ ALWAYS Do These

**1. Respect Tenant Boundaries:**
```php
$appContext = tenant();
$orders = Order::all(); // Auto-filtered by tenant
```

**2. Use Route Parameters Correctly:**
```php
public function edit(string $subdomain, Menu $menu) { /* ... */ }
route('dashboard.menus.edit', ['menu' => $menu, 'subdomain' => request()->route('subdomain')])
```

**3. Handle All Error Scenarios:**
```php
try {
    $result = $loyverseApi->createReceipt($data);
    $this->logSuccess($order, $result);
} catch (LoyverseApiException $e) {
    $this->logFailure($order, $e);
    if ($e->isRateLimit()) {
        $this->release(60); // Retry after 1 minute
        return;
    }
    throw $e;
}
```

**4. Update Changelog:**
```markdown
## [Date: 2025-10-21]
### Fixed - Subdomain route parameter issue
- Added subdomain parameter to all controller methods
- Files: MenuController.php, LocationController.php, etc.
```

---

## 🎯 Immediate Next Steps for Development

### If Working on Phase 6 (Tenant Dashboard Polish)

**Priority Tasks:**
1. Complete team member management UI (edit roles, remove users)
2. Add user activity logging
3. Polish notification settings UI

### If Working on Phase 7 (Testing & Security)

**Critical Requirements:**
1. Write comprehensive unit tests for multi-tenancy
2. Perform security audit (XSS, SQL injection, CSRF)
3. Do load testing with multiple concurrent tenants
4. Test all subscription limit edge cases

### If Working on Phase 8 (Deployment)

**Production Readiness:**
1. Install Laravel Cashier: `composer require laravel/cashier`
2. Configure Stripe production keys
3. Set up wildcard SSL certificate
4. Configure Redis for cache/queue
5. Set up Supervisor for queue workers
6. Configure monitoring and alerting

---

## 📚 Quick Reference Commands

### Development Commands
```bash
# Start all services (concurrently)
composer dev

# Individual services
php artisan serve                    # Web server
php artisan queue:work database --verbose  # Queue worker
php artisan pail                      # Real-time logs
npm run dev                          # Vite dev server

# Testing
composer test                         # Run tests
php artisan test --parallel           # Parallel testing
```

### Database Commands  
```bash
php artisan migrate:fresh --seed     # Fresh start with seeders
php artisan tinker                     # Debug queries
```

### Cache Commands
```bash
php artisan cache:clear              # Clear all caches
php artisan config:cache             # Optimize production
```

### Queue Commands
```bash
php artisan queue:failed             # View failed jobs
php artisan queue:retry all          # Retry all failed jobs
php artisan queue:restart            # Restart workers
```

---

## 🤝 Getting Help & Resources

### Before Starting Work
1. **Check Phase Status** in SAAS-TASKS.md
2. **Read changelog.md** for recent implementations  
3. **Review CLAUDE.md** for established patterns

### When Stuck
1. Review similar implementations in existing code
2. Check Laravel documentation for standard patterns
3. Review SAAS-ARCHITECTURE.md for architecture decisions

### Key Documentation Files
- `SAAS-TASKS.md` - Current task status and breakdown
- `CLAUDE.md` - Multi-tenancy coding guidelines  
- `changelog.md` - Implementation history
- `Context.md` - Business requirements
- `SAAS-ARCHITECTURE.md` - Technical architecture

---

## 🎉 Final Reminders

**Remember This Is A SaaS Platform:**
- Every feature must be tenant-isolated
- Every route must respect subdomain routing
- Every query must be scoped to current tenant
- Every user action must be authorized

**Quality Requirements:**
- Update changelog.md after EVERY successful task
- Write tests for critical functionality
- Handle all error scenarios gracefully
- Document complex business logic

**Security Is Paramount:**
- Never bypass authorization
- Never store secrets in code  
- Never expose one tenant's data to another
- Always validate all inputs

**The Platform Is 80% Complete - Focus On:**
1. Finishing Phase 6 (dashboard polish)
2. Comprehensive testing (Phase 7) 
3. Production deployment (Phase 8)

**Good luck! Follow these patterns, respect the architecture, and keep that changelog updated!** 🚀
