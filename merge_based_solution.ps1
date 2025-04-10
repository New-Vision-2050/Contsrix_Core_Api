# Solution: Find and integrate merge commits from stage by target authors

Write-Host "=== MERGE-BASED INTEGRATION SOLUTION ===" -ForegroundColor Cyan
Write-Host "Finding merge commits on stage created by target authors" -ForegroundColor Yellow
Write-Host "This represents complete features that were merged to stage" -ForegroundColor Green
Write-Host ""

# Step 1: Find all merge commits on stage by target authors
Write-Host "Step 1: Finding merge commits by target authors..." -ForegroundColor Green

$mergeCommits = @()

# Get merge commits by each author
Write-Host "Searching merge commits by AmrSaleh2234..." -ForegroundColor Cyan
$amrMerges = git log --merges --pretty=format:"%H|%s|%an|%ad" --date=short origin/stage --author="AmrSaleh2234" 7b70b84a4c6728b565cd1048d5936eafdb43b34f..
if ($amrMerges) { $mergeCommits += $amrMerges }

Write-Host "Searching merge commits by Momtaz Nussair..." -ForegroundColor Cyan  
$momtazMerges = git log --merges --pretty=format:"%H|%s|%an|%ad" --date=short origin/stage --author="Momtaz Nussair" 7b70b84a4c6728b565cd1048d5936eafdb43b34f..
if ($momtazMerges) { $mergeCommits += $momtazMerges }

Write-Host "Searching merge commits by momtaz-dev..." -ForegroundColor Cyan
$momtazDevMerges = git log --merges --pretty=format:"%H|%s|%an|%ad" --date=short origin/stage --author="momtaz-dev" 7b70b84a4c6728b565cd1048d5936eafdb43b34f..
if ($momtazDevMerges) { $mergeCommits += $momtazDevMerges }

# Sort by date (oldest first)
$sortedMerges = $mergeCommits | Sort-Object { ($_ -split '\|')[3] }

Write-Host ""
Write-Host "Found $($sortedMerges.Count) merge commits by target authors:" -ForegroundColor Green
foreach ($merge in $sortedMerges) {
    $parts = $merge -split '\|'
    $hash = $parts[0].Substring(0, 8)
    $subject = $parts[1]
    $author = $parts[2]
    $date = $parts[3]
    Write-Host "  $hash | $date | $author | $subject" -ForegroundColor Yellow
}

if ($sortedMerges.Count -eq 0) {
    Write-Host "No merge commits found by target authors. Checking regular commits..." -ForegroundColor Yellow
    
    # Alternative: Get the actual feature branches that were merged
    Write-Host "Step 2: Finding feature branches merged by target authors..." -ForegroundColor Green
    
    # Find commits that are merge parents (feature branch tips)
    Write-Host "Analyzing stage branch structure..." -ForegroundColor Cyan
    
    # Get all commits by target authors and their merge information
    $allCommitsByAuthors = git log --pretty=format:"%H|%s|%an|%ad" --date=short origin/stage --author="AmrSaleh2234" --author="Momtaz Nussair" --author="momtaz-dev" 7b70b84a4c6728b565cd1048d5936eafdb43b34f..
    
    Write-Host "Found $($allCommitsByAuthors.Count) total commits by target authors"
    Write-Host "Proceeding with direct stage merge since no author-specific merge commits found..."
    
    # Step 3: Direct merge approach
    Write-Host ""
    Write-Host "Step 3: Direct merge of stage changes..." -ForegroundColor Green
    
    # Create backup
    git branch backup-before-stage-merge-$(Get-Date -Format "yyyyMMddHHmmss") realese/roles-and-permission
    
    # Get current branch point
    $currentHead = git rev-parse HEAD
    
    # Try to merge stage branch from our starting point
    Write-Host "Merging stage branch changes..." -ForegroundColor Yellow
    $mergeResult = git merge origin/stage -X theirs --no-edit 2>&1
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ STAGE MERGE SUCCESSFUL!" -ForegroundColor Green
    } else {
        Write-Host "Merge conflicts detected. Auto-resolving with stage version..." -ForegroundColor Yellow
        
        # Resolve conflicts by taking stage version
        git checkout --theirs . 2>&1 | Out-Null
        git add . 2>&1 | Out-Null
        git commit --no-edit 2>&1 | Out-Null
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ CONFLICTS RESOLVED WITH STAGE VERSION!" -ForegroundColor Green
        } else {
            Write-Host "❌ MERGE FAILED!" -ForegroundColor Red
            git merge --abort 2>&1 | Out-Null
            return
        }
    }
    
} else {
    # Step 2: Apply merge commits in chronological order
    Write-Host ""
    Write-Host "Step 2: Applying merge commits in chronological order..." -ForegroundColor Green
    
    # Create backup
    git branch backup-before-merge-integration-$(Get-Date -Format "yyyyMMddHHmmss") realese/roles-and-permission
    
    $successCount = 0
    foreach ($merge in $sortedMerges) {
        $hash = ($merge -split '\|')[0]
        $subject = ($merge -split '\|')[1]
        $shortHash = $hash.Substring(0, 8)
        
        Write-Host "Applying merge: $shortHash - $subject" -ForegroundColor Cyan
        
        # Cherry-pick the merge commit with first parent
        $result = git cherry-pick -m 1 $hash 2>&1
        
        if ($LASTEXITCODE -eq 0) {
            $successCount++
            Write-Host "  ✅ Success" -ForegroundColor Green
        } else {
            Write-Host "  ⚠️ Conflict detected, resolving with stage version..." -ForegroundColor Yellow
            
            # Resolve with stage version
            git checkout --theirs . 2>&1 | Out-Null
            git add . 2>&1 | Out-Null
            git cherry-pick --continue 2>&1 | Out-Null
            
            if ($LASTEXITCODE -eq 0) {
                $successCount++
                Write-Host "  ✅ Resolved" -ForegroundColor Green
            } else {
                Write-Host "  ❌ Failed" -ForegroundColor Red
                git cherry-pick --abort 2>&1 | Out-Null
            }
        }
    }
    
    Write-Host ""
    Write-Host "✅ MERGE INTEGRATION COMPLETE!" -ForegroundColor Green
    Write-Host "Successfully applied $successCount out of $($sortedMerges.Count) merge commits"
}

Write-Host ""
Write-Host "=== FINAL RESULT ===" -ForegroundColor Green
Write-Host "Recent commits:" -ForegroundColor Cyan
git log --oneline -10

Write-Host ""
Write-Host "Branch status:" -ForegroundColor Cyan
git status --short
