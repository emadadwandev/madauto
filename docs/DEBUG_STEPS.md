# STEP-BY-STEP LOGIN DEBUGGING GUIDE

## Current Issue Analysis

Based on the logs, the pattern is:
1. **POST /login** → **302 redirect to /dashboard** ✅ (authentication succeeds)
2. **GET /login** ❌ (something redirects back to login)

This suggests the dashboard route or controller has an issue.

## Debugging Steps

### Step 1: Test Basic Routes (Before Login)

1. **Health Check**: http://demo.localhost:8000/health
   - Should return JSON with "ok" status
   - Verifies basic tenant routing works

2. **Test Auth Status**: http://demo.localhost:8000/test-auth
   - Should show user as null (not authenticated)
   - Verifies route works without authentication

### Step 2: Login Process

1. Visit: http://demo.localhost:8000/login
2. Enter credentials:
   - Email: `demo@test.com`
   - Password: `password`
3. Submit form

### Step 3: Test Routes After Login (If Login Works)

1. **Test Auth Required**: http://demo.localhost:8000/test-auth-required
   - Should return user data if authenticated
   - Tests auth middleware only

2. **Test Verified Required**: http://demo.localhost:8000/test-verified-required
   - Should return user data if email verified
   - Tests auth + verified middleware

3. **Debug Session**: http://demo.localhost:8000/debug-session
   - Should show tenant context and user data

### Step 4: Check Dashboard

1. **Direct Dashboard**: http://demo.localhost:8000/dashboard
   - This is where the issue likely occurs

## Expected vs Actual

**Expected Flow:**
POST /login → 302 to /dashboard → 200 dashboard page

**Actual Flow:**
POST /login → 302 to /dashboard → ??? → 302 back to /login

## Debug Logs to Check

After trying login, check:
```powershell
Get-Content storage/logs/laravel.log -Tail 30
```

Look for:
- `IdentifyTenant Debug` entries
- `Authentication Debug` entries
- Any error messages

## Commands for Testing

```bash
# Test user verification
php artisan test:password demo@test.com password

# Test subdomain detection  
php artisan test:subdomain demo.localhost:8000

# Check user status
php artisan test:create-user demo@test.com "Demo User" demo
```

## Next Steps Based on Results

If health check fails → Route configuration issue
If login redirects back → Dashboard controller issue  
If tenant context missing → IdentifyTenant middleware issue
If auth fails → User/verification issue
