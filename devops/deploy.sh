#!/bin/bash

# Generate a unique cache bust value using the current timestamp
CACHEBUST=$(date +%s)

# Export CACHEBUST as an environment variable so Docker Compose can use it
export CACHEBUST

set -e
set -x

DEPLOY_DIR=/home/deployer/laravel/deployments/$DEPLOYMENT_ID/code

echo "Deployment ID: $DEPLOYMENT_ID"
echo "Deployment Directory: $DEPLOY_DIR"

# Navigate to deployment directory
mkdir -p DEPLOY_DIR
cd DEPLOY_DIR

# Create .env file
cat <<EOF > .env
APP_ENV=$APP_ENV
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
EOF

echo "APP_ENV: $APP_ENV"

- name: Display .env file
  run: cat .env

# Secure the .env file
chmod 600 .env

cd "$DEPLOY_DIR/devops"

# Build the Docker images without using the cache
docker compose build --no-cache

# Start the containers and remove any orphaned containers
docker compose -p $DEPLOYMENT_ID up --force-recreate --remove-orphans -d
