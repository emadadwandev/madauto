# Testing Tenant Subdomain Authentication

## Steps to test the fixes:

### 1. Add to Windows Hosts File

Add these entries to your Windows hosts file (`C:\Windows\System32\drivers\etc\hosts`):

```
127.0.0.1 localhost
127.0.0.1 admin.localhost  
127.0.0.1 demo.localhost
127.0.0.1 www.localhost
```

### 2. Start the Laravel Server

```powershell
cd e:\2025\dev\Careem\careem-loyverse-integration
php artisan serve --host=0.0.0.0 --port=8000
```

### 3. Test the Different Domains

- **Landing Page**: http://localhost:8000 or http://www.localhost:8000
- **Super Admin**: http://admin.localhost:8000
- **Demo Tenant**: http://demo.localhost:8000

### 4. Test Login Flow

1. Go to http://demo.localhost:8000
2. You should be redirected to http://demo.localhost:8000/login
3. Login with:
   - Email: `demo@test.com`
   - Password: `password`
4. After login, you should be redirected to http://demo.localhost:8000/dashboard

### 5. Debug Information

If you encounter issues, check the debug information:
- Visit http://demo.localhost:8000/debug-session (before login)
- Check the Laravel logs: `Get-Content storage/logs/laravel.log -Tail 20`
- Check the server output for request logs

### 6. Key Fixes Applied

1. **Fixed subdomain extraction**: Now properly handles port numbers (`:8000`)
2. **Added debug middleware**: Logs authentication flow details
3. **Session configuration**: Already properly configured with `SESSION_DOMAIN=.localhost`

### 7. Expected Behavior

- Demo tenant should have its own isolated session
- CSRF tokens should work across the subdomain
- No cross-domain redirects should occur
- Session should persist on demo.localhost:8000

### Troubleshooting

If you still see 419 errors:
1. Clear browser cookies for localhost domain
2. Check that session cookies are being set for `.localhost` domain
3. Verify CSRF token is generated correctly on the subdomain
4. Check Laravel logs for authentication debug information
