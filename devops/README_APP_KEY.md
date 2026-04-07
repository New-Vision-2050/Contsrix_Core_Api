# APP_KEY Quick Reference

## 🎯 What You Need to Know

Your deployment now **automatically handles APP_KEY** - no manual intervention needed!

## ✅ Current Status

- ✅ **Entrypoint script** automatically adds APP_KEY line if missing
- ✅ **Auto-generates** APP_KEY if empty or invalid
- ✅ **GitHub workflow** passes APP_KEY from secrets (if set)
- ✅ **Fallback** to auto-generation if secret not configured

## 🚀 For GitHub Workflow Deployments

### Option 1: Use GitHub Secret (Recommended for Production)

```bash
# Generate locally
php artisan key:generate --show

# Output: base64:abcd1234...
# Add this to GitHub Secrets as APP_KEY
```

**GitHub Repository Settings:**
- Go to: Settings → Secrets and variables → Actions
- Add secret: `APP_KEY` = `base64:your-generated-key`

### Option 2: Auto-Generate (Good for PR/Testing)

**Do nothing!** The container will:
1. Detect missing/empty APP_KEY
2. Add the line to .env
3. Run `php artisan key:generate --force`
4. Continue deployment

## 📋 Deployment Flow

```
GitHub Workflow
    ↓
deploy.sh creates .env
    ↓ (writes APP_KEY from secret or empty)
Docker build
    ↓
Container starts
    ↓
entrypoint.sh runs
    ↓
Checks APP_KEY
    ├─ Valid? → Continue ✅
    └─ Missing/Invalid? → Generate → Continue ✅
```

## 🔍 How to Verify

### Check GitHub Secret:
```yaml
# In workflow output, look for:
"APP_KEY is set: true"  # ← Secret is configured
"APP_KEY is set: false" # ← Will auto-generate
```

### Check Container Logs:
```bash
docker logs <container-name> | grep APP_KEY

# Expected output:
[2026-04-07 14:53:00] Checking APP_KEY...
[2026-04-07 14:53:01] APP_KEY already set  # ← From secret
# OR
[2026-04-07 14:53:01] APP_KEY generated successfully  # ← Auto-generated
```

## 📚 Documentation

- **General Setup**: [APP_KEY_SETUP.md](./APP_KEY_SETUP.md)
- **GitHub Workflow**: [GITHUB_WORKFLOW_SETUP.md](./GITHUB_WORKFLOW_SETUP.md)

## ⚠️ Important Notes

### For Production:
- **Use GitHub Secret** for consistency
- Same APP_KEY across deployments = encrypted data remains valid
- Changing APP_KEY = old encrypted data won't decrypt

### For PR/Testing:
- **Auto-generation is fine** - each PR gets unique key
- PR environments are temporary
- No need to set GitHub secret

## 🛠️ Troubleshooting

### Error: "No APP_KEY variable was found"
**Status**: ✅ FIXED
- Updated `entrypoint.sh` now adds the line automatically

### Want to manually generate?
```bash
# Inside container
docker exec -it <container-name> php artisan key:generate --force
```

### Want to use specific key?
1. Generate: `php artisan key:generate --show`
2. Add to GitHub Secrets as `APP_KEY`
3. Redeploy

## 🎉 Summary

**You don't need to do anything!** The system is fully automated:

- ✅ GitHub secret set? → Uses it
- ✅ GitHub secret empty? → Generates automatically
- ✅ .env missing APP_KEY? → Adds it automatically
- ✅ Container always starts successfully

Just push your code and deploy! 🚀
