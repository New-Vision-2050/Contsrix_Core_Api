# GitHub Secrets Configuration for CI/CD

This document lists all required GitHub secrets that must be configured in your repository for the deployment pipeline to work correctly.

## How to Add GitHub Secrets

1. Go to your GitHub repository
2. Navigate to **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Add each secret listed below

---

## Required Secrets

### Application Configuration

| Secret Name | Description | Example Value |
|------------|-------------|---------------|
| `DEPLOYMENT_ID` | Unique identifier for this deployment | `stage`, `production`, `pr-123` |
| `APP_ENV` | Application environment | `production`, `stage`, `development` |
| `APP_DEBUG` | Enable debug mode | `false` (production), `true` (development) |
| `APP_KEY` | Laravel application encryption key | `base64:xxxxx...` |
| `JWT_SECRET` | JWT token secret key | Generate using `php artisan jwt:secret` |

### Database Configuration

| Secret Name | Description | Example Value |
|------------|-------------|---------------|
| `DB_HOST` | Database host address | `mysql.example.com` or `127.0.0.1` |
| `DB_PORT` | Database port | `3306` |
| `DB_USERNAME` | Database username | `constrix_user` |
| `DB_PASSWORD` | Database password | `secure_password_here` |
| `DB_NAME` | Database name (base) | `constrix_db` |

### Email/SMTP Configuration

| Secret Name | Description | Example Value |
|------------|-------------|---------------|
| `MAIL_HOST` | SMTP server hostname | `smtp.hostinger.com` |
| `MAIL_PORT` | SMTP server port | `465` (SSL) or `587` (TLS) |
| `MAIL_USERNAME` | SMTP username/email | `noreply@example.com` |
| `MAIL_PASSWORD` | SMTP password | `smtp_password_here` |
| `MAIL_ENCRYPTION` | Encryption method | `tls` or `ssl` |
| `MAIL_FROM_ADDRESS` | Default sender email | `noreply@example.com` |

### AWS/Storage Configuration

| Secret Name | Description | Example Value |
|------------|-------------|---------------|
| `AWS_KEY` | AWS/DigitalOcean Spaces access key | `DO00XXXXXXXXXXXXX` |
| `AWS_SECRET` | AWS/DigitalOcean Spaces secret key | `xxxxxxxxxxxxxxxxxxxxx` |
| `AWS_DEFAULT_REGION` | AWS region (optional) | `us-east-1` |
| `AWS_ENDPOINT` | Storage endpoint (optional) | `https://constrix.fra1.digitaloceanspaces.com` |
| `AWS_USE_PATH_STYLE_ENDPOINT` | Path style endpoint (optional) | `true` |
| `MINIO_PUBLIC_BUCKET` | Public bucket name (optional) | `contrix` |
| `MINIO_PRIVATE_BUCKET` | Private bucket name (optional) | `contrix-archive-private` |

### Third-Party API Keys

| Secret Name | Description | Example Value |
|------------|-------------|---------------|
| `GOOGLE_MAPS_API_KEY` | Google Maps API key | `AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXX` |
| `SMS_MORA_KEY` | SMS Mora service API key | `xxxxxxxxxxxxxxxxxxxxxxxx` |
| `SMS_MORA_USER` | SMS Mora username | `your_username` |
| `SMS_MORA_SENDER` | SMS sender name | `YourCompany` |
| `OPENROUTER_API_KEY` | OpenRouter API key | `sk-or-v1-xxxxxxxxxxxxxxxx` |

### Firebase Configuration

| Secret Name | Description | Example Value |
|------------|-------------|---------------|
| `FIREBASE_CREDENTIALS` | Complete Firebase service account JSON | See below for format |

**Firebase Credentials Format:**
```json
{
  "type": "service_account",
  "project_id": "your-project-id",
  "private_key_id": "key-id",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "firebase-adminsdk-xxxxx@your-project.iam.gserviceaccount.com",
  "client_id": "123456789",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/..."
}
```

**How to get Firebase credentials:**
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project
3. Go to Project Settings → Service Accounts
4. Click "Generate New Private Key"
5. Copy the entire JSON content and paste it as the secret value

### Server Deployment Configuration

| Secret Name | Description | Example Value |
|------------|-------------|---------------|
| `SERVER_HOST` | Server hostname or IP | `your-server.com` or `192.168.1.100` |
| `SERVER_USER` | SSH username | `deployer` or `ubuntu` |
| `SSH_PRIVATE_KEY` | SSH private key for authentication | `-----BEGIN RSA PRIVATE KEY-----\n...\n-----END RSA PRIVATE KEY-----` |
| `SSH_PORT` | SSH port (optional) | `22` (default) |
| `DEPLOY_PATH` | Application path on server | `/var/www/html/constrix_api` |
| `WEB_USER` | Web server user | `www-data` (Ubuntu/Debian) or `nginx` (CentOS) |

### Optional Configuration

| Secret Name | Description | Default Value |
|------------|-------------|---------------|
| `APP_NAME` | Application name | `Constrix` |
| `APP_URL` | Application URL | `core-be-{DEPLOYMENT_ID}.constrix-nv.com` |
| `REPLICAS` | Number of container replicas | `1` |
| `DEPLOY_BASE_DIR` | Base deployment directory | `/home/deployer/laravel/deployments` |

---

## Environment-Specific Secrets

You can configure different secrets for different environments using GitHub Environments:

1. Go to **Settings** → **Environments**
2. Create environments: `production`, `stage`, `development`
3. Add environment-specific secrets to each

### Example: Production Environment

```yaml
# .github/workflows/deploy-production.yml
jobs:
  deploy:
    environment: production
    steps:
      - name: Deploy
        env:
          DEPLOYMENT_ID: production
          APP_ENV: production
          APP_DEBUG: false
          # ... other secrets from environment
```

---

## Validation

The deployment script (`deploy.sh`) automatically validates that all required secrets are present before deployment. If any secrets are missing, the deployment will fail with a clear error message listing the missing variables.

---

## Security Best Practices

1. **Never commit secrets** to your repository
2. **Rotate secrets regularly** (especially API keys and passwords)
3. **Use different secrets** for each environment (production, stage, development)
4. **Limit access** to secrets to only necessary team members
5. **Use strong passwords** for database and SMTP credentials
6. **Enable 2FA** on accounts that provide API keys

---

## Generating Required Keys

### Laravel APP_KEY
```bash
php artisan key:generate --show
```

### JWT Secret
```bash
php artisan jwt:secret --show
```

### Random Secure Password
```bash
openssl rand -base64 32
```

---

## Troubleshooting

### Deployment fails with "Missing required environment variables"

**Solution:** Check the error output to see which variables are missing, then add them to your GitHub secrets.

### Database connection fails

**Solution:** Verify `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`, and `DB_NAME` are correct.

### Email sending fails

**Solution:** Verify SMTP credentials and ensure the SMTP server allows connections from your deployment server IP.

### Storage/File upload fails

**Solution:** Verify AWS/DigitalOcean Spaces credentials and bucket names are correct.

---

## Example GitHub Actions Workflow

```yaml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    environment: production
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v3
      
      - name: Deploy to server
        env:
          DEPLOYMENT_ID: ${{ secrets.DEPLOYMENT_ID }}
          APP_ENV: ${{ secrets.APP_ENV }}
          APP_DEBUG: ${{ secrets.APP_DEBUG }}
          DB_HOST: ${{ secrets.DB_HOST }}
          DB_PORT: ${{ secrets.DB_PORT }}
          DB_USERNAME: ${{ secrets.DB_USERNAME }}
          DB_PASSWORD: ${{ secrets.DB_PASSWORD }}
          DB_NAME: ${{ secrets.DB_NAME }}
          APP_KEY: ${{ secrets.APP_KEY }}
          JWT_SECRET: ${{ secrets.JWT_SECRET }}
          MAIL_HOST: ${{ secrets.MAIL_HOST }}
          MAIL_PORT: ${{ secrets.MAIL_PORT }}
          MAIL_USERNAME: ${{ secrets.MAIL_USERNAME }}
          MAIL_PASSWORD: ${{ secrets.MAIL_PASSWORD }}
          MAIL_ENCRYPTION: ${{ secrets.MAIL_ENCRYPTION }}
          MAIL_FROM_ADDRESS: ${{ secrets.MAIL_FROM_ADDRESS }}
          AWS_KEY: ${{ secrets.AWS_KEY }}
          AWS_SECRET: ${{ secrets.AWS_SECRET }}
          GOOGLE_MAPS_API_KEY: ${{ secrets.GOOGLE_MAPS_API_KEY }}
          SMS_MORA_KEY: ${{ secrets.SMS_MORA_KEY }}
          SMS_MORA_USER: ${{ secrets.SMS_MORA_USER }}
          SMS_MORA_SENDER: ${{ secrets.SMS_MORA_SENDER }}
          OPENROUTER_API_KEY: ${{ secrets.OPENROUTER_API_KEY }}
        run: |
          ./devops/deploy.sh
```

---

## Notes

- All secrets marked as **(optional)** have default values in the deployment script
- Secrets are encrypted by GitHub and only exposed to authorized workflows
- You can update secrets at any time without redeploying
- Consider using GitHub Environments for better secret management across different deployment stages
