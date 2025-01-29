#!/bin/bash

set -e
set -x

# Generate a unique cache bust value using the current timestamp
CACHEBUST=$(date +%s)

# Export CACHEBUST as an environment variable so Docker Compose can use it
export CACHEBUST



DEPLOY_DIR=/home/deployer/laravel/deployments/$DEPLOYMENT_ID/code

echo "Deployment ID: $DEPLOYMENT_ID"
echo "Deployment Directory: $DEPLOY_DIR"

# Navigate to deployment directory
mkdir -p $DEPLOY_DIR
cd $DEPLOY_DIR



if [ "$APP_ENV" == "production" ]; then
    EMAIL_HOST="smtp.yourmailprovider.com"
    EMAIL_HOST_USER="your-email@example.com"
    EMAIL_HOST_PASSWORD="your-secure-password"
    EMAIL_PORT=587
    EMAIL_USE_TLS=True
else
    EMAIL_HOST="mailcatcher"
    EMAIL_HOST_USER=""
    EMAIL_HOST_PASSWORD=""
    EMAIL_PORT=1025
    EMAIL_USE_TLS=False
fi

APP_NAME="Constrix"
APP_URL="core-be-$DEPLOYMENT_ID.constrix-nv.com"

# Create .env file
cat <<EOF > .env
APP_ENV=$APP_ENV
APP_URL=$APP_URL
APP_DEBUG=$APP_DEBUG
DB_CONNECTION=mysql
DB_HOST=$DB_HOST
DB_PORT=$DB_PORT
DB_USERNAME=$DB_USERNAME
DB_PASSWORD=$DB_PASSWORD
DB_DATABASE=$DB_NAME-$DEPLOYMENT_ID
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
CACHE_STORE=redis
CACHE_PREFIX=$DEPLOYMENT_ID
REDIS_HOST=redis
APP_KEY=$APP_KEY
JWT_SECRET=$JWT_SECRET
MAIL_HOST = $EMAIL_HOST
MAIL_PORT =
MAIL_USERNAME = $EMAIL_HOST_USER
MAIL_PASSWORD = $EMAIL_HOST_PASSWORD
MAIL_FROM_ADDRESS = no-reply@constrix-nv.com
MAIL_FROM_NAME = $APP_NAME
EMAIL_HOST = $EMAIL_HOST
EMAIL_HOST_USER = $EMAIL_HOST_USER
EMAIL_HOST_PASSWORD = $EMAIL_HOST_PASSWORD
EMAIL_PORT = $EMAIL_PORT
EMAIL_USE_TLS = $EMAIL_USE_TLS
EOF

echo "APP_ENV: $APP_ENV"

cat .env

# Secure the .env file
chmod 600 .env

cd "$DEPLOY_DIR/devops"

# Build the Docker images without using the cache
docker compose build --no-cache

# Start the containers and remove any orphaned containers
docker compose -p $DEPLOYMENT_ID up --force-recreate --remove-orphans -d
