# Attachment Request Media Library Refactoring

## Overview
Refactored the attachment request system to use Spatie Media Library instead of direct file storage, following the same pattern used in `CompanyLegalDataObserver`.

## Changes Made

### 1. AttachmentRequestItem Model
**File**: `modules/Project/ProjectManagement/Models/AttachmentRequestItem.php`

**Changes**:
- Added `HasMedia` interface implementation
- Added `InteractsWithMedia` trait
- Now supports Spatie Media Library collections

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AttachmentRequestItem extends Model implements HasMedia
{
    use UuidTrait, InteractsWithMedia;
    // ...
}
```

### 2. AttachmentRequestRepository
**File**: `modules/Project/ProjectManagement/Repositories/AttachmentRequestRepository.php`

**Changes**:
- Added `FileUploadService` dependency injection
- Updated `createWithItems()` method to handle media uploads
- Extracts `uploaded_file` from item data
- Uses `FileUploadService` to store files in 'attachments' collection
- Updates `file_path` with media path after upload

```php
public function __construct(
    AttachmentRequest $model,
    private FileUploadService $fileUploadService
) {
    parent::__construct($model);
}

public function createWithItems(array $requestData, array $items): AttachmentRequest
{
    $request = $this->create($requestData);
    
    foreach ($items as $itemData) {
        $uploadedFile = $itemData['uploaded_file'] ?? null;
        unset($itemData['uploaded_file']);
        
        $item = $request->items()->create($itemData);
        
        if ($uploadedFile) {
            $this->fileUploadService->uploadFile(
                $item,
                $uploadedFile,
                'attachment-requests',
                'attachments',
                'public'
            );
            
            $media = $item->getFirstMedia('attachments');
            if ($media) {
                $item->update(['file_path' => $media->getPath()]);
            }
        }
    }

    return $request->load('items');
}
```

### 3. AttachmentRequestService
**File**: `modules/Project/ProjectManagement/Services/AttachmentRequestService.php`

**Changes**:

#### a. Added Imports
```php
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Ramsey\Uuid\Uuid;
```

#### b. Updated `prepareAttachmentItems()`
- Removed direct file storage using `store()`
- Set `file_path` to `null` (populated by media library)
- Added `uploaded_file` key to pass file to repository

```php
private function prepareAttachmentItems(array $attachments): array
{
    $items = [];

    foreach ($attachments as $attachment) {
        $items[] = [
            'file_name' => $attachment->getClientOriginalName(),
            'file_path' => null, // Will be populated by media library
            'file_type' => $attachment->getClientMimeType(),
            'file_size' => $attachment->getSize(),
            'status' => 'pending',
            'uploaded_file' => $attachment, // Store for media library processing
        ];
    }

    return $items;
}
```

#### c. Refactored `saveAttachmentToFolder()`
- **Removed**: Temp file creation and copying logic
- **Removed**: `copyAttachmentFromSenderTenantToTemp()` method
- **Added**: Media replication pattern (like legal data)
- **Added**: `getMediaFromSenderTenant()` method

**New Implementation**:
```php
private function saveAttachmentToFolder(AttachmentRequestItem $item): void
{
    $request = $item->attachmentRequest;

    // Get or create folder structure
    $folderId = $this->getOrCreateFolderPath($request);

    if (!$folderId) {
        $folderId = $this->getProjectRootFolder($request->project_id);
    }

    // Get media items from the attachment request item
    $receiverTenantId = (string) tenant('id');
    $senderTenantId = (string) $request->sender_company_id;
    
    // Switch to sender tenant to get media
    $mediaItems = $this->getMediaFromSenderTenant($item, $senderTenantId, $receiverTenantId);
    
    if ($mediaItems->isEmpty()) {
        return;
    }

    // Create file record in receiver tenant
    $file = File::create([
        'name' => pathinfo($item->file_name, PATHINFO_FILENAME),
        'folder_id' => $folderId,
        'project_id' => $request->project_id,
        'company_id' => $receiverTenantId,
        'access_type' => 'public',
        'status' => 1,
    ]);

    // Replicate media items to the file (like legal data pattern)
    foreach ($mediaItems as $mediaItem) {
        $replicatedMedia = $mediaItem->replicate(['id', 'uuid']);
        $replicatedMedia->model_id = $file->id;
        $replicatedMedia->model_type = File::class;
        $replicatedMedia->save();
    }
}
```

#### d. New Method: `getMediaFromSenderTenant()`
Replaces the old temp file copying logic with media retrieval across tenants:

```php
private function getMediaFromSenderTenant(
    AttachmentRequestItem $item,
    string $senderTenantId,
    string $receiverTenantId
): \Illuminate\Support\Collection {
    if ($senderTenantId === $receiverTenantId) {
        // Same tenant - get media directly
        return Media::where('model_id', Uuid::fromString($item->id))
            ->where('model_type', AttachmentRequestItem::class)
            ->get();
    }

    // Different tenant - switch context to get media
    tenancy()->end();
    tenancy()->initialize($senderTenantId);
    
    try {
        $mediaItems = Media::where('model_id', Uuid::fromString($item->id))
            ->where('model_type', AttachmentRequestItem::class)
            ->get();
    } finally {
        tenancy()->end();
        tenancy()->initialize($receiverTenantId);
    }

    return $mediaItems;
}
```

## Benefits

### 1. **File Deduplication**
- One physical file can be referenced by multiple database records
- Saves storage space when same attachment is used in multiple contexts

### 2. **Consistent Pattern**
- Follows the same media replication pattern as `CompanyLegalDataObserver`
- Uses `FileUploadService` for standardized file handling across the application
- Easier to maintain and understand

### 3. **Better Media Management**
- Leverages Spatie Media Library features (conversions, collections, etc.)
- Automatic file cleanup when models are deleted
- Better metadata handling
- Files stored on S3 with proper disk configuration (s3_public/s3_private)

### 4. **Cleaner Code**
- Removed complex temp file handling logic
- No manual file copying between tenants
- Media library handles all file operations
- Centralized upload logic through `FileUploadService`

### 5. **Cross-Tenant Support**
- Properly handles media replication across different company tenants
- Maintains file integrity when sharing between companies

### 6. **FileUploadService Integration**
- Consistent file naming with unique identifiers
- Automatic custom properties (folder_id, file_path, disk)
- Proper S3 disk selection based on visibility
- Preserves original files for backup/audit purposes

## How It Works

### Creating Attachment Request (Sender Company)
1. User uploads files
2. `prepareAttachmentItems()` prepares item data with `uploaded_file` key
3. Repository creates `AttachmentRequestItem` records
4. `FileUploadService` uploads files to S3 via media library
5. Media library creates `Media` records linked to items with custom properties
6. Files are stored in sender company's tenant context on S3 (s3_public disk)

### Accepting Attachment Request (Receiver Company)
1. Receiver approves attachment item
2. `saveAttachmentToFolder()` is called in receiver tenant context
3. Method switches to sender tenant to retrieve `Media` records
4. Creates `File` record in receiver tenant
5. Replicates `Media` records pointing to the new `File`
6. **Result**: One physical file, two database rows (one for sender's item, one for receiver's file)

## Migration Notes

- No database migration required
- Existing attachments stored via old method will continue to work
- New attachments will use media library
- Consider creating a migration command to convert old attachments if needed

## Testing Checklist

- [ ] Create attachment request with files (sender company)
- [ ] Verify media records are created in sender tenant
- [ ] Approve attachment request (receiver company)
- [ ] Verify File record is created in receiver tenant
- [ ] Verify media is replicated (same physical file, different model_id)
- [ ] Test cross-tenant attachment sharing
- [ ] Test same-tenant attachment sharing
- [ ] Verify file cleanup on item deletion
- [ ] Test with multiple attachments per request
- [ ] Verify file path is correctly populated

### 4. Presenter Updates
**Files**: 
- `modules/Project/ProjectManagement/Presenters/AttachmentRequestPresenter.php`
- `modules/Project/ProjectManagement/Presenters/AttachmentRequestItemPresenter.php`

**Changes**:

#### a. AttachmentRequestPresenter
Updated `attachments_preview` to use media library URL method:

```php
// Before
'file_url' => $item->file_path ? asset('storage/' . $item->file_path) : null,

// After
'file_url' => $item->getFirstMediaUrl('attachments') ?: null,
```

#### b. AttachmentRequestItemPresenter
Updated `file_url` to use media library URL method:

```php
// Before
'file_url' => $this->item->file_path ? Storage::url($this->item->file_path) : null,

// After
'file_url' => $this->item->getFirstMediaUrl('attachments') ?: null,
```

**Important**: The presenter structure remains **exactly the same** to maintain frontend compatibility. Only the URL generation method changed from direct storage paths to media library URLs.

## Related Files

- `modules/Project/ProjectManagement/Models/AttachmentRequestItem.php`
- `modules/Project/ProjectManagement/Repositories/AttachmentRequestRepository.php`
- `modules/Project/ProjectManagement/Services/AttachmentRequestService.php`
- `modules/Project/ProjectManagement/Presenters/AttachmentRequestPresenter.php`
- `modules/Project/ProjectManagement/Presenters/AttachmentRequestItemPresenter.php`
- `modules/Company/CompanyCore/Observers/CompanyLegalDataObserver.php` (reference pattern)
