# Production Configuration Fix Steps

The 404 error occurs because your application is configured to listen for `localhost`, but your production server is receiving requests for your actual domain or IP. Laravel's router is rejecting these requests.

Follow these manual steps on your production server to fix the issue.

## Step 1: Edit the `.env` File

Open the `.env` file in your project root directory using a text editor (like `nano` or `vim`).

```bash
nano .env
```

Find and update the following variables. Replace `your-domain.com` with your actual domain name (or server IP address if you don't have a domain yet).

```dotenv
# Change from local to production
APP_ENV=production
APP_DEBUG=false

# Change from localhost to your actual domain/IP
APP_URL=https://your-domain.com
APP_DOMAIN=your-domain.com
```

**Example if using an IP address:**
```dotenv
APP_URL=http://123.45.67.89
APP_DOMAIN=123.45.67.89
```

## Step 2: Clear and Rebuild Caches

After saving the `.env` file, you must clear Laravel's configuration cache for the changes to take effect. Run these commands in your terminal:

```bash
# Clear the old configuration cache
php artisan config:clear

# Re-cache the new configuration (recommended for production speed)
php artisan config:cache

# Clear route cache to ensure routes are re-registered with the new domain
php artisan route:clear
```

## Step 3: Verify Routes

Check if the routes are now registered with the correct domain:

```bash
php artisan route:list
```

You should see your domain (e.g., `admin.your-domain.com`) listed in the "Domain" column instead of `admin.localhost`.

## Step 4: Restart Queue Workers (If applicable)

If you have queue workers running (e.g., via Supervisor), restart them so they pick up the new environment configuration:

```bash
php artisan queue:restart
```

## Step 5: Configure DNS Records (Verified)

**Status:** ✅ DNS records for `admin.madautomation.cloud` and `*` (wildcard) have been verified and are propagating correctly.

If you still see `DNS_PROBE_FINISHED_NXDOMAIN`, try flushing your local DNS cache:
- **Windows:** Run `ipconfig /flushdns` in Command Prompt.
- **Mac:** Run `sudo killall -HUP mDNSResponder` in Terminal.

## Step 6: Final Verification

Once DNS is working, verify the application loads:

1.  **Admin Panel:** Visit `http://admin.madautomation.cloud/login`
    - Should show the login page.
2.  **Landing Page:** Visit `http://madautomation.cloud`
    - Should show the landing page.
3.  **Tenant Test:** Visit `http://test.madautomation.cloud`
    - Should redirect to login or show a tenant-specific page (if tenant exists).

**Troubleshooting:**
- If you see a **Laravel 404** page: The request reached the server, but the route wasn't found. Check `APP_DOMAIN=madautomation.cloud` in `.env`.
- If you see a **Generic 404 (Nginx/Apache)**: The web server isn't configured to handle the subdomain. Check your Nginx `server_name` config to ensure it includes `*.madautomation.cloud`.

## Step 7: Fix Nginx Configuration (The 404 Cause)

Your Nginx configuration has two critical issues causing the 404:
1.  **Typo in Wildcard:** You have `8.madautomation.cloud` instead of `*.madautomation.cloud`.
2.  **Missing PHP Handler:** Your port 80 block is missing the code to actually execute PHP files.

Replace your entire Nginx config file (usually `/etc/nginx/sites-available/careem` or `default`) with this corrected version:

```nginx
server {
    listen 80;
    # FIXED: Added *.madautomation.cloud for subdomains (admin, tenants)
    server_name madautomation.cloud www.madautomation.cloud *.madautomation.cloud;

    root /var/www/careem/public;
    index index.php;

    # Handle Laravel Routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # FIXED: Added PHP Processing for Port 80
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Optional: phpMyAdmin (Keep if you need it)
    location /phpmyadmin {
        root /usr/share/;
        index index.php;
        location ~ ^/phpmyadmin/(.+\.php)$ {
            root /usr/share/;
            fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }
        location ~* ^/phpmyadmin/(.+\.(jpg|jpeg|gif|css|png|js|ico|html|xml|txt))$ {
            root /usr/share/;
        }
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**After saving the file:**
1.  Test the config: `sudo nginx -t`
2.  Restart Nginx: `sudo systemctl restart nginx`

## Troubleshooting: FTP "553 Could not create file" Error

If you cannot upload files via FTP, it means your FTP user doesn't have permission to write to the folder.

**Fix:** Run these commands on your server via SSH:

```bash
# 1. Take ownership of the files (replace 'ubuntu' with your FTP username if different)
sudo chown -R ubuntu:www-data /var/www/careem

# 2. Set write permissions for the owner and the web server group
sudo chmod -R 775 /var/www/careem

# 3. Ensure storage and cache are writable by the web server
sudo chmod -R 777 /var/www/careem/storage
sudo chmod -R 777 /var/www/careem/bootstrap/cache
```

## Step 8: Accessing the Correct Dashboard (Crucial)

You are getting a **404 Not Found** error because you are trying to access **Tenant features** (like Product Mappings) from the **Super Admin Dashboard**.

*   **Super Admin Dashboard:** `http://admin.madautomation.cloud`
    *   Use this ONLY to create tenants, manage subscriptions, and view system logs.
    *   It does **NOT** have access to Products, Orders, or Menus.

*   **Tenant Dashboard:** `http://[tenant-name].madautomation.cloud`
    *   Example: `http://shady.madautomation.cloud`
    *   You **MUST** log in here to manage Product Mappings, Orders, etc.

**Why the error happens:**
When you access `admin.madautomation.cloud/product-mappings`, the system thinks you are looking for a tenant named "admin". Since that tenant doesn't exist (or doesn't own the data), it returns a 404 error.

**Solution:**
1.  Go to `http://admin.madautomation.cloud`.
2.  Click on **Tenants**.
3.  Find your tenant (e.g., "shady").
4.  Click **Impersonate** (this should redirect you to `shady.madautomation.cloud`).
5.  **OR** manually visit `http://shady.madautomation.cloud/dashboard` and log in.

## 4. Fix "404 Not Found" on Delete (Final Fix)

The "404 Not Found" error when deleting is caused by Laravel's "Route Model Binding" trying to find the record with strict tenant scoping before our controller code runs. We need to disable this automatic binding for the delete route and handle it manually.

### Step 1: Upload Updated Files
You need to upload **3 files** to your server.

1.  **`app/Http/Controllers/Dashboard/ProductMappingController.php`**
    *   (This file has the logic to bypass scopes and manually find the record)

2.  **`routes/tenant.php`**
    *   (This file has the route definition changed from `{productMapping}` to `{id}` to disable automatic binding)

3.  **`resources/views/dashboard/product-mappings/index.blade.php`**
    *   (This file has the delete form updated to pass `id` instead of the model object)

### Step 2: Verify
1.  Go to your dashboard: `http://shady.madaautomation.cloud/product-mappings`
2.  Try to delete the mapping again.
3.  It should now work.

### Troubleshooting
If you still see issues, check `storage/logs/laravel.log` on the server.
