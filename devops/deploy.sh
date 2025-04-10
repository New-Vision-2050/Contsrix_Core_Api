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
     EMAIL_HOST=vision-dashbord.com
     EMAIL_PORT=465
     EMAIL_HOST_USER=info@vision-dashbord.com
     EMAIL_HOST_PASSWORD="0;Kl=0G]v%]8"
     EMAIL_ENCRYPTION=tls
     EMAIL_FROM_ADDRESS="info@vision-dashbord.com"
elif [ "$APP_ENV" == "stage" ]; then
    EMAIL_HOST=vision-dashbord.com
    EMAIL_PORT=465
    EMAIL_HOST_USER=info@vision-dashbord.com
    EMAIL_HOST_PASSWORD="0;Kl=0G]v%]8"
    EMAIL_ENCRYPTION=tls
    EMAIL_FROM_ADDRESS="info@vision-dashbord.com"
else
    EMAIL_HOST="mailcatcher"
    EMAIL_HOST_USER=""
    EMAIL_HOST_PASSWORD=""
    EMAIL_PORT=1025
    EMAIL_ENCRYPTION=
    EMAIL_FROM_ADDRESS=""
     #EMAIL_HOST=vision-dashbord.com
     #EMAIL_PORT=465
     #EMAIL_HOST_USER=info@vision-dashbord.com
     #EMAIL_HOST_PASSWORD="0;Kl=0G]v%]8"
     #EMAIL_ENCRYPTION=tls
     #EMAIL_FROM_ADDRESS="info@vision-dashbord.com"
fi

APP_NAME="Constrix"
APP_URL="core-be-$DEPLOYMENT_ID.constrix-nv.com"

if [[ "$DEPLOYMENT_ID" == *"pr"* ]]; then
  DB_NAME="$DB_NAME-pr"
else
  DB_NAME="$DB_NAME-$DEPLOYMENT_ID"
fi

# Create .env file
cat <<EOF > .env
APP_NAME=$APP_NAME
APP_ENV=$APP_ENV
APP_URL=$APP_URL
APP_DEBUG=$APP_DEBUG
DB_CONNECTION=mysql
DB_HOST=$DB_HOST
DB_PORT=$DB_PORT
DB_USERNAME=$DB_USERNAME
DB_PASSWORD=$DB_PASSWORD
DB_DATABASE=$DB_NAME
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
CACHE_STORE=redis
CACHE_PREFIX=$DEPLOYMENT_ID
REDIS_HOST=redis
APP_KEY=$APP_KEY
JWT_SECRET=$JWT_SECRET
MAIL_MAILER=smtp
MAIL_HOST=$EMAIL_HOST
MAIL_PORT=$EMAIL_PORT
MAIL_USERNAME=$EMAIL_HOST_USER
MAIL_PASSWORD=$EMAIL_HOST_PASSWORD
MAIL_ENCRYPTION=$EMAIL_ENCRYPTION
MAIL_FROM_ADDRESS=$EMAIL_FROM_ADDRESS
MAIL_FROM_NAME=$APP_NAME
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
