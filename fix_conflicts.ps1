# Fix all Git conflict markers in PHP files

Write-Host "=== FIXING GIT CONFLICT MARKERS ===" -ForegroundColor Cyan
Write-Host "Searching for files with unresolved merge conflicts..." -ForegroundColor Yellow

# Find all PHP files with conflict markers
$conflictFiles = @()

# Search for conflict markers
Write-Host "Searching for conflict markers..." -ForegroundColor Green
$filesWithConflicts = git diff --name-only --diff-filter=U 2>&1

if (-not $filesWithConflicts) {
    # If no unmerged files, search manually for conflict markers
    $searchResult = Get-ChildItem -Path . -Recurse -Name "*.php" | ForEach-Object {
        $file = $_
        $content = Get-Content $file -ErrorAction SilentlyContinue
        if ($content -match "^<<<<<<<|^=======|^>>>>>>>") {
            $file
        }
    }
    $conflictFiles = $searchResult
} else {
    $conflictFiles = $filesWithConflicts
}

Write-Host "Found $($conflictFiles.Count) files with conflict markers" -ForegroundColor Yellow

if ($conflictFiles.Count -eq 0) {
    Write-Host "No files with conflict markers found!" -ForegroundColor Green
    exit 0
}

# Display files that will be fixed
Write-Host ""
Write-Host "Files to fix:" -ForegroundColor Cyan
$conflictFiles | ForEach-Object { Write-Host "  $_" -ForegroundColor Yellow }

Write-Host ""
Write-Host "Fixing conflicts by keeping the stage version (newer code)..." -ForegroundColor Green

$fixedCount = 0
$failedCount = 0

foreach ($file in $conflictFiles) {
    Write-Host "Processing: $file" -ForegroundColor Cyan
    
    try {
        $content = Get-Content $file -Raw -ErrorAction Stop
        
        if ($content -match "<<<<<<< HEAD|=======|>>>>>>> [a-f0-9]+") {
            Write-Host "  -> Found conflict markers, resolving..." -ForegroundColor Yellow
            
            # Remove conflict markers and keep the "theirs" version (after =======)
            # Pattern explanation:
            # <<<<<<< HEAD.*?\n(.*?)\n=======\n(.*?)\n>>>>>>> [a-f0-9]+.*?\n
            # We want to keep the part after ======= (the stage version)
            
            $fixedContent = $content -replace "(?s)<<<<<<< HEAD.*?\n(.*?)\n=======\n(.*?)\n>>>>>>> [a-f0-9]+.*?\n", '$2'
            
            # Also handle cases where there might be different conflict marker formats
            $fixedContent = $fixedContent -replace "(?s)<<<<<<< HEAD.*?\n(.*?)\n=======\n(.*?)\n>>>>>>> .*?\n", '$2'
            
            # Clean up any remaining standalone markers
            $fixedContent = $fixedContent -replace "^<<<<<<< HEAD.*\n", ""
            $fixedContent = $fixedContent -replace "^=======\n", ""
            $fixedContent = $fixedContent -replace "^>>>>>>> .*\n", ""
            
            # Write the fixed content back
            $fixedContent | Set-Content $file -NoNewline -ErrorAction Stop
            
            Write-Host "  -> Fixed!" -ForegroundColor Green
            $fixedCount++
        } else {
            Write-Host "  -> No conflict markers found" -ForegroundColor Gray
        }
    } catch {
        Write-Host "  -> Failed to fix: $($_.Exception.Message)" -ForegroundColor Red
        $failedCount++
    }
}

Write-Host ""
Write-Host "=== CONFLICT RESOLUTION COMPLETE ===" -ForegroundColor Green
Write-Host "Files processed: $($conflictFiles.Count)" -ForegroundColor Cyan
Write-Host "Successfully fixed: $fixedCount" -ForegroundColor Green
Write-Host "Failed: $failedCount" -ForegroundColor Red

# Add all fixed files to git
Write-Host ""
Write-Host "Adding fixed files to git..." -ForegroundColor Green
git add . 2>&1 | Out-Null

Write-Host "Checking final status..." -ForegroundColor Cyan
git status --short
