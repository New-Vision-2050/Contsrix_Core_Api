# File Permission Limit Observer Implementation

## Overview
Moved file storage limit calculations from `PermissionMiddleware` to `FileObserver` for better architecture and automatic tracking across all file operations.

## Changes Summary

### 1. Created FileObserver
**File**: `modules/ArchiveLibrary/File/Observers/FileObserver.php`

**Features**:
- ✅ Automatic storage limit tracking for all File model operations
- ✅ Handles both `media` (Spatie) and `mediaFile` (CustomMedia) relations
- ✅ Uses existing `company_id` column from File model
- ✅ Throws `UnauthorizedException` when limits exceeded
- ✅ Comprehensive logging for debugging

**Events Handled**:

#### `creating(File $file)` - Phase 1: Pre-validation
- Fires **BEFORE** file record is inserted into database
- Checks if company has completely exhausted storage (0 MB left)
- Throws exception immediately if no storage available
- **Note**: Media not attached yet, so can't check actual file size
- Prevents database insert if storage is exhausted

#### `created(File $file)` - Phase 2: Post-validation
- Fires **AFTER** file record is created
- Gets actual file size from media/mediaFile
- Validates file size against remaining limit
- If limit exceeded: **deletes the file** and throws exception
- If okay: Decreases limit by actual file size (in MB)
- Ensures accurate tracking after media is attached

#### `updating(File $file)` - Pre-update validation
- Fires **BEFORE** file update is saved
- Detects if media changed
- Calculates size difference (new - old)
- Validates sufficient storage for size increase
- Throws exception if insufficient storage (update prevented)
- Adjusts limit based on difference:
  - Positive: Consumes more storage
  - Negative: Frees up storage
  - Zero: No change

#### `deleting(File $file)`
- Gets file size before deletion
- Restores storage limit
- Never blocks deletion (errors logged only)

### 2. Registered Observer
**File**: `modules/ArchiveLibrary/File/Providers/FileServiceProvider.php`

```php
public function boot(): void
{
    // ... existing code ...
    
    // Register File observer for automatic storage limit tracking
    File::observe(FileObserver::class);
}
```

### 3. Updated File Model
**File**: `modules/ArchiveLibrary/File/Models/File.php`

- Added `company_id` to `$fillable` array

### 4. Refactored PermissionMiddleware
**File**: `app/Http/Middleware/PermissionMiddleware.php`

**Removed**:
- ❌ All file permission logic (size-based limits)
- ❌ File size calculation from request
- ❌ File size fetching from database
- ❌ File update/replace logic
- ❌ `getFileSizeFromRequest()` method
- ❌ `getOldFileSizeFromDatabase()` method
- ❌ File model import

**Kept**:
- ✅ Folder permission logic (count-based limits)
- ✅ Folder create/delete tracking

**New Logic - File Permissions Completely Skipped**:
```php
// FIRST: Check if this is a file permission
$isFilePermission = str_contains(strtolower($perm), 'archive-library*file');

if ($isFilePermission) {
    // Do nothing - FileObserver handles all file limit logic
    // No error throwing, no limit checking, no limit adjustment
    break; // Skip to next request
}

// ONLY process folder permissions
$isFolderPermission = str_contains(strtolower($perm), 'archive-library*folder');

if ($isFolderPermission) {
    if ($isCreateOperation && $permissionLimit) {
        // Check and decrease folder limit
    }
    elseif ($isDeleteOperation) {
        // Restore folder limit
    }
}
```

**Key Change**: File permissions are now **completely ignored** in middleware:
- ✅ No limit validation
- ✅ No error throwing
- ✅ No limit adjustment
- ✅ FileObserver handles everything

## Benefits

### Architectural Improvements
1. **Separation of Concerns**: File logic in File module, not in global middleware
2. **Single Responsibility**: Observer handles only file operations
3. **Automatic Tracking**: Works for ALL file creations (API, jobs, console, integrations)
4. **Consistency**: Same logic applies everywhere files are created

### Code Quality
1. **Reduced Complexity**: Middleware is now simpler and focused
2. **Better Maintainability**: File logic in one place
3. **Easier Testing**: Can test observer independently
4. **Clear Dependencies**: Uses repository pattern

### Functionality
1. **Works Everywhere**: Legal Data, Official Documents, Archive Library
2. **No Manual Tracking**: Automatic via Eloquent events
3. **Handles Updates**: Automatically tracks file replacements
4. **Graceful Errors**: Clear exception messages

## How It Works

### Request Flow Comparison

#### File Permission Request (e.g., archive-library.archive-library*file.create)
```
1. User uploads file → API request
2. PermissionMiddleware intercepts
3. Checks permission: "archive-library.archive-library*file.create"
4. Detects: isFilePermission = true
5. ✅ SKIPS all limit logic (no checking, no errors)
6. Request continues to controller
7. Controller creates File model
8. FileObserver: creating() fires → basic validation
9. File record inserted
10. Media attached
11. FileObserver: created() fires → full validation + limit decrease
12. Response returned
```

#### Folder Permission Request (e.g., archive-library.archive-library*folder.create)
```
1. User creates folder → API request
2. PermissionMiddleware intercepts
3. Checks permission: "archive-library.archive-library*folder.create"
4. Detects: isFolderPermission = true
5. ✅ CHECKS limit in middleware
6. If exceeded → throw exception (request blocked)
7. If okay → decrease folder count
8. Request continues to controller
9. Folder created
10. Response returned
```

### File Creation Flow (Two-Phase Validation)
```
Phase 1 - Pre-validation:
1. User uploads file → File::create() called
2. Observer: creating() event fires
3. Check if storage completely exhausted (0 MB)
4. If exhausted → throw exception (INSERT blocked)
5. If okay → continue to insert

Phase 2 - Post-validation:
6. File record created in database
7. Media attached to file
8. Observer: created() event fires
9. Get actual file size from media/mediaFile
10. Check if file size exceeds remaining limit
11. If exceeded → delete file + throw exception
12. If okay → decrease limit by file size
13. File saved successfully with limit updated
```

### File Update Flow
```
1. User replaces file → File::update() called
2. Observer: updating() event fires (BEFORE save)
3. Detect media change
4. Get old file size from database
5. Get new file size from updated model
6. Calculate: sizeDifference = newSize - oldSize
7. If positive (larger file):
   - Check if sufficient storage available
   - If not → throw exception (UPDATE blocked)
   - If yes → decrease limit by difference
8. If negative (smaller file):
   - Increase limit by difference
9. If zero → no change
10. Update proceeds if not blocked
```

### File Deletion Flow
```
1. User deletes file → File::delete()
2. Observer: deleting() event fires
3. Get current file size
4. Restore limit by file size
5. Never blocks deletion
```

## Integration Points

### Archive Library Files
- Direct file uploads through File API
- Tracked automatically via observer

### Company Legal Data
- Files created with `company_id`
- Tracked automatically via observer

### Company Official Documents
- Files created with `company_id`
- Tracked automatically via observer

## Error Handling

### Creation/Update
- Throws `UnauthorizedException` with HTTP 403
- Clear error messages with size details
- Blocks operation if limit exceeded

### Deletion
- Never blocks deletion
- Errors logged only (for audit)
- Ensures data can always be removed

## Testing Considerations

### Unit Tests
- Test observer methods independently
- Mock repositories
- Verify limit calculations

### Integration Tests
- Test file creation with limits
- Test file updates with size changes
- Test file deletions restore limits
- Test across different modules (Legal Data, Official Docs, Archive)

### Edge Cases
- File without company_id
- File without media
- Permission doesn't exist
- Limit not configured
- Concurrent file uploads

## Future Enhancements

### Possible Improvements
1. Queue limit updates for better performance
2. Batch limit calculations for multiple files
3. Add metrics/analytics for storage usage
4. Implement soft limits (warnings before hard limit)
5. Add company notifications when approaching limit

### Configuration Options
Could add config for:
- Enable/disable automatic tracking
- Grace period for temporary overages
- Different limits per file type
- Custom exception messages

## Migration Notes

### For Existing Systems
1. No data migration needed
2. Observer works immediately
3. Existing limits still respected
4. No breaking changes

### For New Deployments
1. Observer registered automatically
2. Use seeders to set initial limits
3. Works out of the box

## Monitoring

### Logs to Check
- File storage limit decreased/increased
- Permission limit exceeded exceptions
- Failed limit calculations (errors)

### Metrics to Track
- Storage consumption per company
- Files rejected due to limits
- Average file sizes
- Limit adjustment frequency

## Support

### Common Issues

**Q: File created but limit not decreased**
- Check if company_id is set on file
- Verify permission exists in database
- Check if CompanyPermissionLimit exists

**Q: Exception thrown but should allow**
- Check actual_limit value
- Verify file size calculation
- Check if permission name matches exactly

**Q: Update not tracking size change**
- Verify media was actually changed
- Check wasMediaChanged() detection
- Review update logs

---

**Implementation Date**: 2025-10-21
**Status**: ✅ Complete
**Tested**: Pending
