#!/bin/sh

set -e

# Function to log with timestamp
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Function to handle errors
handle_error() {
    log "ERROR: $1"
    exit 1
}

log "Starting container initialization..."

# Check if .env file exists
if [ ! -f /var/www/.env ]; then
    handle_error ".env file not found. Container cannot start without environment configuration."
fi

# Generate APP_KEY if not set or empty
log "Checking APP_KEY..."
if ! grep -q "^APP_KEY=base64:" /var/www/.env || grep -q "^APP_KEY=$" /var/www/.env || grep -q "^APP_KEY=base64:PLACEHOLDER" /var/www/.env; then
    log "APP_KEY not found or invalid. Generating new APP_KEY..."
    php artisan key:generate --force || handle_error "Failed to generate APP_KEY"
    log "APP_KEY generated successfully"
else
    log "APP_KEY already set"
fi

# Ensure storage/logs directory exists and has correct permissions
log "Setting up storage directories and permissions..."
mkdir -p /var/www/storage/logs
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/framework/cache
mkdir -p /var/www/bootstrap/cache

chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage
chown -R www-data:www-data /var/www/bootstrap/cache

# Test database connection before running migrations
log "Testing database connection..."
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection successful';" || handle_error "Database connection failed"

# Run Laravel Artisan commands with error handling
log "Running composer dump-autoload..."
composer dump-autoload || handle_error "Composer dump-autoload failed"

log "Creating storage link..."
php artisan storage:link || log "Storage link already exists or failed (non-critical)"

log "Running database migrations..."
php artisan migrate --force || handle_error "Database migration failed"

# Optional: Run seeders (uncomment if needed)
log "Running database seeders..."
php artisan db:seed --force || log "Database seeding failed (non-critical)"
php artisan tenant:seed --force || log "Database Tenant seeding failed (non-critical)"

# Clear and cache configuration
log "Optimizing Laravel..."
php artisan config:clear || log "Config clear failed (non-critical)"
php artisan config:cache || log "Config cache failed (non-critical)"
php artisan route:clear || log "Route clear failed (non-critical)"
php artisan route:cache || log "Route cache failed (non-critical)"
php artisan view:clear || log "View clear failed (non-critical)"
php artisan view:cache || log "View cache failed (non-critical)"
php artisan event:cache || log "Event cache failed (non-critical)"

# Install Octane (downloads RoadRunner binary if not present)
log "Setting up Octane..."
php artisan octane:install --server=roadrunner --no-interaction || log "Octane already installed (non-critical)"

log "Container initialization completed successfully"

# Fix permissions again in case artisan commands created root-owned files
log "Fixing permissions..."
chown -R www-data:www-data /var/www/storage
chown -R www-data:www-data /var/www/bootstrap/cache
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

# Start Supervisor
log "Starting supervisord..."
exec "$@"
