# Solution: Create filtered branch from stage with target authors only, then merge

Write-Host "=== FILTERED BRANCH MERGE SOLUTION ===" -ForegroundColor Cyan
Write-Host "This creates a clean branch with only target authors' commits from stage" -ForegroundColor Yellow
Write-Host ""

# Step 1: Create a new branch from the starting point
Write-Host "Step 1: Creating filtered branch from starting commit..." -ForegroundColor Green
git checkout -b temp-filtered-stage 7b70b84a4c6728b565cd1048d5936eafdb43b34f

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error creating branch" -ForegroundColor Red
    exit 1
}

# Step 2: Apply all commits from target authors in chronological order
Write-Host "Step 2: Applying all commits from target authors..." -ForegroundColor Green

# Get all commits from target authors in chronological order (oldest first)
$allCommits = Get-Content "all_correct_commits.txt"
Write-Host "Applying $($allCommits.Count) commits from target authors..."

$successCount = 0
$errorCount = 0

foreach ($commit in $allCommits) {
    $commitNum = [Array]::IndexOf($allCommits, $commit) + 1
    Write-Progress -Activity "Building filtered branch" -Status "Commit $commitNum/$($allCommits.Count)" -PercentComplete (($commitNum / $allCommits.Count) * 100)
    
    # Check if merge commit
    $isMerge = git cat-file -p $commit | Select-String "^parent " | Measure-Object | Select-Object -ExpandProperty Count
    
    if ($isMerge -gt 1) {
        # For merge commits, use -m 1 to take the first parent
        $result = git cherry-pick -m 1 $commit 2>&1
    } else {
        $result = git cherry-pick $commit 2>&1
    }
    
    if ($LASTEXITCODE -eq 0) {
        $successCount++
    } else {
        # For conflicts, take the stage version (theirs)
        $status = git status --porcelain
        if ($status -match "UU|AA|DD|DU|UD|AU|UA") {
            git checkout --theirs . 2>&1 | Out-Null
            git add . 2>&1 | Out-Null
            git cherry-pick --continue 2>&1 | Out-Null
            if ($LASTEXITCODE -eq 0) {
                $successCount++
            } else {
                $errorCount++
                git cherry-pick --abort 2>&1 | Out-Null
            }
        } else {
            $errorCount++
            git cherry-pick --abort 2>&1 | Out-Null
        }
    }
}

Write-Progress -Activity "Building filtered branch" -Completed

Write-Host ""
Write-Host "Filtered branch created with $successCount successful commits, $errorCount errors"

# Step 3: Switch back to original branch and merge
Write-Host "Step 3: Merging filtered branch into realese/roles-and-permission..." -ForegroundColor Green
git checkout realese/roles-and-permission

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error switching back to original branch" -ForegroundColor Red
    exit 1
}

# Merge the filtered branch with strategy to prefer stage version
Write-Host "Merging with strategy to prefer newer (stage) versions..." -ForegroundColor Yellow
$mergeResult = git merge temp-filtered-stage -X theirs --no-edit 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ MERGE SUCCESSFUL!" -ForegroundColor Green
    
    # Clean up temporary branch
    git branch -D temp-filtered-stage 2>&1 | Out-Null
    
    Write-Host ""
    Write-Host "=== SOLUTION COMPLETED ===" -ForegroundColor Green
    Write-Host "Your branch now contains all stable changes from stage by target authors"
    Write-Host ""
    Write-Host "Recent commits:" -ForegroundColor Cyan
    git log --oneline -10
    Write-Host ""
    Write-Host "Branch status:" -ForegroundColor Cyan
    git status --short
} else {
    Write-Host "Merge failed: $mergeResult" -ForegroundColor Red
    Write-Host "Manual resolution may be needed" -ForegroundColor Yellow
}
