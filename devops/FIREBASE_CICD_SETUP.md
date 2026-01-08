# Firebase Credentials - CI/CD Setup Guide

## Overview
This guide explains how to configure Firebase credentials for automatic deployment via GitHub Actions.

---

## Step 1: Get Firebase Credentials

1. **Go to Firebase Console:**
   - URL: https://console.firebase.google.com/
   - Select your project (e.g., `constrix-45f2f`)

2. **Navigate to Service Accounts:**
   - Click on **Project Settings** (gear icon)
   - Go to **Service accounts** tab

3. **Generate New Private Key:**
   - Click **"Generate new private key"**
   - Click **"Generate key"** in the confirmation dialog
   - A JSON file will be downloaded

4. **Open the JSON file** and copy its entire contents

---

## Step 2: Add to GitHub Secrets

1. **Go to your GitHub repository:**
   - Navigate to: `Settings` → `Secrets and variables` → `Actions`

2. **Create new secret:**
   - Click **"New repository secret"**
   - Name: `FIREBASE_CREDENTIALS`
   - Value: Paste the **entire JSON content** from the downloaded file
   - Click **"Add secret"**

3. **Add server deployment secrets** (if not already added):

| Secret Name | Description | How to Get |
|------------|-------------|------------|
| `SERVER_HOST` | Your server IP or domain | From your hosting provider |
| `SERVER_USER` | SSH username | Usually `ubuntu`, `deployer`, or `root` |
| `SSH_PRIVATE_KEY` | SSH private key | Generate with `ssh-keygen` |
| `SSH_PORT` | SSH port (optional) | Default is `22` |
| `DEPLOY_PATH` | App path on server | e.g., `/var/www/html/constrix_api` |
| `WEB_USER` | Web server user | Usually `www-data` or `nginx` |

---

## Step 3: Generate SSH Key (If Needed)

If you don't have an SSH key for deployment:

```bash
# On your local machine
ssh-keygen -t rsa -b 4096 -C "github-actions-deploy" -f ~/.ssh/github_deploy

# Copy the public key to your server
ssh-copy-id -i ~/.ssh/github_deploy.pub user@your-server

# Copy the private key content for GitHub secret
cat ~/.ssh/github_deploy
# Copy the entire output including BEGIN and END lines
```

Then add the private key content to GitHub secret `SSH_PRIVATE_KEY`.

---

## Step 4: Verify GitHub Secrets

Your GitHub secrets should now include:

### Required Secrets:
- ✅ `FIREBASE_CREDENTIALS` - Complete Firebase JSON
- ✅ `SERVER_HOST` - Server address
- ✅ `SERVER_USER` - SSH username
- ✅ `SSH_PRIVATE_KEY` - SSH private key

### Optional Secrets:
- `SSH_PORT` - Default: 22
- `DEPLOY_PATH` - Default: `/var/www/html/constrix_api`
- `WEB_USER` - Default: `www-data`

---

## Step 5: How It Works

When you push to `main`, `stage`, or `production` branches:

1. **GitHub Actions triggers** the deployment workflow
2. **Connects to your server** via SSH
3. **Creates Firebase credentials file** at `public/firebase_credentials.json`
4. **Sets secure permissions** (600, owned by web server user)
5. **Validates JSON format** and extracts project ID
6. **Runs deployment tasks** (composer, migrations, cache)
7. **Verifies deployment** success

---

## Step 6: Test Deployment

### Manual Trigger:
1. Go to: `Actions` tab in GitHub
2. Select: `Deploy to Server` workflow
3. Click: `Run workflow`
4. Select branch and click `Run workflow`

### Automatic Trigger:
```bash
# Make a change and push
git add .
git commit -m "Test deployment"
git push origin stage
```

Watch the deployment in the `Actions` tab.

---

## Security Features

### ✅ Implemented:
- Firebase credentials stored as GitHub secret (encrypted)
- File created on server with restrictive permissions (600)
- Owned by web server user only
- JSON validation before deployment
- Never committed to Git repository
- Automatic cleanup if deployment fails

### 🔒 File Permissions on Server:
```bash
-rw------- 1 www-data www-data firebase_credentials.json
```
Only the web server can read the file.

---

## Troubleshooting

### Deployment Fails: "Invalid JSON"
**Solution:** Check that your `FIREBASE_CREDENTIALS` secret contains valid JSON:
- Must start with `{` and end with `}`
- No extra characters or formatting
- Copy the entire content from Firebase Console

### Deployment Fails: "Permission Denied"
**Solution:** Verify SSH key is correct:
```bash
# Test SSH connection
ssh -i ~/.ssh/github_deploy user@your-server

# If it works, the key is correct
```

### Firebase File Not Created
**Solution:** Check server permissions:
```bash
# SSH into server
ssh user@your-server

# Check directory permissions
ls -la /var/www/html/constrix_api/public/

# Should be writable by deployment user
```

### "jq: command not found"
**Solution:** Install jq on your server (optional, for validation):
```bash
# Ubuntu/Debian
sudo apt-get install jq

# CentOS/RHEL
sudo yum install jq
```

---

## Verification Commands

After deployment, verify on your server:

```bash
# SSH into server
ssh user@your-server

# Navigate to app directory
cd /var/www/html/constrix_api

# Check file exists
ls -la public/firebase_credentials.json

# Should show:
# -rw------- 1 www-data www-data 1234 Jan 8 17:00 firebase_credentials.json

# Verify JSON is valid
cat public/firebase_credentials.json | jq .project_id

# Should output your project ID, e.g., "constrix-45f2f"

# Test from Laravel
php artisan tinker
>>> file_exists(public_path('firebase_credentials.json'))
=> true
>>> $creds = json_decode(file_get_contents(public_path('firebase_credentials.json')), true);
>>> $creds['project_id']
=> "constrix-45f2f"
```

---

## Workflow File Location

The deployment workflow is located at:
```
.github/workflows/deploy.yml
```

It automatically:
- Pulls latest code
- Creates Firebase credentials
- Installs dependencies
- Runs migrations
- Clears and caches config
- Sets proper permissions
- Verifies deployment

---

## Best Practices

1. ✅ **Rotate credentials regularly** (every 90 days)
2. ✅ **Use different credentials** for dev/stage/production
3. ✅ **Monitor Firebase usage** for suspicious activity
4. ✅ **Keep GitHub secrets updated** when rotating
5. ✅ **Test deployments** in staging before production
6. ✅ **Review deployment logs** in GitHub Actions
7. ✅ **Backup credentials** in secure password manager

---

## Support

For issues:
- Check GitHub Actions logs for detailed error messages
- Verify all secrets are correctly configured
- Test SSH connection manually
- Review Firebase Console for API errors

---

## Quick Reference

### Add Secret to GitHub:
```
Repository → Settings → Secrets and variables → Actions → New repository secret
```

### Trigger Deployment:
```bash
git push origin main    # Automatic
# OR
Actions → Deploy to Server → Run workflow  # Manual
```

### Check Deployment Status:
```
Repository → Actions → Latest workflow run
```

### Verify on Server:
```bash
ssh user@server
ls -la /var/www/html/constrix_api/public/firebase_credentials.json
```

---

**✅ Setup Complete!** Your Firebase credentials will now be automatically deployed with every push.
