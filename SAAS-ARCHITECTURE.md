# SaaS Architecture Documentation

## Overview

Transform the Careem-Loyverse integration from a single-tenant application into a **multi-tenant SaaS platform** where multiple restaurants/businesses can subscribe and use the integration service independently.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Multi-Tenancy Strategy](#multi-tenancy-strategy)
3. [User Roles & Permissions](#user-roles--permissions)
4. [Subscription System](#subscription-system)
5. [Landing Page & Marketing](#landing-page--marketing)
6. [Technical Implementation](#technical-implementation)
7. [Security & Isolation](#security--isolation)
8. [Scalability Considerations](#scalability-considerations)

---

## Architecture Overview

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Landing Page                              │
│  (www.yourapp.com) - Marketing, Pricing, Sign Up                │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Super Admin Portal                            │
│  (admin.yourapp.com) - Manage Tenants, Subscriptions, Billing   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Tenant Dashboards                           │
│  ({tenant}.yourapp.com) - Individual Restaurant Management       │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              Integration Engine (Queue System)                   │
│  Careem Webhooks → Transform → Loyverse API (Per Tenant)        │
└─────────────────────────────────────────────────────────────────┘
```

### Key Components

1. **Public Landing Site** - Marketing, features, pricing, sign up
2. **Super Admin Panel** - Platform-wide management
3. **Tenant Dashboards** - Individual business management
4. **Subscription Engine** - Billing, plans, usage tracking
5. **Multi-Tenant Queue System** - Isolated job processing per tenant
6. **Tenant Isolation Layer** - Data segregation and security

---

## Multi-Tenancy Strategy

### Database Architecture: Hybrid Approach

We'll use a **hybrid multi-tenancy model**:

```
┌──────────────────────────────────────────────┐
│         Central Database (MySQL)             │
├──────────────────────────────────────────────┤
│ • tenants (tenant metadata)                  │
│ • subscriptions (billing info)               │
│ • subscription_plans (pricing tiers)         │
│ • users (all users across tenants)           │
│ • super_admins                               │
│ • system_logs                                │
└──────────────────────────────────────────────┘
                    │
        ┌───────────┴───────────┐
        ▼                       ▼
┌───────────────┐       ┌───────────────┐
│  Tenant Data  │       │  Tenant Data  │
│  (Per Tenant) │       │  (Per Tenant) │
├───────────────┤       ├───────────────┤
│ • orders      │       │ • orders      │
│ • loyverse_   │       │ • loyverse_   │
│   orders      │       │   orders      │
│ • product_    │       │ • product_    │
│   mappings    │       │   mappings    │
│ • api_        │       │ • api_        │
│   credentials │       │   credentials │
│ • webhook_    │       │ • webhook_    │
│   logs        │       │   logs        │
│ • sync_logs   │       │ • sync_logs   │
└───────────────┘       └───────────────┘
  Tenant 1                 Tenant 2
```

**Why Hybrid?**
- **Central Database**: Shared tables for tenants, users, subscriptions (smaller tables)
- **Tenant Scoping**: Use `tenant_id` foreign key with strict scoping
- **Data Isolation**: Row-level security with automatic tenant filtering
- **Scalability**: Can migrate to separate databases per tenant later if needed

### Tenant Identification

**Subdomain-based routing:**
```
https://acme-restaurant.yourapp.com  → Tenant: acme-restaurant
https://pizza-hub.yourapp.com        → Tenant: pizza-hub
https://admin.yourapp.com            → Super Admin Portal
https://www.yourapp.com              → Landing Page
```

**Implementation:**
- Use Laravel's subdomain routing
- Middleware to detect and set current tenant
- Tenant context binding throughout request lifecycle

---

## User Roles & Permissions

### Role Hierarchy

```
┌──────────────────────────────────────────────┐
│              Super Admin                     │
│  • Full platform access                      │
│  • Manage all tenants                        │
│  • View all subscriptions & billing          │
│  • System configuration                      │
│  • Analytics across all tenants              │
└──────────────────────────────────────────────┘
                    │
        ┌───────────┴───────────┐
        ▼                       ▼
┌───────────────┐       ┌───────────────┐
│ Tenant Admin  │       │ Tenant Admin  │
│ • Manage own  │       │ • Manage own  │
│   tenant      │       │   tenant      │
│ • Invite users│       │ • Invite users│
│ • API creds   │       │ • API creds   │
│ • Billing     │       │ • Billing     │
│ • Settings    │       │ • Settings    │
└───────────────┘       └───────────────┘
        │                       │
        ▼                       ▼
┌───────────────┐       ┌───────────────┐
│ Tenant User   │       │ Tenant User   │
│ • View orders │       │ • View orders │
│ • View logs   │       │ • View logs   │
│ • View stats  │       │ • View stats  │
│ (Read-only)   │       │ (Read-only)   │
└───────────────┘       └───────────────┘
```

### Permissions Matrix

| Feature | Super Admin | Tenant Admin | Tenant User |
|---------|-------------|--------------|-------------|
| Manage all tenants | ✅ | ❌ | ❌ |
| View all billing | ✅ | ❌ | ❌ |
| Manage own tenant | ✅ | ✅ | ❌ |
| Invite users | ✅ | ✅ | ❌ |
| Manage API credentials | ✅ | ✅ | ❌ |
| View orders | ✅ | ✅ | ✅ |
| Retry failed orders | ✅ | ✅ | ❌ |
| Manage product mappings | ✅ | ✅ | ❌ |
| View sync logs | ✅ | ✅ | ✅ |
| View analytics | ✅ | ✅ | ✅ |
| Manage subscription | ✅ | ✅ | ❌ |

---

## Subscription System

### Subscription Plans

```php
// Recommended Pricing Tiers

┌──────────────────────────────────────────────────────────┐
│                    STARTER PLAN                          │
│  $29/month                                               │
├──────────────────────────────────────────────────────────┤
│  • Up to 500 orders/month                                │
│  • 1 Careem location                                     │
│  • Email support                                         │
│  • Basic analytics                                       │
│  • 1 user                                                │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│                   BUSINESS PLAN                          │
│  $79/month                                               │
├──────────────────────────────────────────────────────────┤
│  • Up to 2,000 orders/month                              │
│  • 3 Careem locations                                    │
│  • Priority email support                                │
│  • Advanced analytics                                    │
│  • 5 users                                               │
│  • Custom product mappings                               │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│                  ENTERPRISE PLAN                         │
│  $199/month                                              │
├──────────────────────────────────────────────────────────┤
│  • Unlimited orders                                      │
│  • Unlimited locations                                   │
│  • 24/7 phone + email support                            │
│  • Custom integrations                                   │
│  • Unlimited users                                       │
│  • Dedicated account manager                             │
│  • SLA guarantees                                        │
└──────────────────────────────────────────────────────────┘
```

### Billing Features

1. **Payment Integration** - Stripe for payment processing
2. **Trial Period** - 14-day free trial for all plans
3. **Subscription Management** - Upgrade/downgrade, cancel anytime
4. **Usage Tracking** - Monitor order count per billing period
5. **Overage Handling** - Soft limits with upgrade prompts
6. **Invoicing** - Automatic invoice generation and email
7. **Failed Payment Recovery** - Dunning management

### Database Schema for Subscriptions

```sql
-- subscription_plans table
id, name, slug, price, currency, billing_interval,
order_limit, location_limit, user_limit, features (JSON),
is_active, created_at, updated_at

-- subscriptions table
id, tenant_id, subscription_plan_id, stripe_subscription_id,
status (active/cancelled/past_due/trialing),
trial_ends_at, current_period_start, current_period_end,
cancel_at_period_end, created_at, updated_at

-- subscription_usage table
id, subscription_id, tenant_id, month, year,
order_count, last_order_at, created_at, updated_at
```

---

## Landing Page & Marketing

### Landing Page Structure

```
┌──────────────────────────────────────────────────────────┐
│                    HERO SECTION                          │
│  • Compelling headline                                   │
│  • Value proposition                                     │
│  • CTA: "Start Free Trial"                               │
│  • Hero image/animation                                  │
└──────────────────────────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────┐
│                  HOW IT WORKS                            │
│  Step 1: Sign up and connect Loyverse                    │
│  Step 2: Configure Careem webhook                        │
│  Step 3: Orders sync automatically                       │
└──────────────────────────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────┐
│                    FEATURES                              │
│  • Real-time order sync                                  │
│  • Automatic product mapping                             │
│  • Detailed analytics                                    │
│  • Error handling & retry                                │
│  • Multi-location support                                │
│  • Team collaboration                                    │
└──────────────────────────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────┐
│                    PRICING                               │
│  • 3-tier pricing cards                                  │
│  • Feature comparison                                    │
│  • FAQ section                                           │
└──────────────────────────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────┐
│                  TESTIMONIALS                            │
│  • Customer success stories                              │
│  • Logos of restaurants using the service                │
└──────────────────────────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────┐
│                  FOOTER & CTA                            │
│  • Final CTA: "Get Started Free"                         │
│  • Links: About, Contact, Terms, Privacy                 │
│  • Social media links                                    │
└──────────────────────────────────────────────────────────┘
```

### Design Guidelines

**Technology Stack:**
- **Frontend**: Tailwind CSS, Alpine.js, GSAP (animations)
- **Icons**: Heroicons or Phosphor Icons
- **Illustrations**: Custom SVG or undraw.co
- **Fonts**: Inter or DM Sans (modern, clean)
- **Color Scheme**:
  - Primary: Careem green (#00B140) or custom brand color
  - Secondary: Deep blue (#1E40AF)
  - Accent: Orange (#F59E0B) for CTAs
  - Neutrals: Gray scale

**Key Design Principles:**
- Clean, modern, minimal design
- Mobile-first responsive
- Fast loading (< 2s)
- Clear CTAs throughout
- Trust indicators (security badges, testimonials)
- Smooth animations and transitions

---

## Technical Implementation

### Phase 1: Database & Multi-Tenancy Core (Week 1-2)

```bash
# New migrations
- create_tenants_table
- create_subscription_plans_table
- create_subscriptions_table
- create_subscription_usage_table
- add_tenant_id_to_all_tables
- create_tenant_users_table
- create_roles_and_permissions_tables
```

**Key Changes:**
1. Add `tenant_id` to all existing tables (orders, product_mappings, etc.)
2. Create tenant scoping middleware
3. Implement tenant context service
4. Update all Eloquent models with global tenant scope

### Phase 2: Authentication & Authorization (Week 2-3)

```bash
# New features
- Multi-role authentication system
- Super admin panel authentication
- Tenant subdomain detection
- Permission middleware
- Role-based access control
```

### Phase 3: Subscription System (Week 3-4)

```bash
# New features
- Stripe integration
- Subscription plans management
- Usage tracking system
- Billing webhooks from Stripe
- Invoice generation
- Trial period management
```

### Phase 4: Landing Page (Week 4-5)

```bash
# New pages
- Landing page with hero, features, pricing
- Pricing page with detailed comparison
- Sign up flow with tenant creation
- Onboarding wizard
```

### Phase 5: Super Admin Panel (Week 5-6)

```bash
# New features
- Tenant management dashboard
- Subscription overview
- Usage analytics across all tenants
- System health monitoring
- User impersonation (for support)
```

### Phase 6: Tenant Dashboard Enhancements (Week 6-7)

```bash
# Enhanced features
- Team member management
- User invitations
- Role assignments
- Subscription management
- Usage statistics
- Billing history
```

### Phase 7: Testing & Polish (Week 7-8)

```bash
# Testing & optimization
- Comprehensive test suite
- Performance optimization
- Security audit
- Documentation updates
- Deployment preparation
```

---

## Security & Isolation

### Tenant Isolation Strategies

1. **Database Level**
   - All models have `tenant_id` foreign key
   - Global scope automatically filters by tenant
   - Database indexes on tenant_id for performance

2. **Application Level**
   - Middleware enforces tenant context
   - No cross-tenant queries allowed
   - Tenant context bound to request lifecycle

3. **API Level**
   - Webhook URLs include tenant identifier
   - API credentials scoped per tenant
   - Rate limiting per tenant

4. **Queue Level**
   - Jobs tagged with tenant_id
   - Tenant context restored in job execution
   - Failed job isolation per tenant

### Security Features

```php
// Automatic tenant scoping in models
trait HasTenantScope {
    protected static function booted() {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (!$model->tenant_id) {
                $model->tenant_id = tenant()->id;
            }
        });
    }
}

// Middleware for tenant detection
class IdentifyTenant {
    public function handle($request, $next) {
        $subdomain = $request->getHost();
        $tenant = Tenant::where('subdomain', $subdomain)->firstOrFail();
        app()->instance('tenant', $tenant);
        return $next($request);
    }
}

// Policy for authorization
class OrderPolicy {
    public function view(User $user, Order $order) {
        return $user->tenant_id === $order->tenant_id;
    }
}
```

---

## Scalability Considerations

### Infrastructure Requirements

**Development/Staging:**
- Single server with MySQL
- Redis for cache/queue
- CDN for static assets

**Production (Small - Medium Scale):**
- Load balanced web servers (2-3 instances)
- Managed MySQL (RDS or equivalent)
- Redis cluster for cache/sessions
- Dedicated queue workers (2-4 instances)
- CDN (CloudFlare or AWS CloudFront)
- S3 for file storage

**Production (Large Scale):**
- Auto-scaling web servers (5-10+ instances)
- Read replicas for database
- Separate databases per tenant (if needed)
- Multiple queue worker pools
- ElasticSearch for logs and analytics
- Monitoring (New Relic, DataDog)

### Performance Optimization

1. **Caching Strategy**
   - Per-tenant cache keys
   - Aggressive caching of Loyverse data
   - Cache warming for active tenants

2. **Database Optimization**
   - Proper indexing on tenant_id
   - Query optimization
   - Connection pooling
   - Read replicas for reporting

3. **Queue Optimization**
   - Multiple queue workers
   - Priority queues for different job types
   - Horizon for queue monitoring

4. **CDN & Assets**
   - Static assets on CDN
   - Image optimization
   - Lazy loading

---

## Migration Path from Single-Tenant to Multi-Tenant

### Step-by-Step Migration

```bash
# 1. Add tenant_id columns (nullable initially)
php artisan migrate:add-tenant-columns

# 2. Create default tenant for existing data
php artisan tenants:create-default

# 3. Assign existing data to default tenant
php artisan tenants:migrate-existing-data

# 4. Make tenant_id non-nullable
php artisan migrate:finalize-tenant-columns

# 5. Deploy new multi-tenant code
# 6. Test thoroughly
# 7. Create new tenants for new customers
```

---

## Monitoring & Analytics

### Key Metrics to Track

**Platform-Wide:**
- Total tenants (active/inactive)
- Total subscriptions by plan
- Monthly recurring revenue (MRR)
- Churn rate
- Average orders per tenant
- System uptime and performance

**Per-Tenant:**
- Order count (daily/monthly)
- Sync success rate
- Failed order rate
- API usage
- User activity
- Subscription status

### Alerting

- Failed payment notifications
- High error rates per tenant
- Queue depth warnings
- API rate limit approaching
- System resource alerts

---

## Next Steps

1. **Review and approve architecture** ✅
2. **Create detailed task breakdown** ⏳
3. **Setup development environment** ⏳
4. **Begin Phase 1 implementation** ⏳

---

**Document Version:** 1.0
**Last Updated:** 2025-10-19
**Status:** Draft - Pending Review
