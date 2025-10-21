# Deployment Guide - Careem Now to Loyverse POS Integration

## Table of Contents
1. [Server Requirements](#server-requirements)
2. [Pre-Deployment Checklist](#pre-deployment-checklist)
3. [Initial Server Setup](#initial-server-setup)
4. [Application Deployment](#application-deployment)
5. [Queue Worker Setup](#queue-worker-setup)
6. [Laravel Echo Server Setup](#laravel-echo-server-setup)
7. [SSL Certificate Setup](#ssl-certificate-setup)
8. [Environment Configuration](#environment-configuration)
9. [Database Migration](#database-migration)
10. [Post-Deployment Verification](#post-deployment-verification)
11. [Monitoring Setup](#monitoring-setup)
12. [Backup Strategy](#backup-strategy)
13. [Troubleshooting](#troubleshooting)

---

## Server Requirements

### Minimum Specifications
- **CPU**: 2 cores (4 cores recommended for production)
- **RAM**: 4GB (8GB recommended for production)
- **Storage**: 50GB SSD
- **Network**: Stable internet connection with public IP

### Software Requirements
- **Operating System**: Ubuntu 22.04 LTS or Ubuntu 24.04 LTS
- **PHP**: 8.2 or higher
- **Web Server**: Nginx 1.18+ or Apache 2.4+
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **Redis**: 6.0+
- **Node.js**: 18+ LTS (for Laravel Echo Server)
- **Composer**: 2.x
- **Supervisor**: Latest stable version

### PHP Extensions Required
```bash
php8.2-cli
php8.2-fpm
php8.2-mysql
php8.2-mbstring
php8.2-xml
php8.2-bcmath
php8.2-curl
php8.2-zip
php8.2-gd
php8.2-intl
php8.2-redis
```

---

## Pre-Deployment Checklist

- [ ] Domain/subdomain configured and pointing to server
- [ ] SSL certificate ready (or Let's Encrypt setup planned)
- [ ] Database server accessible
- [ ] Redis server accessible
- [ ] Loyverse API credentials obtained
- [ ] Careem webhook secret obtained
- [ ] Server firewall configured
- [ ] SSH access configured
- [ ] Backup solution planned

---

## Initial Server Setup

### 1. Update System Packages

```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Install PHP 8.2 and Extensions

```bash
# Add PHP repository
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP and extensions
sudo apt install php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring \
php8.2-xml php8.2-bcmath php8.2-curl php8.2-zip php8.2-gd php8.2-intl \
php8.2-redis -y

# Verify installation
php -v
```

### 3. Install and Configure MySQL

```bash
# Install MySQL
sudo apt install mysql-server -y

# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE careem_loyverse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'careem_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON careem_loyverse.* TO 'careem_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Install and Configure Redis

```bash
# Install Redis
sudo apt install redis-server -y

# Configure Redis
sudo nano /etc/redis/redis.conf
# Set: supervised systemd
# Set: bind 127.0.0.1

# Restart Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server

# Verify Redis
redis-cli ping
# Should return: PONG
```

### 5. Install Nginx

```bash
# Install Nginx
sudo apt install nginx -y

# Start and enable Nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

### 6. Install Composer

```bash
# Download and install Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer

# Verify installation
composer --version
```

### 7. Install Node.js and NPM

```bash
# Install Node.js 18 LTS
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs -y

# Verify installation
node -v
npm -v
```

### 8. Install Supervisor

```bash
sudo apt install supervisor -y
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

---

## Application Deployment

### 1. Create Application Directory

```bash
# Create web root directory
sudo mkdir -p /var/www/careem-loyverse
sudo chown -R $USER:www-data /var/www/careem-loyverse
```

### 2. Clone/Upload Application

**Option A: Using Git (Recommended)**
```bash
cd /var/www/careem-loyverse
git clone <repository-url> .
```

**Option B: Using FTP/SCP**
```bash
# Upload files via SCP
scp -r /local/path/* user@server:/var/www/careem-loyverse/
```

### 3. Install Dependencies

```bash
cd /var/www/careem-loyverse

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies
npm install

# Build frontend assets
npm run build
```

### 4. Set Permissions

```bash
# Set proper ownership
sudo chown -R www-data:www-data /var/www/careem-loyverse

# Set directory permissions
sudo find /var/www/careem-loyverse -type d -exec chmod 755 {} \;
sudo find /var/www/careem-loyverse -type f -exec chmod 644 {} \;

# Set storage and cache permissions
sudo chmod -R 775 /var/www/careem-loyverse/storage
sudo chmod -R 775 /var/www/careem-loyverse/bootstrap/cache
```

### 5. Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/careem-loyverse
```

**Nginx Configuration:**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/careem-loyverse/public;

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
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Increase timeout for webhook processing
    fastcgi_read_timeout 300;
    proxy_read_timeout 300;
}
```

**Enable the site:**
```bash
sudo ln -s /etc/nginx/sites-available/careem-loyverse /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## Environment Configuration

### 1. Create Environment File

```bash
cd /var/www/careem-loyverse
cp .env.example .env
nano .env
```

### 2. Configure Environment Variables

```env
APP_NAME="Careem-Loyverse Integration"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=careem_loyverse
DB_USERNAME=careem_user
DB_PASSWORD=your_database_password

BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Loyverse Configuration
LOYVERSE_API_URL=https://api.loyverse.com/v1
LOYVERSE_API_KEY=your_loyverse_api_key
LOYVERSE_STORE_ID=your_store_id

# Careem Configuration
CAREEM_WEBHOOK_SECRET=your_webhook_secret
CAREEM_WEBHOOK_URL=https://your-domain.com/api/webhook/careem

# Queue Configuration
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=database

# Broadcasting
BROADCAST_DRIVER=redis

# Laravel Echo Server (if using socket.io)
LARAVEL_ECHO_SERVER_AUTH_HOST=http://localhost
LARAVEL_ECHO_SERVER_HOST=your-domain.com
LARAVEL_ECHO_SERVER_PORT=6001
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Database Migration

### 1. Run Migrations

```bash
cd /var/www/careem-loyverse
php artisan migrate --force
```

### 2. Seed Database (if needed)

```bash
php artisan db:seed --force
```

---

## Queue Worker Setup

### 1. Create Supervisor Configuration

```bash
sudo nano /etc/supervisor/conf.d/careem-loyverse-worker.conf
```

**Supervisor Configuration:**
```ini
[program:careem-loyverse-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/careem-loyverse/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/careem-loyverse/storage/logs/worker.log
stopwaitsecs=3600
```

### 2. Start Queue Workers

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start careem-loyverse-worker:*
```

### 3. Verify Workers are Running

```bash
sudo supervisorctl status
```

---

## Laravel Echo Server Setup

### 1. Install Laravel Echo Server

```bash
cd /var/www/careem-loyverse
npm install -g laravel-echo-server
```

### 2. Initialize Echo Server

```bash
laravel-echo-server init
```

**Configuration (laravel-echo-server.json):**
```json
{
  "authHost": "http://localhost",
  "authEndpoint": "/broadcasting/auth",
  "clients": [
    {
      "appId": "your-app-id",
      "key": "your-app-key"
    }
  ],
  "database": "redis",
  "databaseConfig": {
    "redis": {
      "port": "6379",
      "host": "127.0.0.1"
    }
  },
  "devMode": false,
  "host": null,
  "port": "6001",
  "protocol": "http",
  "socketio": {},
  "secureOptions": 67108864,
  "sslCertPath": "",
  "sslKeyPath": "",
  "sslCertChainPath": "",
  "sslPassphrase": "",
  "apiOriginAllow": {
    "allowCors": true,
    "allowOrigin": "https://your-domain.com",
    "allowMethods": "GET, POST",
    "allowHeaders": "Origin, Content-Type, X-Auth-Token, X-Requested-With, Accept, Authorization, X-CSRF-TOKEN, X-Socket-Id"
  }
}
```

### 3. Create Supervisor Configuration for Echo Server

```bash
sudo nano /etc/supervisor/conf.d/careem-loyverse-echo.conf
```

```ini
[program:careem-loyverse-echo]
directory=/var/www/careem-loyverse
process_name=%(program_name)s_%(process_num)02d
command=laravel-echo-server start
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/careem-loyverse/storage/logs/echo.log
```

### 4. Start Echo Server

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start careem-loyverse-echo:*
```

---

## SSL Certificate Setup

### Using Let's Encrypt (Free)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Obtain and install certificate
sudo certbot --nginx -d your-domain.com

# Verify auto-renewal
sudo certbot renew --dry-run
```

### Update Nginx for SSL

Certbot will automatically update your Nginx configuration. Verify:

```bash
sudo nano /etc/nginx/sites-available/careem-loyverse
```

---

## Post-Deployment Verification

### 1. Test Application

```bash
# Test homepage
curl https://your-domain.com

# Test webhook endpoint
curl -X POST https://your-domain.com/api/webhook/careem \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

### 2. Check Logs

```bash
# Application logs
tail -f /var/www/careem-loyverse/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/error.log

# Queue worker logs
tail -f /var/www/careem-loyverse/storage/logs/worker.log
```

### 3. Verify Queue Workers

```bash
sudo supervisorctl status careem-loyverse-worker:*
```

### 4. Verify Database Connection

```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

### 5. Test Redis Connection

```bash
php artisan tinker
>>> Redis::set('test', 'value');
>>> Redis::get('test');
```

---

## Monitoring Setup

### 1. Setup Laravel Telescope (Optional - Development Only)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### 2. Setup Log Rotation

```bash
sudo nano /etc/logrotate.d/careem-loyverse
```

```
/var/www/careem-loyverse/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### 3. Setup Health Check Endpoint

Create a simple health check:

```bash
curl https://your-domain.com/api/health
```

---

## Backup Strategy

### 1. Database Backup Script

```bash
sudo nano /usr/local/bin/backup-careem-db.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/careem-loyverse"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="careem_loyverse"
DB_USER="careem_user"
DB_PASS="your_password"

mkdir -p $BACKUP_DIR

mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Keep only last 7 days of backups
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $BACKUP_DIR/db_backup_$DATE.sql.gz"
```

```bash
sudo chmod +x /usr/local/bin/backup-careem-db.sh
```

### 2. Setup Cron for Automated Backups

```bash
sudo crontab -e
```

Add:
```
# Daily database backup at 2 AM
0 2 * * * /usr/local/bin/backup-careem-db.sh >> /var/log/careem-backup.log 2>&1
```

### 3. Application Files Backup

```bash
# Manual backup
tar -czf /var/backups/careem-loyverse/app_backup_$(date +%Y%m%d).tar.gz \
  /var/www/careem-loyverse \
  --exclude='/var/www/careem-loyverse/storage/logs' \
  --exclude='/var/www/careem-loyverse/vendor'
```

---

## Troubleshooting

### Queue Workers Not Processing Jobs

```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart careem-loyverse-worker:*

# Check worker logs
tail -f /var/www/careem-loyverse/storage/logs/worker.log
```

### Webhook Not Receiving Data

```bash
# Check Nginx logs
sudo tail -f /var/log/nginx/error.log

# Verify route exists
php artisan route:list | grep webhook

# Test webhook locally
curl -X POST http://localhost/api/webhook/careem \
  -H "Content-Type: application/json" \
  -d '{"order_id": "test123"}'
```

### Database Connection Issues

```bash
# Test MySQL connection
mysql -u careem_user -p careem_loyverse

# Check Laravel database config
php artisan tinker
>>> config('database.connections.mysql')
```

### Permission Issues

```bash
# Reset permissions
sudo chown -R www-data:www-data /var/www/careem-loyverse
sudo chmod -R 775 /var/www/careem-loyverse/storage
sudo chmod -R 775 /var/www/careem-loyverse/bootstrap/cache
```

### Laravel Echo Server Not Working

```bash
# Check Echo Server status
sudo supervisorctl status careem-loyverse-echo:*

# Check Echo Server logs
tail -f /var/www/careem-loyverse/storage/logs/echo.log

# Test Echo Server
curl http://localhost:6001
```

### High Memory Usage

```bash
# Check PHP-FPM settings
sudo nano /etc/php/8.2/fpm/pool.d/www.conf

# Adjust:
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

---

## Maintenance Commands

### Clear Application Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Rebuild Cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Check Failed Jobs

```bash
php artisan queue:failed
```

### Retry Failed Jobs

```bash
# Retry all
php artisan queue:retry all

# Retry specific job
php artisan queue:retry <job-id>
```

### Restart Queue Workers

```bash
php artisan queue:restart
```

---

## Updating the Application

### 1. Pull Latest Code

```bash
cd /var/www/careem-loyverse
git pull origin main
```

### 2. Update Dependencies

```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### 3. Run Migrations

```bash
php artisan migrate --force
```

### 4. Clear and Rebuild Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Restart Services

```bash
sudo supervisorctl restart careem-loyverse-worker:*
sudo supervisorctl restart careem-loyverse-echo:*
sudo systemctl reload php8.2-fpm
```

---

## Security Hardening

### 1. Firewall Configuration

```bash
# Allow SSH, HTTP, HTTPS
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable
```

### 2. Disable Directory Listing

Already handled in Nginx configuration.

### 3. Hide PHP Version

```bash
sudo nano /etc/php/8.2/fpm/php.ini
# Set: expose_php = Off

sudo systemctl restart php8.2-fpm
```

### 4. Setup Fail2Ban (Optional)

```bash
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

---

## Contact & Support

For deployment issues or questions, refer to:
- **Project Documentation**: See Context.md and instruction.md
- **Laravel Documentation**: https://laravel.com/docs
- **Nginx Documentation**: https://nginx.org/en/docs/

---

**Deployment Complete!** Your Careem Now to Loyverse POS integration service should now be running.
