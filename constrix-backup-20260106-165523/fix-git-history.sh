#!/bin/bash

# ============================================
# Git History Security Fix
# Removes hard-coded secrets from ALL branches
# ============================================

set -e

echo "🔒 Git History Security Fix"
echo "============================"
echo ""
echo "⚠️  WARNING: This will rewrite Git history for ALL branches!"
echo "⚠️  All team members will need to re-clone or reset their branches."
echo ""

# Check if we're in a git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo "❌ Error: Not in a Git repository"
    exit 1
fi

# Get repository root
REPO_ROOT=$(git rev-parse --show-toplevel)
cd "$REPO_ROOT"

echo "📂 Repository: $REPO_ROOT"
echo ""

# ============================================
# STEP 1: Create Backup
# ============================================

echo "STEP 1: Creating backup..."
BACKUP_DIR="../constrix-backup-$(date +%Y%m%d-%H%M%S)"

if [ -d "$BACKUP_DIR" ]; then
    echo "⚠️  Backup directory already exists: $BACKUP_DIR"
    read -p "Continue anyway? (y/n): " continue_backup
    if [[ $continue_backup != "y" ]]; then
        exit 1
    fi
else
    echo "Creating backup at: $BACKUP_DIR"
    cp -r . "$BACKUP_DIR"
    echo "✅ Backup created"
fi

echo ""

# ============================================
# STEP 2: Confirm Operation
# ============================================

echo "STEP 2: Confirmation"
echo ""
echo "This script will remove the following secrets from Git history:"
echo "  • Email passwords (G:1Wc;c;L9b)"
echo "  • Email addresses (vision@speedpharma.link)"
echo "  • Google Maps API key"
echo "  • SMS Mora API key"
echo "  • OpenRouter API key"
echo ""
echo "After this operation:"
echo "  ✓ All branches will be cleaned"
echo "  ✓ Git history will be rewritten"
echo "  ⚠️  Force push will be required"
echo "  ⚠️  Team members must re-clone or reset"
echo ""

read -p "Do you want to continue? Type 'YES' to proceed: " confirm
if [[ $confirm != "YES" ]]; then
    echo "Operation cancelled."
    exit 0
fi

echo ""

# ============================================
# STEP 3: Install git-filter-repo if needed
# ============================================

echo "STEP 3: Checking dependencies..."

if ! command -v git-filter-repo &> /dev/null; then
    echo "git-filter-repo not found. Installing..."
    
    # Try to install via pip
    if command -v pip3 &> /dev/null; then
        pip3 install git-filter-repo
    elif command -v pip &> /dev/null; then
        pip install git-filter-repo
    else
        echo "❌ Error: pip not found. Please install git-filter-repo manually:"
        echo "   pip install git-filter-repo"
        echo "   Or download from: https://github.com/newren/git-filter-repo"
        exit 1
    fi
fi

echo "✅ git-filter-repo is available"
echo ""

# ============================================
# STEP 4: Create replacement file
# ============================================

echo "STEP 4: Creating replacement patterns..."

cat > /tmp/secrets-replacement.txt <<'EOF'
# Email credentials
G:1Wc;c;L9b==>***REMOVED***
vision@speedpharma.link==>***REMOVED***

# API Keys
AIzaSyD5izq7FZI-nHdrt6mx5UeKRkUSjvagS5g==>***REMOVED***
9d036169a982498edbdcd92d99a838112546a986==>***REMOVED***
saadmashal==>***REMOVED***
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==>***REMOVED***

# Sender names
Vision Dim==>***REMOVED***
EOF

echo "✅ Replacement patterns created"
echo ""

# ============================================
# STEP 5: Run git-filter-repo
# ============================================

echo "STEP 5: Removing secrets from Git history..."
echo "⏳ This may take several minutes..."
echo ""

# Fetch all branches first
git fetch --all

# Run git-filter-repo
git filter-repo --replace-text /tmp/secrets-replacement.txt --force

echo ""
echo "✅ Secrets removed from Git history"
echo ""

# ============================================
# STEP 6: Verify changes
# ============================================

echo "STEP 6: Verifying changes..."

# Check if secrets still exist in history
FOUND_SECRETS=0

echo "Scanning for remaining secrets..."
if git log --all --source --full-history -S "G:1Wc;c;L9b" | grep -q "commit"; then
    echo "⚠️  Found: Email password still in history"
    FOUND_SECRETS=1
fi

if git log --all --source --full-history -S "vision@speedpharma.link" | grep -q "commit"; then
    echo "⚠️  Found: Email address still in history"
    FOUND_SECRETS=1
fi

if git log --all --source --full-history -S "AIzaSyD5izq7FZI" | grep -q "commit"; then
    echo "⚠️  Found: Google Maps API key still in history"
    FOUND_SECRETS=1
fi

if [ $FOUND_SECRETS -eq 0 ]; then
    echo "✅ No secrets found in Git history"
else
    echo "⚠️  Some secrets may still exist. Please review manually."
fi

echo ""

# ============================================
# STEP 7: Force push to remote
# ============================================

echo "STEP 7: Push changes to remote"
echo ""
echo "⚠️  CRITICAL: This will force-push to ALL branches!"
echo "⚠️  Team members will need to reset their local branches."
echo ""

read -p "Push changes to remote? Type 'PUSH' to continue: " push_confirm
if [[ $push_confirm != "PUSH" ]]; then
    echo ""
    echo "Changes NOT pushed to remote."
    echo "Local repository has been cleaned."
    echo "To push manually later, run:"
    echo "  git push origin --force --all"
    echo "  git push origin --force --tags"
    exit 0
fi

echo ""
echo "Pushing to remote..."

# Add back the remote (git-filter-repo removes it)
git remote add origin $(git config --get remote.origin.url 2>/dev/null || echo "REMOTE_URL_HERE")

# Force push all branches
git push origin --force --all
git push origin --force --tags

echo ""
echo "✅ Changes pushed to remote"

# ============================================
# STEP 8: Cleanup
# ============================================

echo ""
echo "STEP 8: Cleanup..."

rm -f /tmp/secrets-replacement.txt

echo "✅ Cleanup complete"

# ============================================
# FINAL REPORT
# ============================================

echo ""
echo "════════════════════════════════════════"
echo "🎉 Git History Security Fix Complete!"
echo "════════════════════════════════════════"
echo ""
echo "✅ Completed actions:"
echo "  • Backup created at: $BACKUP_DIR"
echo "  • Secrets removed from ALL branches"
echo "  • Changes pushed to remote"
echo ""
echo "⚠️  IMPORTANT NEXT STEPS:"
echo ""
echo "1. Team Communication:"
echo "   Send this message to all team members:"
echo ""
echo "   ┌─────────────────────────────────────┐"
echo "   │ 🔒 CRITICAL: Git History Rewritten  │"
echo "   └─────────────────────────────────────┘"
echo "   "
echo "   The Git repository has been cleaned for security."
echo "   ALL team members must reset their local branches:"
echo "   "
echo "   git fetch origin"
echo "   git reset --hard origin/YOUR_BRANCH"
echo "   "
echo "   Or re-clone the repository:"
echo "   git clone [repository-url]"
echo ""
echo "2. Rotate ALL Credentials (CRITICAL!):"
echo "   [ ] Database password"
echo "   [ ] APP_KEY (php artisan key:generate)"
echo "   [ ] JWT_SECRET (php artisan jwt:secret)"
echo "   [ ] Email password"
echo "   [ ] AWS keys"
echo "   [ ] Google Maps API key"
echo "   [ ] SMS Mora API key"
echo "   [ ] OpenRouter API key"
echo ""
echo "3. Update GitHub Secrets:"
echo "   [ ] Go to repository Settings → Secrets"
echo "   [ ] Add all new credentials"
echo "   [ ] Refer to: devops/GITHUB_SECRETS.md"
echo ""
echo "4. Test Deployments:"
echo "   [ ] Test dev branch deployment"
echo "   [ ] Test stage branch deployment"
echo "   [ ] Test production deployment"
echo ""
echo "════════════════════════════════════════"
echo ""
echo "Backup location: $BACKUP_DIR"
echo ""
