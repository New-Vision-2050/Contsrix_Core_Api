# Solution 2: Direct integration of stage branch changes

Write-Host "=== DIRECT STAGE INTEGRATION SOLUTION ===" -ForegroundColor Cyan
Write-Host "This directly integrates the stable stage branch" -ForegroundColor Yellow
Write-Host ""

# Step 1: Backup current branch
Write-Host "Step 1: Creating backup of current branch..." -ForegroundColor Green
git branch backup-realese-roles-permission realese/roles-and-permission

# Step 2: Reset to production base and apply stage changes
Write-Host "Step 2: Resetting to production base..." -ForegroundColor Green
git reset --hard origin/production

# Step 3: Merge specific range from stage
Write-Host "Step 3: Merging stage changes from commit range..." -ForegroundColor Green
Write-Host "This will bring all changes from stage after the starting commit" -ForegroundColor Yellow

# Create a temporary branch with the stage changes we want
git checkout -b temp-stage-changes origin/stage

# Reset to our starting point and then merge
git checkout realese/roles-and-permission
git reset --hard origin/production

# Merge the stage branch with strategy to take their (stage) version on conflicts
Write-Host "Merging stage branch (taking stage version on conflicts)..." -ForegroundColor Yellow
$mergeResult = git merge temp-stage-changes -X theirs --allow-unrelated-histories 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ INTEGRATION SUCCESSFUL!" -ForegroundColor Green
    
    # Clean up
    git branch -D temp-stage-changes 2>&1 | Out-Null
    
    Write-Host ""
    Write-Host "=== SOLUTION COMPLETED ===" -ForegroundColor Green
    Write-Host "Your branch is now fully integrated with stable stage"
    Write-Host "Backup branch: backup-realese-roles-permission"
    Write-Host ""
    git log --oneline -10
} else {
    Write-Host "Integration failed: $mergeResult" -ForegroundColor Red
    Write-Host "Restoring from backup..." -ForegroundColor Yellow
    git reset --hard backup-realese-roles-permission
}
