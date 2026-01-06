# Manual Git History Fix - Step by Step

If the scripts don't work, follow these manual steps:

## Prerequisites

```powershell
# 1. Install git-filter-repo
pip install git-filter-repo
```

## Step-by-Step Execution

### Step 1: Backup Your Repository

```powershell
cd C:\projects\constrix-microservices
Copy-Item -Path "constrix_api" -Destination "constrix_api_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')" -Recurse
cd constrix_api
```

### Step 2: Create Replacement File

```powershell
@"
G:1Wc;c;L9b==>***REMOVED***
vision@speedpharma.link==>***REMOVED***
AIzaSyD5izq7FZI-nHdrt6mx5UeKRkUSjvagS5g==>***REMOVED***
9d036169a982498edbdcd92d99a838112546a986==>***REMOVED***
saadmashal==>***REMOVED***
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==>***REMOVED***
Vision Dim==>***REMOVED***
"@ | Out-File -FilePath "secrets-replacement.txt" -Encoding UTF8
```

### Step 3: Fetch All Branches

```powershell
git fetch --all
```

### Step 4: Run git-filter-repo

```powershell
# This will rewrite ALL history
git filter-repo --replace-text secrets-replacement.txt --force
```

**Expected output:**
```
Parsed X commits
New history written in Y seconds; now repacking/cleaning...
Repacking objects... done
Cleaning... done
```

### Step 5: Add Remote Back

git-filter-repo removes the remote, so add it back:

```powershell
# Get your remote URL first
git remote -v
# If empty, add it back:
git remote add origin https://github.com/YOUR_ORG/YOUR_REPO.git
```

### Step 6: Verify Secrets Are Removed

```powershell
# Search for secrets in history - should return nothing
git log --all -S "G:1Wc;c;L9b"
git log --all -S "vision@speedpharma.link"
git log --all -S "AIzaSyD5izq7FZI"
```

If these commands return nothing, secrets are removed! ✅

### Step 7: Force Push to Remote

**⚠️ WARNING: This overwrites remote history!**

```powershell
# Push all branches
git push origin --force --all

# Push all tags
git push origin --force --tags
```

### Step 8: Cleanup

```powershell
Remove-Item secrets-replacement.txt
```

### Step 9: Verify on GitHub

1. Go to your repository on GitHub
2. Check commit history
3. View old commits - secrets should show `***REMOVED***`

## If Something Goes Wrong

### Restore from Backup

```powershell
cd C:\projects\constrix-microservices
Remove-Item -Recurse -Force constrix_api
Rename-Item "constrix_api_backup_TIMESTAMP" "constrix_api"
cd constrix_api
```

### Re-add Remote After Restore

```powershell
git remote add origin https://github.com/YOUR_ORG/YOUR_REPO.git
```

## Team Reset Instructions

Send this to all team members after completion:

---

**Git Repository Updated - Reset Required**

```bash
# Option 1: Reset your current branch
git fetch origin
git reset --hard origin/YOUR_BRANCH

# Option 2: Fresh clone
cd ..
git clone https://github.com/YOUR_ORG/YOUR_REPO.git constrix_api_new
```

---

## Verification Checklist

After completion:

- [ ] Backup exists
- [ ] git-filter-repo completed without errors
- [ ] Secrets not found in: `git log --all -S "password"`
- [ ] Remote added back successfully
- [ ] Force push completed: `git push origin --force --all`
- [ ] All branches visible on GitHub
- [ ] Team members notified
- [ ] All credentials rotated
- [ ] GitHub Secrets updated

## Troubleshooting

### "command not found: git-filter-repo"

```powershell
pip install git-filter-repo
# Or
pip3 install git-filter-repo
# Or download from: https://github.com/newren/git-filter-repo
```

### "fatal: not a git repository"

Make sure you're in the correct directory:
```powershell
cd C:\projects\constrix-microservices\constrix_api
git status
```

### "Updates were rejected"

Someone pushed during the operation. Have team stop pushing and retry.

### Force push fails with authentication error

```powershell
# Update credentials
git config --global credential.helper wincred
# Try push again
git push origin --force --all
```

## Expected Timeline

- Backup: 1-2 minutes
- git-filter-repo: 5-30 minutes (depends on repo size)
- Force push: 2-5 minutes
- **Total: ~15-40 minutes**

## Support Commands

```powershell
# Check repository size
git count-objects -vH

# View recent commits
git log --oneline -20

# List all branches
git branch -a

# Check current branch
git branch --show-current

# View remote
git remote -v
```
