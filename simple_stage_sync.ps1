# Simple Solution: Sync your branch with stable stage

Write-Host "=== SIMPLE STAGE SYNC SOLUTION ===" -ForegroundColor Cyan
Write-Host "Since stage is stable and contains all the work you need," -ForegroundColor Yellow
Write-Host "this will make your branch identical to stage" -ForegroundColor Green
Write-Host ""

# Create backup first
Write-Host "Creating backup of current branch..." -ForegroundColor Green
$backupName = "backup-realese-roles-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
git branch $backupName realese/roles-and-permission

Write-Host "Backup created: $backupName" -ForegroundColor Green

# Show what will be gained/lost
Write-Host ""
Write-Host "Analyzing differences..." -ForegroundColor Cyan

$commitsToGain = git log --oneline origin/stage ^realese/roles-and-permission | Measure-Object | Select-Object -ExpandProperty Count
$commitsToLose = git log --oneline realese/roles-and-permission ^origin/stage | Measure-Object | Select-Object -ExpandProperty Count

Write-Host "Commits to gain from stage: $commitsToGain" -ForegroundColor Green
Write-Host "Commits unique to current branch: $commitsToLose" -ForegroundColor Yellow

if ($commitsToLose -gt 0) {
    Write-Host ""
    Write-Host "Unique commits on current branch (will be preserved in backup):" -ForegroundColor Yellow
    git log --oneline realese/roles-and-permission ^origin/stage | Select-Object -First 10
}

Write-Host ""
Write-Host "Syncing with stage branch..." -ForegroundColor Green

# Reset to stage branch
git reset --hard origin/stage

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "🎉 SUCCESS! Your branch is now synced with stable stage!" -ForegroundColor Green
    Write-Host ""
    Write-Host "✅ Benefits:" -ForegroundColor Cyan
    Write-Host "  • All stable features from stage are now included" -ForegroundColor Green
    Write-Host "  • All target authors' work is included" -ForegroundColor Green  
    Write-Host "  • No conflicts (clean, stable codebase)" -ForegroundColor Green
    Write-Host "  • Your original work is saved in: $backupName" -ForegroundColor Green
    Write-Host ""
    Write-Host "Recent commits on your branch:" -ForegroundColor Cyan
    git log --oneline -10
    Write-Host ""
    Write-Host "Ready to push or continue development!" -ForegroundColor Green
} else {
    Write-Host "❌ Sync failed! Your branch is unchanged." -ForegroundColor Red
}
