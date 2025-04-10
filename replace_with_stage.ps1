# Solution 3: Replace current branch with stable stage branch

Write-Host "=== REPLACE WITH STAGE SOLUTION ===" -ForegroundColor Cyan
Write-Host "This replaces your current branch with the stable stage branch" -ForegroundColor Yellow
Write-Host "Your current work will be backed up first" -ForegroundColor Green
Write-Host ""

# Step 1: Create backup
Write-Host "Step 1: Creating backup of current branch..." -ForegroundColor Green
git branch backup-realese-roles-permission-$(Get-Date -Format "yyyyMMdd-HHmmss") realese/roles-and-permission

# Step 2: Check what unique commits exist on current branch
Write-Host "Step 2: Checking for unique commits on current branch..." -ForegroundColor Cyan
$uniqueCommits = git log --oneline origin/stage..realese/roles-and-permission 2>&1

if ($uniqueCommits -and $uniqueCommits.Count -gt 0) {
    Write-Host "Found unique commits on current branch:" -ForegroundColor Yellow
    $uniqueCommits | ForEach-Object { Write-Host "  $_" -ForegroundColor Yellow }
    Write-Host ""
    Write-Host "These will be saved in the backup branch" -ForegroundColor Green
}

# Step 3: Replace current branch with stage
Write-Host "Step 3: Replacing current branch with stable stage..." -ForegroundColor Green
git reset --hard origin/stage

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ REPLACEMENT SUCCESSFUL!" -ForegroundColor Green
    Write-Host ""
    Write-Host "=== SOLUTION COMPLETED ===" -ForegroundColor Green
    Write-Host "Your branch is now identical to the stable stage branch" -ForegroundColor Cyan
    Write-Host "All changes from target authors are now included" -ForegroundColor Green
    Write-Host ""
    Write-Host "Recent commits:" -ForegroundColor Cyan
    git log --oneline -10
    Write-Host ""
    Write-Host "Branch status:" -ForegroundColor Cyan
    git status
} else {
    Write-Host "❌ REPLACEMENT FAILED!" -ForegroundColor Red
    Write-Host "Your original branch is safe" -ForegroundColor Green
}
