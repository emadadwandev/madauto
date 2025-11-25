# Test Credentials & Access Guide

This document provides all necessary credentials and instructions for testing the multi-tenant SaaS platform.

---

## 🔑 Test Accounts

### Super Admin Account
- **Email:** `admin@saas.test`
- **Password:** `password`
- **Access URL:** `http://admin.yourapp.test` (or `http://admin.localhost:8000`)
- **Permissions:** Full platform-wide access
  - Manage all tenants
  - View all subscriptions
  - System monitoring
  - Failed jobs management
  - User impersonation

---

### Tenant Admin Account (Demo Restaurant)
- **Email:** `admin@demo.test`
- **Password:** `password`
- **Access URL:** `http://demo.yourapp.test` (or `http://demo.localhost:8000`)
- **Tenant:** Demo Restaurant (subdomain: `demo`)
- **Plan:** Business Plan (14-day trial)
- **Permissions:** Full access within tenant
  - Manage orders and syncs
  - Configure API credentials
  - Manage product mappings
  - Invite/remove team members
  - Manage subscription
  - View analytics

---

### Tenant User Account (Demo Restaurant)
- **Email:** `user@demo.test`
- **Password:** `password`
- **Access URL:** `http://demo.yourapp.test` (or `http://demo.localhost:8000`)
- **Tenant:** Demo Restaurant (subdomain: `demo`)
- **Plan:** Business Plan (14-day trial)
- **Permissions:** Read-only access within tenant
  - View orders
  - View sync logs
  - View analytics
  - Cannot modify settings or data

---

## 🌐 Setting Up Subdomains (Local Development)

### Option 1: Using hosts file (Recommended)

**Windows:** Edit `C:\Windows\System32\drivers\etc\hosts`
**Mac/Linux:** Edit `/etc/hosts`

Add these lines:
```
127.0.0.1 yourapp.test
127.0.0.1 www.yourapp.test
127.0.0.1 admin.yourapp.test
127.0.0.1 demo.yourapp.test
```

Then access:
- Landing page: `http://yourapp.test:8000`
- Super admin: `http://admin.yourapp.test:8000`
- Demo tenant: `http://demo.yourapp.test:8000`

### Option 2: Using localhost subdomain

Some browsers support `*.localhost` subdomains natively:
- Landing page: `http://localhost:8000`
- Super admin: `http://admin.localhost:8000`
- Demo tenant: `http://demo.localhost:8000`

---

## 🚀 Quick Start Testing

### 1. Start the Application
```bash
# Terminal 1: Start web server
php artisan serve

# Terminal 2: Start queue worker
php artisan queue:work database --verbose

# Terminal 3 (Optional): Watch logs
php artisan pail
```

### 2. Test Super Admin Panel

**IMPORTANT:** You must access the admin subdomain BEFORE logging in!

1. Go to `http://admin.localhost:8000/login` (or `http://admin.yourapp.test:8000/login`)
2. Login with `admin@saas.test` / `password`
3. You will be automatically redirected to the super admin dashboard
4. You should see:
   - Dashboard with platform metrics
   - 1 active tenant (Demo Restaurant)
   - MRR calculation
   - System health status
4. Navigate to **Tenants** → View Demo Restaurant details
5. Try **Impersonate** feature to log in as tenant admin

### 3. Test Tenant Dashboard
1. Go to `http://demo.yourapp.test:8000`
2. Login with `admin@demo.test` / `password`
3. You should see:
   - Main dashboard
   - Subscription status (Business - Trial)
   - Orders section (empty initially)
   - Settings section

### 4. Test Read-Only User
1. Logout and login with `user@demo.test` / `password`
2. Verify you can view but not modify:
   - Can view orders
   - Cannot access settings
   - Cannot create/edit product mappings
   - Cannot invite users

### 5. Test User Invitation Flow
1. Login as `admin@demo.test`
2. Go to **Team** → **Invite User**
3. Send invitation to a test email
4. Check the `invitations` table for the token
5. Access `/invitations/{token}` to accept invitation

---

## 📊 Database Overview

After seeding, you should have:
- **Users:** 4 (1 super admin, 2 tenant admin, 1 tenant user)
- **Tenants:** 1 (Demo Restaurant)
- **Roles:** 3 (super_admin, tenant_admin, tenant_user)
- **Subscriptions:** 1 (Business plan, trialing status)
- **Subscription Plans:** 3 (Starter, Business, Enterprise)

---

## 🧪 Testing Scenarios

### Scenario 1: Super Admin Managing Tenants
1. Login as super admin
2. View all tenants
3. View Demo Restaurant details
4. Try suspending/activating the tenant
5. Impersonate tenant admin
6. Perform actions as tenant
7. Stop impersonation

### Scenario 2: Tenant Admin Full Workflow
1. Login as tenant admin
2. Configure Loyverse API credentials (Settings)
3. Create product mappings
4. Simulate Careem webhook (send test order)
5. View sync logs
6. Retry failed syncs
7. Invite new team member
8. Manage subscription (view usage, upgrade/downgrade)

### Scenario 3: Multi-Tenancy Isolation Test
1. Create a second tenant via registration page
2. Login to both tenants in different browsers
3. Verify data isolation:
   - Orders from tenant A not visible in tenant B
   - Product mappings are separate
   - API credentials are separate
   - Team members are separate

### Scenario 4: Subscription Limits
1. Login as tenant admin
2. Check current usage (Dashboard → Subscription)
3. Create orders to approach limit (Business: 2000 orders/month)
4. Verify usage tracking updates
5. Test limit enforcement when exceeded
6. Test upgrade flow to higher plan

---

## 🔧 Troubleshooting

### Subdomain not working
- **Check hosts file** is properly configured
- **Clear browser cache** and DNS cache
- **Restart web server** after hosts file changes
- **Try different browser** (some browsers cache DNS aggressively)

### Cannot login
- **Check database** has seeded users
- **Run seeder again:** `php artisan db:seed --class=TestUserSeeder`
- **Check email is verified:** All seeded users have `email_verified_at` set

### Queue jobs not processing
- **Check queue worker is running:** Look for the process
- **Check failed jobs:** `php artisan queue:failed`
- **Restart queue worker:** `php artisan queue:restart`

### Tenant context issues
- **Clear cache:** `php artisan cache:clear`
- **Check middleware** is applied to routes
- **Check subdomain** is correctly extracted

---

## 📝 Next Steps After Testing

1. **Configure Stripe** for real subscription processing
2. **Set up email service** for notifications
3. **Add real Loyverse API credentials** in tenant settings
4. **Configure Careem webhook** to point to your application
5. **Test end-to-end order flow** with real data
6. **Add comprehensive tests** (Phase 7)
7. **Perform security audit** (Phase 7)
8. **Deploy to production** (Phase 8)

---

## 🆘 Support

If you encounter issues:
1. Check `storage/logs/laravel.log` for errors
2. Use `php artisan pail` for real-time log monitoring
3. Check the `sync_logs` table for integration errors
4. Review the `failed_jobs` table for queue issues

---

**Last Updated:** 2025-10-20
**Version:** 1.0
