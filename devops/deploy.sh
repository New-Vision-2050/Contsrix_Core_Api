#!/bin/bash

set -e
set -x

# Generate a unique cache bust value using the current timestamp
CACHEBUST=$(date +%s)

# Generate a random suffix for the docker compose project name
RANDOM_SUFFIX=$(head /dev/urandom | tr -dc 'a-z0-9' | head -c 8)
DOCKER_NAMESPACE="${DEPLOYMENT_ID}-${RANDOM_SUFFIX}"
#DOCKER_NAMESPACE="${DEPLOYMENT_ID}"
# Export variables as environment variables so Docker Compose can use them
export CACHEBUST

DEPLOY_DIR=/home/deployer/laravel/deployments/$DEPLOYMENT_ID/code

echo "Deployment ID: $DEPLOYMENT_ID"
echo "Deployment Directory: $DEPLOY_DIR"
echo "Docker Namespace: $DOCKER_NAMESPACE"

# Navigate to deployment directory
mkdir -p $DEPLOY_DIR
cd $DEPLOY_DIR

# Find containers and images related to the existing deployment
OLD_CONTAINERS=$(docker ps -a --filter "name=${DEPLOYMENT_ID}-" --format "{{.ID}}" | tr '\n' ' ')
OLD_IMAGES=$(docker images --filter "reference=*${DEPLOYMENT_ID}-*" --format "{{.ID}}" | tr '\n' ' ')
echo "Found old containers: $OLD_CONTAINERS"
echo "Found old images: $OLD_IMAGES"

if [ "$APP_ENV" == "production" ]; then
     REPLICAS=1
     EMAIL_HOST=smtp.stackmail.com
     EMAIL_PORT=465
     EMAIL_HOST_USER=admin@constrix-nv.com
     EMAIL_HOST_PASSWORD="Ul6cade0c"
     EMAIL_ENCRYPTION=tls
     EMAIL_FROM_ADDRESS="admin@constrix.com"
elif [ "$APP_ENV" == "stage" ]; then
     REPLICAS=1
     EMAIL_HOST=smtp.stackmail.com
     EMAIL_PORT=465
     EMAIL_HOST_USER=admin@constrix-nv.com
     EMAIL_HOST_PASSWORD="Ul6cade0c"
     EMAIL_ENCRYPTION=tls
     EMAIL_FROM_ADDRESS="admin@constrix.com"
else
    REPLICAS=1
    EMAIL_HOST=smtp.stackmail.com
    EMAIL_PORT=465
    EMAIL_HOST_USER=admin@constrix-nv.com
    EMAIL_HOST_PASSWORD="Ul6cade0c"
    EMAIL_ENCRYPTION=tls
    EMAIL_FROM_ADDRESS="admin@constrix.com"
fi

export REPLICAS

APP_NAME="Constrix"
APP_URL="core-be-$DEPLOYMENT_ID.constrix-nv.com"

if [[ "$DEPLOYMENT_ID" == *"pr"* && "$APP_ENV" != "production" ]]; then
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
QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
CACHE_PREFIX=$DEPLOYMENT_ID
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
AWS_ACCESS_KEY_ID=$AWS_KEY
AWS_SECRET_ACCESS_KEY=$AWS_SECRET
AWS_DEFAULT_REGION=us-east-1
AWS_ENDPOINT=https://constrix.fra1.digitaloceanspaces.com
AWS_USE_PATH_STYLE_ENDPOINT=true
MINIO_PUBLIC_BUCKET=contrix
MINIO_PRIVATE_BUCKET=contrix-archive-private
GOOGLE_MAPS_API_KEY=AIzaSyD5izq7FZI-nHdrt6mx5UeKRkUSjvagS5g
SMS_MORA_KEY=9d036169a982498edbdcd92d99a838112546a986
SMS_MORA_USER=saadmashal
SMS_MORA_SENDER="Vision Dim"
OPENROUTER_API_KEY=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

EOF

echo "APP_ENV: $APP_ENV"

cat .env

# Secure the .env file
chmod 600 .env

cd "$DEPLOY_DIR/devops"

# Build the Docker images without using the cache
docker compose build --no-cache

# Start the containers with the new unique namespace and remove any orphaned containers
docker compose -p $DOCKER_NAMESPACE up --force-recreate --remove-orphans -d

# Wait for new containers to be fully up and running
echo "Waiting for new containers to stabilize..."
sleep 10

# Stop and remove old containers related to this deployment
if [ -n "$OLD_CONTAINERS" ]; then
    echo "Stopping and removing old containers for deployment $DEPLOYMENT_ID"
    for container_id in $OLD_CONTAINERS; do
        echo "Removing container $container_id"
        docker rm -f $container_id || true
    done
fi

# Remove old images related to this deployment
if [ -n "$OLD_IMAGES" ]; then
    echo "Removing old Docker images for deployment $DEPLOYMENT_ID"
    for img_id in $OLD_IMAGES; do
        # Check if the image is not being used by any container
        if ! docker ps -a --filter "ancestor=$img_id" --format "{{.ID}}" | grep -q .; then
            echo "Removing image $img_id"
            docker rmi -f $img_id || true
        else
            echo "Image $img_id is still in use, skipping removal"
        fi
    done
fi

# Clean up any dangling images
docker image prune -f --filter "until=24h"

# System cleanup functions
cleanup_server() {
    echo "Performing server cleanup..."

    # Clean up Docker resources
    echo "Cleaning up unused Docker resources..."
    # Remove all stopped containers
    docker container prune -f
    # Remove unused networks
    docker network prune -f
    # Remove unused volumes (use with caution)
    docker volume prune -f
    # Remove all dangling images
    docker image prune -f

    # Clean up system temp files
    echo "Cleaning up system temporary files..."
    # Clean apt cache
    if command -v apt-get &> /dev/null; then
        apt-get clean -y || true
        apt-get autoclean -y || true
    fi

    # Clean /tmp directory (files older than 7 days)
    find /tmp -type f -atime +7 -delete 2>/dev/null || true

    # Clean log files (keep some history)
    find /var/log -type f -name "*.gz" -delete 2>/dev/null || true
    find /var/log -type f -name "*.1" -delete 2>/dev/null || true

    # Check disk space after cleanup
    echo "Disk space after cleanup:"
    df -h /
}

# Run server cleanup
cleanup_server

echo "Deployment completed successfully with new docker namespace: $DOCKER_NAMESPACE"
