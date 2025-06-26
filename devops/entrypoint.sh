#!/bin/sh

set -e

# Generate .env file from environment variables if it doesn't exist
#if [ ! -f /var/www/.env ]; then
#    echo "Generating .env file..."
#    cat <<EOL > /var/www/.env
#APP_ENV=${APP_ENV}
#APP_DEBUG=${APP_DEBUG}
#
#DB_HOST=${DB_HOST}
#DB_PORT=${DB_PORT}
#DB_USER=${DB_USER}
#DB_PASSWORD=${DB_PASSWORD}
#DB_NAME=${DB_NAME}-${DEPLOYMENT_ID}
#EOL
#fi

# Run Laravel Artisan commands
echo "Running Laravel commands..."
yes | composer dump-autoload
php artisan storage:link
yes | php artisan migrate --force
#yes | php artisan db:seed --force


# Ensure storage/logs directory exists and has correct permissions
echo "Setting up log directory permissions..."
mkdir -p /var/www/storage/logs
chmod -R 775 /var/www/storage/logs
chown -R www-data:www-data /var/www/storage/logs

# Start Supervisor
exec "$@"
