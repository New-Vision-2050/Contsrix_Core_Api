@echo off
REM ============================================
REM Git History Security Fix - Windows Version
REM ============================================

echo.
echo ============================================
echo Git History Security Fix
echo ============================================
echo.
echo WARNING: This will rewrite Git history!
echo.
pause

REM Check if git-filter-repo is installed
python -c "import git_filter_repo" 2>nul
if errorlevel 1 (
    echo.
    echo Installing git-filter-repo...
    pip install git-filter-repo
    if errorlevel 1 (
        echo ERROR: Failed to install git-filter-repo
        echo Please run: pip install git-filter-repo
        pause
        exit /b 1
    )
)

echo.
echo Creating backup...
set BACKUP_DIR=..\constrix-backup-%date:~-4,4%%date:~-10,2%%date:~-7,2%-%time:~0,2%%time:~3,2%%time:~6,2%
set BACKUP_DIR=%BACKUP_DIR: =0%
xcopy /E /I /H /Y . "%BACKUP_DIR%"
echo Backup created at: %BACKUP_DIR%
echo.

echo Creating replacement file...
(
echo G:1Wc;c;L9b==^>***REMOVED***
echo vision@speedpharma.link==^>***REMOVED***
echo AIzaSyD5izq7FZI-nHdrt6mx5UeKRkUSjvagS5g==^>***REMOVED***
echo 9d036169a982498edbdcd92d99a838112546a986==^>***REMOVED***
echo saadmashal==^>***REMOVED***
echo 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==^>***REMOVED***
echo Vision Dim==^>***REMOVED***
) > secrets-replacement.txt

echo.
echo Fetching all branches...
git fetch --all

echo.
echo Running git-filter-repo...
echo This may take several minutes...
git filter-repo --replace-text secrets-replacement.txt --force

echo.
echo Adding remote back...
for /f "tokens=*" %%i in ('git config --get remote.origin.url') do set REMOTE_URL=%%i
git remote add origin %REMOTE_URL%

echo.
echo Secrets have been removed from Git history.
echo.
echo Do you want to FORCE PUSH to remote?
echo WARNING: This will overwrite remote history!
echo Type YES to continue, anything else to cancel:
set /p PUSH_CONFIRM=

if /i "%PUSH_CONFIRM%"=="YES" (
    echo.
    echo Force pushing all branches...
    git push origin --force --all
    git push origin --force --tags
    echo.
    echo SUCCESS: Changes pushed to remote!
) else (
    echo.
    echo Changes NOT pushed to remote.
    echo To push manually later run:
    echo   git push origin --force --all
    echo   git push origin --force --tags
)

echo.
echo Cleaning up...
del secrets-replacement.txt

echo.
echo ============================================
echo COMPLETED!
echo ============================================
echo.
echo Backup location: %BACKUP_DIR%
echo.
echo NEXT STEPS:
echo 1. Notify team members to reset their branches
echo 2. Rotate ALL credentials immediately
echo 3. Update GitHub Secrets
echo 4. Test deployments
echo.
echo See GIT_HISTORY_FIX_GUIDE.md for details
echo.
pause
