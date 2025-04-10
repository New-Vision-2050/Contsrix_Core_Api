# Aggressive cherry-pick script - takes ALL commits and resolves conflicts with stage branch version

Write-Host "=== AGGRESSIVE CHERRY-PICK MODE ===" -ForegroundColor Cyan
Write-Host "This will cherry-pick ALL commits and resolve conflicts with stage branch version" -ForegroundColor Yellow
Write-Host ""

# Read all commits (we already have the correct list)
$allCommits = Get-Content "all_correct_commits.txt"
Write-Host "Total commits to cherry-pick: $($allCommits.Count)" -ForegroundColor Cyan
Write-Host "This includes merge commits (will use -m 1 for merge commits)" -ForegroundColor Yellow

Write-Host ""
Write-Host "Starting aggressive cherry-pick..." -ForegroundColor Green
Write-Host ""

# Stats
$successCount = 0
$conflictResolvedCount = 0
$mergeCommitCount = 0
$errorCommits = @()

for ($i = 0; $i -lt $allCommits.Count; $i++) {
    $commit = $allCommits[$i]
    $commitNum = $i + 1
    
    Write-Progress -Activity "Cherry-picking all commits" -Status "Commit $commitNum/$($allCommits.Count)" -PercentComplete (($commitNum / $allCommits.Count) * 100)
    Write-Host "[$commitNum/$($allCommits.Count)] Processing: $($commit.Substring(0, 8))" -ForegroundColor Cyan
    
    # Check if it's a merge commit
    $isMerge = git cat-file -p $commit | Select-String "^parent " | Measure-Object | Select-Object -ExpandProperty Count
    
    if ($isMerge -gt 1) {
        Write-Host "  -> Merge commit detected, using -m 1" -ForegroundColor Yellow
        $result = git cherry-pick -m 1 $commit 2>&1
        $mergeCommitCount++
    } else {
        $result = git cherry-pick $commit 2>&1
    }
    
    if ($LASTEXITCODE -eq 0) {
        $successCount++
        Write-Host "  -> Success" -ForegroundColor Green
    } else {
        # Check if we have conflicts
        $status = git status --porcelain 2>&1
        
        if ($status -match "UU|AA|DD|DU|UD|AU|UA") {
            Write-Host "  -> Conflicts detected, resolving with stage version..." -ForegroundColor Yellow
            
            # Take the stage branch version for all conflicts
            git checkout --theirs . 2>&1 | Out-Null
            
            # Add all files
            git add . 2>&1 | Out-Null
            
            # Continue the cherry-pick
            $continueResult = git cherry-pick --continue 2>&1
            
            if ($LASTEXITCODE -eq 0) {
                $conflictResolvedCount++
                Write-Host "  -> Conflict resolved, continuing" -ForegroundColor Green
            } else {
                Write-Host "  -> Failed to resolve conflict: $continueResult" -ForegroundColor Red
                $errorCommits += $commit
                git cherry-pick --abort 2>&1 | Out-Null
            }
        } else {
            Write-Host "  -> Other error: $result" -ForegroundColor Red
            $errorCommits += $commit
            
            # Try to abort and continue
            git cherry-pick --abort 2>&1 | Out-Null
        }
    }
    
    # Brief pause to avoid overwhelming git
    if ($commitNum % 10 -eq 0) {
        Start-Sleep -Milliseconds 100
    }
}

Write-Progress -Activity "Cherry-picking all commits" -Completed

Write-Host ""
Write-Host "=== AGGRESSIVE CHERRY-PICK COMPLETE ===" -ForegroundColor Green
Write-Host "Total commits processed: $($allCommits.Count)" -ForegroundColor Cyan
Write-Host "Clean cherry-picks: $successCount" -ForegroundColor Green
Write-Host "Conflicts resolved (stage version): $conflictResolvedCount" -ForegroundColor Yellow
Write-Host "Merge commits processed: $mergeCommitCount" -ForegroundColor Magenta
Write-Host "Failed commits: $($errorCommits.Count)" -ForegroundColor Red
Write-Host "Total successful: $($successCount + $conflictResolvedCount)" -ForegroundColor Green
Write-Host "Success rate: $([Math]::Round((($successCount + $conflictResolvedCount) / $allCommits.Count) * 100, 1))%" -ForegroundColor Cyan

if ($errorCommits.Count -gt 0) {
    Write-Host ""
    Write-Host "Failed commits:" -ForegroundColor Red
    $errorCommits | ForEach-Object { 
        $shortHash = $_.Substring(0, 8)
        Write-Host "  $shortHash" -ForegroundColor Red 
    }
}

Write-Host ""
Write-Host "Final status:" -ForegroundColor Cyan
git log --oneline -5
Write-Host ""
git status --short
