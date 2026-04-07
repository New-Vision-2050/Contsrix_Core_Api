# GitHub Workflow APP_KEY Setup

## Current Setup

Your GitHub workflow (`.github/workflows/ci_cd.yml`) already handles APP_KEY deployment through GitHub Secrets.

## Two Scenarios

### Scenario 1: Using GitHub Secret (Recommended for Production)

**For production/stage/dev branches:**

1. **Set GitHub Secret** (if not already set):
   - Go to your repository → Settings → Secrets and variables → Actions
   - Add/Update secret: `APP_KEY`
   - Generate value locally: `php artisan key:generate --show`
   - Copy the output (e.g., `base64:abcd1234...`)

2. **Workflow automatically injects it**:
   - The workflow passes `APP_KEY` from secrets to the deployment
   - `deploy.sh` writes it to `.env` file (line 139)
   - Container starts with the APP_KEY already set

3. **Entrypoint behavior**:
   - Checks if APP_KEY is valid
   - Since it's already set from GitHub secret, it logs: "APP_KEY already set"
   - No generation needed ✅

### Scenario 2: Auto-Generation (For Testing/PR Deployments)

**If APP_KEY secret is not set or empty:**

1. **Workflow runs without APP_KEY**:
   - `deploy.sh` creates `.env` with empty or missing APP_KEY line
   - Container builds and starts

2. **Entrypoint automatically handles it**:
   ```
   [2026-04-07 14:53:00] Checking APP_KEY...
   [2026-04-07 14:53:01] APP_KEY line not found in .env. Adding it...
   [2026-04-07 14:53:02] APP_KEY not set or invalid. Generating new APP_KEY...
   [2026-04-07 14:53:03] APP_KEY generated successfully
   ```

3. **Container continues normally** ✅

## Current Workflow Configuration

### Environment Variables Passed (Line 102-118):
```yaml
env:
  APP_KEY: ${{ secrets.APP_KEY }}  # ← Passed from GitHub Secrets
  DB_HOST: ${{ secrets.DB_HOST }}
  # ... other secrets
```

### Deploy Script Creates .env (Line 124-162):
```bash
cat <<EOF > .env
APP_KEY="${APP_KEY}"  # ← Written to .env file
# ... other variables
EOF
```

### Entrypoint Validates (entrypoint.sh):
```bash
# Adds APP_KEY line if missing
if ! grep -q "^APP_KEY=" /var/www/.env; then
    echo "APP_KEY=" >> /var/www/.env
fi

# Generates if empty/invalid
if ! grep -q "^APP_KEY=base64:" /var/www/.env; then
    php artisan key:generate --force
fi
```

## Recommendations

### For Production/Stage/Dev Branches:
✅ **Keep using GitHub Secrets** - More secure and consistent
- Set `APP_KEY` secret in GitHub repository settings
- Generate once: `php artisan key:generate --show`
- Use the same key across deployments for data consistency

### For PR Deployments:
✅ **Auto-generation works fine** - Each PR gets its own key
- No need to set APP_KEY secret for PRs
- Entrypoint generates it automatically
- PR environments are temporary anyway

## How to Set GitHub Secret

### Generate APP_KEY locally:
```bash
# In your local Laravel project
php artisan key:generate --show
```

Output example:
```
base64:abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGH==
```

### Add to GitHub:
1. Go to: `https://github.com/YOUR_ORG/YOUR_REPO/settings/secrets/actions`
2. Click "New repository secret"
3. Name: `APP_KEY`
4. Value: `base64:abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGH==`
5. Click "Add secret"

## Verification

### Check if APP_KEY secret is set:
The workflow already has a debug step (line 38-44):
```yaml
- name: Debug Secrets
  run: |
    echo "APP_KEY is set: ${{ secrets.APP_KEY != '' }}"
```

### Check container logs:
```bash
# SSH to your deployment server
ssh deployer@your-server

# View container logs
docker logs <container-name> | grep APP_KEY
```

You should see:
- If secret is set: `APP_KEY already set`
- If not set: `APP_KEY generated successfully`

## Troubleshooting

### Issue: "No APP_KEY variable was found"
**Solution**: This is now fixed by the updated `entrypoint.sh`
- The script adds the APP_KEY line if missing
- Then generates the key automatically

### Issue: Different APP_KEY on each deployment
**Cause**: Not using GitHub secret, auto-generating each time
**Impact**: 
- ⚠️ Encrypted data from previous deployment won't decrypt
- ⚠️ Sessions will be invalidated

**Solution**: Set APP_KEY as GitHub secret for consistency

### Issue: Want to rotate APP_KEY
**Steps**:
1. Generate new key: `php artisan key:generate --show`
2. Update GitHub secret with new value
3. Redeploy
4. ⚠️ Note: Old encrypted data won't decrypt with new key

## Security Best Practices

✅ **DO:**
- Use GitHub Secrets for production APP_KEY
- Generate strong keys using `php artisan key:generate`
- Keep the same APP_KEY across deployments
- Rotate keys periodically (with migration plan)

❌ **DON'T:**
- Commit APP_KEY to version control
- Share APP_KEY in plain text
- Use weak or predictable keys
- Change APP_KEY without planning for encrypted data

## Files Involved

1. **`.github/workflows/ci_cd.yml`** - Passes APP_KEY from secrets
2. **`devops/deploy.sh`** - Writes APP_KEY to .env file
3. **`devops/entrypoint.sh`** - Validates and generates if needed
4. **`.env.example`** - Template with placeholder

## Summary

Your setup is now **fully automated and fault-tolerant**:

1. ✅ If GitHub secret `APP_KEY` is set → Uses it
2. ✅ If GitHub secret is missing/empty → Auto-generates
3. ✅ If .env is missing APP_KEY line → Adds it automatically
4. ✅ Container always starts successfully with valid APP_KEY

No manual intervention needed! 🚀
