# Production Deployment Guide for Laravel Reverb

This guide explains how to configure GitHub Actions and Laravel Reverb for production. The necessary code updates to the `devops/` folder and `.github/workflows/ci_cd.yml` have already been applied to your repository.

## 1. Setting Up GitHub Repository Secrets
To pass the keys securely into your production environment, you must add them as **Repository Secrets** in your GitHub repository.

1. Open your GitHub Repository in the browser.
2. Go to **Settings** > **Secrets and variables** > **Actions**.
3. Click on **New repository secret**.
4. Add the following three secrets using the same values from your local `.env` file:

| Secret Name | Example Value (from your local .env) |
|-------------|--------------------------------------|
| `REVERB_APP_ID` | `284073` |
| `REVERB_APP_KEY` | `2g5vxr2uqayt53tvzyc1` |
| `REVERB_APP_SECRET` | `gtguvezyildfhxdjlgte` |

*Note: For maximum security, you should generate new keys for production rather than reusing your local ones. However, copying the local ones will work immediately.*

## 2. Changes Made to the Infrastructure

I've already updated the following files to support Reverb in production seamlessly:

### A. Supervisor (`devops/supervisord.conf`)
Added the `[program:reverb]` process to automatically start Reverb when the Docker container boots up:
```ini
[program:reverb]
command=php /var/www/artisan reverb:start --host=0.0.0.0 --port=8080
directory=/var/www
autostart=true
autorestart=true
```

### B. Nginx (`devops/nginx/sites-enabled/default.conf`)
Added reverse proxy configurations to intercept WebSocket traffic on `/app` and `/apps` and send it to Reverb on port `8080`, bypassing Octane:
```nginx
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection $connection_upgrade;
    ...
}
```

### C. Deployment Script (`devops/deploy.sh`)
Updated the script that generates the `.env` file inside the Docker container to include the Reverb configuration:
```bash
BROADCAST_CONNECTION=reverb
REVERB_APP_ID="${REVERB_APP_ID}"
REVERB_APP_KEY="${REVERB_APP_KEY}"
REVERB_APP_SECRET="${REVERB_APP_SECRET}"
REVERB_HOST="0.0.0.0"
REVERB_PORT=8080
REVERB_SCHEME="https" # Managed via Traefik
```

### D. GitHub Actions (`.github/workflows/ci_cd.yml`)
Updated the SSH deployment script to properly inject the GitHub secrets as environment variables into the server during the deployment phase.

## 3. Frontend Configuration for Production

When you deploy your frontend, the `Echo` configuration needs to be slightly adjusted for production because it will be running over `https/wss` through Traefik and Nginx.

```javascript
window.echo = new Echo({
    broadcaster: 'reverb',
    key: '2g5vxr2uqayt53tvzyc1', // Provide your REVERB_APP_KEY
    wsHost: 'core-be-production.constrix-nv.com', // Your production domain
    wsPort: 443, // Standard HTTPS port (Traefik handles this)
    wssPort: 443,
    forceTLS: true, // Crucial for production
    enabledTransports: ['ws', 'wss'],
});
```

## 4. Next Steps
Once you've added the secrets to GitHub:
1. Commit and push these changes to your `dev` or `production` branch.
2. The GitHub Action will run, inject the secrets, build the `.env` file, and restart the Docker container.
3. Supervisor will automatically boot up the `queue:work` and `reverb:start` processes inside the container.
