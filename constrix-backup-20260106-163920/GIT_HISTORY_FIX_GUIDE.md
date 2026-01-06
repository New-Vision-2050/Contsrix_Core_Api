# Git History Security Fix Guide

## ⚠️ CRITICAL WARNING

This operation will:
- **Rewrite Git history** for ALL branches
- **Require force push** to remote
- **Require all team members** to reset their local repositories
- **Cannot be undone** (except from backup)

## Prerequisites

### 1. Install Python and git-filter-repo

**Windows:**
```powershell
# Install Python (if not already installed)
# Download from: https://www.python.org/downloads/

# Install git-filter-repo via pip
pip install git-filter-repo

# OR download directly
# https://github.com/newren/git-filter-repo/releases
```

**Linux/Mac:**
```bash
pip3 install git-filter-repo
```

### 2. Coordinate with Team

**Send this message to ALL team members BEFORE starting:**

```
🔒 SCHEDULED MAINTENANCE - DO NOT PUSH

We will be performing a security update on the Git repository.

⏰ Scheduled time: [DATE/TIME]
⏱️ Duration: ~30 minutes

📋 What to do:
1. Commit and push all your work NOW
2. Do not push during the maintenance window
3. After maintenance, you will receive instructions to reset your local branches

🚫 Stop all development and pushes at: [TIME]
```

## Execution Steps

### For Windows (PowerShell/Git Bash)

**Option A: Using Git Bash (Recommended)**

1. Open Git Bash in the repository folder
2. Make executable:
   ```bash
   chmod +x devops/fix-git-history.sh
   ```
3. Run the script:
   ```bash
   ./devops/fix-git-history.sh
   ```

**Option B: Using PowerShell**

1. Open PowerShell as Administrator
2. Navigate to repository:
   ```powershell
   cd C:\projects\constrix-microservices\constrix_api
   ```
3. Install WSL (Windows Subsystem for Linux) if needed:
   ```powershell
   wsl --install
   ```
4. Run in WSL:
   ```powershell
   wsl bash devops/fix-git-history.sh
   ```

**Option C: Manual Steps (Windows)**

If the script doesn't work, follow these manual steps:

```powershell
# 1. Create backup
cd ..
Copy-Item -Path "constrix_api" -Destination "constrix_api_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')" -Recurse
cd constrix_api

# 2. Install git-filter-repo
pip install git-filter-repo

# 3. Create replacement file
@"
G:1Wc;c;L9b==>***REMOVED***
vision@speedpharma.link==>***REMOVED***
AIzaSyD5izq7FZI-nHdrt6mx5UeKRkUSjvagS5g==>***REMOVED***
9d036169a982498edbdcd92d99a838112546a986==>***REMOVED***
saadmashal==>***REMOVED***
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==>***REMOVED***
Vision Dim==>***REMOVED***
"@ | Out-File -FilePath "secrets-replacement.txt" -Encoding UTF8

# 4. Fetch all branches
git fetch --all

# 5. Run git-filter-repo
git filter-repo --replace-text secrets-replacement.txt --force

# 6. Add remote back (filter-repo removes it)
git remote add origin YOUR_REMOTE_URL

# 7. Force push all branches
git push origin --force --all
git push origin --force --tags

# 8. Cleanup
Remove-Item secrets-replacement.txt
```

### For Linux/Mac

```bash
cd /path/to/constrix_api
chmod +x devops/fix-git-history.sh
./devops/fix-git-history.sh
```

## What the Script Does

### Step 1: Backup
Creates a complete backup of your repository in case something goes wrong.

### Step 2: Confirmation
Asks you to confirm the operation by typing "YES".

### Step 3: Install Dependencies
Checks for and installs `git-filter-repo` if needed.

### Step 4: Create Replacement Patterns
Creates a file that maps secrets to `***REMOVED***`.

### Step 5: Rewrite History
Scans every commit in every branch and replaces secrets.

### Step 6: Verify
Checks if any secrets remain in the history.

### Step 7: Force Push
Pushes the cleaned history to GitHub (requires typing "PUSH").

### Step 8: Cleanup
Removes temporary files.

## Post-Execution - Team Instructions

After the script completes, **send this to ALL team members:**

---

### 🔒 Git Repository Updated - Action Required

The Git repository has been cleaned for security. **You must reset your local branches.**

#### Option 1: Reset Existing Clone (Faster)

```bash
# Save any uncommitted work first!
git stash

# Fetch the cleaned history
git fetch origin

# For each branch you're working on:
git checkout branch-name
git reset --hard origin/branch-name

# Restore your work
git stash pop
```

#### Option 2: Fresh Clone (Safest)

```bash
# Backup your work
cd ..
mv constrix_api constrix_api_old

# Clone fresh
git clone [repository-url] constrix_api

# Copy any local-only work from constrix_api_old if needed
```

#### Verify Your Reset Worked

```bash
# This should show no secrets
git log --all -S "G:1Wc;c;L9b"
# Should return nothing

# Check your current branch is clean
git status
```

---

## Post-Execution - Critical Security Steps

### 1. Rotate ALL Credentials Immediately

| Credential | How to Rotate |
|------------|---------------|
| **Database Password** | Change in database, update GitHub secret `DB_PASSWORD` |
| **APP_KEY** | `php artisan key:generate --show`, update `APP_KEY` secret |
| **JWT_SECRET** | `php artisan jwt:secret --show`, update `JWT_SECRET` secret |
| **Email Password** | Change in email provider, update `MAIL_PASSWORD` secret |
| **AWS Keys** | Rotate in DigitalOcean Spaces, update `AWS_KEY` and `AWS_SECRET` |
| **Google Maps API** | Regenerate at console.cloud.google.com, update `GOOGLE_MAPS_API_KEY` |
| **SMS Mora API** | Contact SMS Mora support, update `SMS_MORA_KEY` |
| **OpenRouter API** | Regenerate at openrouter.ai, update `OPENROUTER_API_KEY` |

### 2. Update All GitHub Secrets

Go to: `https://github.com/YOUR_ORG/YOUR_REPO/settings/secrets/actions`

Update each secret with the new values.

### 3. Test Deployments

```bash
# Test each environment
git checkout dev
git push origin dev
# Watch GitHub Actions

git checkout stage
git push origin stage
# Watch GitHub Actions

# Only after successful stage deployment:
git checkout production
git push origin production
# Watch GitHub Actions
```

## Troubleshooting

### Error: "git-filter-repo: command not found"

**Fix:**
```bash
pip install git-filter-repo
# Or
pip3 install git-filter-repo
```

### Error: "remote 'origin' already exists"

The remote wasn't removed by filter-repo. Skip the `git remote add` step.

### Error: "Updates were rejected"

Someone pushed during the operation. Coordinate with team to stop pushes, then retry.

### Script hangs or takes too long

Large repositories may take 10-30 minutes. Be patient. Check progress with:
```bash
# In another terminal
git log --oneline | wc -l
```

### Need to Rollback

```bash
# From backup
cd ..
rm -rf constrix_api
mv constrix_api_backup_TIMESTAMP constrix_api
cd constrix_api

# Reset remote
git push origin --force --all
```

## Verification Checklist

After completion, verify:

- [ ] Script completed without errors
- [ ] Backup exists at the specified location
- [ ] Force push completed successfully
- [ ] Secrets removed from history:
  ```bash
  git log --all -S "G:1Wc;c;L9b" # Should be empty
  git log --all -S "vision@speedpharma.link" # Should be empty
  git log --all -S "AIzaSyD5izq7FZI" # Should be empty
  ```
- [ ] All team members notified
- [ ] All credentials rotated
- [ ] All GitHub secrets updated
- [ ] Test deployment successful on dev
- [ ] Test deployment successful on stage
- [ ] Production deployment planned

## Timeline

**Recommended execution timeline:**

1. **Day 1 - Preparation**
   - Read this guide
   - Test on a fork/test repository
   - Schedule maintenance window
   - Notify team members

2. **Day 2 - Execution (Maintenance Window)**
   - Stop all team pushes (15 mins before)
   - Run the script (30-60 mins)
   - Verify results
   - Update GitHub secrets
   - Notify team to reset

3. **Day 3 - Verification**
   - Confirm all team members reset
   - Rotate credentials
   - Test all environments
   - Monitor for issues

4. **Day 4 - Production Deploy**
   - Deploy to production with new secrets
   - Monitor for issues
   - Mark incident as resolved

## Support

If you encounter issues:

1. Check the backup is intact
2. Review error messages carefully
3. Search git-filter-repo documentation
4. Consider rolling back and retrying
5. Consult with team before proceeding

## References

- [git-filter-repo Documentation](https://github.com/newren/git-filter-repo)
- [GitHub Secrets Documentation](https://docs.github.com/en/actions/security-guides/encrypted-secrets)
- [Git History Rewriting Guide](https://git-scm.com/book/en/v2/Git-Tools-Rewriting-History)
