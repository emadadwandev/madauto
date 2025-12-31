# Supervisor Setup for Queue Workers

This guide will help you set up Supervisor to manage Laravel queue workers on your production server.

## Step 1: Install Supervisor

```bash
sudo apt-get update
sudo apt-get install supervisor
```

## Step 2: Copy Configuration File

Copy the supervisor configuration to the server:

```bash
# Upload the file
scp supervisor-queue-worker.conf user@your-server:/tmp/

# On the server, move it to supervisor config directory
sudo mv /tmp/supervisor-queue-worker.conf /etc/supervisor/conf.d/careem-queue-worker.conf
```

## Step 3: Update Configuration

Edit the configuration file to match your server paths:

```bash
sudo nano /etc/supervisor/conf.d/careem-queue-worker.conf
```

**Update these values:**
- `command`: Change `/var/www/careem` to your actual application path
- `user`: Change `www-data` to your web server user (usually `www-data` or `ubuntu`)
- `stdout_logfile`: Update to your actual storage/logs path

## Step 4: Reload Supervisor

```bash
# Reread configuration files
sudo supervisorctl reread

# Update supervisor to add new programs
sudo supervisorctl update

# Start the queue workers
sudo supervisorctl start careem-queue-worker:*
```

## Step 5: Check Status

```bash
# Check if workers are running
sudo supervisorctl status

# Should show something like:
# careem-queue-worker:careem-queue-worker_00   RUNNING   pid 12345, uptime 0:00:10
# careem-queue-worker:careem-queue-worker_01   RUNNING   pid 12346, uptime 0:00:10
```

## Common Supervisor Commands

```bash
# View all programs
sudo supervisorctl status

# Start all queue workers
sudo supervisorctl start careem-queue-worker:*

# Stop all queue workers
sudo supervisorctl stop careem-queue-worker:*

# Restart all queue workers (use after code deployment)
sudo supervisorctl restart careem-queue-worker:*

# View logs
sudo tail -f /var/www/careem/storage/logs/queue-worker.log

# Reload supervisor (after config changes)
sudo supervisorctl reread
sudo supervisorctl update
```

## Configuration Explained

```ini
[program:careem-queue-worker]
# Program identifier
process_name=%(program_name)s_%(process_num)02d

# Command to run (adjust path to your application)
command=php /var/www/careem/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --queue=high,default

# Start automatically when supervisor starts
autostart=true

# Restart automatically if process crashes
autorestart=true

# Send stop signal to process group
stopasgroup=true
killasgroup=true

# User to run the process as (use your web server user)
user=www-data

# Number of processes to run (2 workers for better performance)
numprocs=2

# Redirect stderr to stdout
redirect_stderr=true

# Log file location
stdout_logfile=/var/www/careem/storage/logs/queue-worker.log

# Wait time before killing process (3600 = 1 hour)
stopwaitsecs=3600
```

## Queue Worker Parameters

- `--sleep=3`: Sleep 3 seconds when no jobs available
- `--tries=3`: Try failed jobs up to 3 times
- `--max-time=3600`: Worker will restart after 1 hour (prevents memory leaks)
- `--queue=high,default`: Process 'high' priority jobs first, then 'default'

## After Code Deployment

Always restart queue workers after deploying new code:

```bash
sudo supervisorctl restart careem-queue-worker:*
```

Or add this to your deployment script:

```bash
# In your deploy.sh
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart careem-queue-worker:*
```

## Troubleshooting

### Workers not starting
```bash
# Check supervisor logs
sudo tail -f /var/log/supervisor/supervisord.log

# Check queue worker logs
sudo tail -f /var/www/careem/storage/logs/queue-worker.log

# Check Laravel logs
sudo tail -f /var/www/careem/storage/logs/laravel-*.log
```

### Permission issues
```bash
# Fix storage permissions
sudo chown -R www-data:www-data /var/www/careem/storage
sudo chmod -R 775 /var/www/careem/storage
```

### Failed jobs piling up
```bash
# View failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Clear all failed jobs
php artisan queue:flush
```

### Check queue status
```bash
# Enter tinker
php artisan tinker

# Count pending jobs
>>> DB::table('jobs')->count();

# Count failed jobs
>>> DB::table('failed_jobs')->count();

# View latest job
>>> DB::table('jobs')->latest()->first();
```

## Production Checklist

- [ ] Supervisor installed
- [ ] Configuration file copied and updated with correct paths
- [ ] User changed to match web server user
- [ ] Supervisor reloaded and workers started
- [ ] Workers showing as RUNNING in supervisorctl status
- [ ] Log file being written to storage/logs/queue-worker.log
- [ ] Test order processing working
- [ ] Deployment script includes worker restart command

## Monitoring

Set up monitoring to alert if queue workers stop:

```bash
# Add to cron for monitoring (every 5 minutes)
*/5 * * * * supervisorctl status careem-queue-worker:* | grep -q RUNNING || echo "Queue workers down!" | mail -s "Alert: Queue Workers Down" admin@yourdomain.com
```

Or use Laravel Horizon (alternative to basic queue workers):

```bash
composer require laravel/horizon
php artisan horizon:install
```

## Auto-Start on Server Reboot

Supervisor is configured to start automatically on boot. Verify:

```bash
# Check if supervisor service is enabled
sudo systemctl is-enabled supervisor

# If not enabled, enable it
sudo systemctl enable supervisor

# Start supervisor service
sudo systemctl start supervisor
```
