#!/bin/bash

##############################################################################
# Careem-Loyverse Integration - Production Deployment Script
##############################################################################
# This script handles safe deployment to production server
# Usage: ./deploy.sh
##############################################################################

set -e  # Exit on any error

echo "🚀 Starting deployment process..."
echo ""

# Configuration - UPDATE THESE VALUES
APP_DIR="/var/www/careem"
WEB_USER="www-data"
BACKUP_DIR="/var/www/backups"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then
    print_error "Please run as root or with sudo"
    exit 1
fi

# Navigate to application directory
cd $APP_DIR
print_success "Changed to application directory: $APP_DIR"

# Enable maintenance mode
print_warning "Enabling maintenance mode..."
sudo -u $WEB_USER php artisan down || true
print_success "Maintenance mode enabled"

# Create backup
BACKUP_NAME="backup-$(date +%Y%m%d-%H%M%S)"
print_warning "Creating backup: $BACKUP_NAME"
mkdir -p $BACKUP_DIR
cp -r $APP_DIR $BACKUP_DIR/$BACKUP_NAME
print_success "Backup created at: $BACKUP_DIR/$BACKUP_NAME"

# Pull latest code from git
print_warning "Pulling latest code from git..."
sudo -u $WEB_USER git fetch origin
sudo -u $WEB_USER git pull origin main
print_success "Code updated from git"

# Install/Update composer dependencies
print_warning "Installing composer dependencies..."
sudo -u $WEB_USER composer install --no-dev --optimize-autoloader --no-interaction
print_success "Composer dependencies installed"

# Run database migrations
print_warning "Running database migrations..."
sudo -u $WEB_USER php artisan migrate --force
print_success "Database migrations completed"

# Clear and cache config
print_warning "Clearing and caching configuration..."
sudo -u $WEB_USER php artisan config:clear
sudo -u $WEB_USER php artisan config:cache
print_success "Configuration cached"

# Clear and cache routes
print_warning "Clearing and caching routes..."
sudo -u $WEB_USER php artisan route:clear
sudo -u $WEB_USER php artisan route:cache
print_success "Routes cached"

# Clear and cache views
print_warning "Clearing and caching views..."
sudo -u $WEB_USER php artisan view:clear
sudo -u $WEB_USER php artisan view:cache
print_success "Views cached"

# Install npm dependencies and build assets
print_warning "Building frontend assets..."
sudo -u $WEB_USER npm install
sudo -u $WEB_USER npm run build
print_success "Frontend assets built"

# Fix permissions
print_warning "Fixing file permissions..."
chown -R $WEB_USER:$WEB_USER $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/storage
chmod -R 775 $APP_DIR/bootstrap/cache
print_success "Permissions fixed"

# Restart queue workers
print_warning "Restarting queue workers..."
if command -v supervisorctl &> /dev/null; then
    supervisorctl restart careem-queue-worker:*
    print_success "Queue workers restarted via Supervisor"
else
    print_warning "Supervisor not found, skipping queue worker restart"
fi

# Restart PHP-FPM
print_warning "Restarting PHP-FPM..."
if systemctl is-active --quiet php8.3-fpm; then
    systemctl restart php8.3-fpm
    print_success "PHP-FPM restarted"
elif systemctl is-active --quiet php8.2-fpm; then
    systemctl restart php8.2-fpm
    print_success "PHP-FPM restarted"
elif systemctl is-active --quiet php8.1-fpm; then
    systemctl restart php8.1-fpm
    print_success "PHP-FPM restarted"
else
    print_warning "PHP-FPM service not found, skipping restart"
fi

# Disable maintenance mode
print_warning "Disabling maintenance mode..."
sudo -u $WEB_USER php artisan up
print_success "Maintenance mode disabled"

# Clear opcache (if using opcache)
print_warning "Clearing OPcache..."
if command -v cachetool &> /dev/null; then
    cachetool opcache:reset --fcgi=/var/run/php/php-fpm.sock
    print_success "OPcache cleared"
else
    print_warning "Cachetool not found, skipping OPcache reset"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
print_success "🎉 Deployment completed successfully!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Run health checks
print_warning "Running health checks..."
echo ""

# Check if application is responding
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://dw.madautomation.cloud)
if [ $HTTP_STATUS -eq 200 ]; then
    print_success "Application is responding (HTTP $HTTP_STATUS)"
else
    print_error "Application returned HTTP $HTTP_STATUS"
fi

# Check queue workers
if command -v supervisorctl &> /dev/null; then
    WORKER_STATUS=$(supervisorctl status careem-queue-worker:* | grep -c RUNNING || true)
    if [ $WORKER_STATUS -gt 0 ]; then
        print_success "$WORKER_STATUS queue worker(s) are running"
    else
        print_error "No queue workers are running!"
    fi
fi

# Check database connection
if sudo -u $WEB_USER php artisan tinker --execute="DB::connection()->getPdo();" &>/dev/null; then
    print_success "Database connection successful"
else
    print_error "Database connection failed!"
fi

echo ""
print_success "Deployment complete! Monitor logs for any issues:"
echo "  • Application logs: tail -f $APP_DIR/storage/logs/laravel-*.log"
echo "  • Queue worker logs: tail -f $APP_DIR/storage/logs/queue-worker.log"
echo "  • Nginx error logs: tail -f /var/log/nginx/error.log"
echo ""
