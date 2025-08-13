#!/bin/bash

# Container debugging script
# Usage: ./debug-container.sh [DEPLOYMENT_ID]

DEPLOYMENT_ID=${1:-production}
CONTAINER_PREFIX="backend-${DEPLOYMENT_ID}"

echo "=== Container Debugging Script ==="
echo "Deployment ID: $DEPLOYMENT_ID"
echo "Container Prefix: $CONTAINER_PREFIX"
echo ""

# Find containers
echo "=== Finding Containers ==="
CONTAINERS=$(docker ps -a --filter "name=${CONTAINER_PREFIX}" --format "table {{.ID}}\t{{.Names}}\t{{.Status}}\t{{.Ports}}")
echo "$CONTAINERS"
echo ""

# Get container IDs
CONTAINER_IDS=$(docker ps -a --filter "name=${CONTAINER_PREFIX}" --format "{{.ID}}")

if [ -z "$CONTAINER_IDS" ]; then
    echo "No containers found with prefix: $CONTAINER_PREFIX"
    exit 1
fi

for CONTAINER_ID in $CONTAINER_IDS; do
    echo "=== Container: $CONTAINER_ID ==="

    # Container info
    echo "--- Container Info ---"
    docker inspect $CONTAINER_ID --format='{{.Name}}: {{.State.Status}} ({{.State.ExitCode}})'
    docker inspect $CONTAINER_ID --format='Health: {{.State.Health.Status}}'
    docker inspect $CONTAINER_ID --format='Started: {{.State.StartedAt}}'
    docker inspect $CONTAINER_ID --format='Finished: {{.State.FinishedAt}}'
    echo ""

    # Recent logs
    echo "--- Recent Logs (last 50 lines) ---"
    docker logs --tail 50 $CONTAINER_ID
    echo ""

    # Health check logs if available
    echo "--- Health Check Logs ---"
    docker inspect $CONTAINER_ID --format='{{range .State.Health.Log}}{{.Start}}: {{.Output}}{{end}}' 2>/dev/null || echo "No health check logs available"
    echo ""

    # Process list if container is running
    if [ "$(docker inspect $CONTAINER_ID --format='{{.State.Status}}')" == "running" ]; then
        echo "--- Running Processes ---"
        docker exec $CONTAINER_ID ps aux 2>/dev/null || echo "Cannot access running processes"
        echo ""

        # Disk usage
        echo "--- Disk Usage ---"
        docker exec $CONTAINER_ID df -h 2>/dev/null || echo "Cannot access disk usage"
        echo ""

        # Memory usage
        echo "--- Memory Usage ---"
        docker exec $CONTAINER_ID free -h 2>/dev/null || echo "Cannot access memory usage"
        echo ""

        # Laravel logs
        echo "--- Laravel Logs (last 20 lines) ---"
        docker exec $CONTAINER_ID tail -20 /var/www/storage/logs/laravel.log 2>/dev/null || echo "No Laravel logs found"
        echo ""

        # Supervisor logs
        echo "--- Supervisor Logs ---"
        docker exec $CONTAINER_ID tail -20 /var/log/supervisord.log 2>/dev/null || echo "No supervisor logs found"
        echo ""

        # PHP-FPM logs
        echo "--- PHP-FPM Logs ---"
        docker exec $CONTAINER_ID tail -10 /var/log/php-fpm_err.log 2>/dev/null || echo "No PHP-FPM error logs found"
        echo ""

        # Nginx logs
        echo "--- Nginx Error Logs ---"
        docker exec $CONTAINER_ID tail -10 /var/log/nginx_error.log 2>/dev/null || echo "No Nginx error logs found"
        echo ""
    fi

    echo "=================================="
    echo ""
done

# Docker system info
echo "=== Docker System Info ==="
docker system df
echo ""

echo "=== Docker Events (last 10) ==="
docker events --since 10m --until now --filter "container=${CONTAINER_PREFIX}" 2>/dev/null || echo "No recent events found"
echo ""

echo "=== Debugging Complete ==="
