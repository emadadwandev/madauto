# Careem Now to Loyverse POS Integration - Setup Guide

## Prerequisites

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Composer
- Node.js & NPM
- Git

## Installation Steps

### 1. Clone and Install Dependencies

```bash
cd careem-loyverse-integration

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Build frontend assets
npm run build
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Configure Database

Edit `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=careem_loyverse
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Queue Configuration (using database)
QUEUE_CONNECTION=database

# Cache Configuration (using database)
CACHE_STORE=database

# Session Configuration (using database)
SESSION_DRIVER=database
```

### 4. Run Database Migrations

```bash
php artisan migrate
```

### 5. Seed API Credentials (Optional)

If you want to pre-populate API credentials:

```bash
php artisan db:seed --class=ApiCredentialSeeder
```

## Running the Application

### Development Environment

#### 1. Start Laravel Development Server

```bash
php artisan serve
```

The application will be available at: `http://localhost:8000`

#### 2. Start Queue Worker

Open a new terminal and run:

**On Windows:**
```bash
run-queue-worker.bat
```

**On Linux/Mac:**
```bash
chmod +x run-queue-worker.sh
./run-queue-worker.sh
```

Or manually:
```bash
php artisan queue:work database --sleep=3 --tries=3 --timeout=60 --verbose
```

### Production Environment

#### 1. Configure Web Server

Set up your web server (Nginx/Apache) to point to the `public` directory.

**Example Nginx Configuration:**

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/careem-loyverse-integration/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### 2. Set Up Supervisor for Queue Worker

Install Supervisor:

```bash
sudo apt-get install supervisor
```

Create supervisor configuration:

```bash
sudo nano /etc/supervisor/conf.d/careem-loyverse-worker.conf
```

Add the following (adjust paths):

```ini
[program:careem-loyverse-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/careem-loyverse-integration/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/careem-loyverse-integration/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Start Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start careem-loyverse-queue-worker:*
```

## Configuration

### 1. Access the Dashboard

Navigate to: `http://your-domain.com/dashboard`

### 2. Configure API Credentials

Go to **Settings** (API Credentials page):

1. **Loyverse API Settings:**
   - Enter your Loyverse Access Token
   - Click "Save Access Token"
   - Click "Test Loyverse Connection" to verify

2. **Careem Webhook Settings:**
   - Enter your Careem Webhook Secret
   - Click "Save Webhook Secret"
   - Copy the Webhook URL and provide it to Careem

### 3. Set Up Product Mappings

Go to **Product Mappings**:

1. Click "Add New Mapping" to manually create mappings
2. Or click "Auto-Map by SKU" to automatically match products
3. Or import from CSV using the "Import from CSV" button

**CSV Format for Import:**
```csv
careem_product_id,careem_sku,careem_product_name,loyverse_item_id,loyverse_variant_id
PROD001,SKU001,Product Name,loyverse_item_123,
```

## Webhook URL

Your webhook URL for Careem Now is:

```
https://your-domain.com/api/webhook/careem
```

Provide this URL to Careem along with your webhook secret.

## Testing the Integration

### 1. Send a Test Webhook

You can use tools like Postman or cURL to send a test webhook:

```bash
curl -X POST https://your-domain.com/api/webhook/careem \
  -H "Content-Type: application/json" \
  -H "X-Careem-Signature: your_webhook_signature" \
  -d '{
    "order_id": "TEST123",
    "items": [
      {
        "product_id": "PROD001",
        "name": "Test Product",
        "quantity": 1,
        "price": 10.00
      }
    ],
    "customer": {
      "name": "Test Customer",
      "phone": "1234567890"
    }
  }'
```

### 2. Monitor the Process

1. Check **Dashboard** for order statistics
2. View **Orders** page to see the order
3. Check **Sync Logs** for sync activity
4. Monitor queue worker output for real-time processing

## Monitoring & Maintenance

### Check Queue Status

```bash
php artisan queue:work --once
```

### View Failed Jobs

```bash
php artisan queue:failed
```

### Retry Failed Jobs

```bash
# Retry a specific job
php artisan queue:retry <job-id>

# Retry all failed jobs
php artisan queue:retry all
```

### Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### View Logs

Application logs are located in:
```
storage/logs/laravel.log
```

Queue worker logs (if using supervisor):
```
storage/logs/queue-worker.log
```

## Troubleshooting

### Issue: Orders not processing

**Solution:**
1. Ensure queue worker is running
2. Check `storage/logs/laravel.log` for errors
3. Verify product mappings exist
4. Check Loyverse API credentials

### Issue: Webhook signature verification fails

**Solution:**
1. Verify webhook secret in Settings matches Careem's configuration
2. Check webhook logs in the database
3. Ensure signature header is being sent correctly

### Issue: Products not mapping

**Solution:**
1. Go to Product Mappings page
2. Use "Auto-Map by SKU" or manually create mappings
3. Ensure Careem product IDs match the mappings

### Issue: Loyverse API errors

**Solution:**
1. Test connection in Settings page
2. Verify access token is valid
3. Check rate limits (55 requests/minute)
4. Review sync logs for specific error messages

## Security Notes

- All API credentials are encrypted before storage
- Webhook requests are verified using signatures
- HTTPS is required for production
- Keep `.env` file secure and never commit to git
- Regularly rotate API tokens
- Monitor failed login attempts

## Support

For issues or questions, check:
- Application logs in `storage/logs/`
- Sync logs in the dashboard
- Queue worker output
- Webhook logs in the database

## License

Copyright © 2025. All rights reserved.
